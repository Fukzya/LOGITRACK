<?php
/* Controlador de choferes: valida, llama al modelo Chofer y responde JSON. */
require_once '../config/api.php';
exigir_permiso_crud('choferes');
require_once '../modelos/Chofer.php';

$choferes = new Chofer($pdo);

function datos_chofer(array $d): array
{
    return [
        'nombre'               => campo_texto($d, 'nombre', 'El nombre', 80),
        'apellido'             => campo_texto($d, 'apellido', 'El apellido', 80),
        'chofer_tipo_licencia' => campo_codigo($d, 'chofer_tipo_licencia', 'La licencia', '/^[A-Z0-9]{1,10}$/', 'un código de licencia (ej. C)'),
    ];
}

$errores = [1452 => 'El tipo de licencia elegido no existe.'];

switch ($_SERVER['REQUEST_METHOD']) {

    case 'GET':
        $filtros = [
            'nombre'   => trim($_GET['nombre'] ?? ''),
            'apellido' => trim($_GET['apellido'] ?? ''),
            'licencia' => trim($_GET['tipo_licencia'] ?? ''),
        ];
        responder($choferes->listar($filtros, params_listado('chofer_legajo'), ver_inactivos()));

    case 'POST':
        $c  = datos_chofer(leer_json());
        $id = intentar(fn() => $choferes->crear($c), $errores);
        responder(['mensaje' => "Chofer creado con legajo $id.", 'id' => $id], 201);

    case 'PUT':
        $d      = leer_json();
        $legajo = campo_entero($d, 'chofer_legajo', 'El legajo');
        if (!empty($d['reactivar'])) {
            if (!$choferes->cambiarActivo($legajo, true)) error_api('No existe un chofer con ese legajo.', 404);
            responder(['mensaje' => 'Chofer reactivado.']);
        }
        $c = datos_chofer($d);
        if (!intentar(fn() => $choferes->actualizar($legajo, $c), $errores)) error_api('No existe un chofer activo con ese legajo.', 404);
        responder(['mensaje' => 'Chofer actualizado.']);

    case 'DELETE':
        $legajo = campo_entero(leer_json(), 'chofer_legajo', 'El legajo');
        if (!$choferes->cambiarActivo($legajo, false)) error_api('No existe un chofer con ese legajo.', 404);
        responder(['mensaje' => 'Chofer dado de baja.']);

    default:
        error_api('Método no permitido.', 405);
}
