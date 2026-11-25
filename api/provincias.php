<?php
/*
 * ============================================================================================
 * QUÉ ES:  la API ("controlador") de las provincias.
 *          La pantalla le pide datos y le manda altas, cambios y bajas. Este archivo revisa
 *          que los datos sean válidos y que el rol tenga permiso, y llama al modelo
 *          modelos/Provincia.php, que es el único que habla con la base. Responde en JSON.
 * PARA CAMBIAR:
 *   - Qué datos se piden, si son obligatorios o su largo máximo -> "CAMBIAR:" acá abajo.
 *   - Quién puede hacer qué (operador / admin)                 -> config/permisos.php
 *   - Qué trae la consulta (columnas, filtros, orden)           -> modelos/Provincia.php
 *   - Lo que se ve (textos, columnas, botones)                  -> paginas/ (pantalla Provincias)
 *   - Los mensajes de error de la base (reglas de fechas, capacidad...) vienen de los
 *     triggers y procedimientos: se cambian con un script nuevo en database/cambios/.
 * Guía con ejemplos: docs/como_cambiar.md (buscá "CAMBIAR:" con Ctrl+Shift+F).
 * ============================================================================================
 */
/* Controlador de provincias: valida, llama al modelo Provincia y responde JSON. */
require_once '../config/api.php';
exigir_permiso_crud('provincias');
require_once '../modelos/Provincia.php';

$provincias = new Provincia($pdo);

switch ($_SERVER['REQUEST_METHOD']) {

    case 'GET':
        responder($provincias->listar(params_listado('provincia_nombre')));

    case 'POST':
        // CAMBIAR: largo máximo del nombre de una provincia (100 caracteres).
        $nombre = campo_texto(leer_json(), 'provincia_nombre', 'El nombre', 100);
        $id = intentar(fn() => $provincias->crear($nombre), [1062 => "Ya existe la provincia $nombre."]);
        responder(['mensaje' => 'Provincia creada.', 'id' => $id], 201);

    case 'PUT':
        $d      = leer_json();
        $cod    = campo_entero($d, 'provincia_cod', 'El código');
        $nombre = campo_texto($d, 'provincia_nombre', 'El nombre', 100);
        $ok = intentar(fn() => $provincias->actualizar($cod, $nombre), [1062 => "Ya existe la provincia $nombre."]);
        if (!$ok) error_api('No existe esa provincia.', 404);
        responder(['mensaje' => 'Provincia actualizada.']);

    case 'DELETE':
        $cod = campo_entero(leer_json(), 'provincia_cod', 'El código');
        $ok = intentar(fn() => $provincias->eliminar($cod),
                       [1451 => 'No se puede eliminar: la provincia tiene localidades cargadas.']);
        if (!$ok) error_api('No existe esa provincia.', 404);
        responder(['mensaje' => 'Provincia eliminada.']);

    default:
        error_api('Método no permitido.', 405);
}
