<?php
/*
 * Conexión a la base (una por pedido). La usan la API (config/db.php) y las pantallas
 * (para controlar la sesión). Cada conexión es una SESIÓN DE BASE DE DATOS: se abre acá y
 * MariaDB la cierra sola cuando PHP termina de atender el pedido.
 *
 * Mínimo privilegio (script 016): la app NUNCA usa root. La cuenta de base depende del rol de la
 * sesión de la aplicación:
 *   sin sesión (login)  -> logitrack_login     (solo puede validar usuario y abrir la sesión)
 *   operador            -> logitrack_operador  (rol_operador)
 *   admin               -> logitrack_admin     (rol_admin)
 * Las contraseñas están en config/credenciales.php, que NO se versiona
 * (lo genera database/configurar_cuentas.php).
 */
function conexion(?string $cuenta = null): PDO
{
    // $cuenta = 'login' lo usa solo api/login.php (aunque quede una sesión vieja abierta).
    $rol    = $_SESSION['rol'] ?? null;
    $cuenta ??= in_array($rol, ['admin', 'operador'], true) ? $rol : 'login';

    static $conexiones = [];
    if (isset($conexiones[$cuenta])) return $conexiones[$cuenta];

    $archivo = __DIR__ . '/credenciales.php';
    if (!is_file($archivo)) {
        error_log('[LogiTrack] Falta config/credenciales.php: correr "php database/configurar_cuentas.php".');
        fallo_conexion();
    }
    $datos = (require $archivo)[$cuenta];

    try {
        $pdo = new PDO("mysql:host=localhost;dbname=logitrack;charset=utf8mb4", $datos['usuario'], $datos['clave'], [
            PDO::ATTR_ERRMODE           => PDO::ERRMODE_EXCEPTION,  // todo error de la base es una excepción
            PDO::MYSQL_ATTR_FOUND_ROWS  => true,                    // rowCount() de un UPDATE = filas encontradas
        ]);
    } catch (PDOException $e) {
        // El detalle técnico va al log; al usuario no se le muestran datos del servidor.
        error_log('[LogiTrack] Error de conexión: ' . $e->getMessage());
        fallo_conexion();
    }
    return $conexiones[$cuenta] = $pdo;
}

function fallo_conexion(): never
{
    http_response_code(503);
    header('Content-Type: application/json; charset=utf-8');
    die(json_encode(['error' => 'No se pudo conectar con la base de datos.']));
}
