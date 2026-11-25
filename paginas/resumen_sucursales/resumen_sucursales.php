<?php
/*
 * ============================================================================================
 * QUÉ ES:  el diseño (HTML) de la pantalla "Resumen por sucursal": el resumen de envíos y viajes por sucursal en un período.
 *          Lo que hace la pantalla (cargar, guardar, botones) está en resumen_sucursales.js.
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
require '../plantilla.php'; inicio_pagina('Resumen por sucursal'); ?>

    <!-- Período (procedimiento_resumen_por_sucursal(desde, hasta)) -->
    <div class="bg-surface-container-lowest rounded-xl p-space-lg shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] flex flex-col gap-space-md">
        <div class="flex items-center justify-between">
            <h3 class="text-headline-sm text-on-surface">Período</h3>
            <span class="material-symbols-outlined text-outline">date_range</span>
        </div>
        <form id="form-periodo" class="flex flex-wrap items-end gap-space-md">
            <div class="flex flex-col gap-space-xs">
                <label class="text-label-sm text-on-surface-variant" for="filtro-desde">Desde</label>
                <input class="bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none w-40 text-on-surface" id="filtro-desde" type="date"/>
            </div>
            <div class="flex flex-col gap-space-xs">
                <label class="text-label-sm text-on-surface-variant" for="filtro-hasta">Hasta (vacío = hoy)</label>
                <input class="bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none w-40 text-on-surface" id="filtro-hasta" type="date"/>
            </div>
            <div class="flex items-center gap-2 h-10">
                <button class="bg-primary hover:bg-primary-container text-on-primary px-4 py-2 rounded-lg text-label-md transition-colors" type="submit">Ver resumen</button>
                <button class="bg-surface-container hover:bg-surface-container-high text-on-surface px-4 py-2 rounded-lg text-label-md transition-colors" type="button" id="btn-todo">Todo el historial</button>
            </div>
        </form>
        <p class="text-xs text-on-surface-variant">"Pendientes hoy" no depende del período: son los envíos con destino en la sucursal que todavía no fueron entregados ni devueltos.</p>
    </div>

    <div class="bg-surface-container-lowest rounded-xl shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] flex flex-col overflow-hidden">
        <div id="total-resumen" class="px-space-lg py-3 text-sm text-on-surface-variant"></div>
        <div class="overflow-x-auto w-full">
            <table class="w-full min-w-[900px] text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container text-on-surface text-label-sm border-b border-surface-container-highest select-none">
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Sucursal</th>
                        <th class="py-3.5 px-4 font-headline-sm text-right whitespace-nowrap" title="Envíos recibidos para despachar desde esta sucursal">Despachados</th>
                        <th class="py-3.5 px-4 font-headline-sm text-right whitespace-nowrap">Kg despachados</th>
                        <th class="py-3.5 px-4 font-headline-sm text-right whitespace-nowrap" title="Envíos del período que van a esta sucursal">Con destino acá</th>
                        <th class="py-3.5 px-4 font-headline-sm text-right whitespace-nowrap">Entregados</th>
                        <th class="py-3.5 px-4 font-headline-sm text-right whitespace-nowrap">Viajes que salieron</th>
                        <th class="py-3.5 px-4 font-headline-sm text-right whitespace-nowrap">Pendientes hoy</th>
                    </tr>
                </thead>
                <tbody id="tabla-resumen" class="divide-y divide-surface-container text-sm text-on-surface"></tbody>
            </table>
        </div>
    </div>

<?php fin_pagina('resumen_sucursales.js'); ?>
