<?php require '../plantilla.php'; inicio_pagina('Tipos de vehículo'); ?>

    <form id="form-tipo">
        <h3 id="titulo-form">Nuevo tipo de vehículo</h3>
        <input type="text" id="input-cod" placeholder="Código (4 letras)" required pattern="[A-Za-z]{4}"
               maxlength="4" title="4 letras, ej. CAMN">
        <input type="text" id="input-nombre" placeholder="Nombre" required maxlength="60">
        <input type="number" id="input-capacidad" placeholder="Capacidad (kg)" required min="1" step="0.01">
        <label><input type="checkbox" id="input-licencia"> Requiere licencia especial</label>
        <button type="submit">Agregar</button>
        <button type="button" class="btn-cancelar" hidden>Cancelar</button>
    </form>
    <hr>

    <table border="1">
        <thead>
            <tr>
                <th id="th-cod">Código</th>
                <th id="th-nombre">Nombre</th>
                <th id="th-capacidad">Capacidad (kg)</th>
                <th>Licencia especial</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="tabla-tipos"></tbody>
    </table>

<?php fin_pagina('tipo_vehiculo.js'); ?>
