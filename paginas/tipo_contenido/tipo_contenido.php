<?php require '../plantilla.php'; inicio_pagina('Tipos de contenido'); ?>

    <form id="form-tipo">
        <h3 id="titulo-form">Nuevo tipo de contenido</h3>
        <input type="text" id="input-cod" placeholder="Código (3 letras)" required pattern="[A-Za-z]{3}"
               maxlength="3" title="3 letras, ej. FRG">
        <input type="text" id="input-nombre" placeholder="Nombre" required maxlength="50">
        <label><input type="checkbox" id="input-seguro"> Requiere seguro</label>
        <button type="submit">Agregar</button>
        <button type="button" class="btn-cancelar" hidden>Cancelar</button>
    </form>
    <hr>

    <table border="1">
        <thead>
            <tr><th id="th-cod">Código</th><th id="th-nombre">Nombre</th><th>Requiere seguro</th><th>Acciones</th></tr>
        </thead>
        <tbody id="tabla-tipos"></tbody>
    </table>

<?php fin_pagina('tipo_contenido.js'); ?>
