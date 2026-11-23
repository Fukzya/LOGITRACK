<?php require '../plantilla.php'; inicio_pagina('Clientes'); ?>

    <form id="form-cliente">
        <h3 id="titulo-form">Nuevo cliente</h3>
        <input type="number" id="input-dni" placeholder="DNI" required min="1000000" max="99999999">
        <input type="text" id="input-nombre" placeholder="Nombre" required maxlength="80">
        <input type="text" id="input-apellido" placeholder="Apellido" required maxlength="80">
        <input type="email" id="input-mail" placeholder="Mail (opcional)" maxlength="150">
        <input type="tel" id="input-telefono" placeholder="Teléfono (opcional)" maxlength="30"
               pattern="\+?[0-9 \(\)\-]{6,30}" title="Números, espacios, guiones, paréntesis y un + inicial">
        <select id="input-localidad" required><option value="">Elegí una localidad</option></select>
        <button type="submit">Agregar</button>
        <button type="button" class="btn-cancelar" hidden>Cancelar</button>
    </form>

    <hr>
    <input type="number" id="filtro-dni" placeholder="Buscar por DNI">
    <input type="text" id="filtro-nombre" placeholder="Buscar por nombre">
    <input type="text" id="filtro-apellido" placeholder="Buscar por apellido">
    <button id="btn-filtrar">Filtrar</button>
    <button id="btn-limpiar">Limpiar</button>
    <?php if ($esAdmin): ?>
    <label><input type="checkbox" id="filtro-mostrar-inactivos"> Mostrar clientes inactivos</label>
    <?php endif; ?>
    <hr>

    <div id="paginacion"></div>
    <table border="1">
        <thead>
            <tr>
                <th id="th-dni">DNI</th>
                <th id="th-nombre">Nombre</th>
                <th id="th-apellido">Apellido</th>
                <th>Mail</th>
                <th>Teléfono</th>
                <th id="th-localidad">Localidad</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="tabla-clientes"></tbody>
    </table>

<?php fin_pagina('clientes.js'); ?>
