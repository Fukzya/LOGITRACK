<?php
/*
 * ============================================================================================
 * QUÉ ES:  abre la conexión para las APIs (la arma config/conexion.php). Nada para cambiar acá.
 * ============================================================================================
 */
/*
 * Conexión de la API. Sin cabeceras CORS: las pantallas y la API están en el mismo sitio, así
 * que el navegador no necesita permiso para "otro origen" (y otro sitio no puede usar la API).
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexion.php';
$pdo = conexion();
