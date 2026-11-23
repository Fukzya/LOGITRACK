<?php require '../plantilla.php'; inicio_pagina('Localidades'); ?>

    <form id="form-localidad">
        <h3 id="titulo-form">Nueva localidad</h3>
        <input type="text" id="input-nombre" placeholder="Nombre de la localidad" required maxlength="100">
        <select id="input-provincia" required><option value="">Elegí una provincia</option></select>
        <button type="submit">Agregar</button>
        <button type="button" class="btn-cancelar" hidden>Cancelar</button>
    </form>
    <hr>
    <label>Provincia:
        <select id="filtro-provincia"><option value="">Todas</option></select>
    </label>
    <button id="btn-filtrar">Filtrar</button>
    <button id="btn-limpiar">Limpiar</button>
    <hr>

    <table border="1">
        <thead>
            <tr>
                <th id="th-cod">Código</th>
                <th id="th-nombre">Nombre</th>
                <th id="th-provincia">Provincia</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="tabla-localidades"></tbody>
    </table>

<?php fin_pagina('localidades.js'); ?>
