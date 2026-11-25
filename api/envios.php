<?php
/*
 * ============================================================================================
 * QUÉ ES:  la API ("controlador") de los envíos (paquetes): peso, contenido, remitente, destinatario y sucursales.
 *          La pantalla le pide datos y le manda altas, cambios y bajas. Este archivo revisa
 *          que los datos sean válidos y que el rol tenga permiso, y llama al modelo
 *          modelos/Envio.php, que es el único que habla con la base. Responde en JSON.
 * PARA CAMBIAR:
 *   - Qué datos se piden, si son obligatorios o su largo máximo -> "CAMBIAR:" acá abajo.
 *   - Quién puede hacer qué (operador / admin)                 -> config/permisos.php
 *   - Qué trae la consulta (columnas, filtros, orden)           -> modelos/Envio.php
 *   - Lo que se ve (textos, columnas, botones)                  -> paginas/ (pantalla Envíos)
 *   - Los mensajes de error de la base (reglas de fechas, capacidad...) vienen de los
 *     triggers y procedimientos: se cambian con un script nuevo en database/cambios/.
 * Guía con ejemplos: docs/como_cambiar.md (buscá "CAMBIAR:" con Ctrl+Shift+F).
 * ============================================================================================
 */
/* Controlador de envíos: valida, llama al modelo Envio y responde JSON. */
require_once '../config/api.php';
exigir_permiso_crud('envios');
require_once '../modelos/Envio.php';
require_once '../config/validaciones.php';

$envios = new Envio($pdo);

// CAMBIAR: los datos que se piden para un envío están en config/validaciones.php
//          (datos_envio). Acá: el mensaje si algún código elegido no existe.
$errores = [1452 => 'El remitente, el destinatario, una sucursal, el tipo de contenido o la dirección indicados no existen.'];

switch ($_SERVER['REQUEST_METHOD']) {

    case 'GET':
        $filtros = [
            'fecha_desde'   => campo_fecha($_GET, 'fecha_desde', 'La fecha desde', false),
            'fecha_hasta'   => campo_fecha($_GET, 'fecha_hasta', 'La fecha hasta', false),
            'sucursal_orig' => trim($_GET['sucursal_orig'] ?? ''),
            'tipo'          => trim($_GET['tipo_cont'] ?? ''),
            'solo_pendientes' => ($_GET['solo_pendientes'] ?? '') === '1',
        ];
        responder($envios->listar($filtros, params_listado('nro_envio', 'DESC'), ver_inactivos()));

    case 'POST':
        $e   = datos_envio(leer_json());
        $nro = intentar(fn() => $envios->crear($e), $errores);
        responder(['mensaje' => "Envío $nro creado.", 'nro_envio_creado' => $nro], 201);

    case 'PUT':
        $d   = leer_json();
        $nro = campo_entero($d, 'nro_envio', 'El número de envío');
        if (!empty($d['reactivar'])) {
            if (!$envios->cambiarActivo($nro, true)) error_api('No existe ese envío.', 404);
            responder(['mensaje' => 'Envío reactivado.']);
        }
        $e = datos_envio($d);
        if (!intentar(fn() => $envios->actualizar($nro, $e), $errores)) error_api('No existe un envío activo con ese número.', 404);
        responder(['mensaje' => 'Envío actualizado.']);

    case 'DELETE':
        $nro = campo_entero(leer_json(), 'nro_envio', 'El número de envío');
        if (!$envios->cambiarActivo($nro, false)) error_api('No existe ese envío.', 404);
        responder(['mensaje' => 'Envío dado de baja.']);

    default:
        error_api('Método no permitido.', 405);
}
