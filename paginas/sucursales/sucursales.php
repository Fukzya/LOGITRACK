<?php require '../plantilla.php'; inicio_pagina('Sucursales'); ?>

    <form id="form-sucursal">
        <h3 id="titulo-form">Nueva sucursal</h3>
        <input type="text" id="input-cod" placeholder="Código (3 dígitos)" required pattern="[0-9]{3}"
               maxlength="3" title="3 dígitos, ej. 007">
        <input type="text" id="input-nombre" placeholder="Nombre" required maxlength="120">
        <select id="input-localidad" required><option value="">Elegí una localidad</option></select>
        <input type="text" id="input-direccion" placeholder="Dirección (opcional)" maxlength="200">
        <button type="submit">Agregar</button>
        <button type="button" class="btn-cancelar" hidden>Cancelar</button>
    </form>

    <hr>
    <input type="text" id="filtro-nombre" placeholder="Buscar por nombre">
    <button id="btn-filtrar">Filtrar</button>
    <button id="btn-limpiar">Limpiar</button>
    <?php if ($esAdmin): ?>
    <label><input type="checkbox" id="filtro-mostrar-inactivos"> Mostrar sucursales inactivas</label>
    <?php endif; ?>
    <hr>

    <div id="paginacion"></div>
    <table border="1">
        <thead>
            <tr>
                <th id="th-cod">Código</th>
                <th id="th-nombre">Nombre</th>
                <th id="th-localidad">Localidad</th>
                <th>Dirección</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="tabla-sucursales"></tbody>
    </table>

<?php fin_pagina('sucursales.js'); ?>
