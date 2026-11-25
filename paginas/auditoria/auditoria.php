<?php require '../plantilla.php'; inicio_pagina('Auditoría', true); ?>

    <!-- Filtros. Las filas las escriben los triggers AFTER de envio, viaje y usuario (script 013). -->
    <div class="bg-surface-container-lowest rounded-xl p-space-lg shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] flex flex-col gap-space-md">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-headline-sm text-on-surface">Filtros y Búsqueda</h3>
                <p class="text-xs text-on-surface-variant mt-1">Cada cambio en un envío, un viaje o un usuario queda registrado por la base: qué campo cambió, el valor anterior, el nuevo, quién y cuándo.</p>
            </div>
            <span class="material-symbols-outlined text-outline">tune</span>
        </div>
        <div class="flex flex-wrap items-end gap-space-md">
            <div class="flex flex-col gap-space-xs">
                <label class="text-label-sm text-on-surface-variant" for="filtro-tabla">Qué</label>
                <select class="bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="filtro-tabla">
                    <option value="">Todo</option>
                    <option value="envio">Envíos</option>
                    <option value="viaje">Viajes</option>
                    <option value="usuario">Usuarios</option>
                </select>
            </div>
            <div class="flex flex-col gap-space-xs">
                <label class="text-label-sm text-on-surface-variant" for="filtro-clave">N.º (envío, viaje o usuario)</label>
                <input class="bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none w-40 text-on-surface" id="filtro-clave" type="text" maxlength="30"/>
            </div>
            <div class="flex flex-col gap-space-xs">
                <label class="text-label-sm text-on-surface-variant" for="filtro-usuario">Usuario</label>
                <input class="bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none w-40 text-on-surface" id="filtro-usuario" type="text" maxlength="100"/>
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

    <div class="bg-surface-container-lowest rounded-xl shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] flex flex-col overflow-hidden">
        <div id="paginacion"></div>
        <div class="overflow-x-auto w-full">
            <table class="w-full min-w-[950px] text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container text-on-surface text-label-sm border-b border-surface-container-highest select-none">
                        <th id="th-fecha" class="py-3.5 px-4 font-headline-sm hover:text-primary transition-colors cursor-pointer whitespace-nowrap" title="Ordenar">Fecha y hora</th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Qué</th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Campo</th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Antes</th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Después</th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Usuario</th>
                    </tr>
                </thead>
                <tbody id="tabla-auditoria" class="divide-y divide-surface-container text-sm text-on-surface"></tbody>
            </table>
        </div>
    </div>

<?php fin_pagina('auditoria.js'); ?>
