<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.html");
    exit;
}
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>LogiTrack</title>
</head>
<body>
    <h1>LogiTrack</h1>
    <h2>Menú principal</h2>
    <ul>
        <li><a href="paginas/provincias/provincias.php">Provincias</a></li>
        <li><a href="paginas/localidades/localidades.php">Localidades</a></li>
        <li><a href="paginas/clientes/clientes.php">Clientes</a></li>
        <li><a href="paginas/direcciones/direcciones.php">Direcciones</a></li>
        <li><a href="paginas/sucursales/sucursales.php">Sucursales</a></li>
        <li><a href="paginas/tipo_contenido/tipo_contenido.php">Tipos de Contenido</a></li>
        <li><a href="paginas/tipo_vehiculo/tipo_vehiculo.php">Tipos de Vehículo</a></li>
        <li><a href="paginas/vehiculos/vehiculos.php">Vehículos</a></li>
        <li><a href="paginas/choferes/choferes.php">Choferes</a></li>
        <li><a href="paginas/envios/envios.php">Envíos</a></li>
        <li><a href="paginas/viajes/viajes.php">Viajes</a></li>
        <li><a href="paginas/viaje_envio/viaje_envio.php">Viaje-Envío</a></li>
        <li><a href="paginas/historial_estado/historial_estado.php">Historial de Estados</a></li>
        <li><a href="paginas/incidentes/incidentes.php">Incidentes</a></li>
        <li><a href="paginas/usuarios/usuarios.php">Gestión de usuarios</a></li>
    </ul>
    <br>
    <a href="login.html" onclick="cerrarSesion(event)">Cerrar sesión</a>

    <script src="index.js"></script>
</body>
</html>