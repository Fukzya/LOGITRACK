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
    <title>LogiTrack - Envíos</title>
</head>
<body>
    <h1>LogiTrack</h1>
    <a href="../../index.php">← Volver al menú</a>

    <h2>Envíos</h2>

    <input type="date" id="input-fecha">
    <input type="number" id="input-peso" placeholder="Peso kg">
    <input type="text" id="input-tipo" placeholder="Tipo contenido (FRG/STD/PEL)">
    <input type="number" id="input-remitente" placeholder="DNI remitente">
    <input type="number" id="input-destinatario" placeholder="DNI destinatario">
    <input type="text" id="input-suc-orig" placeholder="Sucursal origen">
    <input type="text" id="input-suc-dest" placeholder="Sucursal destino">
    <input type="number" id="input-direccion" placeholder="ID dirección entrega">
    <button onclick="crearEnvio()">Agregar</button>

    <hr>
    <label>Fecha desde: <input type="date" id="filtro-fecha-desde"></label>
    <label>Fecha hasta: <input type="date" id="filtro-fecha-hasta"></label>
    <label>Sucursal origen:
        <select id="filtro-sucursal-orig">
            <option value="">Todas las sucursales</option>
        </select>
    </label>
    <label>Tipo:
        <select id="filtro-tipo">
            <option value="">Todos los tipos</option>
            <option value="STD">Estándar</option>
            <option value="FRG">Frágil</option>
            <option value="PEL">Peligroso</option>
        </select>
    </label>
    <button id="btn-filtrar">Filtrar</button>
    <button id="btn-limpiar">Limpiar</button>
    <?php if ($_SESSION['rol'] === 'admin'): ?>
    <label>
        <input type="checkbox" id="filtro-mostrar-inactivos">
        Mostrar envíos inactivos
    </label>
    <?php endif; ?>
    <hr>

    <div id="paginacion"></div>

    <table border="1">
        <thead>
            <tr>
                <th id="th-nro-envio" style="cursor:pointer">Nro</th>
                <th id="th-fecha-envio" style="cursor:pointer">Fecha</th>
                <th id="th-peso-kg" style="cursor:pointer">Peso kg</th>
                <th>Tipo</th>
                <th>Remitente</th>
                <th>Destinatario</th>
                <th>Suc. Origen</th>
                <th>Suc. Destino</th>
                <th>Dirección</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="tabla-envios">
        </tbody>
    </table>

    <br>
    <a href="../../login.html" onclick="cerrarSesion(event)">Cerrar sesión</a>

    <script src="envios.js"></script>
</body>
</html>