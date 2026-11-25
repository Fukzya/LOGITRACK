<?php
/*
 * ============================================================================================
 * QUÉ ES:  el diseño (HTML) de la pantalla "Viaje-Envío": qué envíos van en qué viaje (asignar y quitar; tramo 2 = trasbordo).
 *          Lo que hace la pantalla (cargar, guardar, botones) está en viaje_envio.js.
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
require '../plantilla.php'; inicio_pagina('Viaje-Envío'); ?>

    <!-- Formulario de Asignación -->
    <div class="bg-surface-container-lowest rounded-xl p-space-lg shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] flex flex-col gap-space-lg" id="asignar-envio-card">
        <div class="flex items-center justify-between">
            <h2 class="text-headline-md text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">link</span>
                <span id="titulo-form">Asignar un envío a un viaje</span>
            </h2>
            <span class="text-xs text-on-surface-variant">Asocie números de despacho a los viajes programados</span>
        </div>
        <form id="form-asignacion" class="flex flex-col gap-space-md">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-space-md">
                <div class="flex flex-col gap-space-xs">
                    <label class="text-label-sm text-on-surface-variant" for="input-viaje">ID del viaje</label>
                    <input class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="input-viaje" placeholder="ID de viaje" required min="1" type="number"/>
                </div>
                <div class="flex flex-col gap-space-xs">
                    <label class="text-label-sm text-on-surface-variant" for="input-envio">Nro de envío</label>
                    <input class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="input-envio" placeholder="Nro de envío" required min="1" type="number"/>
                </div>
                <div class="flex flex-col gap-space-xs justify-end">
                    <button class="bg-primary hover:bg-primary-container text-on-primary px-4 py-2 rounded-lg text-label-md transition-colors shadow-sm whitespace-nowrap h-10" type="submit">Asignar</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Tarjeta de Filtros -->
    <div class="bg-surface-container-lowest rounded-xl p-space-lg shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] flex flex-col gap-space-md">
        <div class="flex items-center justify-between">
            <h3 class="text-headline-sm text-on-surface">Filtros de Búsqueda</h3>
            <span class="material-symbols-outlined text-outline">tune</span>
        </div>
        <div class="flex flex-wrap items-end gap-space-md">
            <div class="flex flex-col gap-space-xs">
                <label class="text-label-sm text-on-surface-variant" for="filtro-viaje">Buscar por ID de viaje</label>
                <input class="bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none w-48 text-on-surface" id="filtro-viaje" placeholder="ID de viaje" type="number"/>
            </div>
            <div class="flex flex-col gap-space-xs">
                <label class="text-label-sm text-on-surface-variant" for="filtro-envio">Buscar por nro de envío</label>
                <input class="bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none w-48 text-on-surface" id="filtro-envio" placeholder="Nro de envío" type="number"/>
            </div>
            <div class="flex items-center gap-2 h-10">
                <button class="bg-primary hover:bg-primary-container text-on-primary px-4 py-2 rounded-lg text-label-md transition-colors" type="button" id="btn-filtrar">Filtrar</button>
                <button class="bg-surface-container hover:bg-surface-container-high text-on-surface px-4 py-2 rounded-lg text-label-md transition-colors" type="button" id="btn-limpiar">Limpiar</button>
            </div>
        </div>
    </div>

    <!-- Tarjeta de Tabla -->
    <div class="bg-surface-container-lowest rounded-xl shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] flex flex-col overflow-hidden">
        <div id="paginacion"></div>
        <div class="overflow-x-auto w-full">
            <table class="w-full min-w-[950px] text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container text-on-surface text-label-sm border-b border-surface-container-highest select-none">
                        <th id="th-viaje" class="py-3.5 px-4 font-headline-sm hover:text-primary transition-colors cursor-pointer whitespace-nowrap" title="Ordenar">Viaje</th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Salida</th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Llegada</th>
                        <th id="th-envio" class="py-3.5 px-4 font-headline-sm hover:text-primary transition-colors cursor-pointer whitespace-nowrap" title="Ordenar">Envío</th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap" title="1 = directo o primer viaje; 2 = después del trasbordo">Tramo</th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Fecha del envío</th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Recorrido del viaje</th>
                        <th class="py-3.5 px-4 font-headline-sm text-center whitespace-nowrap min-w-[170px]">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tabla-asignaciones" class="divide-y divide-surface-container text-sm text-on-surface"></tbody>
            </table>
        </div>
    </div>

<?php fin_pagina('viaje_envio.js'); ?>
