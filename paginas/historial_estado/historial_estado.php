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
    <title>LogiTrack - Historial de Estados</title>
</head>
<body>
    <h1>LogiTrack</h1>
    <a href="../../index.php">← Volver al menú</a>

    <h2>Historial de Estados</h2>

    <input type="number" id="input-envio" placeholder="Nro envío">
    <input type="text" id="input-sucursal" placeholder="Código sucursal">
    <select id="input-estado">
        <option value="RECIBIDO">RECIBIDO</option>
        <option value="EN_TRANSITO">EN_TRANSITO</option>
        <option value="EN_SUCURSAL">EN_SUCURSAL</option>
        <option value="DEMORADO">DEMORADO</option>
        <option value="ENTREGADO">ENTREGADO</option>
        <option value="DEVUELTO">DEVUELTO</option>
    </select>
    <input type="datetime-local" id="input-fecha">
    <input type="text" id="input-observacion" placeholder="Observación (opcional)">
    <button onclick="crearHistorial()">Agregar</button>

    <hr>
    <input type="number" id="filtro-nro-envio" placeholder="Nro de envío">
    <select id="filtro-estado">
        <option value="">Todos los estados</option>
        <option value="En sucursal origen">En sucursal origen</option>
        <option value="En tránsito">En tránsito</option>
        <option value="Redirigido">Redirigido</option>
        <option value="Entregado">Entregado</option>
        <option value="EN_TRANSITO">EN_TRANSITO</option>
        <option value="EN_SUCURSAL">EN_SUCURSAL</option>
    </select>
    <label>Fecha desde: <input type="date" id="filtro-fecha-desde"></label>
    <label>Fecha hasta: <input type="date" id="filtro-fecha-hasta"></label>
    <label>Sucursal:
        <select id="filtro-sucursal">
            <option value="">Todas las sucursales</option>
        </select>
    </label>
    <button id="btn-filtrar">Filtrar</button>
    <button id="btn-limpiar">Limpiar</button>
    <hr>

    <div id="paginacion"></div>

    <table border="1">
        <thead>
            <tr>
                <th id="th-id-historial" style="cursor:pointer">ID</th>
                <th id="th-nro-envio" style="cursor:pointer">Nro Envío</th>
                <th>Sucursal</th>
                <th>Estado</th>
                <th id="th-fecha-hora" style="cursor:pointer">Fecha/Hora</th>
                <th>Observación</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="tabla-historial">
        </tbody>
    </table>

    <br>
    <a href="../../login.html" onclick="cerrarSesion(event)">Cerrar sesión</a>

    <script src="historial_estado.js"></script>
</body>
</html>