<?php
require_once '../config/session.php';
verificar_sesion();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!es_admin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit();
}

$metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {

    case 'GET':
        $usuarios = $pdo->query("SELECT usuario_id, username, rol, activo FROM usuario")
                        ->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($usuarios);
        break;

    case 'POST':
        $datos    = json_decode(file_get_contents('php://input'), true);
        $username = trim($datos['username'] ?? '');
        $password = $datos['password'] ?? '';
        $rol      = $datos['rol'] ?? 'operador';

        if ($username === '' || $password === '') {
            http_response_code(400);
            echo json_encode(['error' => 'El usuario y la contraseña son obligatorios']);
            break;
        }

        try {
            $hash     = password_hash($password, PASSWORD_DEFAULT);
            $consulta = $pdo->prepare("INSERT INTO usuario (username, password_hash, rol) VALUES (:username, :hash, :rol)");
            $consulta->execute([':username' => $username, ':hash' => $hash, ':rol' => $rol]);
            echo json_encode(['mensaje' => 'Usuario creado', 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                http_response_code(409);
                echo json_encode(['error' => 'El usuario ya existe']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => $e->getMessage()]);
            }
        }
        break;

    case 'PUT':
        $datos  = json_decode(file_get_contents('php://input'), true);
        $id     = (int)($datos['usuario_id'] ?? 0);
        $propio = (int)$_SESSION['usuario_id'];

        // Bloquear cambios de rol o desactivación del propio usuario
        if ($id === $propio) {
            if (array_key_exists('rol', $datos)) {
                http_response_code(403);
                echo json_encode(['error' => 'No podés cambiar tu propio rol']);
                break;
            }
            if (array_key_exists('activo', $datos) && (int)$datos['activo'] === 0) {
                http_response_code(403);
                echo json_encode(['error' => 'No podés desactivarte a vos mismo']);
                break;
            }
        }

        // Solo activo
        if (array_key_exists('activo', $datos) && count($datos) === 2) {
            $consulta = $pdo->prepare("UPDATE usuario SET activo = :activo WHERE usuario_id = :id");
            $consulta->execute([':activo' => (int)$datos['activo'], ':id' => $id]);
            echo json_encode(['mensaje' => 'Estado actualizado']);
            break;
        }

        // Solo password
        if (array_key_exists('password', $datos) && count($datos) === 2) {
            $hash     = password_hash($datos['password'], PASSWORD_DEFAULT);
            $consulta = $pdo->prepare("UPDATE usuario SET password_hash = :hash WHERE usuario_id = :id");
            $consulta->execute([':hash' => $hash, ':id' => $id]);
            echo json_encode(['mensaje' => 'Contraseña actualizada']);
            break;
        }

        // Solo rol
        if (array_key_exists('rol', $datos) && count($datos) === 2) {
            $consulta = $pdo->prepare("UPDATE usuario SET rol = :rol WHERE usuario_id = :id");
            $consulta->execute([':rol' => $datos['rol'], ':id' => $id]);
            echo json_encode(['mensaje' => 'Rol actualizado']);
            break;
        }

        // username + rol, con password opcional
        if (!empty($datos['password'])) {
            $hash     = password_hash($datos['password'], PASSWORD_DEFAULT);
            $consulta = $pdo->prepare("UPDATE usuario SET username = :username, rol = :rol, password_hash = :hash WHERE usuario_id = :id");
            $consulta->execute([':username' => $datos['username'], ':rol' => $datos['rol'], ':hash' => $hash, ':id' => $id]);
        } else {
            $consulta = $pdo->prepare("UPDATE usuario SET username = :username, rol = :rol WHERE usuario_id = :id");
            $consulta->execute([':username' => $datos['username'], ':rol' => $datos['rol'], ':id' => $id]);
        }
        echo json_encode(['mensaje' => 'Usuario actualizado']);
        break;

    case 'DELETE':
        $datos  = json_decode(file_get_contents('php://input'), true);
        $id     = (int)($datos['usuario_id'] ?? 0);

        if ($id === (int)$_SESSION['usuario_id']) {
            http_response_code(403);
            echo json_encode(['error' => 'No podés desactivarte a vos mismo']);
            break;
        }

        $consulta = $pdo->prepare("UPDATE usuario SET activo = 0 WHERE usuario_id = :id");
        $consulta->execute([':id' => $id]);
        echo json_encode(['mensaje' => 'Usuario desactivado']);
        break;
}
