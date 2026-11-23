<?php
/*
 * Encabezado y pie comunes de todas las pantallas (capa Vista).
 * Se ejecuta en el servidor antes de mandar nada al navegador:
 *   - sin sesión iniciada, redirige al login;
 *   - las pantallas solo para admin redirigen al menú si entra un operador.
 */
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../login.html');
    exit;
}
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

$esAdmin = ($_SESSION['rol'] ?? '') === 'admin';

function inicio_pagina(string $titulo, bool $soloAdmin = false): void
{
    global $esAdmin;
    if ($soloAdmin && !$esAdmin) {
        header('Location: ../../index.php');
        exit;
    }
    ?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>LogiTrack - <?= htmlspecialchars($titulo) ?></title>
</head>
<body>
    <h1>LogiTrack</h1>
    <a href="../../index.php">← Volver al menú</a>
    <h2><?= htmlspecialchars($titulo) ?></h2>
<?php
}

/** $js: el script propio de la pantalla (ej. 'clientes.js'). */
function fin_pagina(string $js): void
{
    global $esAdmin;
    ?>
    <br>
    <a href="../../login.html" onclick="cerrarSesion(event)">Cerrar sesión</a>

    <script>const esAdmin = <?= $esAdmin ? 'true' : 'false' ?>;</script>
    <script src="../comun.js"></script>
    <script src="../catalogos.js"></script>
    <script src="../crud.js"></script>
    <script src="<?= htmlspecialchars($js) ?>"></script>
</body>
</html>
<?php
}
