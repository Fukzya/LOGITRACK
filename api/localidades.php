<?php
/*
 * ============================================================================================
 * QUÉ ES:  la API ("controlador") de las localidades y su provincia.
 *          La pantalla le pide datos y le manda altas, cambios y bajas. Este archivo revisa
 *          que los datos sean válidos y que el rol tenga permiso, y llama al modelo
 *          modelos/Localidad.php, que es el único que habla con la base. Responde en JSON.
 * PARA CAMBIAR:
 *   - Qué datos se piden, si son obligatorios o su largo máximo -> "CAMBIAR:" acá abajo.
 *   - Quién puede hacer qué (operador / admin)                 -> config/permisos.php
 *   - Qué trae la consulta (columnas, filtros, orden)           -> modelos/Localidad.php
 *   - Lo que se ve (textos, columnas, botones)                  -> paginas/ (pantalla Localidades)
 *   - Los mensajes de error de la base (reglas de fechas, capacidad...) vienen de los
 *     triggers y procedimientos: se cambian con un script nuevo en database/cambios/.
 * Guía con ejemplos: docs/como_cambiar.md (buscá "CAMBIAR:" con Ctrl+Shift+F).
 * ============================================================================================
 */
/* Controlador de localidades: valida, llama al modelo Localidad y responde JSON. */
require_once '../config/api.php';
exigir_permiso_crud('localidades');
require_once '../modelos/Localidad.php';

$localidades = new Localidad($pdo);

// CAMBIAR: qué datos pide el alta y la edición. En cada campo_...(): el nombre del dato,
//          el nombre que ve el usuario en los mensajes y los límites (largo máximo, mínimo y máximo).
//          Si agregás un dato acá, agregalo también al modelo (SQL) y a la pantalla (campos).
function datos_localidad(array $d): array
{
    return [
        'localidad_nombre' => campo_texto($d, 'localidad_nombre', 'El nombre', 100),
        'provincia_cod'    => campo_entero($d, 'provincia_cod', 'La provincia'),
    ];
}

$errores = [
    1062 => 'Ya existe una localidad con ese nombre en esa provincia.',
    1452 => 'La provincia elegida no existe.',
];

switch ($_SERVER['REQUEST_METHOD']) {

    case 'GET':
        $provincia = campo_entero($_GET, 'provincia_cod', 'La provincia', 1, PHP_INT_MAX, false);
        responder($localidades->listar($provincia, params_listado('provincia_nombre')));

    case 'POST':
        $l  = datos_localidad(leer_json());
        $id = intentar(fn() => $localidades->crear($l), $errores);
        responder(['mensaje' => 'Localidad creada.', 'id' => $id], 201);

    case 'PUT':
        $d   = leer_json();
        $cod = campo_entero($d, 'localidad_cod', 'El código');
        $l   = datos_localidad($d);
        if (!intentar(fn() => $localidades->actualizar($cod, $l), $errores)) error_api('No existe esa localidad.', 404);
        responder(['mensaje' => 'Localidad actualizada.']);

    case 'DELETE':
        $cod = campo_entero(leer_json(), 'localidad_cod', 'El código');
        $ok = intentar(fn() => $localidades->eliminar($cod),
                       [1451 => 'No se puede eliminar: la localidad tiene clientes, direcciones o sucursales.']);
        if (!$ok) error_api('No existe esa localidad.', 404);
        responder(['mensaje' => 'Localidad eliminada.']);

    default:
        error_api('Método no permitido.', 405);
}
