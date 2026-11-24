<?php require '../plantilla.php'; inicio_pagina('Direcciones'); ?>

    <!-- Formulario de Nueva Dirección / Edición -->
    <div class="bg-surface-container-lowest rounded-xl p-space-lg shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] flex flex-col gap-space-lg" id="nueva-direccion-card">
        <div class="flex items-center justify-between">
            <h2 class="text-headline-md text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">location_on</span>
                <span id="titulo-form">Nueva dirección</span>
            </h2>
            <span class="text-xs text-on-surface-variant">Catálogo de domicilios de entrega vinculados a clientes</span>
        </div>
        <form id="form-direccion" class="flex flex-col gap-space-md">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-space-md">
                <div class="flex flex-col gap-space-xs">
                    <label class="text-label-sm text-on-surface-variant" for="input-calle">Calle y número</label>
                    <input class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="input-calle" placeholder="Calle y número" required maxlength="200" type="text"/>
                </div>
                <div class="flex flex-col gap-space-xs">
                    <label class="text-label-sm text-on-surface-variant" for="input-localidad">Localidad</label>
                    <select class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="input-localidad" required>
                        <option value="">Elegí una localidad</option>
                    </select>
                </div>
                <div class="flex flex-col gap-space-xs">
                    <label class="text-label-sm text-on-surface-variant" for="input-dni">DNI del cliente</label>
                    <div class="flex gap-2">
                        <input class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="input-dni" placeholder="DNI del cliente" required min="1" max="99999999" type="number"/>
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
                <label class="text-label-sm text-on-surface-variant" for="filtro-calle">Buscar por calle</label>
                <input class="bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none w-44 text-on-surface" id="filtro-calle" placeholder="Calle" type="text"/>
            </div>
            <div class="flex flex-col gap-space-xs">
                <label class="text-label-sm text-on-surface-variant" for="filtro-dni">DNI del cliente</label>
                <input class="bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none w-44 text-on-surface" id="filtro-dni" placeholder="DNI" type="number"/>
            </div>
            <div class="flex flex-col gap-space-xs">
                <label class="text-label-sm text-on-surface-variant" for="filtro-localidad">Localidad</label>
                <select class="bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="filtro-localidad">
                    <option value="">Todas las localidades</option>
                </select>
            </div>
            <div class="flex items-center gap-2 h-10">
                <button class="bg-primary hover:bg-primary-container text-on-primary px-4 py-2 rounded-lg text-label-md transition-colors" type="button" id="btn-filtrar">Filtrar</button>
                <button class="bg-surface-container hover:bg-surface-container-high text-on-surface px-4 py-2 rounded-lg text-label-md transition-colors" type="button" id="btn-limpiar">Limpiar</button>
            </div>
            <?php if ($esAdmin): ?>
            <div class="flex items-center gap-2 ml-auto h-10">
                <input class="w-4 h-4 text-primary rounded accent-primary cursor-pointer" id="filtro-mostrar-inactivos" type="checkbox"/>
                <label class="text-sm text-on-surface select-none cursor-pointer" for="filtro-mostrar-inactivos">Mostrar direcciones inactivas</label>
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
                        <th id="th-id" class="py-3.5 px-4 font-headline-sm hover:text-primary transition-colors cursor-pointer whitespace-nowrap" title="Ordenar">ID</th>
                        <th id="th-calle" class="py-3.5 px-4 font-headline-sm hover:text-primary transition-colors cursor-pointer whitespace-nowrap" title="Ordenar">Calle y número</th>
                        <th id="th-localidad" class="py-3.5 px-4 font-headline-sm hover:text-primary transition-colors cursor-pointer whitespace-nowrap" title="Ordenar">Localidad</th>
                        <th id="th-dni" class="py-3.5 px-4 font-headline-sm hover:text-primary transition-colors cursor-pointer whitespace-nowrap" title="Ordenar">Cliente</th>
                        <th class="py-3.5 px-4 font-headline-sm text-center whitespace-nowrap">Estado</th>
                        <th class="py-3.5 px-4 font-headline-sm text-center whitespace-nowrap min-w-[170px]">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tabla-direcciones" class="divide-y divide-surface-container text-sm text-on-surface"></tbody>
            </table>
        </div>
    </div>

<?php fin_pagina('direcciones.js'); ?>
