<?php require '../plantilla.php'; inicio_pagina('Envíos'); ?>

    <!-- Formulario de Nuevo Envío / Edición -->
    <!-- El alta se hace con el procedimiento almacenado procedimiento_crear_envio, en una transacción. -->
    <div class="bg-surface-container-lowest rounded-xl p-space-lg shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] flex flex-col gap-space-lg" id="nuevo-envio-card">
        <div class="flex items-center justify-between">
            <h2 class="text-headline-md text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">local_shipping</span>
                <span id="titulo-form">Nuevo envío</span>
            </h2>
            <span class="text-xs text-on-surface-variant">Complete los datos obligatorios para registrar un despacho</span>
        </div>
        <form id="form-envio" class="flex flex-col gap-space-md">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-space-md">
                <div class="flex flex-col gap-space-xs">
                    <label class="text-label-sm text-on-surface-variant" for="input-fecha">Fecha</label>
                    <input class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="input-fecha" data-hasta-ahora type="date" required/>
                </div>
                <div class="flex flex-col gap-space-xs">
                    <label class="text-label-sm text-on-surface-variant" for="input-peso">Peso (kg)</label>
                    <input class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="input-peso" placeholder="0.00" required min="0.01" max="999999.99" step="0.01" type="number"/>
                </div>
                <div class="flex flex-col gap-space-xs">
                    <label class="text-label-sm text-on-surface-variant" for="input-tipo">Tipo de contenido</label>
                    <select class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="input-tipo" required>
                        <option value="">Tipo de contenido</option>
                    </select>
                </div>
                <div class="flex flex-col gap-space-xs">
                    <label class="text-label-sm text-on-surface-variant" for="input-remitente">DNI remitente</label>
                    <input class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="input-remitente" placeholder="Ej: 10002166" required min="1" max="99999999" type="number"/>
                </div>
                <div class="flex flex-col gap-space-xs">
                    <label class="text-label-sm text-on-surface-variant" for="input-destinatario">DNI destinatario</label>
                    <input class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="input-destinatario" placeholder="Ej: 10003330" required min="1" max="99999999" type="number"/>
                </div>
                <div class="flex flex-col gap-space-xs">
                    <label class="text-label-sm text-on-surface-variant" for="input-suc-orig">Sucursal de origen</label>
                    <select class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="input-suc-orig" required>
                        <option value="">Sucursal de origen</option>
                    </select>
                </div>
                <div class="flex flex-col gap-space-xs">
                    <label class="text-label-sm text-on-surface-variant" for="input-suc-dest">Sucursal de destino</label>
                    <select class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="input-suc-dest" required>
                        <option value="">Sucursal de destino</option>
                    </select>
                </div>
                <div class="flex flex-col gap-space-xs">
                    <label class="text-label-sm text-on-surface-variant" for="input-direccion">ID dirección de entrega</label>
                    <div class="flex gap-2">
                        <input class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="input-direccion" placeholder="ID dir" required min="1" type="number"/>
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
            <h3 class="text-headline-sm text-on-surface">Filtros y Búsqueda Avanzada</h3>
            <span class="material-symbols-outlined text-outline">tune</span>
        </div>
        <div class="flex flex-wrap items-end gap-space-md">
            <div class="flex flex-col gap-space-xs">
                <label class="text-label-sm text-on-surface-variant" for="filtro-fecha-desde">Fecha desde</label>
                <input class="bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none w-40 text-on-surface" id="filtro-fecha-desde" type="date"/>
            </div>
            <div class="flex flex-col gap-space-xs">
                <label class="text-label-sm text-on-surface-variant" for="filtro-fecha-hasta">Fecha hasta</label>
                <input class="bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none w-40 text-on-surface" id="filtro-fecha-hasta" type="date"/>
            </div>
            <div class="flex flex-col gap-space-xs">
                <label class="text-label-sm text-on-surface-variant" for="filtro-sucursal-orig">Sucursal de origen</label>
                <select class="bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="filtro-sucursal-orig">
                    <option value="">Todas las sucursales de origen</option>
                </select>
            </div>
            <div class="flex flex-col gap-space-xs">
                <label class="text-label-sm text-on-surface-variant" for="filtro-tipo">Tipos</label>
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
                <label class="text-sm text-on-surface select-none cursor-pointer" for="filtro-mostrar-inactivos">Mostrar envíos inactivos</label>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tarjeta Principal de Tabla -->
    <div class="bg-surface-container-lowest rounded-xl shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] flex flex-col overflow-hidden">
        <!-- Paginación superior dinámica -->
        <div id="paginacion"></div>

        <!-- Tabla con desplazamiento horizontal para evitar cortes -->
        <div class="overflow-x-auto w-full">
            <table class="w-full min-w-[1150px] text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container text-on-surface text-label-sm border-b border-surface-container-highest select-none">
                        <th id="th-nro" class="py-3.5 px-4 font-headline-sm hover:text-primary transition-colors cursor-pointer whitespace-nowrap" title="Ordenar">Nro</th>
                        <th id="th-fecha" class="py-3.5 px-4 font-headline-sm hover:text-primary transition-colors cursor-pointer whitespace-nowrap" title="Ordenar">Fecha</th>
                        <th id="th-peso" class="py-3.5 px-4 font-headline-sm text-right hover:text-primary transition-colors cursor-pointer whitespace-nowrap" title="Ordenar">Peso (kg)</th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Tipo</th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Remitente</th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Destinatario</th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Origen</th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Destino</th>
                        <th class="py-3.5 px-4 font-headline-sm min-w-[200px]">Dirección de entrega</th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Último estado</th>
                        <th class="py-3.5 px-4 font-headline-sm text-center whitespace-nowrap" title="Baja lógica: un envío inactivo fue dado de baja">Registro</th>
                        <th class="py-3.5 px-4 font-headline-sm text-center whitespace-nowrap min-w-[170px]">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tabla-envios" class="divide-y divide-surface-container text-sm text-on-surface"></tbody>
            </table>
        </div>
    </div>

    <!-- Cambiar estado (procedimiento_registrar_estado_envio): agrega una fila al historial -->
    <dialog id="dialogo-estado" class="m-auto rounded-xl p-0 shadow-xl backdrop:bg-black/40 w-full max-w-lg">
        <form method="dialog" id="form-estado" class="flex flex-col gap-space-md p-space-lg bg-surface-container-lowest">
            <h2 class="text-headline-md text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">published_with_changes</span>
                <span id="titulo-estado">Cambiar estado</span>
            </h2>
            <p id="texto-estado" class="text-sm text-on-surface-variant"></p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-space-md">
                <div class="flex flex-col gap-space-xs">
                    <label class="text-label-sm text-on-surface-variant" for="estado-nuevo">Nuevo estado</label>
                    <select class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="estado-nuevo" required>
                        <option value="">Elegí el estado</option>
                    </select>
                </div>
                <div class="flex flex-col gap-space-xs">
                    <label class="text-label-sm text-on-surface-variant" for="estado-fecha">Fecha y hora</label>
                    <input class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="estado-fecha" data-hasta-ahora type="datetime-local" required/>
                </div>
                <div class="flex flex-col gap-space-xs md:col-span-2">
                    <label class="text-label-sm text-on-surface-variant" for="estado-sucursal">Sucursal donde está el envío</label>
                    <select class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="estado-sucursal" required>
                        <option value="">Elegí la sucursal</option>
                    </select>
                </div>
                <div class="flex flex-col gap-space-xs md:col-span-2">
                    <label class="text-label-sm text-on-surface-variant" for="estado-observacion">Observación (opcional)</label>
                    <input class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="estado-observacion" maxlength="1000" type="text"/>
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <button class="bg-surface-container hover:bg-surface-container-high text-on-surface px-4 py-2 rounded-lg text-label-md transition-colors" type="button" id="btn-cancelar-estado">Cancelar</button>
                <button class="bg-primary hover:bg-primary-container text-on-primary px-4 py-2 rounded-lg text-label-md transition-colors" type="submit">Registrar estado</button>
            </div>
        </form>
    </dialog>

<?php fin_pagina('envios.js'); ?>
