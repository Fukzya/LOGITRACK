<?php
/* Controlador del tablero del menú principal (solo lectura, desde las vistas). */
require_once '../config/api.php';
require_once '../modelos/Tablero.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    error_api('Método no permitido.', 405);
}
responder((new Tablero($pdo))->resumen());
