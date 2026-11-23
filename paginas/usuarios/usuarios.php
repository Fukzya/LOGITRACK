<?php require '../plantilla.php'; inicio_pagina('Gestión de usuarios', true); ?>

    <form id="form-usuario">
        <h3 id="titulo-form">Nuevo usuario</h3>
        <input type="text" id="input-username" placeholder="Usuario" required minlength="3" maxlength="50"
               pattern="[a-zA-Z0-9_.]{3,50}" title="Letras, números, punto o guion bajo">
        <input type="password" id="input-password" placeholder="Contraseña (mín. 8)" required minlength="8" maxlength="72"
               autocomplete="new-password">
        <select id="input-rol" required>
            <option value="operador">operador</option>
            <option value="admin">admin</option>
        </select>
        <button type="submit">Agregar</button>
    </form>

    <!-- Cambio de contraseña: se completa al tocar "Cambiar contraseña" en una fila. -->
    <form id="form-password" hidden>
        <h3 id="titulo-password"></h3>
        <input type="password" id="input-password-nueva" placeholder="Contraseña nueva (mín. 8)" required
               minlength="8" maxlength="72" autocomplete="new-password">
        <button type="submit">Guardar contraseña</button>
        <button type="button" id="btn-cancelar-password">Cancelar</button>
    </form>
    <hr>

    <table border="1">
        <thead>
            <tr>
                <th id="th-id">ID</th>
                <th id="th-username">Usuario</th>
                <th id="th-rol">Rol</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="tabla-usuarios"></tbody>
    </table>

<?php fin_pagina('usuarios.js'); ?>
