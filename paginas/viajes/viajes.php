<?php require '../plantilla.php'; inicio_pagina('Viajes'); ?>

    <form id="form-viaje">
        <h3 id="titulo-form">Nuevo viaje</h3>
        <label>Salida <input type="datetime-local" id="input-salida" required></label>
        <label>Llegada (opcional) <input type="datetime-local" id="input-llegada"></label>
        <select id="input-vehiculo" required><option value="">Elegí el vehículo</option></select>
        <select id="input-chofer" required><option value="">Elegí el chofer</option></select>
        <button type="submit">Agregar</button>
        <button type="button" class="btn-cancelar" hidden>Cancelar</button>
    </form>

    <hr>
    <label>Salida desde: <input type="date" id="filtro-fecha-desde"></label>
    <label>Salida hasta: <input type="date" id="filtro-fecha-hasta"></label>
    <select id="filtro-chofer"><option value="">Todos los choferes</option></select>
    <label><input type="checkbox" id="filtro-solo-activos"> Solo viajes en curso</label>
    <button id="btn-filtrar">Filtrar</button>
    <button id="btn-limpiar">Limpiar</button>
    <hr>

    <div id="paginacion"></div>
    <table border="1">
        <thead>
            <tr>
                <th id="th-id">ID</th>
                <th id="th-salida">Salida</th>
                <th id="th-llegada">Llegada</th>
                <th>Vehículo</th>
                <th>Chofer</th>
                <th>Envíos</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="tabla-viajes"></tbody>
    </table>

<?php fin_pagina('viajes.js'); ?>
