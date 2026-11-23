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
    <title>LogiTrack - Choferes</title>
</head>
<body>
    <h1>LogiTrack</h1>
    <a href="../../index.php">← Volver al menú</a>

    <h2>Choferes</h2>

    <input type="text" id="input-nombre" placeholder="Nombre">
    <input type="text" id="input-apellido" placeholder="Apellido">
    <input type="text" id="input-licencia" placeholder="Tipo de licencia">
    <button onclick="crearChofer()">Agregar</button>

    <hr>
    <input type="text" id="filtro-nombre" placeholder="Buscar por nombre">
    <input type="text" id="filtro-apellido" placeholder="Buscar por apellido">
    <select id="filtro-tipo-licencia">
        <option value="">Todos</option>
        <option value="B">B</option>
        <option value="C">C</option>
        <option value="D">D</option>
        <option value="E">E</option>
    </select>
    <button id="btn-filtrar">Filtrar</button>
    <button id="btn-limpiar">Limpiar</button>
    <?php if ($_SESSION['rol'] === 'admin'): ?>
    <label>
        <input type="checkbox" id="filtro-mostrar-inactivos">
        Mostrar choferes inactivos
    </label>
    <?php endif; ?>
    <hr>

    <div id="paginacion"></div>

    <table border="1">
        <thead>
            <tr>
                <th id="th-chofer-legajo" style="cursor:pointer">Legajo</th>
                <th id="th-nombre" style="cursor:pointer">Nombre</th>
                <th id="th-apellido" style="cursor:pointer">Apellido</th>
                <th id="th-tipo-licencia" style="cursor:pointer">Licencia</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="tabla-choferes">
        </tbody>
    </table>

    <br>
    <a href="../../login.html" onclick="cerrarSesion(event)">Cerrar sesión</a>

    <script>
        const esAdmin = <?php echo ($_SESSION['rol'] === 'admin') ? 'true' : 'false'; ?>;
    </script>
    <script src="choferes.js"></script>
</body>
</html>
