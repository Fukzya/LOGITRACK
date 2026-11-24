<?php require '../plantilla.php'; inicio_pagina('Historial de Estados'); ?>

    <!-- Formulario de Nuevo Registro de Estado / Edición -->
    <div class="bg-surface-container-lowest rounded-xl p-space-lg shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] flex flex-col gap-space-lg" id="nuevo-historial-card">
        <div class="flex items-center justify-between">
            <h2 class="text-headline-md text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">history</span>
                <span id="titulo-form">Registrar un estado</span>
            </h2>
            <span class="text-xs text-on-surface-variant">Actualice la trazabilidad de un envío con su nuevo estado</span>
        </div>
        <form id="form-historial" class="flex flex-col gap-space-md">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-space-md">
                <div class="flex flex-col gap-space-xs">
                    <label class="text-label-sm text-on-surface-variant" for="input-envio">Nro de envío</label>
                    <input class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="input-envio" placeholder="Nro de envío" required min="1" type="number"/>
                </div>
                <div class="flex flex-col gap-space-xs">
                    <label class="text-label-sm text-on-surface-variant" for="input-sucursal">Sucursal</label>
                    <select class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="input-sucursal" required>
                        <option value="">Sucursal</option>
                    </select>
                </div>
                <div class="flex flex-col gap-space-xs">
                    <label class="text-label-sm text-on-surface-variant" for="input-estado">Estado</label>
                    <select class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="input-estado" required>
                        <option value="">Estado</option>
                    </select>
                </div>
                <div class="flex flex-col gap-space-xs">
                    <label class="text-label-sm text-on-surface-variant" for="input-fecha">Fecha y hora (vacío = ahora)</label>
                    <input class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="input-fecha" data-hasta-ahora type="datetime-local"/>
                </div>
                <div class="flex flex-col gap-space-xs md:col-span-2">
                    <label class="text-label-sm text-on-surface-variant" for="input-observacion">Observación (opcional)</label>
                    <div class="flex gap-2">
                        <input class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="input-observacion" placeholder="Detalle u observación del evento" maxlength="1000" type="text"/>
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
                <label class="text-label-sm text-on-surface-variant" for="filtro-envio">Nro de envío</label>
                <input class="bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none w-36 text-on-surface" id="filtro-envio" placeholder="Nro de envío" type="number"/>
            </div>
            <div class="flex flex-col gap-space-xs">
                <label class="text-label-sm text-on-surface-variant" for="filtro-estado">Estado</label>
                <select class="bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="filtro-estado">
                    <option value="">Todos los estados</option>
                </select>
            </div>
            <div class="flex flex-col gap-space-xs">
                <label class="text-label-sm text-on-surface-variant" for="filtro-sucursal">Sucursal</label>
                <select class="bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="filtro-sucursal">
                    <option value="">Todas las sucursales</option>
                </select>
            </div>
            <div class="flex flex-col gap-space-xs">
                <label class="text-label-sm text-on-surface-variant" for="filtro-fecha-desde">Desde</label>
                <input class="bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none w-36 text-on-surface" id="filtro-fecha-desde" type="date"/>
            </div>
            <div class="flex flex-col gap-space-xs">
                <label class="text-label-sm text-on-surface-variant" for="filtro-fecha-hasta">Hasta</label>
                <input class="bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none w-36 text-on-surface" id="filtro-fecha-hasta" type="date"/>
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
            <table class="w-full min-w-[1050px] text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container text-on-surface text-label-sm border-b border-surface-container-highest select-none">
                        <th id="th-id" class="py-3.5 px-4 font-headline-sm hover:text-primary transition-colors cursor-pointer whitespace-nowrap" title="Ordenar">ID</th>
                        <th id="th-envio" class="py-3.5 px-4 font-headline-sm hover:text-primary transition-colors cursor-pointer whitespace-nowrap" title="Ordenar">Envío</th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Sucursal</th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Estado</th>
                        <th id="th-fecha" class="py-3.5 px-4 font-headline-sm hover:text-primary transition-colors cursor-pointer whitespace-nowrap" title="Ordenar">Fecha y hora</th>
                        <th class="py-3.5 px-4 font-headline-sm min-w-[200px]">Observación</th>
                    </tr>
                </thead>
                <tbody id="tabla-historial" class="divide-y divide-surface-container text-sm text-on-surface"></tbody>
            </table>
        </div>
    </div>

<?php fin_pagina('historial_estado.js'); ?>
