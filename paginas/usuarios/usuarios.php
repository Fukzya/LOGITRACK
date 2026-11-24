<?php require '../plantilla.php'; inicio_pagina('Gestión de usuarios', true); ?>

    <!-- Formulario de Nuevo Usuario -->
    <div class="bg-surface-container-lowest rounded-xl p-space-lg shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] flex flex-col gap-space-lg" id="nuevo-usuario-card">
        <div class="flex items-center justify-between">
            <h2 class="text-headline-md text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">manage_accounts</span>
                <span id="titulo-form">Nuevo usuario</span>
            </h2>
            <span class="text-xs text-on-surface-variant">Alta de credenciales y roles para operadores del sistema</span>
        </div>
        <form id="form-usuario" class="flex flex-col gap-space-md">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-space-md">
                <div class="flex flex-col gap-space-xs">
                    <label class="text-label-sm text-on-surface-variant" for="input-username">Nombre de usuario</label>
                    <input class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="input-username" placeholder="Usuario" required minlength="3" maxlength="50" pattern="[a-zA-Z0-9_.]{3,50}" title="Letras, números, punto o guion bajo" type="text"/>
                </div>
                <div class="flex flex-col gap-space-xs">
                    <label class="text-label-sm text-on-surface-variant" for="input-password">Contraseña (mín. 8)</label>
                    <input class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="input-password" placeholder="Contraseña inicial" required minlength="8" maxlength="72" autocomplete="new-password" type="password"/>
                </div>
                <div class="flex flex-col gap-space-xs">
                    <label class="text-label-sm text-on-surface-variant" for="input-rol">Rol</label>
                    <select class="w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface" id="input-rol" required>
                        <option value="operador">Operador</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
                <div class="flex flex-col gap-space-xs justify-end">
                    <button class="bg-primary hover:bg-primary-container text-on-primary px-4 py-2 rounded-lg text-label-md transition-colors shadow-sm whitespace-nowrap h-10" type="submit">Agregar</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Formulario de Cambio de Contraseña (Emergente) -->
    <form id="form-password" class="bg-[#ffedd5] border border-primary/30 rounded-xl p-space-lg shadow-sm flex flex-col gap-space-md" hidden>
        <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">key</span>
            <h3 class="text-headline-sm text-on-surface" id="titulo-password">Cambiar contraseña</h3>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-space-md items-end">
            <div class="flex flex-col gap-space-xs md:col-span-2">
                <label class="text-label-sm text-on-surface-variant" for="input-password-nueva">Nueva contraseña (mín. 8 caracteres)</label>
                <input class="w-full bg-surface-container-lowest px-3 py-2 rounded-lg text-sm border border-outline-variant focus:ring-1 focus:ring-primary outline-none text-on-surface" id="input-password-nueva" placeholder="Nueva contraseña" required minlength="8" maxlength="72" autocomplete="new-password" type="password"/>
            </div>
            <div class="flex gap-2">
                <button class="bg-primary hover:bg-primary-container text-on-primary px-4 py-2 rounded-lg text-label-md transition-colors shadow-sm whitespace-nowrap" type="submit">Guardar contraseña</button>
                <button class="bg-surface-container hover:bg-surface-container-high text-on-surface px-4 py-2 rounded-lg text-label-md transition-colors whitespace-nowrap" type="button" id="btn-cancelar-password">Cancelar</button>
            </div>
        </div>
    </form>

    <!-- Tarjeta de Tabla -->
    <div class="bg-surface-container-lowest rounded-xl shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] flex flex-col overflow-hidden">
        <div class="overflow-x-auto w-full">
            <table class="w-full min-w-[850px] text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container text-on-surface text-label-sm border-b border-surface-container-highest select-none">
                        <th id="th-id" class="py-3.5 px-4 font-headline-sm hover:text-primary transition-colors cursor-pointer whitespace-nowrap" title="Ordenar">ID</th>
                        <th id="th-username" class="py-3.5 px-4 font-headline-sm hover:text-primary transition-colors cursor-pointer whitespace-nowrap" title="Ordenar">Usuario</th>
                        <th id="th-rol" class="py-3.5 px-4 font-headline-sm hover:text-primary transition-colors cursor-pointer whitespace-nowrap" title="Ordenar">Rol</th>
                        <th class="py-3.5 px-4 font-headline-sm text-center whitespace-nowrap">Estado</th>
                        <th class="py-3.5 px-4 font-headline-sm text-center whitespace-nowrap min-w-[260px]">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tabla-usuarios" class="divide-y divide-surface-container text-sm text-on-surface"></tbody>
            </table>
        </div>
    </div>

<?php fin_pagina('usuarios.js'); ?>
