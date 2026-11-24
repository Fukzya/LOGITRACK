<?php require '../plantilla.php'; inicio_pagina('Tipos de vehículo'); ?>

    <!-- Formulario de Nuevo Tipo / Edición -->
    <div class="bg-surface-container-lowest rounded-xl p-space-lg shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] flex flex-col gap-space-lg" id="nuevo-tipo-card">
        <div class="flex items-center justify-between">
            <h2 class="text-headline-md text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">category</span>
                <span id="titulo-form">Nuevo tipo de vehículo</span>
            </h2>
            <span class="text-xs text-on-surface-variant">Clasificación de vehículos por tamaño y requerimientos</span>
        </div>
        <form id="form-tipo" class="flex flex-col gap-space-md">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-space-md items-end">
                <div class="flex flex-col gap-space-xs">
                    <label class="text-label-sm text-on-surface-variant" for="input-cod">Código (4 letras)</label>
                    <input class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface uppercase" id="input-cod" placeholder="Ej. CAMN" required pattern="[A-Za-z]{4}" maxlength="4" title="4 letras, ej. CAMN" type="text"/>
                </div>
                <div class="flex flex-col gap-space-xs">
                    <label class="text-label-sm text-on-surface-variant" for="input-nombre">Nombre</label>
                    <input class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="input-nombre" placeholder="Nombre descriptivo" required maxlength="60" type="text"/>
                </div>
                <div class="flex flex-col gap-space-xs">
                    <label class="text-label-sm text-on-surface-variant" for="input-capacidad">Capacidad (kg)</label>
                    <input class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="input-capacidad" placeholder="Capacidad en kg" required min="1" step="0.01" type="number"/>
                </div>
                <div class="flex items-center justify-between gap-4 h-10">
                    <label class="flex items-center gap-2 text-sm text-on-surface select-none cursor-pointer">
                        <input class="w-4 h-4 text-primary rounded accent-primary cursor-pointer" id="input-licencia" type="checkbox"/>
                        Requiere licencia especial
                    </label>
                    <div class="flex gap-2">
                        <button class="bg-primary hover:bg-primary-container text-on-primary px-4 py-2 rounded-lg text-label-md transition-colors shadow-sm whitespace-nowrap" type="submit">Agregar</button>
                        <button class="btn-cancelar bg-surface-container hover:bg-surface-container-high text-on-surface px-4 py-2 rounded-lg text-label-md transition-colors whitespace-nowrap" type="button" hidden>Cancelar</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Tarjeta de Tabla -->
    <div class="bg-surface-container-lowest rounded-xl shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] flex flex-col overflow-hidden">
        <div class="overflow-x-auto w-full">
            <table class="w-full min-w-[750px] text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container text-on-surface text-label-sm border-b border-surface-container-highest select-none">
                        <th id="th-cod" class="py-3.5 px-4 font-headline-sm hover:text-primary transition-colors cursor-pointer whitespace-nowrap" title="Ordenar">Código</th>
                        <th id="th-nombre" class="py-3.5 px-4 font-headline-sm hover:text-primary transition-colors cursor-pointer whitespace-nowrap" title="Ordenar">Nombre</th>
                        <th id="th-capacidad" class="py-3.5 px-4 font-headline-sm hover:text-primary transition-colors cursor-pointer whitespace-nowrap" title="Ordenar">Capacidad (kg)</th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Licencia especial</th>
                        <th class="py-3.5 px-4 font-headline-sm text-center whitespace-nowrap min-w-[170px]">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tabla-tipos" class="divide-y divide-surface-container text-sm text-on-surface"></tbody>
            </table>
        </div>
    </div>

<?php fin_pagina('tipo_vehiculo.js'); ?>
