<?php
/*
 * Conexión a la base (una por pedido). La usan la API (config/db.php) y las pantallas
 * (para controlar la sesión). Cada conexión es una SESIÓN DE BASE DE DATOS: se abre acá y
 * MariaDB la cierra sola cuando PHP termina de atender el pedido.
 */
function conexion(): PDO
{
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $host = 'localhost';
    $dbname = 'logitrack';
    $user = 'root';
    $password = '';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password, [
            PDO::ATTR_ERRMODE           => PDO::ERRMODE_EXCEPTION,  // todo error de la base es una excepción
            PDO::MYSQL_ATTR_FOUND_ROWS  => true,                    // rowCount() de un UPDATE = filas encontradas
        ]);
    } catch (PDOException $e) {
        // El detalle técnico va al log; al usuario no se le muestran datos del servidor.
        error_log('[LogiTrack] Error de conexión: ' . $e->getMessage());
        http_response_code(503);
        header('Content-Type: application/json; charset=utf-8');
        die(json_encode(['error' => 'No se pudo conectar con la base de datos.']));
    }
    return $pdo;
}
