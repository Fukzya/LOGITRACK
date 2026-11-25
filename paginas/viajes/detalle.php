<?php
/*
 * ============================================================================================
 * QUÉ ES:  el diseño (HTML) de la pantalla "Detalle de viaje": el detalle de un viaje: carga, envíos, agregar envíos y cerrar el viaje.
 *          Lo que hace la pantalla (cargar, guardar, botones) está en detalle.js.
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
require '../plantilla.php'; inicio_pagina('Detalle de viaje'); ?>

    <!-- Datos del viaje y carga (funcion_carga_viaje) -->
    <div class="bg-surface-container-lowest rounded-xl p-space-lg shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] flex flex-col gap-space-md">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <h2 class="text-headline-md text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">route</span>
                <span id="titulo-viaje">Viaje</span>
            </h2>
            <div class="flex gap-2">
                <a class="bg-surface-container hover:bg-surface-container-high text-on-surface px-4 py-2 rounded-lg text-label-md transition-colors" href="viajes.php">Ver todos los viajes</a>
                <button class="bg-primary hover:bg-primary-container text-on-primary px-4 py-2 rounded-lg text-label-md transition-colors" id="btn-cerrar-viaje" type="button" hidden>Cerrar viaje</button>
            </div>
        </div>
        <div id="datos-viaje" class="grid grid-cols-1 md:grid-cols-3 gap-space-md text-sm text-on-surface"></div>
        <div>
            <div class="flex justify-between text-xs text-on-surface-variant mb-1">
                <span>Carga del vehículo</span><span id="texto-carga"></span>
            </div>
            <div class="w-full h-3 bg-surface-container rounded-full overflow-hidden">
                <div id="barra-carga" class="h-full bg-primary rounded-full" style="width: 0%"></div>
            </div>
        </div>
    </div>

    <!-- Envíos que lleva -->
    <div class="bg-surface-container-lowest rounded-xl shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] flex flex-col overflow-hidden">
        <div class="p-space-lg pb-space-md">
            <h2 class="text-headline-md text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">inventory_2</span>Envíos en este viaje
            </h2>
        </div>
        <div class="overflow-x-auto w-full">
            <table class="w-full min-w-[800px] text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container text-on-surface text-label-sm border-b border-surface-container-highest select-none">
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Envío</th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Tramo</th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Peso (kg)</th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Remitente → destinatario</th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Destino del envío</th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Último estado</th>
                    </tr>
                </thead>
                <tbody id="tabla-envios" class="divide-y divide-surface-container text-sm text-on-surface"></tbody>
            </table>
        </div>
    </div>

    <!-- Envíos pendientes que se pueden agregar (solo si el viaje no salió) -->
    <div id="tarjeta-pendientes" class="bg-surface-container-lowest rounded-xl shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] flex flex-col overflow-hidden" hidden>
        <div class="flex items-center justify-between gap-4 flex-wrap p-space-lg pb-space-md">
            <div>
                <h2 class="text-headline-md text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">add_box</span>Agregar envíos pendientes
                </h2>
                <p class="text-xs text-on-surface-variant mt-1">Envíos recientes sin terminar que salen de la sucursal de origen del viaje (o llegan ahí en otro viaje antes de la salida) y entran en la capacidad libre. Primero los que van a la provincia de destino.</p>
            </div>
            <button class="bg-primary hover:bg-primary-container text-on-primary px-4 py-2 rounded-lg text-label-md transition-colors whitespace-nowrap disabled:opacity-50 disabled:cursor-not-allowed" id="btn-agregar" type="button" disabled>Agregar seleccionados</button>
        </div>
        <div class="overflow-x-auto w-full">
            <table class="w-full min-w-[800px] text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container text-on-surface text-label-sm border-b border-surface-container-highest select-none">
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap"></th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Envío</th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Fecha</th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Peso (kg)</th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Remitente → destinatario</th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Destino del envío</th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Por qué aparece</th>
                    </tr>
                </thead>
                <tbody id="tabla-pendientes" class="divide-y divide-surface-container text-sm text-on-surface"></tbody>
            </table>
        </div>
    </div>

    <!-- Cerrar viaje (mismo diálogo que en la lista de viajes) -->
    <dialog id="dialogo-cerrar" class="m-auto rounded-xl p-0 shadow-xl backdrop:bg-black/40 w-full max-w-md">
        <form method="dialog" id="form-cerrar" class="flex flex-col gap-space-md p-space-lg bg-surface-container-lowest">
            <h2 class="text-headline-md text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">flag</span>Cerrar viaje
            </h2>
            <p class="text-sm text-on-surface-variant">Los envíos que no estén entregados o devueltos pasan a "En sucursal" de destino. Si alguno no se puede actualizar, no se cierra nada.</p>
            <div class="flex flex-col gap-space-xs">
                <label class="text-label-sm text-on-surface-variant" for="input-llegada-real">Llegada real</label>
                <input class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="input-llegada-real" data-hasta-ahora type="datetime-local" required/>
            </div>
            <div class="flex justify-end gap-2">
                <button class="bg-surface-container hover:bg-surface-container-high text-on-surface px-4 py-2 rounded-lg text-label-md transition-colors" type="button" id="btn-cancelar-cierre">Cancelar</button>
                <button class="bg-primary hover:bg-primary-container text-on-primary px-4 py-2 rounded-lg text-label-md transition-colors" type="submit">Cerrar viaje</button>
            </div>
        </form>
    </dialog>

<?php fin_pagina('detalle.js'); ?>
