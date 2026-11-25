<?php
require_once '../config/session.php';
require_once '../config/conexion.php';
header('Content-Type: application/json; charset=utf-8');
$pdo = conexion('login');   // cuenta logitrack_login: solo lee usuario y abre la sesión

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Método no permitido']));
}

$datos = json_decode(file_get_contents('php://input'), true);
$username = (string)($datos['username'] ?? '');
$password = (string)($datos['password'] ?? '');

$stmt = $pdo->prepare('SELECT usuario_id, username, password_hash, rol FROM usuario WHERE username = :username AND activo = 1');
$stmt->execute([':username' => $username]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if ($usuario && password_verify($password, $usuario['password_hash'])) {
    iniciar_sesion((int)$usuario['usuario_id'], $usuario['username'], $usuario['rol']);
    echo json_encode(['ok' => true, 'rol' => $usuario['rol'], 'username' => $usuario['username']]);
} else {
    http_response_code(401);
    echo json_encode(['ok' => false, 'mensaje' => 'Usuario o contraseña incorrectos']);
}
