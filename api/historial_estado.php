<?php
/* Controlador del historial de estados: valida, llama al modelo HistorialEstado y responde JSON. */
require_once '../config/api.php';
require_once '../modelos/HistorialEstado.php';

$historial = new HistorialEstado($pdo);

function datos_historial(array $d): array
{
    return [
        'nro_envio'    => campo_entero($d, 'nro_envio', 'El número de envío'),
        'sucursal_cod' => campo_codigo($d, 'sucursal_cod', 'La sucursal', '/^[0-9]{3}$/', '3 dígitos', false),
        'estado'       => campo_codigo($d, 'estado', 'El estado', '/^[A-Z_]{3,30}$/', 'un código de estado'),
        // Si no se indica, el estado se registra con la fecha y hora actuales.
        'fecha_hora'   => campo_fecha_hora($d, 'fecha_hora', 'La fecha y hora', false) ?? date('Y-m-d H:i:s'),
        'observacion'  => campo_texto($d, 'observacion', 'La observación', 1000, false),
    ];
}

$errores = [1452 => 'El envío, la sucursal o el estado indicados no existen.'];

switch ($_SERVER['REQUEST_METHOD']) {

    case 'GET':
        $filtros = [
            'nro_envio'   => campo_entero($_GET, 'nro_envio', 'El envío', 1, PHP_INT_MAX, false),
            'estado'      => trim($_GET['estado'] ?? ''),
            'sucursal'    => trim($_GET['sucursal_cod'] ?? ''),
            'fecha_desde' => campo_fecha($_GET, 'fecha_desde', 'La fecha desde', false),
            'fecha_hasta' => campo_fecha($_GET, 'fecha_hasta', 'La fecha hasta', false),
        ];
        responder($historial->listar($filtros, params_listado('id_historial', 'DESC')));

    case 'POST':
        $h  = datos_historial(leer_json());
        $id = intentar(fn() => $historial->crear($h), $errores);
        responder(['mensaje' => 'Estado registrado.', 'id' => $id], 201);

    case 'PUT':
        $d  = leer_json();
        $id = campo_entero($d, 'id_historial', 'El registro');
        $h  = datos_historial($d);
        if (!intentar(fn() => $historial->actualizar($id, $h), $errores)) error_api('No existe ese registro.', 404);
        responder(['mensaje' => 'Estado actualizado.']);

    case 'DELETE':
        $id = campo_entero(leer_json(), 'id_historial', 'El registro');
        if (!$historial->eliminar($id)) error_api('No existe ese registro.', 404);
        responder(['mensaje' => 'Estado eliminado.']);

    default:
        error_api('Método no permitido.', 405);
}
