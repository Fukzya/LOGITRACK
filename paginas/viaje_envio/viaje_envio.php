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
    <title>LogiTrack - Viaje Envío</title>
</head>
<body>
    <h1>LogiTrack</h1>
    <a href="../../index.php">← Volver al menú</a>

    <h2>Viaje - Envío</h2>

    <input type="number" id="input-viaje" placeholder="ID viaje">
    <input type="number" id="input-envio" placeholder="Nro envío">
    <button onclick="crearViajeEnvio()">Asignar</button>

    <hr>
    <input type="number" id="filtro-viaje-id" placeholder="Buscar por ID viaje">
    <input type="number" id="filtro-nro-envio" placeholder="Buscar por Nro envío">
    <button id="btn-filtrar">Filtrar</button>
    <button id="btn-limpiar">Limpiar</button>
    <hr>

    <div id="paginacion"></div>

    <table border="1">
        <thead>
            <tr>
                <th id="th-viaje-id" style="cursor:pointer">Viaje ID</th>
                <th id="th-nro-envio" style="cursor:pointer">Nro Envío</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="tabla-viaje-envio">
        </tbody>
    </table>

    <br>
    <a href="../../login.html" onclick="cerrarSesion(event)">Cerrar sesión</a>

    <script src="viaje_envio.js"></script>
</body>
</html>