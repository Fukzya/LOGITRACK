<?php
/* Controlador de tipos de vehículo: valida, llama al modelo TipoVehiculo y responde JSON. */
require_once '../config/api.php';
require_once '../modelos/TipoVehiculo.php';

$tipos = new TipoVehiculo($pdo);

function datos_tipo_vehiculo(array $d): array
{
    return [
        'tipo_veh_nombre'                => campo_texto($d, 'tipo_veh_nombre', 'El nombre', 60),
        'tipo_veh_capacidad_kg'          => campo_decimal($d, 'tipo_veh_capacidad_kg', 'La capacidad (kg)', 1, 99999999.99),
        'tipo_veh_requiere_lic_especial' => campo_bool($d, 'tipo_veh_requiere_lic_especial'),
    ];
}

function cod_tipo_vehiculo(array $d): string
{
    return campo_codigo($d, 'tipo_veh_cod', 'El código', '/^[A-Z]{4}$/', '4 letras (ej. CAMN)');
}

switch ($_SERVER['REQUEST_METHOD']) {

    case 'GET':
        responder($tipos->listar(params_listado('tipo_veh_nombre')));

    case 'POST':
        $d   = leer_json();
        $cod = cod_tipo_vehiculo($d);
        $t   = ['tipo_veh_cod' => $cod] + datos_tipo_vehiculo($d);
        intentar(fn() => $tipos->crear($t), [1062 => 'Ya existe un tipo con ese código o ese nombre.']);
        responder(['mensaje' => 'Tipo de vehículo creado.'], 201);

    case 'PUT':
        $d   = leer_json();
        $cod = cod_tipo_vehiculo($d);
        $t   = datos_tipo_vehiculo($d);
        $ok  = intentar(fn() => $tipos->actualizar($cod, $t), [1062 => 'Ya existe un tipo con ese nombre.']);
        if (!$ok) error_api('No existe ese tipo de vehículo.', 404);
        responder(['mensaje' => 'Tipo de vehículo actualizado.']);

    case 'DELETE':
        $cod = cod_tipo_vehiculo(leer_json());
        $ok = intentar(fn() => $tipos->eliminar($cod), [1451 => 'No se puede eliminar: hay vehículos de este tipo.']);
        if (!$ok) error_api('No existe ese tipo de vehículo.', 404);
        responder(['mensaje' => 'Tipo de vehículo eliminado.']);

    default:
        error_api('Método no permitido.', 405);
}
