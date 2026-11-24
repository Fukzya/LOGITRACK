<?php require '../plantilla.php'; inicio_pagina('Localidades'); ?>

    <!-- Formulario de Nueva Localidad / Edición -->
    <div class="bg-surface-container-lowest rounded-xl p-space-lg shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] flex flex-col gap-space-lg" id="nueva-localidad-card">
        <div class="flex items-center justify-between">
            <h2 class="text-headline-md text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">pin_drop</span>
                <span id="titulo-form">Nueva localidad</span>
            </h2>
            <span class="text-xs text-on-surface-variant">Ciudades y nodos de entrega vinculados por provincia</span>
        </div>
        <form id="form-localidad" class="flex flex-col gap-space-md">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-space-md">
                <div class="flex flex-col gap-space-xs">
                    <label class="text-label-sm text-on-surface-variant" for="input-nombre">Nombre de la localidad</label>
                    <input class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="input-nombre" placeholder="Nombre" required maxlength="100" type="text"/>
                </div>
                <div class="flex flex-col gap-space-xs">
                    <label class="text-label-sm text-on-surface-variant" for="input-provincia">Provincia</label>
                    <div class="flex gap-2">
                        <select class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="input-provincia" required>
                            <option value="">Elegí una provincia</option>
                        </select>
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
                <label class="text-label-sm text-on-surface-variant" for="filtro-provincia">Provincia</label>
                <select class="bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="filtro-provincia">
                    <option value="">Todas</option>
                </select>
            </div>
            <div class="flex items-center gap-2 h-10">
                <button class="bg-primary hover:bg-primary-container text-on-primary px-4 py-2 rounded-lg text-label-md transition-colors" type="button" id="btn-filtrar">Filtrar</button>
                <button class="bg-surface-container hover:bg-surface-container-high text-on-surface px-4 py-2 rounded-lg text-label-md transition-colors" type="button" id="btn-limpiar">Limpiar</button>
            </div>
        </div>
    </div>

    <!-- Tarjeta de Tabla -->
    <div class="bg-surface-container-lowest rounded-xl shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] flex flex-col overflow-hidden">
        <div class="overflow-x-auto w-full">
            <table class="w-full min-w-[650px] text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container text-on-surface text-label-sm border-b border-surface-container-highest select-none">
                        <th id="th-cod" class="py-3.5 px-4 font-headline-sm hover:text-primary transition-colors cursor-pointer whitespace-nowrap" title="Ordenar">Código</th>
                        <th id="th-nombre" class="py-3.5 px-4 font-headline-sm hover:text-primary transition-colors cursor-pointer whitespace-nowrap" title="Ordenar">Nombre</th>
                        <th id="th-provincia" class="py-3.5 px-4 font-headline-sm hover:text-primary transition-colors cursor-pointer whitespace-nowrap" title="Ordenar">Provincia</th>
                        <th class="py-3.5 px-4 font-headline-sm text-center whitespace-nowrap min-w-[170px]">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tabla-localidades" class="divide-y divide-surface-container text-sm text-on-surface"></tbody>
            </table>
        </div>
    </div>

<?php fin_pagina('localidades.js'); ?>
