<?php
/*
 * ============================================================================================
 * QUÉ ES:  la API ("controlador") de los choferes (legajo, nombre y tipo de licencia).
 *          La pantalla le pide datos y le manda altas, cambios y bajas. Este archivo revisa
 *          que los datos sean válidos y que el rol tenga permiso, y llama al modelo
 *          modelos/Chofer.php, que es el único que habla con la base. Responde en JSON.
 * PARA CAMBIAR:
 *   - Qué datos se piden, si son obligatorios o su largo máximo -> "CAMBIAR:" acá abajo.
 *   - Quién puede hacer qué (operador / admin)                 -> config/permisos.php
 *   - Qué trae la consulta (columnas, filtros, orden)           -> modelos/Chofer.php
 *   - Lo que se ve (textos, columnas, botones)                  -> paginas/ (pantalla Choferes)
 *   - Los mensajes de error de la base (reglas de fechas, capacidad...) vienen de los
 *     triggers y procedimientos: se cambian con un script nuevo en database/cambios/.
 * Guía con ejemplos: docs/como_cambiar.md (buscá "CAMBIAR:" con Ctrl+Shift+F).
 * ============================================================================================
 */
/* Controlador de choferes: valida, llama al modelo Chofer y responde JSON. */
require_once '../config/api.php';
exigir_permiso_crud('choferes');
require_once '../modelos/Chofer.php';

$choferes = new Chofer($pdo);

// CAMBIAR: qué datos pide el alta y la edición. En cada campo_...(): el nombre del dato,
//          el nombre que ve el usuario en los mensajes y los límites (largo máximo, mínimo y máximo).
//          Si agregás un dato acá, agregalo también al modelo (SQL) y a la pantalla (campos).
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
