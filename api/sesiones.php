<?php
/*
 * Controlador de sesiones (solo admin).
 *   GET ?ver=abiertas     sesiones de la aplicación abiertas ahora (vista_sesiones_abiertas)
 *   GET (sin ver)         historial: sesiones cerradas, paginadas, filtro ?username=
 *   GET ?ver=conexiones   conexiones abiertas en MariaDB (cada una es una sesión de base de datos)
 *   POST {sesion_id}      cierra una sesión ajena (motivo CERRADA_POR_ADMIN)
 */
require_once '../config/api.php';

if (!es_admin()) {
    error_api('Acceso denegado: solo un administrador puede ver las sesiones.', 403);
}

$sesiones = modelo_sesion();
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
