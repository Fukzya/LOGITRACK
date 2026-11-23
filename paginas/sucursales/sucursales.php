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
    <title>LogiTrack - Sucursales</title>
</head>
<body>
    <h1>LogiTrack</h1>
    <a href="../../index.php">← Volver al menú</a>

    <h2>Sucursales</h2>

    <input type="text" id="input-cod" placeholder="Código">
    <input type="text" id="input-nombre" placeholder="Nombre">
    <input type="number" id="input-localidad" placeholder="Código de localidad">
    <input type="text" id="input-direccion" placeholder="Dirección">
    <button onclick="crearSucursal()">Agregar</button>

    <hr>
    <input type="text" id="filtro-nombre" placeholder="Buscar por nombre">
    <button id="btn-filtrar">Filtrar</button>
    <button id="btn-limpiar">Limpiar</button>
    <?php if ($_SESSION['rol'] === 'admin'): ?>
    <label>
        <input type="checkbox" id="filtro-mostrar-inactivos">
        Mostrar sucursales inactivas
    </label>
    <?php endif; ?>
    <hr>

    <div id="paginacion"></div>

    <table border="1">
        <thead>
            <tr>
                <th id="th-sucursal-cod" style="cursor:pointer">Código</th>
                <th id="th-sucursal-nombre" style="cursor:pointer">Nombre</th>
                <th id="th-localidad" style="cursor:pointer">Localidad</th>
                <th>Dirección</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="tabla-sucursales">
        </tbody>
    </table>

    <br>
    <a href="../../login.html" onclick="cerrarSesion(event)">Cerrar sesión</a>

    <script>
        const esAdmin = <?php echo ($_SESSION['rol'] === 'admin') ? 'true' : 'false'; ?>;
    </script>
    <script src="sucursales.js"></script>
</body>
</html>
