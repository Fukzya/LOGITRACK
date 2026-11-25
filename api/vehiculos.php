<?php
/*
 * ============================================================================================
 * QUÉ ES:  la API ("controlador") de los vehículos (patente, marca, modelo, año y tipo).
 *          La pantalla le pide datos y le manda altas, cambios y bajas. Este archivo revisa
 *          que los datos sean válidos y que el rol tenga permiso, y llama al modelo
 *          modelos/Vehiculo.php, que es el único que habla con la base. Responde en JSON.
 * PARA CAMBIAR:
 *   - Qué datos se piden, si son obligatorios o su largo máximo -> "CAMBIAR:" acá abajo.
 *   - Quién puede hacer qué (operador / admin)                 -> config/permisos.php
 *   - Qué trae la consulta (columnas, filtros, orden)           -> modelos/Vehiculo.php
 *   - Lo que se ve (textos, columnas, botones)                  -> paginas/ (pantalla Vehículos)
 *   - Los mensajes de error de la base (reglas de fechas, capacidad...) vienen de los
 *     triggers y procedimientos: se cambian con un script nuevo en database/cambios/.
 * Guía con ejemplos: docs/como_cambiar.md (buscá "CAMBIAR:" con Ctrl+Shift+F).
 * ============================================================================================
 */
/* Controlador de vehículos: valida, llama al modelo Vehiculo y responde JSON. */
require_once '../config/api.php';
exigir_permiso_crud('vehiculos');
require_once '../modelos/Vehiculo.php';

$vehiculos = new Vehiculo($pdo);

function patente(array $d): string
{
    // Formato Mercosur (AB123CD) o el anterior (ABC123).
    return campo_codigo($d, 'vehiculo_patente', 'La patente', '/^([A-Z]{2}[0-9]{3}[A-Z]{2}|[A-Z]{3}[0-9]{3})$/', 'AB123CD o ABC123');
}

// CAMBIAR: qué datos pide el alta y la edición. En cada campo_...(): el nombre del dato,
//          el nombre que ve el usuario en los mensajes y los límites (largo máximo, mínimo y máximo).
//          Si agregás un dato acá, agregalo también al modelo (SQL) y a la pantalla (campos).
function datos_vehiculo(array $d): array
{
    return [
        'marca'        => campo_texto($d, 'marca', 'La marca', 60),
        'modelo'       => campo_texto($d, 'modelo', 'El modelo', 60),
        'anio'         => campo_entero($d, 'anio', 'El año', 1950, (int)date('Y') + 1, false),
        'tipo_veh_cod' => campo_codigo($d, 'tipo_veh_cod', 'El tipo de vehículo', '/^[A-Z]{4}$/', '4 letras'),
    ];
}

$errores = [1452 => 'El tipo de vehículo elegido no existe.'];

switch ($_SERVER['REQUEST_METHOD']) {

    case 'GET':
        $filtros = [
            'patente' => mb_strtoupper(trim($_GET['patente'] ?? '')),
            'marca'   => trim($_GET['marca'] ?? ''),
            'tipo'    => trim($_GET['tipo_veh_cod'] ?? ''),
        ];
        responder($vehiculos->listar($filtros, params_listado('vehiculo_patente'), ver_inactivos()));

    case 'POST':
        $d   = leer_json();
        $pat = patente($d);
        $v   = ['vehiculo_patente' => $pat] + datos_vehiculo($d);
        intentar(fn() => $vehiculos->crear($v), $errores + [1062 => "Ya existe un vehículo con patente $pat."]);
        responder(['mensaje' => 'Vehículo creado.'], 201);

    case 'PUT':
        $d   = leer_json();
        $pat = patente($d);
        if (!empty($d['reactivar'])) {
            if (!$vehiculos->cambiarActivo($pat, true)) error_api('No existe un vehículo con esa patente.', 404);
            responder(['mensaje' => 'Vehículo reactivado.']);
        }
        $v = datos_vehiculo($d);
        if (!intentar(fn() => $vehiculos->actualizar($pat, $v), $errores)) error_api('No existe un vehículo activo con esa patente.', 404);
        responder(['mensaje' => 'Vehículo actualizado.']);

    case 'DELETE':
        $pat = patente(leer_json());
        if (!$vehiculos->cambiarActivo($pat, false)) error_api('No existe un vehículo con esa patente.', 404);
        responder(['mensaje' => 'Vehículo dado de baja.']);

    default:
        error_api('Método no permitido.', 405);
}
