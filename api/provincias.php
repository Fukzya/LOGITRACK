<?php
/* Controlador de provincias: valida, llama al modelo Provincia y responde JSON. */
require_once '../config/api.php';
require_once '../modelos/Provincia.php';

$provincias = new Provincia($pdo);

switch ($_SERVER['REQUEST_METHOD']) {

    case 'GET':
        responder($provincias->listar(params_listado('provincia_nombre')));

    case 'POST':
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
