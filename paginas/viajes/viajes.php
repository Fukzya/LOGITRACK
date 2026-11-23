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
    <title>LogiTrack - Viajes</title>
</head>
<body>
    <h1>LogiTrack</h1>
    <a href="../../index.php">← Volver al menú</a>

    <h2>Viajes</h2>

    <input type="datetime-local" id="input-salida">
    <input type="datetime-local" id="input-llegada">
    <input type="text" id="input-patente" placeholder="Patente vehículo">
    <input type="number" id="input-chofer" placeholder="Legajo chofer">
    <button onclick="crearViaje()">Agregar</button>

    <hr>
    <label>Fecha desde: <input type="date" id="filtro-fecha-desde"></label>
    <label>Fecha hasta: <input type="date" id="filtro-fecha-hasta"></label>
    <label>Chofer:
        <select id="filtro-chofer">
            <option value="">Todos los choferes</option>
        </select>
    </label>
    <label><input type="checkbox" id="filtro-solo-activos"> Solo viajes activos</label>
    <button id="btn-filtrar">Filtrar</button>
    <button id="btn-limpiar">Limpiar</button>
    <hr>

    <div id="paginacion"></div>

    <table border="1">
        <thead>
            <tr>
                <th id="th-viaje-id" style="cursor:pointer">ID</th>
                <th id="th-fecha-salida" style="cursor:pointer">Salida</th>
                <th id="th-fecha-llegada" style="cursor:pointer">Llegada</th>
                <th>Patente</th>
                <th>Chofer</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="tabla-viajes">
        </tbody>
    </table>

    <br>
    <a href="../../login.html" onclick="cerrarSesion(event)">Cerrar sesión</a>

    <script src="viajes.js"></script>
</body>
</html>