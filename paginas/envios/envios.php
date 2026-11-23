<?php require '../plantilla.php'; inicio_pagina('Envíos'); ?>

    <!-- El alta se hace con el procedimiento almacenado procedimiento_crear_envio, en una transacción. -->
    <form id="form-envio">
        <h3 id="titulo-form">Nuevo envío</h3>
        <label>Fecha <input type="date" id="input-fecha" required></label>
        <input type="number" id="input-peso" placeholder="Peso (kg)" required min="0.01" max="999999.99" step="0.01">
        <select id="input-tipo" required><option value="">Tipo de contenido</option></select>
        <input type="number" id="input-remitente" placeholder="DNI remitente" required min="1" max="99999999">
        <input type="number" id="input-destinatario" placeholder="DNI destinatario" required min="1" max="99999999">
        <select id="input-suc-orig" required><option value="">Sucursal de origen</option></select>
        <select id="input-suc-dest" required><option value="">Sucursal de destino</option></select>
        <input type="number" id="input-direccion" placeholder="ID dirección de entrega" required min="1">
        <button type="submit">Agregar</button>
        <button type="button" class="btn-cancelar" hidden>Cancelar</button>
    </form>

    <hr>
    <label>Fecha desde: <input type="date" id="filtro-fecha-desde"></label>
    <label>Fecha hasta: <input type="date" id="filtro-fecha-hasta"></label>
    <select id="filtro-sucursal-orig"><option value="">Todas las sucursales de origen</option></select>
    <select id="filtro-tipo"><option value="">Todos los tipos</option></select>
    <button id="btn-filtrar">Filtrar</button>
    <button id="btn-limpiar">Limpiar</button>
    <?php if ($esAdmin): ?>
    <label><input type="checkbox" id="filtro-mostrar-inactivos"> Mostrar envíos inactivos</label>
    <?php endif; ?>
    <hr>

    <div id="paginacion"></div>
    <table border="1">
        <thead>
            <tr>
                <th id="th-nro">Nro</th>
                <th id="th-fecha">Fecha</th>
                <th id="th-peso">Peso (kg)</th>
                <th>Tipo</th>
                <th>Remitente</th>
                <th>Destinatario</th>
                <th>Origen</th>
                <th>Destino</th>
                <th>Dirección de entrega</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="tabla-envios"></tbody>
    </table>

<?php fin_pagina('envios.js'); ?>
