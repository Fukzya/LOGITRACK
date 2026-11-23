<?php require '../plantilla.php'; inicio_pagina('Historial de estados'); ?>

    <form id="form-historial">
        <h3 id="titulo-form">Registrar un estado</h3>
        <input type="number" id="input-envio" placeholder="Nro de envío" required min="1">
        <select id="input-sucursal" required><option value="">Sucursal</option></select>
        <select id="input-estado" required><option value="">Estado</option></select>
        <label>Fecha y hora (vacío = ahora) <input type="datetime-local" id="input-fecha"></label>
        <input type="text" id="input-observacion" placeholder="Observación (opcional)" maxlength="1000">
        <button type="submit">Agregar</button>
        <button type="button" class="btn-cancelar" hidden>Cancelar</button>
    </form>

    <hr>
    <input type="number" id="filtro-envio" placeholder="Nro de envío">
    <select id="filtro-estado"><option value="">Todos los estados</option></select>
    <select id="filtro-sucursal"><option value="">Todas las sucursales</option></select>
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
                <th id="th-envio">Envío</th>
                <th>Sucursal</th>
                <th>Estado</th>
                <th id="th-fecha">Fecha y hora</th>
                <th>Observación</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="tabla-historial"></tbody>
    </table>

<?php fin_pagina('historial_estado.js'); ?>
