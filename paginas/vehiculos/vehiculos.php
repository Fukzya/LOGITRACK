<?php require '../plantilla.php'; inicio_pagina('Vehículos'); ?>

    <!-- Formulario de Nuevo Vehículo / Edición -->
    <div class="bg-surface-container-lowest rounded-xl p-space-lg shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] flex flex-col gap-space-lg" id="nuevo-vehiculo-card">
        <div class="flex items-center justify-between">
            <h2 class="text-headline-md text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">directions_car</span>
                <span id="titulo-form">Nuevo vehículo</span>
            </h2>
            <span class="text-xs text-on-surface-variant">Registre los datos de las unidades de transporte de la flota</span>
        </div>
        <form id="form-vehiculo" class="flex flex-col gap-space-md">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-space-md">
                <div class="flex flex-col gap-space-xs">
                    <label class="text-label-sm text-on-surface-variant" for="input-patente">Patente (AB123CD / ABC123)</label>
                    <input class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface uppercase" id="input-patente" placeholder="Patente" required maxlength="7" pattern="[A-Za-z]{2}[0-9]{3}[A-Za-z]{2}|[A-Za-z]{3}[0-9]{3}" title="AB123CD o ABC123" type="text"/>
                </div>
                <div class="flex flex-col gap-space-xs">
                    <label class="text-label-sm text-on-surface-variant" for="input-marca">Marca</label>
                    <input class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="input-marca" placeholder="Marca" required maxlength="60" type="text"/>
                </div>
                <div class="flex flex-col gap-space-xs">
                    <label class="text-label-sm text-on-surface-variant" for="input-modelo">Modelo</label>
                    <input class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="input-modelo" placeholder="Modelo" required maxlength="60" type="text"/>
                </div>
                <div class="flex flex-col gap-space-xs">
                    <label class="text-label-sm text-on-surface-variant" for="input-anio">Año (opcional)</label>
                    <input class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="input-anio" placeholder="Año" min="1950" max="2100" type="number"/>
                </div>
                <div class="flex flex-col gap-space-xs">
                    <label class="text-label-sm text-on-surface-variant" for="input-tipo">Tipo</label>
                    <div class="flex gap-2">
                        <select class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="input-tipo" required>
                            <option value="">Elegí el tipo</option>
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
                <label class="text-label-sm text-on-surface-variant" for="filtro-patente">Buscar por patente</label>
                <input class="bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none w-44 text-on-surface uppercase" id="filtro-patente" placeholder="Patente" type="text"/>
            </div>
            <div class="flex flex-col gap-space-xs">
                <label class="text-label-sm text-on-surface-variant" for="filtro-marca">Buscar por marca</label>
                <input class="bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none w-44 text-on-surface" id="filtro-marca" placeholder="Marca" type="text"/>
            </div>
            <div class="flex flex-col gap-space-xs">
                <label class="text-label-sm text-on-surface-variant" for="filtro-tipo">Tipo</label>
                <select class="bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="filtro-tipo">
                    <option value="">Todos los tipos</option>
                </select>
            </div>
            <div class="flex items-center gap-2 h-10">
                <button class="bg-primary hover:bg-primary-container text-on-primary px-4 py-2 rounded-lg text-label-md transition-colors" type="button" id="btn-filtrar">Filtrar</button>
                <button class="bg-surface-container hover:bg-surface-container-high text-on-surface px-4 py-2 rounded-lg text-label-md transition-colors" type="button" id="btn-limpiar">Limpiar</button>
            </div>
            <?php if ($esAdmin): ?>
            <div class="flex items-center gap-2 ml-auto h-10">
                <input class="w-4 h-4 text-primary rounded accent-primary cursor-pointer" id="filtro-mostrar-inactivos" type="checkbox"/>
                <label class="text-sm text-on-surface select-none cursor-pointer" for="filtro-mostrar-inactivos">Mostrar vehículos inactivos</label>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tarjeta de Tabla -->
    <div class="bg-surface-container-lowest rounded-xl shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] flex flex-col overflow-hidden">
        <div id="paginacion"></div>
        <div class="overflow-x-auto w-full">
            <table class="w-full min-w-[900px] text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container text-on-surface text-label-sm border-b border-surface-container-highest select-none">
                        <th id="th-patente" class="py-3.5 px-4 font-headline-sm hover:text-primary transition-colors cursor-pointer whitespace-nowrap" title="Ordenar">Patente</th>
                        <th id="th-marca" class="py-3.5 px-4 font-headline-sm hover:text-primary transition-colors cursor-pointer whitespace-nowrap" title="Ordenar">Marca</th>
                        <th id="th-modelo" class="py-3.5 px-4 font-headline-sm hover:text-primary transition-colors cursor-pointer whitespace-nowrap" title="Ordenar">Modelo</th>
                        <th id="th-anio" class="py-3.5 px-4 font-headline-sm hover:text-primary transition-colors cursor-pointer whitespace-nowrap" title="Ordenar">Año</th>
                        <th id="th-tipo" class="py-3.5 px-4 font-headline-sm hover:text-primary transition-colors cursor-pointer whitespace-nowrap" title="Ordenar">Tipo</th>
                        <th class="py-3.5 px-4 font-headline-sm text-center whitespace-nowrap">Estado</th>
                        <th class="py-3.5 px-4 font-headline-sm text-center whitespace-nowrap min-w-[170px]">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tabla-vehiculos" class="divide-y divide-surface-container text-sm text-on-surface"></tbody>
            </table>
        </div>
    </div>

<?php fin_pagina('vehiculos.js'); ?>
