<?php
/*
 * ============================================================================================
 * QUÉ ES:  la API ("controlador") de los estados por los que pasa cada envío (no se editan ni se borran).
 *          La pantalla le pide datos y le manda altas, cambios y bajas. Este archivo revisa
 *          que los datos sean válidos y que el rol tenga permiso, y llama al modelo
 *          modelos/HistorialEstado.php, que es el único que habla con la base. Responde en JSON.
 * PARA CAMBIAR:
 *   - Qué datos se piden, si son obligatorios o su largo máximo -> "CAMBIAR:" acá abajo.
 *   - Quién puede hacer qué (operador / admin)                 -> config/permisos.php
 *   - Qué trae la consulta (columnas, filtros, orden)           -> modelos/HistorialEstado.php
 *   - Lo que se ve (textos, columnas, botones)                  -> paginas/ (pantalla Historial de estados)
 *   - Los mensajes de error de la base (reglas de fechas, capacidad...) vienen de los
 *     triggers y procedimientos: se cambian con un script nuevo en database/cambios/.
 * Guía con ejemplos: docs/como_cambiar.md (buscá "CAMBIAR:" con Ctrl+Shift+F).
 * ============================================================================================
 */
/* Controlador del historial de estados: valida, llama al modelo HistorialEstado y responde JSON.
   Solo permite consultar y registrar estados nuevos (el historial es inmutable). */
require_once '../config/api.php';
exigir_permiso_crud('historial_estado');
require_once '../modelos/HistorialEstado.php';

$historial = new HistorialEstado($pdo);

// CAMBIAR: qué datos pide el alta y la edición. En cada campo_...(): el nombre del dato,
//          el nombre que ve el usuario en los mensajes y los límites (largo máximo, mínimo y máximo).
//          Si agregás un dato acá, agregalo también al modelo (SQL) y a la pantalla (campos).
function datos_historial(array $d): array
{
    return [
        'nro_envio'    => campo_entero($d, 'nro_envio', 'El número de envío'),
        'sucursal_cod' => campo_codigo($d, 'sucursal_cod', 'La sucursal', '/^[0-9]{3}$/', '3 dígitos', false),
        'estado'       => campo_codigo($d, 'estado', 'El estado', '/^[A-Z_]{3,30}$/', 'un código de estado'),
        // Si no se indica, el modelo usa NOW() de la base. Las reglas de fechas (no en el futuro,
        // no antes del último estado, nada después de uno final) las controla
        // trigger_before_insert_historial_estado y su mensaje llega tal cual.
        'fecha_hora'   => campo_fecha_hora($d, 'fecha_hora', 'La fecha y hora', false),
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

    // El historial es inmutable: no se edita ni se borra, cada cambio de estado es una fila nueva.
    case 'PUT':
    case 'DELETE':
        error_api('El historial no se modifica ni se borra: para cambiar el estado de un envío, registrá un estado nuevo.', 405);

    default:
        error_api('Método no permitido.', 405);
}
