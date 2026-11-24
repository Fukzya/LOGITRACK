<?php require '../plantilla.php'; inicio_pagina('Sesiones', true); ?>

    <!-- Sesiones de la aplicación abiertas ahora -->
    <div class="bg-surface-container-lowest rounded-xl shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] flex flex-col overflow-hidden">
        <div class="flex items-center justify-between p-space-lg pb-space-md">
            <div>
                <h2 class="text-headline-md text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">devices</span>Sesiones abiertas
                </h2>
                <p class="text-xs text-on-surface-variant mt-1">Usuarios logueados en LogiTrack. Una sesión vence a los 30 minutos sin uso.</p>
            </div>
            <button id="btn-actualizar" class="bg-surface-container hover:bg-surface-container-high text-on-surface px-4 py-2 rounded-lg text-label-md transition-colors whitespace-nowrap flex items-center gap-1" type="button">
                <span class="material-symbols-outlined text-[18px]">refresh</span>Actualizar
            </button>
        </div>
        <div class="overflow-x-auto w-full">
            <table class="w-full min-w-[900px] text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container text-on-surface text-label-sm border-b border-surface-container-highest select-none">
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Usuario</th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Inicio</th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Último uso</th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">IP</th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Navegador</th>
                        <th class="py-3.5 px-4 font-headline-sm text-center whitespace-nowrap">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tabla-abiertas" class="divide-y divide-surface-container text-sm text-on-surface"></tbody>
            </table>
        </div>
    </div>

    <!-- Conexiones a la base (sesiones de base de datos) -->
    <div class="bg-surface-container-lowest rounded-xl shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] flex flex-col overflow-hidden">
        <div class="p-space-lg pb-space-md">
            <h2 class="text-headline-md text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">database</span>Conexiones a la base
            </h2>
            <p class="text-xs text-on-surface-variant mt-1">Cada conexión es una sesión de MariaDB. LogiTrack abre una por pedido y la cierra al terminar; un cliente como HeidiSQL la mantiene abierta.</p>
            <p id="esta-conexion" class="text-xs text-on-surface mt-2"></p>
        </div>
        <div class="overflow-x-auto w-full">
            <table class="w-full min-w-[900px] text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container text-on-surface text-label-sm border-b border-surface-container-highest select-none">
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Id</th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Usuario</th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Host</th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Base</th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Comando</th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Segundos</th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Consulta</th>
                    </tr>
                </thead>
                <tbody id="tabla-conexiones" class="divide-y divide-surface-container text-sm text-on-surface"></tbody>
            </table>
        </div>
    </div>

    <!-- Historial de sesiones cerradas -->
    <div class="bg-surface-container-lowest rounded-xl shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] flex flex-col overflow-hidden">
        <div class="flex flex-wrap items-end justify-between gap-space-md p-space-lg pb-space-md">
            <h2 class="text-headline-md text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">history</span>Historial de sesiones
            </h2>
            <div class="flex items-end gap-2">
                <div class="flex flex-col gap-space-xs">
                    <label class="text-label-sm text-on-surface-variant" for="filtro-username">Usuario</label>
                    <input class="bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="filtro-username" placeholder="Nombre de usuario" type="text"/>
                </div>
                <button id="btn-filtrar" class="bg-primary hover:bg-primary-container text-on-primary px-4 py-2 rounded-lg text-label-md transition-colors shadow-sm" type="button">Filtrar</button>
                <button id="btn-limpiar" class="bg-surface-container hover:bg-surface-container-high text-on-surface px-4 py-2 rounded-lg text-label-md transition-colors" type="button">Limpiar</button>
            </div>
        </div>
        <div class="overflow-x-auto w-full">
            <table class="w-full min-w-[850px] text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container text-on-surface text-label-sm border-b border-surface-container-highest select-none">
                        <th id="th-username" class="py-3.5 px-4 font-headline-sm hover:text-primary transition-colors cursor-pointer whitespace-nowrap">Usuario</th>
                        <th id="th-inicio" class="py-3.5 px-4 font-headline-sm hover:text-primary transition-colors cursor-pointer whitespace-nowrap">Inicio</th>
                        <th id="th-fin" class="py-3.5 px-4 font-headline-sm hover:text-primary transition-colors cursor-pointer whitespace-nowrap">Fin</th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">Duración</th>
                        <th id="th-motivo" class="py-3.5 px-4 font-headline-sm hover:text-primary transition-colors cursor-pointer whitespace-nowrap">Motivo de cierre</th>
                        <th class="py-3.5 px-4 font-headline-sm whitespace-nowrap">IP</th>
                    </tr>
                </thead>
                <tbody id="tabla-historial" class="divide-y divide-surface-container text-sm text-on-surface"></tbody>
            </table>
        </div>
        <div id="paginacion"></div>
    </div>

<?php fin_pagina('sesiones.js'); ?>
