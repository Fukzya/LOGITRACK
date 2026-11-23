<?php require '../plantilla.php'; inicio_pagina('Vehículos'); ?>

    <form id="form-vehiculo">
        <h3 id="titulo-form">Nuevo vehículo</h3>
        <input type="text" id="input-patente" placeholder="Patente (AB123CD)" required maxlength="7"
               pattern="[A-Za-z]{2}[0-9]{3}[A-Za-z]{2}|[A-Za-z]{3}[0-9]{3}" title="AB123CD o ABC123">
        <input type="text" id="input-marca" placeholder="Marca" required maxlength="60">
        <input type="text" id="input-modelo" placeholder="Modelo" required maxlength="60">
        <input type="number" id="input-anio" placeholder="Año (opcional)" min="1950" max="2100">
        <select id="input-tipo" required><option value="">Elegí el tipo</option></select>
        <button type="submit">Agregar</button>
        <button type="button" class="btn-cancelar" hidden>Cancelar</button>
    </form>

    <hr>
    <input type="text" id="filtro-patente" placeholder="Buscar por patente">
    <input type="text" id="filtro-marca" placeholder="Buscar por marca">
    <select id="filtro-tipo"><option value="">Todos los tipos</option></select>
    <button id="btn-filtrar">Filtrar</button>
    <button id="btn-limpiar">Limpiar</button>
    <?php if ($esAdmin): ?>
    <label><input type="checkbox" id="filtro-mostrar-inactivos"> Mostrar vehículos inactivos</label>
    <?php endif; ?>
    <hr>

    <div id="paginacion"></div>
    <table border="1">
        <thead>
            <tr>
                <th id="th-patente">Patente</th>
                <th id="th-marca">Marca</th>
                <th id="th-modelo">Modelo</th>
                <th id="th-anio">Año</th>
                <th id="th-tipo">Tipo</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="tabla-vehiculos"></tbody>
    </table>

<?php fin_pagina('vehiculos.js'); ?>
