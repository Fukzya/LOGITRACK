<?php
/*
 * ============================================================================================
 * QUÉ ES:  la API del asistente "Nuevo envío": crea el envío (y si hace falta el viaje) y lo
 *          asigna, todo en una sola transacción (todo o nada).
 * PARA CAMBIAR: las licencias que manejan camiones -> "CAMBIAR:" abajo. Los datos del envío
 *               y del viaje se validan en config/validaciones.php.
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
 */
require_once '../config/api.php';
exigir_permiso('envios', 'alta');
exigir_permiso('viajes', 'alta');   // el asistente también puede crear el viaje
require_once '../config/validaciones.php';
require_once '../modelos/Envio.php';
require_once '../modelos/Viaje.php';
require_once '../modelos/ViajeEnvio.php';
require_once '../modelos/Direccion.php';

// Licencias que habilitan a manejar vehículos que piden licencia especial (camión, semirremolque).
// CAMBIAR: licencias que pueden manejar camión y semirremolque. La base controla lo
//          mismo en procedimiento_validar_recursos_viaje (script 013): hay que cambiar los dos.
const LICENCIAS_ESPECIALES = ['C', 'E'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_api('Método no permitido.', 405);
}

$envios      = new Envio($pdo);
$viajes      = new Viaje($pdo);
$asignar     = new ViajeEnvio($pdo);
$direcciones = new Direccion($pdo);

// La hora la da la base (PHP de XAMPP viene con la zona horaria de Berlín).
$ahora = (string)$pdo->query('SELECT NOW()')->fetchColumn();

$d = leer_json();
$e = datos_envio($d);

if ($direcciones->dueno($e['direccion_entrega_id']) !== $e['destinatario_dni']) {
    error_api('La dirección de entrega tiene que ser una dirección activa del destinatario.');
}

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
    if ($viajeNuevo['fecha_salida'] <= $ahora) {
        error_api('La salida del viaje nuevo tiene que ser una fecha y hora futura.');
    }
    $dia = substr($viajeNuevo['fecha_salida'], 0, 10);
    $r   = $viajes->datosRecursos($viajeNuevo['vehiculo_patente'], $viajeNuevo['chofer_legajo'], $dia);
    if ($r === null)                 error_api('El vehículo o el chofer elegidos no existen.');
    if (!$r['vehiculo_activo'])      error_api('El vehículo elegido está dado de baja.');
    if (!$r['chofer_activo'])        error_api('El chofer elegido está dado de baja.');
    if ($r['vehiculo_ocupado'])      error_api('El vehículo ya tiene otro viaje ese día.');
    if ($r['chofer_ocupado'])        error_api('El chofer ya tiene otro viaje ese día.');
    if ($r['requiere_especial'] && !in_array($r['licencia'], LICENCIAS_ESPECIALES, true)) {
        error_api("Ese vehículo pide licencia C o E y el chofer tiene licencia {$r['licencia']}.");
    }
}

$pdo->beginTransaction();
try {
    // 1) BLOQUEAR PRIMERO, LEER DESPUÉS (cambio 017). Lo primero de la transacción es bloquear los
    //    viajes elegidos (SELECT ... FOR UPDATE): si otro operador está asignando al mismo viaje, acá
    //    se espera a que termine y después se ve su carga. Antes se creaba el envío primero; esa
    //    lectura fijaba la "foto" de la transacción (REPEATABLE READ) y la carga se veía vieja.
    //    Este control es para dar un mensaje claro; el control final lo hace el trigger de la base.
    foreach ($tramos as $viajeId) {
        $v = $viajes->bloquearParaAsignar($viajeId);
        if ($v === null)                   throw new ValidacionException("El viaje $viajeId no existe.");
        if ($v['fecha_llegada'] !== null)  throw new ValidacionException("El viaje $viajeId ya terminó.");
        if ($v['fecha_salida'] <= $ahora)  throw new ValidacionException("El viaje $viajeId ya salió.");
        $libre = (float)$v['capacidad'] - $v['carga'];
        if ($e['peso_kg'] > $libre) {
            throw new ValidacionException(sprintf('El viaje %d ya no tiene lugar: quedan %s kg libres y el envío pesa %s kg.',
                $viajeId, number_format($libre, 2, ',', '.'), number_format($e['peso_kg'], 2, ',', '.')));
        }
    }

    // 2) Viaje nuevo (si se eligió crear uno): nadie más lo ve hasta el COMMIT.
    if ($viajeNuevo !== null) {
        $tramos = [$viajes->crear($viajeNuevo)];
    }

    // 3) El envío (procedimiento; se suma a esta transacción).
    $nro = $envios->crear($e);

    // 4) Las asignaciones. El trigger trigger_before_insert_viaje_envio numera el tramo, controla el
    //    trasbordo (el 2.º viaje sale de donde llega el 1.º, después de su llegada) y la capacidad.
    foreach ($tramos as $viajeId) {
        $asignar->asignarConProcedimiento($viajeId, $nro);
    }

    $pdo->commit();
} catch (Throwable $t) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if ($t instanceof PDOException && codigo_mysql($t) === 1452) {
        error_api('El remitente, el destinatario, una sucursal o el tipo de contenido no existen.');
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
