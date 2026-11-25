<?php
/*
 * ============================================================================================
 * QUÉ ES:  cierra la sesión (en la base y en PHP). Nada para cambiar acá.
 * ============================================================================================
 */
// Logout solo por POST: un enlace o una imagen de otro sitio no puede cerrar la sesión.
require_once '../config/session.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Método no permitido']));
}

cerrar_sesion('LOGOUT');
echo json_encode(['ok' => true]);
