<?php
require_once '../config/session.php';
header('Content-Type: application/json; charset=utf-8');

if (!empty($_SESSION['usuario_id'])) {
    echo json_encode([
        'autenticado' => true,
        'username'    => $_SESSION['username'],
        'rol'         => $_SESSION['rol']
    ]);
} else {
    echo json_encode(['autenticado' => false]);
}
