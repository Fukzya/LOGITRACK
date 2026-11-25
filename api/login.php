<?php
/*
 * ============================================================================================
 * QUÉ ES:  recibe usuario y contraseña, los compara con el hash guardado y abre la sesión.
 * PARA CAMBIAR: el mensaje de error del login -> "CAMBIAR:" abajo.
 * Guía con ejemplos: docs/como_cambiar.md (buscá "CAMBIAR:" con Ctrl+Shift+F).
 * ============================================================================================
 */
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
    // CAMBIAR: mensaje si el usuario o la contraseña no coinciden (a propósito no dice cuál
    //          de los dos falló: así no se puede averiguar qué usuarios existen).
    echo json_encode(['ok' => false, 'mensaje' => 'Usuario o contraseña incorrectos']);
}
