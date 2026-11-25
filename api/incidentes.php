<?php
/* Controlador de incidentes: valida, llama al modelo Incidente y responde JSON. */
require_once '../config/api.php';
exigir_permiso_crud('incidentes');
require_once '../modelos/Incidente.php';

$incidentes = new Incidente($pdo);

function datos_incidente(array $d): array
{
    return [
        'viaje_id'       => campo_entero($d, 'viaje_id', 'El viaje'),
        'fecha_hora'     => campo_fecha_hora($d, 'fecha_hora', 'La fecha y hora'),
        'tipo_incidente' => campo_codigo($d, 'tipo_incidente', 'El tipo de incidente', '/^[A-Z_]{3,50}$/', 'un código de tipo'),
        'descripcion'    => campo_texto($d, 'descripcion', 'La descripción', 1000, false),
    ];
}

$errores = [1452 => 'El viaje o el tipo de incidente indicados no existen.'];

switch ($_SERVER['REQUEST_METHOD']) {

    case 'GET':
        $filtros = [
            'viaje_id'    => campo_entero($_GET, 'viaje_id', 'El viaje', 1, PHP_INT_MAX, false),
            'tipo'        => trim($_GET['tipo'] ?? ''),
            'fecha_desde' => campo_fecha($_GET, 'fecha_desde', 'La fecha desde', false),
            'fecha_hasta' => campo_fecha($_GET, 'fecha_hasta', 'La fecha hasta', false),
        ];
        responder($incidentes->listar($filtros, params_listado('fecha_hora', 'DESC')));

    case 'POST':
        $i  = datos_incidente(leer_json());
        $id = intentar(fn() => $incidentes->crear($i), $errores);
        responder(['mensaje' => 'Incidente registrado.', 'id' => $id], 201);

    case 'PUT':
        $d  = leer_json();
        $id = campo_entero($d, 'incidente_id', 'El incidente');
        $i  = datos_incidente($d);
        if (!intentar(fn() => $incidentes->actualizar($id, $i), $errores)) error_api('No existe ese incidente.', 404);
        responder(['mensaje' => 'Incidente actualizado.']);

    case 'DELETE':
        $id = campo_entero(leer_json(), 'incidente_id', 'El incidente');
        if (!$incidentes->eliminar($id)) error_api('No existe ese incidente.', 404);
        responder(['mensaje' => 'Incidente eliminado.']);

    default:
        error_api('Método no permitido.', 405);
}
