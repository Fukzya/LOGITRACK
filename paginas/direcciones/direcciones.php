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
    <title>LogiTrack - Direcciones</title>
</head>
<body>
    <h1>LogiTrack</h1>
    <a href="../../index.php">← Volver al menú</a>

    <h2>Direcciones</h2>

    <input type="text" id="input-calle" placeholder="Calle y número">
    <input type="number" id="input-localidad" placeholder="Código de localidad">
    <input type="number" id="input-dni" placeholder="DNI del cliente">
    <button onclick="crearDireccion()">Agregar</button>

    <hr>
    <input type="text" id="filtro-calle" placeholder="Buscar por calle">
    <input type="number" id="filtro-cliente-dni" placeholder="Buscar por DNI cliente">
    <input type="number" id="filtro-localidad-cod" placeholder="Buscar por localidad">
    <button id="btn-filtrar">Filtrar</button>
    <button id="btn-limpiar">Limpiar</button>
    <?php if ($_SESSION['rol'] === 'admin'): ?>
    <label>
        <input type="checkbox" id="filtro-mostrar-inactivos">
        Mostrar direcciones inactivas
    </label>
    <?php endif; ?>
    <hr>

    <div id="paginacion"></div>

    <table border="1">
        <thead>
            <tr>
                <th id="th-direccion-id" style="cursor:pointer">ID</th>
                <th id="th-calle" style="cursor:pointer">Calle y número</th>
                <th id="th-localidad-cod" style="cursor:pointer">Localidad</th>
                <th id="th-cliente-dni" style="cursor:pointer">Cliente DNI</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="tabla-direcciones">
        </tbody>
    </table>
    <br>
    <a href="../../login.html" onclick="cerrarSesion(event)">Cerrar sesión</a>

    <script>const esAdmin = <?php echo ($_SESSION['rol'] === 'admin') ? 'true' : 'false'; ?>;</script>
    <script src="direcciones.js"></script>
</body>
</html>