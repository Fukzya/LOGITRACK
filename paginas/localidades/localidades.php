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
    <title>LogiTrack - Localidades</title>
</head>
<body>
    <h1>LogiTrack</h1>
    <a href="../../index.php">← Volver al menú</a>

    <h2>Localidades</h2>

    <input type="text" id="input-nombre" placeholder="Nombre de localidad">
    <input type="number" id="input-provincia" placeholder="Código de provincia">
    <button onclick="crearLocalidad()">Agregar</button>

    <table border="1">
        <thead>
            <tr>
                <th id="th-localidad-cod" style="cursor:pointer">Código</th>
                <th id="th-localidad-nombre" style="cursor:pointer">Nombre</th>
                <th id="th-provincia-cod" style="cursor:pointer">Provincia</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="tabla-localidades">
        </tbody>
    </table>

    <br>
    <a href="../../login.html" onclick="cerrarSesion(event)">Cerrar sesión</a>

    <script src="localidades.js"></script>
</body>
</html>