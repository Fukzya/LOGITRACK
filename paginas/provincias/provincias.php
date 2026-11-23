<?php require '../plantilla.php'; inicio_pagina('Provincias'); ?>

    <form id="form-provincia">
        <h3 id="titulo-form">Nueva provincia</h3>
        <input type="text" id="input-nombre" placeholder="Nombre de la provincia" required maxlength="100">
        <button type="submit">Agregar</button>
        <button type="button" class="btn-cancelar" hidden>Cancelar</button>
    </form>
    <hr>

    <table border="1">
        <thead>
            <tr><th id="th-cod">Código</th><th id="th-nombre">Nombre</th><th>Acciones</th></tr>
        </thead>
        <tbody id="tabla-provincias"></tbody>
    </table>

<?php fin_pagina('provincias.js'); ?>
