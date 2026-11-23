<?php
/* Controlador de tipos de contenido: valida, llama al modelo TipoContenido y responde JSON. */
require_once '../config/api.php';
require_once '../modelos/TipoContenido.php';

$tipos = new TipoContenido($pdo);

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
