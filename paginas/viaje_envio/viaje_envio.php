<?php require '../plantilla.php'; inicio_pagina('Envíos por viaje'); ?>

    <form id="form-asignacion">
        <h3 id="titulo-form">Asignar un envío a un viaje</h3>
        <input type="number" id="input-viaje" placeholder="ID del viaje" required min="1">
        <input type="number" id="input-envio" placeholder="Nro de envío" required min="1">
        <button type="submit">Asignar</button>
    </form>

    <hr>
    <input type="number" id="filtro-viaje" placeholder="Buscar por ID de viaje">
    <input type="number" id="filtro-envio" placeholder="Buscar por nro de envío">
    <button id="btn-filtrar">Filtrar</button>
    <button id="btn-limpiar">Limpiar</button>
    <hr>

    <div id="paginacion"></div>
    <table border="1">
        <thead>
            <tr>
                <th id="th-viaje">Viaje</th>
                <th>Salida</th>
                <th>Llegada</th>
                <th id="th-envio">Envío</th>
                <th>Fecha del envío</th>
                <th>Origen → Destino</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="tabla-asignaciones"></tbody>
    </table>

<?php fin_pagina('viaje_envio.js'); ?>
