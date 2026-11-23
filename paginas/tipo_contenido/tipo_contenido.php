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
    <title>LogiTrack - Tipos de Contenido</title>
</head>
<body>
    <h1>LogiTrack</h1>
    <a href="../../index.php">← Volver al menú</a>

    <h2>Tipos de Contenido</h2>

    <input type="text" id="input-cod" placeholder="Código (3 letras)">
    <input type="text" id="input-nombre" placeholder="Nombre">
    <select id="input-seguro">
        <option value="0">No requiere seguro</option>
        <option value="1">Requiere seguro</option>
    </select>
    <button onclick="crearTipo()">Agregar</button>

    <table border="1">
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Requiere Seguro</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="tabla-tipos">
        </tbody>
    </table>

    <br>
    <a href="../../login.html" onclick="cerrarSesion(event)">Cerrar sesión</a>

    <script src="tipo_contenido.js"></script>
</body>
</html>