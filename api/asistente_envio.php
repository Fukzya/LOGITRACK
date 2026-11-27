<?php
/*
 * ============================================================================================
 * QUÉ ES:  la API del asistente "Nuevo envío": crea el envío (y si hace falta el viaje) y lo
 *          asigna, todo en una sola transacción (todo o nada).
 * PARA CAMBIAR: los datos del envío y del viaje se validan (solo el formato) en
 *               config/validaciones.php. Las REGLAS (licencias, capacidad, fechas, dirección del
 *               destinatario...) están en la base: triggers y procedimientos.
 * Guía con ejemplos: docs/como_cambiar.md (buscá "CAMBIAR:" con Ctrl+Shift+F).
 * ============================================================================================
 */
/*
 * Controlador del asistente "Nuevo envío" (paginas/nuevo_envio).
 *
 * POST con los datos del envío y qué hacer con el viaje:
 *   viaje_modo = 'existente' (+ viajes: [id] directo o [id1, id2] con trasbordo, en orden)
 *              | 'nuevo' (+ viaje_nuevo {fecha_salida, sucursal_origen_cod, sucursal_destino_cod,
 *                vehiculo_patente, chofer_legajo})
 *              | 'ninguno' (se asigna más tarde)
 *
 * Todo pasa en UNA transacción: crear el viaje (si es nuevo), crear el envío (procedimiento
 * procedimiento_crear_envio) y asignarlo (procedimiento procedimiento_asignar_envio_a_viaje).
 * Si cualquier paso falla -por ejemplo el viaje ya no tiene lugar- se hace ROLLBACK y no queda
 * nada a medias: ni un envío sin viaje ni un viaje vacío.
 *
 * Este archivo NO repite reglas del negocio (cambio 020): las controla la base y su mensaje
 * llega tal cual a la pantalla.
 *   - dirección de entrega del destinatario -> trigger de envio (procedimiento_validar_direccion_envio)
 *   - vehículo y chofer activos, libres ese día y licencia -> triggers de viaje
 *     (procedimiento_validar_recursos_viaje, funcion_licencia_maneja_especial)
 *   - viaje que no terminó ni salió, capacidad (con FOR UPDATE del viaje), trasbordo
 *     -> trigger_before_insert_viaje_envio. Un viaje nuevo siempre se asigna en la misma
 *     transacción, así que si su salida no es futura el trigger lo rechaza ("El viaje ya salió").
 */
require_once '../config/api.php';
exigir_permiso('envios', 'alta');
exigir_permiso('viajes', 'alta');   // el asistente también puede crear el viaje
require_once '../config/validaciones.php';
require_once '../modelos/Envio.php';
require_once '../modelos/Viaje.php';
require_once '../modelos/ViajeEnvio.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_api('Método no permitido.', 405);
}

$envios  = new Envio($pdo);
$viajes  = new Viaje($pdo);
$asignar = new ViajeEnvio($pdo);

$d = leer_json();
$e = datos_envio($d);

$modo = campo_opcion($d, 'viaje_modo', 'El viaje', ['existente', 'nuevo', 'ninguno']);

$viajeNuevo = null;
$tramos     = [];   // ids de los viajes, en el orden en que los recorre el envío
if ($modo === 'existente') {
    $lista = is_array($d['viajes'] ?? null) ? array_values($d['viajes']) : [];
    if (count($lista) < 1 || count($lista) > 2) error_api('Elegí un viaje directo o dos viajes con un trasbordo.');
    foreach ($lista as $i => $id) $tramos[] = campo_entero(['v' => $id], 'v', 'El viaje ' . ($i + 1));
} elseif ($modo === 'nuevo') {
    $viajeNuevo = datos_viaje(is_array($d['viaje_nuevo'] ?? null) ? $d['viaje_nuevo'] : []);
    $viajeNuevo['fecha_llegada'] = null; // un viaje nuevo todavía no llegó
}

$pdo->beginTransaction();
try {
    // 1) Viaje nuevo (si se eligió crear uno): nadie más lo ve hasta el COMMIT. Sus triggers
    //    controlan los recursos (activos, libres ese día, licencia).
    if ($viajeNuevo !== null) {
        $tramos = [$viajes->crear($viajeNuevo)];
    }

    // 2) El envío (procedimiento; se suma a esta transacción). Su trigger controla la dirección.
    $nro = $envios->crear($e);

    // 3) Las asignaciones. trigger_before_insert_viaje_envio BLOQUEA el viaje (FOR UPDATE: si otro
    //    operador asigna al mismo viaje, acá se espera), controla que no haya salido, numera el
    //    tramo, controla el trasbordo y la capacidad (leída con bloqueo, cambio 017).
    foreach ($tramos as $viajeId) {
        $asignar->asignarConProcedimiento($viajeId, $nro);
    }

    $pdo->commit();
} catch (Throwable $t) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if ($t instanceof PDOException && codigo_mysql($t) === 1452) {
        error_api('El remitente, el destinatario, una sucursal, el tipo de contenido o el viaje no existen.');
    }
    throw $t; // manejar_error() responde el mensaje adecuado (los SIGNAL de los triggers, tal cual)
}

$mensaje = "Envío $nro creado";
if (count($tramos) === 2) {
    $mensaje .= " y asignado a los viajes {$tramos[0]} y {$tramos[1]} (con trasbordo).";
} elseif ($tramos) {
    $mensaje .= $viajeNuevo !== null ? " y asignado al viaje nuevo {$tramos[0]}." : " y asignado al viaje {$tramos[0]}.";
} else {
    $mensaje .= ' sin viaje (queda pendiente de asignar).';
}
responder(['mensaje' => $mensaje, 'nro_envio' => $nro, 'viajes' => $tramos], 201);
