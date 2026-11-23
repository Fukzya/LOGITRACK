<?php
// Devuelve las opciones de las tablas catálogo para llenar los <select> del front.
// GET ?tipo=estados | incidentes | licencias
require_once '../config/session.php';
verificar_sesion();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit();
}

// Cada tipo devuelve las columnas como { cod, nombre } para que el front sea uno solo.
$consultas = [
    'estados'    => 'SELECT estado_cod AS cod, estado_nombre AS nombre FROM estado_envio ORDER BY es_final, estado_nombre',
    'incidentes' => 'SELECT tipo_incidente_cod AS cod, tipo_incidente_nombre AS nombre FROM tipo_incidente ORDER BY tipo_incidente_nombre',
    'licencias'  => "SELECT licencia_cod AS cod, CONCAT(licencia_cod, ' - ', licencia_descripcion) AS nombre FROM tipo_licencia ORDER BY licencia_cod",
];

$tipo = $_GET['tipo'] ?? '';
if (!isset($consultas[$tipo])) {
    http_response_code(400);
    echo json_encode(['error' => 'Tipo de catálogo inválido']);
    exit();
}

echo json_encode($pdo->query($consultas[$tipo])->fetchAll(PDO::FETCH_ASSOC));
