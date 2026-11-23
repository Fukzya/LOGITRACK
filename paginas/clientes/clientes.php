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
    <title>LogiTrack - Clientes</title>
</head>
<body>
    <h1>LogiTrack</h1>
    <a href="../../index.php">← Volver al menú</a>

    <h2>Clientes</h2>

    <input type="number" id="input-dni" placeholder="DNI">
    <input type="text" id="input-nombre" placeholder="Nombre">
    <input type="text" id="input-apellido" placeholder="Apellido">
    <input type="text" id="input-mail" placeholder="Mail">
    <input type="text" id="input-telefono" placeholder="Teléfono">
    <input type="number" id="input-localidad" placeholder="Código de localidad">
    <button onclick="crearCliente()">Agregar</button>

    <hr>
    <input type="number" id="filtro-dni" placeholder="Buscar por DNI">
    <input type="text" id="filtro-nombre" placeholder="Buscar por nombre">
    <input type="text" id="filtro-apellido" placeholder="Buscar por apellido">
    <button id="btn-filtrar">Filtrar</button>
    <button id="btn-limpiar">Limpiar</button>
    <?php if ($_SESSION['rol'] === 'admin'): ?>
    <label>
        <input type="checkbox" id="filtro-mostrar-inactivos">
        Mostrar clientes inactivos
    </label>
    <?php endif; ?>
    <hr>

    <div id="paginacion"></div>

    <table border="1">
        <thead>
            <tr>
                <th id="th-cliente-dni" style="cursor:pointer">DNI</th>
                <th id="th-nombre" style="cursor:pointer">Nombre</th>
                <th id="th-apellido" style="cursor:pointer">Apellido</th>
                <th>Mail</th>
                <th>Teléfono</th>
                <th>Localidad</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="tabla-clientes">
        </tbody>
    </table>

    <br>
    <a href="../../login.html" onclick="cerrarSesion(event)">Cerrar sesión</a>

    <script>const esAdmin = <?php echo ($_SESSION['rol'] === 'admin') ? 'true' : 'false'; ?>;</script>
    <script src="clientes.js"></script>
</body>
</html>
