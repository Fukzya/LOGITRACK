<?php
// Devuelve las opciones de las tablas catálogo para llenar los <select> del front.
// GET ?tipo=estados | incidentes | licencias | localidades
require_once '../config/api.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    error_api('Método no permitido.', 405);
}

// Cada tipo devuelve las columnas como { cod, nombre } para que el front sea uno solo.
$consultas = [
    'estados'     => 'SELECT estado_cod AS cod, estado_nombre AS nombre FROM estado_envio ORDER BY es_final, estado_nombre',
    'incidentes'  => 'SELECT tipo_incidente_cod AS cod, tipo_incidente_nombre AS nombre FROM tipo_incidente ORDER BY tipo_incidente_nombre',
    'licencias'   => "SELECT licencia_cod AS cod, CONCAT(licencia_cod, ' - ', licencia_descripcion) AS nombre FROM tipo_licencia ORDER BY licencia_cod",
    'localidades' => "SELECT l.localidad_cod AS cod, CONCAT(l.localidad_nombre, ' (', p.provincia_nombre, ')') AS nombre
                      FROM localidad l JOIN provincia p ON p.provincia_cod = l.provincia_cod ORDER BY nombre",
];

$tipo = $_GET['tipo'] ?? '';
if (!isset($consultas[$tipo])) {
    error_api('Tipo de catálogo inválido.', 400);
}

responder($pdo->query($consultas[$tipo])->fetchAll(PDO::FETCH_ASSOC));
