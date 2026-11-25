<?php
/*
 * ============================================================================================
 * QUÉ ES:  la API ("controlador") de las direcciones de entrega de cada cliente.
 *          La pantalla le pide datos y le manda altas, cambios y bajas. Este archivo revisa
 *          que los datos sean válidos y que el rol tenga permiso, y llama al modelo
 *          modelos/Direccion.php, que es el único que habla con la base. Responde en JSON.
 * PARA CAMBIAR:
 *   - Qué datos se piden, si son obligatorios o su largo máximo -> "CAMBIAR:" acá abajo.
 *   - Quién puede hacer qué (operador / admin)                 -> config/permisos.php
 *   - Qué trae la consulta (columnas, filtros, orden)           -> modelos/Direccion.php
 *   - Lo que se ve (textos, columnas, botones)                  -> paginas/ (pantalla Direcciones)
 *   - Los mensajes de error de la base (reglas de fechas, capacidad...) vienen de los
 *     triggers y procedimientos: se cambian con un script nuevo en database/cambios/.
 * Guía con ejemplos: docs/como_cambiar.md (buscá "CAMBIAR:" con Ctrl+Shift+F).
 * ============================================================================================
 */
/* Controlador de direcciones: valida, llama al modelo Direccion y responde JSON. */
require_once '../config/api.php';
exigir_permiso_crud('direcciones');
require_once '../modelos/Direccion.php';

$direcciones = new Direccion($pdo);

// CAMBIAR: qué datos pide el alta y la edición. En cada campo_...(): el nombre del dato,
//          el nombre que ve el usuario en los mensajes y los límites (largo máximo, mínimo y máximo).
//          Si agregás un dato acá, agregalo también al modelo (SQL) y a la pantalla (campos).
function datos_direccion(array $d): array
{
    return [
        'calle_y_numero' => campo_texto($d, 'calle_y_numero', 'La calle y número', 200),
        'localidad_cod'  => campo_entero($d, 'localidad_cod', 'La localidad'),
        'cliente_dni'    => campo_entero($d, 'cliente_dni', 'El DNI del cliente', 1, 99999999),
    ];
}

$errores = [1452 => 'La localidad o el cliente indicados no existen.'];

switch ($_SERVER['REQUEST_METHOD']) {

    case 'GET':
        $filtros = [
            'calle'       => trim($_GET['calle'] ?? ''),
            'cliente_dni' => campo_entero($_GET, 'cliente_dni', 'El DNI', 1, 99999999, false),
            'localidad'   => campo_entero($_GET, 'localidad_cod', 'La localidad', 1, PHP_INT_MAX, false),
        ];
        responder($direcciones->listar($filtros, params_listado('direccion_id'), ver_inactivos()));

    case 'POST':
        $dir = datos_direccion(leer_json());
        $id  = intentar(fn() => $direcciones->crear($dir), $errores);
        responder(['mensaje' => "Dirección creada (ID $id).", 'id' => $id], 201);

    case 'PUT':
        $d  = leer_json();
        $id = campo_entero($d, 'direccion_id', 'El ID');
        if (!empty($d['reactivar'])) {
            if (!$direcciones->cambiarActivo($id, true)) error_api('No existe esa dirección.', 404);
            responder(['mensaje' => 'Dirección reactivada.']);
        }
        $dir = datos_direccion($d);
        if (!intentar(fn() => $direcciones->actualizar($id, $dir), $errores)) error_api('No existe una dirección activa con ese ID.', 404);
        responder(['mensaje' => 'Dirección actualizada.']);

    case 'DELETE':
        $id = campo_entero(leer_json(), 'direccion_id', 'El ID');
        if (!$direcciones->cambiarActivo($id, false)) error_api('No existe esa dirección.', 404);
        responder(['mensaje' => 'Dirección dada de baja.']);

    default:
        error_api('Método no permitido.', 405);
}
