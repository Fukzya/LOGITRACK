<?php require '../plantilla.php'; inicio_pagina('Choferes'); ?>

    <form id="form-chofer">
        <h3 id="titulo-form">Nuevo chofer</h3>
        <input type="text" id="input-nombre" placeholder="Nombre" required maxlength="80">
        <input type="text" id="input-apellido" placeholder="Apellido" required maxlength="80">
        <select id="input-licencia" required><option value="">Elegí la licencia</option></select>
        <button type="submit">Agregar</button>
        <button type="button" class="btn-cancelar" hidden>Cancelar</button>
    </form>

    <hr>
    <input type="text" id="filtro-nombre" placeholder="Buscar por nombre">
    <input type="text" id="filtro-apellido" placeholder="Buscar por apellido">
    <select id="filtro-licencia"><option value="">Todas las licencias</option></select>
    <button id="btn-filtrar">Filtrar</button>
    <button id="btn-limpiar">Limpiar</button>
    <?php if ($esAdmin): ?>
    <label><input type="checkbox" id="filtro-mostrar-inactivos"> Mostrar choferes inactivos</label>
    <?php endif; ?>
    <hr>

    <div id="paginacion"></div>
    <table border="1">
        <thead>
            <tr>
                <th id="th-legajo">Legajo</th>
                <th id="th-nombre">Nombre</th>
                <th id="th-apellido">Apellido</th>
                <th id="th-licencia">Licencia</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="tabla-choferes"></tbody>
    </table>

<?php fin_pagina('choferes.js'); ?>
