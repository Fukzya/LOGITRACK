<?php require '../plantilla.php'; inicio_pagina('Direcciones'); ?>

    <form id="form-direccion">
        <h3 id="titulo-form">Nueva dirección</h3>
        <input type="text" id="input-calle" placeholder="Calle y número" required maxlength="200">
        <select id="input-localidad" required><option value="">Elegí una localidad</option></select>
        <input type="number" id="input-dni" placeholder="DNI del cliente" required min="1" max="99999999">
        <button type="submit">Agregar</button>
        <button type="button" class="btn-cancelar" hidden>Cancelar</button>
    </form>

    <hr>
    <input type="text" id="filtro-calle" placeholder="Buscar por calle">
    <input type="number" id="filtro-dni" placeholder="Buscar por DNI del cliente">
    <select id="filtro-localidad"><option value="">Todas las localidades</option></select>
    <button id="btn-filtrar">Filtrar</button>
    <button id="btn-limpiar">Limpiar</button>
    <?php if ($esAdmin): ?>
    <label><input type="checkbox" id="filtro-mostrar-inactivos"> Mostrar direcciones inactivas</label>
    <?php endif; ?>
    <hr>

    <div id="paginacion"></div>
    <table border="1">
        <thead>
            <tr>
                <th id="th-id">ID</th>
                <th id="th-calle">Calle y número</th>
                <th id="th-localidad">Localidad</th>
                <th id="th-dni">Cliente</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="tabla-direcciones"></tbody>
    </table>

<?php fin_pagina('direcciones.js'); ?>
