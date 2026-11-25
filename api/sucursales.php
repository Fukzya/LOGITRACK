<?php
/*
 * ============================================================================================
 * QUÉ ES:  la API ("controlador") de las sucursales (código de 3 dígitos, nombre, localidad y dirección).
 *          La pantalla le pide datos y le manda altas, cambios y bajas. Este archivo revisa
 *          que los datos sean válidos y que el rol tenga permiso, y llama al modelo
 *          modelos/Sucursal.php, que es el único que habla con la base. Responde en JSON.
 * PARA CAMBIAR:
 *   - Qué datos se piden, si son obligatorios o su largo máximo -> "CAMBIAR:" acá abajo.
 *   - Quién puede hacer qué (operador / admin)                 -> config/permisos.php
 *   - Qué trae la consulta (columnas, filtros, orden)           -> modelos/Sucursal.php
 *   - Lo que se ve (textos, columnas, botones)                  -> paginas/ (pantalla Sucursales)
 *   - Los mensajes de error de la base (reglas de fechas, capacidad...) vienen de los
 *     triggers y procedimientos: se cambian con un script nuevo en database/cambios/.
 * Guía con ejemplos: docs/como_cambiar.md (buscá "CAMBIAR:" con Ctrl+Shift+F).
 * ============================================================================================
 */
/* Controlador de sucursales: valida, llama al modelo Sucursal y responde JSON. */
require_once '../config/api.php';
exigir_permiso_crud('sucursales');
require_once '../modelos/Sucursal.php';

$sucursales = new Sucursal($pdo);

function cod_sucursal(array $d): string
{
    return campo_codigo($d, 'sucursal_cod', 'El código', '/^[0-9]{3}$/', '3 dígitos (ej. 007)', false);
}

// CAMBIAR: qué datos pide el alta y la edición. En cada campo_...(): el nombre del dato,
//          el nombre que ve el usuario en los mensajes y los límites (largo máximo, mínimo y máximo).
//          Si agregás un dato acá, agregalo también al modelo (SQL) y a la pantalla (campos).
function datos_sucursal(array $d): array
{
    return [
        'sucursal_nombre'        => campo_texto($d, 'sucursal_nombre', 'El nombre', 120),
        'sucursal_localidad_cod' => campo_entero($d, 'sucursal_localidad_cod', 'La localidad'),
        'direccion'              => campo_texto($d, 'direccion', 'La dirección', 200, false),
    ];
}

$errores = [1452 => 'La localidad elegida no existe.'];

switch ($_SERVER['REQUEST_METHOD']) {

    case 'GET':
        // Pantalla "Resumen por sucursal" (procedimiento_resumen_por_sucursal).
        if (isset($_GET['resumen'])) {
            responder($sucursales->resumen(campo_fecha($_GET, 'desde', 'La fecha desde', false),
                                           campo_fecha($_GET, 'hasta', 'La fecha hasta', false)));
        }
        $filtros = ['nombre' => trim($_GET['nombre'] ?? '')];
        responder($sucursales->listar($filtros, params_listado('sucursal_cod'), ver_inactivos()));

    case 'POST':
        $d   = leer_json();
        $cod = cod_sucursal($d);
        $s   = ['sucursal_cod' => $cod] + datos_sucursal($d);
        intentar(fn() => $sucursales->crear($s), $errores + [1062 => "Ya existe una sucursal con código $cod."]);
        responder(['mensaje' => 'Sucursal creada.'], 201);

    case 'PUT':
        $d   = leer_json();
        $cod = cod_sucursal($d);
        if (!empty($d['reactivar'])) {
            if (!$sucursales->cambiarActivo($cod, true)) error_api('No existe una sucursal con ese código.', 404);
            responder(['mensaje' => 'Sucursal reactivada.']);
        }
        $s = datos_sucursal($d);
        if (!intentar(fn() => $sucursales->actualizar($cod, $s), $errores)) error_api('No existe una sucursal activa con ese código.', 404);
        responder(['mensaje' => 'Sucursal actualizada.']);

    case 'DELETE':
        $cod = cod_sucursal(leer_json());
        if (!$sucursales->cambiarActivo($cod, false)) error_api('No existe una sucursal con ese código.', 404);
        responder(['mensaje' => 'Sucursal dada de baja.']);

    default:
        error_api('Método no permitido.', 405);
}
