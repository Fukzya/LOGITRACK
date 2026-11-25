<?php
/*
 * ============================================================================================
 * QUÉ ES:  el diseño (HTML) de la pantalla "Sucursales": las sucursales (código de 3 dígitos, nombre, localidad y dirección).
 *          Lo que hace la pantalla (cargar, guardar, botones) está en sucursales.js.
 * PARA CAMBIAR:
 *   - Títulos, textos, etiquetas y ayudas -> directamente acá, en el HTML.
 *   - Un campo nuevo del formulario       -> el <input> acá + su id en "campos" del .js
 *                                            (+ la API y el modelo, para que se guarde).
 *   - Una columna nueva de la tabla       -> el <th> acá + la función en "columnas" del .js,
 *                                            en la misma posición.
 *   - Menú, encabezado y pie (iguales en todas las pantallas) -> paginas/plantilla.php
 * Guía con ejemplos: docs/como_cambiar.md (buscá "CAMBIAR:" con Ctrl+Shift+F).
 * ============================================================================================
 */
require '../plantilla.php'; inicio_pagina('Sucursales'); ?>

    <!-- Formulario de Nueva Sucursal / Edición -->
    <div class="bg-surface-container-lowest rounded-xl p-space-lg shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] flex flex-col gap-space-lg" id="nueva-sucursal-card">
        <div class="flex items-center justify-between">
            <h2 class="text-headline-md text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">store</span>
                <span id="titulo-form">Nueva sucursal</span>
            </h2>
            <span class="text-xs text-on-surface-variant">Centros operativos y puntos de distribución de la red</span>
        </div>
        <form id="form-sucursal" class="flex flex-col gap-space-md">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-space-md">
                <div class="flex flex-col gap-space-xs">
                    <label class="text-label-sm text-on-surface-variant" for="input-cod">Código (3 dígitos)</label>
                    <input class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="input-cod" placeholder="Ej. 007" required pattern="[0-9]{3}" maxlength="3" title="3 dígitos, ej. 007" type="text"/>
                </div>
                <div class="flex flex-col gap-space-xs">
                    <label class="text-label-sm text-on-surface-variant" for="input-nombre">Nombre</label>
                    <input class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="input-nombre" placeholder="Nombre de la sucursal" required maxlength="120" type="text"/>
                </div>
                <div class="flex flex-col gap-space-xs">
                    <label class="text-label-sm text-on-surface-variant" for="input-localidad">Localidad</label>
                    <select class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="input-localidad" required>
                        <option value="">Elegí una localidad</option>
                    </select>
                </div>
                <div class="flex flex-col gap-space-xs">
                    <label class="text-label-sm text-on-surface-variant" for="input-direccion">Dirección (opcional)</label>
                    <div class="flex gap-2">
                        <input class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="input-direccion" placeholder="Dirección" maxlength="200" type="text"/>
                        <button class="bg-primary hover:bg-primary-container text-on-primary px-4 py-2 rounded-lg text-label-md transition-colors shadow-sm whitespace-nowrap shrink-0" type="submit">Agregar</button>
                        <button class="btn-cancelar bg-surface-container hover:bg-surface-container-high text-on-surface px-4 py-2 rounded-lg text-label-md transition-colors whitespace-nowrap shrink-0" type="button" hidden>Cancelar</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Tarjeta de Filtros -->
    <div class="bg-surface-container-lowest rounded-xl p-space-lg shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] flex flex-col gap-space-md">
        <div class="flex items-center justify-between">
            <h3 class="text-headline-sm text-on-surface">Filtros y Búsqueda</h3>
            <span class="material-symbols-outlined text-outline">tune</span>
        </div>
        <div class="flex flex-wrap items-end gap-space-md">
            <div class="flex flex-col gap-space-xs">
                <label class="text-label-sm text-on-surface-variant" for="filtro-nombre">Buscar por nombre</label>
                <input class="bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none w-56 text-on-surface" id="filtro-nombre" placeholder="Nombre" type="text"/>
            </div>
            <div class="flex items-center gap-2 h-10">
                <button class="bg-primary hover:bg-primary-container text-on-primary px-4 py-2 rounded-lg text-label-md transition-colors" type="button" id="btn-filtrar">Filtrar</button>
                <button class="bg-surface-container hover:bg-surface-container-high text-on-surface px-4 py-2 rounded-lg text-label-md transition-colors" type="button" id="btn-limpiar">Limpiar</button>
            </div>
            <?php if ($esAdmin): ?>
            <div class="flex items-center gap-2 ml-auto h-10">
                <input class="w-4 h-4 text-primary rounded accent-primary cursor-pointer" id="filtro-mostrar-inactivos" type="checkbox"/>
                <label class="text-sm text-on-surface select-none cursor-pointer" for="filtro-mostrar-inactivos">Mostrar sucursales inactivas</label>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tarjeta de Tabla -->
    <div class="bg-surface-container-lowest rounded-xl shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] flex flex-col overflow-hidden">
        <div id="paginacion"></div>
        <div class="overflow-x-auto w-full">
            <table class="w-full min-w-[850px] text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container text-on-surface text-label-sm border-b border-surface-container-highest select-none">
                        <th id="th-cod" class="py-3.5 px-4 font-headline-sm hover:text-primary transition-colors cursor-pointer whitespace-nowrap" title="Ordenar">Código</th>
                        <th id="th-nombre" class="py-3.5 px-4 font-headline-sm hover:text-primary transition-colors cursor-pointer whitespace-nowrap" title="Ordenar">Nombre</th>
                        <th id="th-localidad" class="py-3.5 px-4 font-headline-sm hover:text-primary transition-colors cursor-pointer whitespace-nowrap" title="Ordenar">Localidad</th>
                        <th id="th-provincia" class="py-3.5 px-4 font-headline-sm hover:text-primary transition-colors cursor-pointer whitespace-nowrap" title="Ordenar">Provincia</th>
                        <th class="py-3.5 px-4 font-headline-sm min-w-[200px]">Dirección</th>
                        <th class="py-3.5 px-4 font-headline-sm text-center whitespace-nowrap">Estado</th>
                        <th class="py-3.5 px-4 font-headline-sm text-center whitespace-nowrap min-w-[170px]">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tabla-sucursales" class="divide-y divide-surface-container text-sm text-on-surface"></tbody>
            </table>
        </div>
    </div>

<?php fin_pagina('sucursales.js'); ?>
