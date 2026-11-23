<?php
/* Controlador de viajes: valida, llama al modelo Viaje y responde JSON. */
require_once '../config/api.php';
require_once '../modelos/Viaje.php';

$viajes = new Viaje($pdo);

function datos_viaje(array $d): array
{
    $v = [
        'fecha_salida'     => campo_fecha_hora($d, 'fecha_salida', 'La fecha de salida'),
        'fecha_llegada'    => campo_fecha_hora($d, 'fecha_llegada', 'La fecha de llegada', false),
        'vehiculo_patente' => campo_codigo($d, 'vehiculo_patente', 'El vehículo', '/^[A-Z0-9]{6,10}$/', 'una patente'),
        'chofer_legajo'    => campo_entero($d, 'chofer_legajo', 'El chofer'),
    ];
    // Misma regla que trigger_before_update_viaje, también para el alta.
    if ($v['fecha_llegada'] !== null && $v['fecha_llegada'] <= $v['fecha_salida']) {
        throw new ValidacionException('La fecha de llegada tiene que ser posterior a la de salida.');
    }
    return $v;
}

$errores = [1452 => 'El vehículo o el chofer elegidos no existen.'];

switch ($_SERVER['REQUEST_METHOD']) {

    case 'GET':
        $filtros = [
            'fecha_desde'  => campo_fecha($_GET, 'fecha_desde', 'La fecha desde', false),
            'fecha_hasta'  => campo_fecha($_GET, 'fecha_hasta', 'La fecha hasta', false),
            'chofer'       => campo_entero($_GET, 'chofer_legajo', 'El chofer', 1, PHP_INT_MAX, false),
            'solo_activos' => ($_GET['solo_activos'] ?? '') === '1',
        ];
        responder($viajes->listar($filtros, params_listado('viaje_id', 'DESC')));

    case 'POST':
        $v  = datos_viaje(leer_json());
        $id = intentar(fn() => $viajes->crear($v), $errores);
        responder(['mensaje' => "Viaje $id creado.", 'id' => $id], 201);

    case 'PUT':
        $d  = leer_json();
        $id = campo_entero($d, 'viaje_id', 'El viaje');
        $v  = datos_viaje($d);
        if (!intentar(fn() => $viajes->actualizar($id, $v), $errores)) error_api('No existe ese viaje.', 404);
        responder(['mensaje' => 'Viaje actualizado.']);

    // Si tiene envíos asignados, trigger_before_delete_viaje lo rechaza y su mensaje llega tal cual.
    case 'DELETE':
        $id = campo_entero(leer_json(), 'viaje_id', 'El viaje');
        $ok = intentar(fn() => $viajes->eliminar($id), [1451 => 'No se puede eliminar: el viaje tiene incidentes registrados.']);
        if (!$ok) error_api('No existe ese viaje.', 404);
        responder(['mensaje' => 'Viaje eliminado.']);

    default:
        error_api('Método no permitido.', 405);
}
