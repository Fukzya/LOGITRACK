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
    <title>LogiTrack - Gestión de usuarios</title>
</head>
<body>
    <h1>LogiTrack</h1>
    <a href="../../index.php">← Volver al menú</a>

    <h2>Gestión de usuarios</h2>

    <input type="text" id="input-username" placeholder="Usuario">
    <input type="password" id="input-password" placeholder="Contraseña">
    <select id="input-rol">
        <option value="operador">operador</option>
        <option value="admin">admin</option>
    </select>
    <button onclick="crearUsuario()">Agregar</button>

    <div id="mensaje-error"></div>

    <table border="1">
        <thead>
            <tr>
                <th>ID</th>
                <th>Usuario</th>
                <th>Rol</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="tabla-usuarios">
        </tbody>
    </table>

    <br>
    <a href="../../login.html" onclick="cerrarSesion(event)">Cerrar sesión</a>

    <script src="usuarios.js"></script>
</body>
</html>
