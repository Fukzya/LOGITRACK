<?php
/* Controlador de la asignación envío-viaje: valida, llama al modelo ViajeEnvio y responde JSON. */
require_once '../config/api.php';
exigir_permiso_crud('viaje_envio');
require_once '../modelos/ViajeEnvio.php';

$asignaciones = new ViajeEnvio($pdo);

function claves(array $d): array
{
    return [campo_entero($d, 'viaje_id', 'El viaje'), campo_entero($d, 'nro_envio', 'El número de envío')];
}

switch ($_SERVER['REQUEST_METHOD']) {

    case 'GET':
        $filtros = [
            'viaje_id'  => campo_entero($_GET, 'viaje_id', 'El viaje', 1, PHP_INT_MAX, false),
            'nro_envio' => campo_entero($_GET, 'nro_envio', 'El envío', 1, PHP_INT_MAX, false),
        ];
        responder($asignaciones->listar($filtros, params_listado('viaje_id')));

    case 'POST':
        [$viaje, $envio] = claves(leer_json());
        // Por procedimiento: las cuentas de la app no tienen INSERT directo sobre viaje_envio.
        intentar(fn() => $asignaciones->asignarConProcedimiento($viaje, $envio), [
            1062 => "El envío $envio ya está asignado al viaje $viaje.",
            1452 => 'El viaje o el envío indicados no existen.',
        ]);
        responder(['mensaje' => "Envío $envio asignado al viaje $viaje."], 201);

    case 'DELETE':
        [$viaje, $envio] = claves(leer_json());
        if (!$asignaciones->quitar($viaje, $envio)) error_api('Ese envío no está asignado a ese viaje.', 404);
        responder(['mensaje' => "Envío $envio quitado del viaje $viaje."]);

    default:
        error_api('Método no permitido.', 405);
}
