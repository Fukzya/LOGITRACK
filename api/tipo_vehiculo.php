<?php
/*
 * ============================================================================================
 * QUÉ ES:  la API ("controlador") de los tipos de vehículo, su capacidad en kg y si piden licencia especial.
 *          La pantalla le pide datos y le manda altas, cambios y bajas. Este archivo revisa
 *          que los datos sean válidos y que el rol tenga permiso, y llama al modelo
 *          modelos/TipoVehiculo.php, que es el único que habla con la base. Responde en JSON.
 * PARA CAMBIAR:
 *   - Qué datos se piden, si son obligatorios o su largo máximo -> "CAMBIAR:" acá abajo.
 *   - Quién puede hacer qué (operador / admin)                 -> config/permisos.php
 *   - Qué trae la consulta (columnas, filtros, orden)           -> modelos/TipoVehiculo.php
 *   - Lo que se ve (textos, columnas, botones)                  -> paginas/ (pantalla Tipos de vehículo)
 *   - Los mensajes de error de la base (reglas de fechas, capacidad...) vienen de los
 *     triggers y procedimientos: se cambian con un script nuevo en database/cambios/.
 * Guía con ejemplos: docs/como_cambiar.md (buscá "CAMBIAR:" con Ctrl+Shift+F).
 * ============================================================================================
 */
/* Controlador de tipos de vehículo: valida, llama al modelo TipoVehiculo y responde JSON. */
require_once '../config/api.php';
exigir_permiso_crud('tipo_vehiculo');
require_once '../modelos/TipoVehiculo.php';

$tipos = new TipoVehiculo($pdo);

// CAMBIAR: qué datos pide el alta y la edición. En cada campo_...(): el nombre del dato,
//          el nombre que ve el usuario en los mensajes y los límites (largo máximo, mínimo y máximo).
//          Si agregás un dato acá, agregalo también al modelo (SQL) y a la pantalla (campos).
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
