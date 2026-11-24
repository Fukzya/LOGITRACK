<?php
/*
 * Sesión de la APLICACIÓN (quién está logueado). Único lugar donde se controla: lo usan la API
 * (verificar_sesion), las pantallas (verificar_sesion_pagina) y api/check_session.php.
 *
 *  - PHP guarda los datos del usuario y le da al navegador una cookie con el id de sesión.
 *  - La base registra cada sesión (tabla sesion) y decide si sigue activa: 30 min sin uso,
 *    logout, cerrada por un admin, usuario dado de baja o con el rol cambiado (trigger).
 */
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/../modelos/Sesion.php';

const MINUTOS_SESION = 30;   // el mismo valor que funcion_minutos_sesion() en la base

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');                         // no acepta ids de sesión inventados
    ini_set('session.gc_maxlifetime', (string)(MINUTOS_SESION * 60)); // PHP no borra la sesión antes de tiempo
    session_set_cookie_params([
        'lifetime' => 0,          // la cookie se borra al cerrar el navegador
        'path'     => '/',
        'httponly' => true,       // JavaScript no puede leerla
        'samesite' => 'Strict',   // otro sitio no puede mandarla
        'secure'   => false,      // XAMPP usa http; con https iría true
    ]);
    session_start();
}

const MENSAJES_SESION = [
    'VENCIDA'           => 'Tu sesión venció por inactividad. Volvé a entrar.',
    'LOGOUT'            => 'Cerraste la sesión.',
    'CERRADA_POR_ADMIN' => 'Un administrador cerró tu sesión.',
    'BAJA_USUARIO'      => 'Tu usuario fue dado de baja.',
    'CAMBIO_ROL'        => 'Tu rol cambió. Volvé a entrar.',
    'SIN_SESION'        => 'No autenticado.',
];

function modelo_sesion(): Sesion
{
    static $modelo = null;
    return $modelo ??= new Sesion(conexion());
}

/** Hash del id de sesión de PHP: es lo único que se guarda en la base. */
function token_sesion(): string
{
    return hash('sha256', session_id());
}

/** Login correcto: id de sesión nuevo (evita la fijación de sesión) y fila nueva en la base. */
function iniciar_sesion(int $usuario_id, string $username, string $rol): void
{
    session_regenerate_id(true);
    $sesionId = modelo_sesion()->iniciar($usuario_id, token_sesion(),
                                         $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null);
    $_SESSION = [
        'usuario_id' => $usuario_id,
        'username'   => $username,
        'rol'        => $rol,
        'sesion_id'  => $sesionId,
    ];
}

/**
 * null si la sesión es válida (y la marca como usada); si no, el motivo
 * (VENCIDA, CERRADA_POR_ADMIN, BAJA_USUARIO, CAMBIO_ROL, LOGOUT o SIN_SESION).
 */
function motivo_sesion_invalida(): ?string
{
    if (empty($_SESSION['sesion_id'])) return 'SIN_SESION';

    $id = (int)$_SESSION['sesion_id'];
    $s  = modelo_sesion()->estado($id);
    if (!$s || !hash_equals($s['token_hash'], token_sesion())) return 'SIN_SESION';
    if ($s['fin'] !== null) return $s['motivo_cierre'];
    if (!$s['activa']) {
        modelo_sesion()->cerrarVencidas();
        return 'VENCIDA';
    }
    modelo_sesion()->tocar($id, $_SESSION['username']);
    return null;
}

/** Borra la sesión de PHP: datos, cookie y archivo del servidor. */
function terminar_sesion_php(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000, 'path' => $p['path'], 'domain' => $p['domain'],
            'secure' => $p['secure'], 'httponly' => $p['httponly'], 'samesite' => $p['samesite'],
        ]);
    }
    session_destroy();
}

/** Logout: cierra la fila en la base con el motivo y borra la sesión de PHP. */
function cerrar_sesion(string $motivo = 'LOGOUT'): void
{
    if (!empty($_SESSION['sesion_id'])) {
        try {
            modelo_sesion()->cerrar((int)$_SESSION['sesion_id'], $motivo);
        } catch (PDOException $e) {
            // Ya estaba cerrada (por ejemplo, la cerró un admin): igual se sale.
        }
    }
    terminar_sesion_php();
}

/** Para la API: sin sesión válida responde 401 con el motivo y corta. */
function verificar_sesion(): void
{
    $motivo = motivo_sesion_invalida();
    if ($motivo === null) return;
    terminar_sesion_php();
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => MENSAJES_SESION[$motivo] ?? 'No autenticado.', 'motivo' => $motivo]);
    exit();
}

/** Para las pantallas: sin sesión válida redirige al login (con el motivo) antes de dibujar nada. */
function verificar_sesion_pagina(string $raiz): void
{
    $motivo = motivo_sesion_invalida();
    if ($motivo === null) return;
    terminar_sesion_php();
    header('Location: ' . $raiz . 'login.html' . ($motivo !== 'SIN_SESION' ? '?motivo=' . $motivo : ''));
    exit();
}

function es_admin(): bool
{
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
}
