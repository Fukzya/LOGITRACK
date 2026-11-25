<?php
/*
 * ============================================================================================
 * QUÉ ES:  la API ("controlador") de las sesiones abiertas y cerradas y las conexiones a la base (solo admin).
 *          La pantalla le pide datos y le manda altas, cambios y bajas. Este archivo revisa
 *          que los datos sean válidos y que el rol tenga permiso, y llama al modelo
 *          modelos/Sesion.php, que es el único que habla con la base. Responde en JSON.
 * PARA CAMBIAR:
 *   - Qué datos se piden, si son obligatorios o su largo máximo -> "CAMBIAR:" acá abajo.
 *   - Quién puede hacer qué (operador / admin)                 -> config/permisos.php
 *   - Qué trae la consulta (columnas, filtros, orden)           -> modelos/Sesion.php
 *   - Lo que se ve (textos, columnas, botones)                  -> paginas/ (pantalla Sesiones)
 *   - Los mensajes de error de la base (reglas de fechas, capacidad...) vienen de los
 *     triggers y procedimientos: se cambian con un script nuevo en database/cambios/.
 * Guía con ejemplos: docs/como_cambiar.md (buscá "CAMBIAR:" con Ctrl+Shift+F).
 * ============================================================================================
 */
/*
 * Controlador de sesiones (solo admin).
 *   GET ?ver=abiertas     sesiones de la aplicación abiertas ahora (vista_sesiones_abiertas)
 *   GET (sin ver)         historial: sesiones cerradas, paginadas, filtro ?username=
 *   GET ?ver=conexiones   conexiones abiertas en MariaDB (cada una es una sesión de base de datos)
 *   POST {sesion_id}      cierra una sesión ajena (motivo CERRADA_POR_ADMIN)
 */
require_once '../config/api.php';

exigir_permiso('sesiones', 'gestionar');   // no figura en PERMISOS_OPERADOR: solo admin

$sesiones = modelo_sesion();
// CAMBIAR: el listado sale de la tabla sesion y de vista_sesiones_abiertas; el tiempo
//          de vencimiento está en config/session.php (MINUTOS_SESION).
$propia   = (int)$_SESSION['sesion_id'];

switch ($_SERVER['REQUEST_METHOD']) {

    case 'GET':
        switch ($_GET['ver'] ?? '') {
            case 'abiertas':
                $sesiones->cerrarVencidas();
                $filas = array_map(function ($f) use ($propia) {
                    $f['propia'] = (int)$f['sesion_id'] === $propia;
                    unset($f['token_hash']);   // no sale de la base
                    return $f;
                }, $sesiones->abiertas());
                responder(['datos' => $filas]);

            case 'conexiones':
                responder(['datos' => $sesiones->conexiones(), 'esta_conexion' => $sesiones->estaConexion()]);

            default:   // historial (lo pide crud.js sin ?ver)
                responder($sesiones->historial(params_listado('fin', 'DESC'), trim((string)($_GET['username'] ?? ''))));
        }

    case 'POST':
        $id = campo_entero(leer_json(), 'sesion_id', 'La sesión');
        if ($id === $propia) error_api('Esa es tu sesión: para salir usá "Cerrar sesión".');
        $sesiones->cerrar($id, 'CERRADA_POR_ADMIN');   // si ya estaba cerrada, el procedimiento avisa
        responder(['mensaje' => 'Sesión cerrada. El usuario vuelve al login en su próximo clic.']);

    default:
        error_api('Método no permitido.', 405);
}
