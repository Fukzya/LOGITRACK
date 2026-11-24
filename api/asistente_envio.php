<?php
/*
 * Controlador del asistente "Nuevo envío" (paginas/nuevo_envio).
 *
 * POST con los datos del envío y qué hacer con el viaje:
 *   viaje_modo = 'existente' (+ viaje_id) | 'nuevo' (+ viaje_nuevo {fecha_salida, vehiculo_patente,
 *                chofer_legajo}) | 'ninguno' (se asigna más tarde)
 *
 * Todo pasa en UNA transacción: crear el viaje (si es nuevo), crear el envío (procedimiento
 * procedimiento_crear_envio) y asignarlo (procedimiento procedimiento_asignar_envio_a_viaje).
 * Si cualquier paso falla -por ejemplo el viaje ya no tiene lugar- se hace ROLLBACK y no queda
 * nada a medias: ni un envío sin viaje ni un viaje vacío.
 */
require_once '../config/api.php';
require_once '../config/validaciones.php';
require_once '../modelos/Envio.php';
require_once '../modelos/Viaje.php';
require_once '../modelos/ViajeEnvio.php';
require_once '../modelos/Direccion.php';

// Licencias que habilitan a manejar vehículos que piden licencia especial (camión, semirremolque).
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
$viajeId    = null;
if ($modo === 'existente') {
    $viajeId = campo_entero($d, 'viaje_id', 'El viaje');
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
    if ($viajeNuevo !== null) {
        $viajeId = $viajes->crear($viajeNuevo);
    }

    $nro = $envios->crear($e); // se suma a esta transacción

    if ($viajeId !== null) {
        // FOR UPDATE: bloquea el viaje para que nadie le cargue peso mientras verificamos la capacidad.
        $v = $viajes->bloquearParaAsignar($viajeId);
        if ($v === null)                   throw new ValidacionException('El viaje elegido no existe.');
        if ($v['fecha_llegada'] !== null)  throw new ValidacionException('El viaje elegido ya terminó.');
        if ($v['fecha_salida'] <= $ahora) throw new ValidacionException('El viaje elegido ya salió.');
        $libre = (float)$v['capacidad'] - $v['carga'];
        if ($e['peso_kg'] > $libre) {
            throw new ValidacionException(sprintf('El viaje %d ya no tiene lugar: quedan %s kg libres y el envío pesa %s kg.',
                $viajeId, number_format($libre, 2, ',', '.'), number_format($e['peso_kg'], 2, ',', '.')));
        }
        $asignar->asignarConProcedimiento($viajeId, $nro);
    }

    $pdo->commit();
} catch (Throwable $t) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if ($t instanceof PDOException && codigo_mysql($t) === 1452) {
        error_api('El remitente, el destinatario, una sucursal o el tipo de contenido no existen.');
    }
    throw $t; // manejar_error() responde el mensaje adecuado
}

$mensaje = "Envío $nro creado";
if ($viajeId !== null) {
    $mensaje .= $viajeNuevo !== null ? " y asignado al viaje nuevo $viajeId." : " y asignado al viaje $viajeId.";
} else {
    $mensaje .= ' sin viaje (queda pendiente de asignar).';
}
responder(['mensaje' => $mensaje, 'nro_envio' => $nro, 'viaje_id' => $viajeId], 201);
