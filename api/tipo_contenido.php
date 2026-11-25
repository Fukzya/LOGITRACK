<?php
/*
 * ============================================================================================
 * QUÉ ES:  la API ("controlador") de los tipos de contenido de un envío (estándar, frágil, peligroso).
 *          La pantalla le pide datos y le manda altas, cambios y bajas. Este archivo revisa
 *          que los datos sean válidos y que el rol tenga permiso, y llama al modelo
 *          modelos/TipoContenido.php, que es el único que habla con la base. Responde en JSON.
 * PARA CAMBIAR:
 *   - Qué datos se piden, si son obligatorios o su largo máximo -> "CAMBIAR:" acá abajo.
 *   - Quién puede hacer qué (operador / admin)                 -> config/permisos.php
 *   - Qué trae la consulta (columnas, filtros, orden)           -> modelos/TipoContenido.php
 *   - Lo que se ve (textos, columnas, botones)                  -> paginas/ (pantalla Tipos de contenido)
 *   - Los mensajes de error de la base (reglas de fechas, capacidad...) vienen de los
 *     triggers y procedimientos: se cambian con un script nuevo en database/cambios/.
 * Guía con ejemplos: docs/como_cambiar.md (buscá "CAMBIAR:" con Ctrl+Shift+F).
 * ============================================================================================
 */
/* Controlador de tipos de contenido: valida, llama al modelo TipoContenido y responde JSON. */
require_once '../config/api.php';
exigir_permiso_crud('tipo_contenido');
require_once '../modelos/TipoContenido.php';

$tipos = new TipoContenido($pdo);

// CAMBIAR: qué datos pide el alta y la edición. En cada campo_...(): el nombre del dato,
//          el nombre que ve el usuario en los mensajes y los límites (largo máximo, mínimo y máximo).
//          Si agregás un dato acá, agregalo también al modelo (SQL) y a la pantalla (campos).
function datos_tipo_contenido(array $d): array
{
    return [
        'tipo_cont_nombre'          => campo_texto($d, 'tipo_cont_nombre', 'El nombre', 50),
        'tipo_cont_requiere_seguro' => campo_bool($d, 'tipo_cont_requiere_seguro'),
    ];
}

function cod_tipo_contenido(array $d): string
{
    return campo_codigo($d, 'tipo_cont_cod', 'El código', '/^[A-Z]{3}$/', '3 letras (ej. FRG)');
}

switch ($_SERVER['REQUEST_METHOD']) {

    case 'GET':
        responder($tipos->listar(params_listado('tipo_cont_nombre')));

    case 'POST':
        $d   = leer_json();
        $cod = cod_tipo_contenido($d);
        $t   = ['tipo_cont_cod' => $cod] + datos_tipo_contenido($d);
        intentar(fn() => $tipos->crear($t), [1062 => 'Ya existe un tipo con ese código o ese nombre.']);
        responder(['mensaje' => 'Tipo de contenido creado.'], 201);

    case 'PUT':
        $d   = leer_json();
        $cod = cod_tipo_contenido($d);
        $t   = datos_tipo_contenido($d);
        $ok  = intentar(fn() => $tipos->actualizar($cod, $t), [1062 => 'Ya existe un tipo con ese nombre.']);
        if (!$ok) error_api('No existe ese tipo de contenido.', 404);
        responder(['mensaje' => 'Tipo de contenido actualizado.']);

    case 'DELETE':
        $cod = cod_tipo_contenido(leer_json());
        $ok = intentar(fn() => $tipos->eliminar($cod), [1451 => 'No se puede eliminar: hay envíos de este tipo.']);
        if (!$ok) error_api('No existe ese tipo de contenido.', 404);
        responder(['mensaje' => 'Tipo de contenido eliminado.']);

    default:
        error_api('Método no permitido.', 405);
}
