<?php require '../plantilla.php'; inicio_pagina('Viajes'); ?>

    <!-- Formulario de Nuevo Viaje / Edición -->
    <div class="bg-surface-container-lowest rounded-xl p-space-lg shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] flex flex-col gap-space-lg" id="nuevo-viaje-card">
        <div class="flex items-center justify-between">
            <h2 class="text-headline-md text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">route</span>
                <span id="titulo-form">Nuevo viaje</span>
            </h2>
            <span class="text-xs text-on-surface-variant">Complete los datos para programar un viaje logístico</span>
        </div>
        <form id="form-viaje" class="flex flex-col gap-space-md">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-space-md">
                <div class="flex flex-col gap-space-xs">
                    <label class="text-label-sm text-on-surface-variant" for="input-salida">Fecha y hora de salida</label>
                    <input class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="input-salida" type="datetime-local" required/>
                </div>
                <div class="flex flex-col gap-space-xs">
                    <label class="text-label-sm text-on-surface-variant" for="input-llegada">Llegada real (al cerrar el viaje)</label>
                    <input class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="input-llegada" data-hasta-ahora type="datetime-local"/>
                    <small class="text-xs text-on-surface-variant">La llegada estimada la calcula la base: salida + 24 h.</small>
                </div>
                <div class="flex flex-col gap-space-xs md:col-span-2">
                    <label class="text-label-sm text-on-surface-variant" for="input-origen">Sale de</label>
                    <select class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="input-origen" required>
                        <option value="">Sucursal de origen</option>
                    </select>
                </div>
                <div class="flex flex-col gap-space-xs md:col-span-2">
                    <label class="text-label-sm text-on-surface-variant" for="input-destino">Llega a</label>
                    <select class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="input-destino" required>
                        <option value="">Sucursal de destino</option>
                    </select>
                </div>
                <div class="flex flex-col gap-space-xs">
                    <label class="text-label-sm text-on-surface-variant" for="input-vehiculo">Vehículo asignado</label>
                    <select class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="input-vehiculo" required>
                        <option value="">Elegí el vehículo</option>
                    </select>
                </div>
                <div class="flex flex-col gap-space-xs">
                    <label class="text-label-sm text-on-surface-variant" for="input-chofer">Chofer asignado</label>
                    <div class="flex gap-2">
                        <select class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="input-chofer" required>
                            <option value="">Elegí el chofer</option>
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
                <label class="text-label-sm text-on-surface-variant" for="filtro-fecha-desde">Salida desde</label>
                <input class="bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none w-40 text-on-surface" id="filtro-fecha-desde" type="date"/>
            </div>
            <div class="flex flex-col gap-space-xs">
                <label class="text-label-sm text-on-surface-variant" for="filtro-fecha-hasta">Salida hasta</label>
                <input class="bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none w-40 text-on-surface" id="filtro-fecha-hasta" type="date"/>
            </div>
            <div class="flex flex-col gap-space-xs">
                <label class="text-label-sm text-on-surface-variant" for="filtro-chofer">Chofer</label>
                <select class="bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="filtro-chofer">
                    <option value="">Todos los choferes</option>
                </select>
            </div>
            <div class="flex items-center gap-2 h-10">
                <button class="bg-primary hover:bg-primary-container text-on-primary px-4 py-2 rounded-lg text-label-md transition-colors" type="button" id="btn-filtrar">Filtrar</button>
                <button class="bg-surface-container hover:bg-surface-container-high text-on-surface px-4 py-2 rounded-lg text-label-md transition-colors" type="button" id="btn-limpiar">Limpiar</button>
            </div>
            <div class="flex items-center gap-2 ml-auto h-10">
                <input class="w-4 h-4 text-primary rounded accent-primary cursor-pointer" id="filtro-solo-activos" type="checkbox"/>
                <label class="text-sm text-on-surface select-none cursor-pointer" for="filtro-solo-activos">Solo viajes en curso</label>
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
                        <th id="th-id" class="py-3.5 px-4 font-headline-sm hover:text-primary transition-colors cursor-pointer whitespace-nowrap" title="Ordenar">ID</th>
                        <th id="th-salida" class="py-3.5 px-4 font-headline-sm hover:text-primary transition-colors cursor-pointer whitespace-nowrap" title="Ordenar">Salida</th>
                        <th id="th-llegada" class="py-3.5 px-4 font-headline-sm hover:text-primary transition-colors cursor-pointer whitespace-nowrap" title="Ordenar">Llegada</th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Ruta</th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Vehículo</th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Chofer</th>
                        <th class="py-3.5 px-4 font-headline-sm text-center whitespace-nowrap">Envíos</th>
                        <th class="py-3.5 px-4 font-headline-sm text-center whitespace-nowrap min-w-[170px]">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tabla-viajes" class="divide-y divide-surface-container text-sm text-on-surface"></tbody>
            </table>
        </div>
    </div>

    <!-- Cerrar viaje (procedimiento_cerrar_viaje): llegada real + envíos "En sucursal", todo o nada -->
    <dialog id="dialogo-cerrar" class="m-auto rounded-xl p-0 shadow-xl backdrop:bg-black/40 w-full max-w-md">
        <form method="dialog" id="form-cerrar" class="flex flex-col gap-space-md p-space-lg bg-surface-container-lowest">
            <h2 class="text-headline-md text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">flag</span>
                <span id="titulo-cerrar">Cerrar viaje</span>
            </h2>
            <p id="texto-cerrar" class="text-sm text-on-surface-variant"></p>
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

<?php fin_pagina('viajes.js'); ?>
