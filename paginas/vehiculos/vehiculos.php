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
    <title>LogiTrack - Vehículos</title>
</head>
<body>
    <h1>LogiTrack</h1>
    <a href="../../index.php">← Volver al menú</a>

    <h2>Vehículos</h2>

    <input type="text" id="input-patente" placeholder="Patente">
    <input type="text" id="input-marca" placeholder="Marca">
    <input type="text" id="input-modelo" placeholder="Modelo">
    <input type="number" id="input-anio" placeholder="Año">
    <input type="text" id="input-tipo" placeholder="Código tipo vehículo">
    <button onclick="crearVehiculo()">Agregar</button>

    <hr>
    <input type="text" id="filtro-patente" placeholder="Buscar por patente">
    <input type="text" id="filtro-marca" placeholder="Buscar por marca">
    <select id="filtro-tipo-veh">
        <option value="">Todos</option>
        <option value="CAMN">Camión</option>
        <option value="FURM">Furgoneta</option>
        <option value="MOTO">Moto mensajería</option>
        <option value="SEMI">Semi-remolque</option>
    </select>
    <button id="btn-filtrar">Filtrar</button>
    <button id="btn-limpiar">Limpiar</button>
    <?php if ($_SESSION['rol'] === 'admin'): ?>
    <label>
        <input type="checkbox" id="filtro-mostrar-inactivos">
        Mostrar vehículos inactivos
    </label>
    <?php endif; ?>
    <hr>

    <div id="paginacion"></div>

    <table border="1">
        <thead>
            <tr>
                <th id="th-patente" style="cursor:pointer">Patente</th>
                <th id="th-marca" style="cursor:pointer">Marca</th>
                <th id="th-modelo" style="cursor:pointer">Modelo</th>
                <th id="th-anio" style="cursor:pointer">Año</th>
                <th id="th-tipo-veh" style="cursor:pointer">Tipo</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="tabla-vehiculos">
        </tbody>
    </table>

    <br>
    <a href="../../login.html" onclick="cerrarSesion(event)">Cerrar sesión</a>

    <script>
        const esAdmin = <?php echo ($_SESSION['rol'] === 'admin') ? 'true' : 'false'; ?>;
    </script>
    <script src="vehiculos.js"></script>
</body>
</html>
