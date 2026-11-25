<?php
/*
 * ============================================================================================
 * QUÉ ES:  la API ("controlador") de las tarjetas "Hoy" del menú (pendientes, viajes en curso y programados, clientes).
 *          La pantalla le pide datos y le manda altas, cambios y bajas. Este archivo revisa
 *          que los datos sean válidos y que el rol tenga permiso, y llama al modelo
 *          modelos/Tablero.php, que es el único que habla con la base. Responde en JSON.
 * PARA CAMBIAR:
 *   - Qué datos se piden, si son obligatorios o su largo máximo -> "CAMBIAR:" acá abajo.
 *   - Quién puede hacer qué (operador / admin)                 -> config/permisos.php
 *   - Qué trae la consulta (columnas, filtros, orden)           -> modelos/Tablero.php
 *   - Lo que se ve (textos, columnas, botones)                  -> paginas/ (pantalla Menú principal)
 *   - Los mensajes de error de la base (reglas de fechas, capacidad...) vienen de los
 *     triggers y procedimientos: se cambian con un script nuevo en database/cambios/.
 * Guía con ejemplos: docs/como_cambiar.md (buscá "CAMBIAR:" con Ctrl+Shift+F).
 * ============================================================================================
 */
/* Controlador del tablero del menú principal (solo lectura, desde las vistas). */
require_once '../config/api.php';
require_once '../modelos/Tablero.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    error_api('Método no permitido.', 405);
}
// CAMBIAR: los números de las tarjetas se calculan en modelos/Tablero.php.
responder((new Tablero($pdo))->resumen());
