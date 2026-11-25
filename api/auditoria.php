<?php
/* Controlador de auditoría (solo admin, solo lectura): los cambios los registran los triggers. */
require_once '../config/api.php';
require_once '../modelos/Auditoria.php';

exigir_permiso('auditoria', 'gestionar');   // no figura en PERMISOS_OPERADOR: solo admin
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    error_api('La auditoría no se modifica: la escriben los triggers de la base.', 405);
}

$tabla = trim($_GET['tabla'] ?? '');
if ($tabla !== '' && !in_array($tabla, ['envio', 'viaje', 'usuario'], true)) {
    error_api('Tabla inválida.');
}
$filtros = [
    'tabla'       => $tabla,
    'clave'       => campo_texto($_GET, 'clave', 'La clave', 30, false),
    'usuario'     => campo_texto($_GET, 'usuario', 'El usuario', 100, false),
    'fecha_desde' => campo_fecha($_GET, 'fecha_desde', 'La fecha desde', false),
    'fecha_hasta' => campo_fecha($_GET, 'fecha_hasta', 'La fecha hasta', false),
];
responder((new Auditoria($pdo))->listar($filtros, params_listado('auditoria_id', 'DESC')));
