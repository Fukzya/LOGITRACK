<?php
/* Controlador de viajes: valida, llama al modelo Viaje y responde JSON. */
require_once '../config/api.php';
require_once '../modelos/Viaje.php';
require_once '../config/validaciones.php';

$viajes = new Viaje($pdo);

$errores = [1452 => 'El vehículo o el chofer elegidos no existen.'];

switch ($_SERVER['REQUEST_METHOD']) {

    case 'GET':
        // Asistente de envío: rutas directas y con trasbordo (procedimiento_buscar_rutas) y la
        // lista de todos los viajes que todavía no salieron.
        if (isset($_GET['rutas'])) {
            responder($viajes->buscarRutas(
                campo_codigo($_GET, 'sucursal_orig', 'La sucursal de origen', '/^[0-9]{3}$/', '3 dígitos', false),
                campo_codigo($_GET, 'sucursal_dest', 'La sucursal de destino', '/^[0-9]{3}$/', '3 dígitos', false),
                campo_decimal($_GET, 'peso', 'El peso (kg)', 0.01, 999999.99)
            ));
        }
        // Asistente de envío: vehículos y choferes libres para crear un viaje ese día.
        if (isset($_GET['recursos'])) {
            responder($viajes->recursosLibres(campo_fecha($_GET, 'fecha', 'La fecha')));
        }
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
