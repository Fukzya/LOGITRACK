<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../login.html");
    exit;
}
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>LogiTrack - Provincias</title>
</head>
<body>
    <h1>LogiTrack</h1>
    <a href="../../index.php">← Volver al menú</a>

    <h2>Provincias</h2>

    <input type="text" id="input-provincia" placeholder="Nombre de provincia">
    <button onclick="crearProvincia()">Agregar</button>

    <table border="1">
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="tabla-provincias">
        </tbody>
    </table>

    <br>
    <a href="../../login.html" onclick="cerrarSesion(event)">Cerrar sesión</a>

    <script src="provincias.js"></script>
</body>
</html>