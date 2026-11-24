<?php
// ¿Hay una sesión válida? Lo usan el login (para saltearlo) y las pantallas (para volver al login).
require_once '../config/session.php';
header('Content-Type: application/json; charset=utf-8');

$motivo = motivo_sesion_invalida();
if ($motivo === null) {
    echo json_encode([
        'autenticado' => true,
        'username'    => $_SESSION['username'],
        'rol'         => $_SESSION['rol'],
    ]);
} else {
    terminar_sesion_php();
    echo json_encode(['autenticado' => false, 'motivo' => $motivo]);
}
