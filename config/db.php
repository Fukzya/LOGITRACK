<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Manejar preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
    
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
    die(json_encode(['error' => 'No se pudo conectar con la base de datos.']));
}