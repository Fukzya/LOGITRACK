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
    <title>LogiTrack - Incidentes</title>
</head>
<body>
    <h1>LogiTrack</h1>
    <a href="../../index.php">← Volver al menú</a>

    <h2>Incidentes</h2>

    <input type="number" id="input-viaje" placeholder="ID viaje">
    <input type="datetime-local" id="input-fecha">
    <select id="input-tipo">
        <option value="DEMORA">DEMORA</option>
        <option value="AVERIA">AVERIA</option>
        <option value="ACCIDENTE">ACCIDENTE</option>
        <option value="ROBO">ROBO</option>
        <option value="OTRO">OTRO</option>
    </select>
    <input type="text" id="input-descripcion" placeholder="Descripción (opcional)">
    <button onclick="crearIncidente()">Agregar</button>

    <hr>
    <input type="number" id="filtro-viaje-id" placeholder="Buscar por ID viaje">
    <select id="filtro-tipo">
        <option value="">Todos</option>
        <option value="ACCIDENTE">ACCIDENTE</option>
        <option value="DEMORA">DEMORA</option>
        <option value="ROBO">ROBO</option>
        <option value="OTRO">OTRO</option>
    </select>
    <label>Desde: <input type="date" id="filtro-fecha-desde"></label>
    <label>Hasta: <input type="date" id="filtro-fecha-hasta"></label>
    <button id="btn-filtrar">Filtrar</button>
    <button id="btn-limpiar">Limpiar</button>
    <hr>

    <div id="paginacion"></div>

    <table border="1">
        <thead>
            <tr>
                <th id="th-incidente-id" style="cursor:pointer">ID</th>
                <th id="th-viaje-id" style="cursor:pointer">Viaje</th>
                <th id="th-fecha-hora" style="cursor:pointer">Fecha/Hora</th>
                <th id="th-tipo-incidente" style="cursor:pointer">Tipo</th>
                <th>Descripción</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="tabla-incidentes">
        </tbody>
    </table>

    <br>
    <a href="../../login.html" onclick="cerrarSesion(event)">Cerrar sesión</a>

    <script src="incidentes.js"></script>
</body>
</html>