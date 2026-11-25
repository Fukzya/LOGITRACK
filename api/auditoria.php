<?php
/*
 * ============================================================================================
 * QUÉ ES:  la API ("controlador") de el registro de cambios (quién cambió qué y cuándo; solo admin).
 *          La pantalla le pide datos y le manda altas, cambios y bajas. Este archivo revisa
 *          que los datos sean válidos y que el rol tenga permiso, y llama al modelo
 *          modelos/Auditoria.php, que es el único que habla con la base. Responde en JSON.
 * PARA CAMBIAR:
 *   - Qué datos se piden, si son obligatorios o su largo máximo -> "CAMBIAR:" acá abajo.
 *   - Quién puede hacer qué (operador / admin)                 -> config/permisos.php
 *   - Qué trae la consulta (columnas, filtros, orden)           -> modelos/Auditoria.php
 *   - Lo que se ve (textos, columnas, botones)                  -> paginas/ (pantalla Auditoría)
 *   - Los mensajes de error de la base (reglas de fechas, capacidad...) vienen de los
 *     triggers y procedimientos: se cambian con un script nuevo en database/cambios/.
 * Guía con ejemplos: docs/como_cambiar.md (buscá "CAMBIAR:" con Ctrl+Shift+F).
 * ============================================================================================
 */
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
// CAMBIAR: qué filtros acepta la auditoría (tabla, clave, usuario, fechas).
$filtros = [
    'tabla'       => $tabla,
    'clave'       => campo_texto($_GET, 'clave', 'La clave', 30, false),
    'usuario'     => campo_texto($_GET, 'usuario', 'El usuario', 100, false),
    'fecha_desde' => campo_fecha($_GET, 'fecha_desde', 'La fecha desde', false),
    'fecha_hasta' => campo_fecha($_GET, 'fecha_hasta', 'La fecha hasta', false),
];
responder((new Auditoria($pdo))->listar($filtros, params_listado('auditoria_id', 'DESC')));
