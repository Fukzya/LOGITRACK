<?php require '../plantilla.php'; inicio_pagina('Incidentes'); ?>

    <form id="form-incidente">
        <h3 id="titulo-form">Registrar un incidente</h3>
        <input type="number" id="input-viaje" placeholder="ID del viaje" required min="1">
        <label>Fecha y hora <input type="datetime-local" id="input-fecha" required></label>
        <select id="input-tipo" required><option value="">Tipo de incidente</option></select>
        <input type="text" id="input-descripcion" placeholder="Descripción (opcional)" maxlength="1000">
        <button type="submit">Agregar</button>
        <button type="button" class="btn-cancelar" hidden>Cancelar</button>
    </form>

    <hr>
    <input type="number" id="filtro-viaje" placeholder="ID del viaje">
    <select id="filtro-tipo"><option value="">Todos los tipos</option></select>
    <label>Desde: <input type="date" id="filtro-fecha-desde"></label>
    <label>Hasta: <input type="date" id="filtro-fecha-hasta"></label>
    <button id="btn-filtrar">Filtrar</button>
    <button id="btn-limpiar">Limpiar</button>
    <hr>

    <div id="paginacion"></div>
    <table border="1">
        <thead>
            <tr>
                <th id="th-id">ID</th>
                <th id="th-viaje">Viaje</th>
                <th id="th-fecha">Fecha y hora</th>
                <th id="th-tipo">Tipo</th>
                <th>Descripción</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="tabla-incidentes"></tbody>
    </table>

<?php fin_pagina('incidentes.js'); ?>
