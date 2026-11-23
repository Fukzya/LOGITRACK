<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function iniciar_sesion($usuario_id, $username, $rol) {
    $_SESSION['usuario_id']    = $usuario_id;
    $_SESSION['username']      = $username;
    $_SESSION['rol']           = $rol;
    $_SESSION['ultimo_acceso'] = time();
}

function verificar_sesion() {
    if (empty($_SESSION['usuario_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'No autenticado']);
        exit();
    }

    $TIMEOUT = 30 * 60;
    if (isset($_SESSION['ultimo_acceso'])) {
        if (time() - $_SESSION['ultimo_acceso'] > $TIMEOUT) {
            session_destroy();
            http_response_code(401);
            echo json_encode(['error' => 'Sesión expirada']);
            exit();
        }
    }
    $_SESSION['ultimo_acceso'] = time();
}

function cerrar_sesion() {
    session_destroy();
}

function es_admin() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
}
