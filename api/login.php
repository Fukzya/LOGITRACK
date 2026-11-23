<?php
require_once '../config/session.php';
require_once '../config/db.php';

$metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {
    case 'POST':
        $datos = json_decode(file_get_contents('php://input'), true);
        $username = $datos['username'] ?? '';
        $password = $datos['password'] ?? '';

        $stmt = $pdo->prepare('SELECT usuario_id, username, password_hash, rol FROM usuario WHERE username = :username AND activo = 1');
        $stmt->execute([':username' => $username]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && password_verify($password, $usuario['password_hash'])) {
            session_regenerate_id(true);
            iniciar_sesion($usuario['usuario_id'], $usuario['username'], $usuario['rol']);
            echo json_encode(['ok' => true, 'rol' => $usuario['rol'], 'username' => $usuario['username']]);
        } else {
            http_response_code(401);
            echo json_encode(['ok' => false, 'mensaje' => 'Usuario o contraseña incorrectos']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
}
