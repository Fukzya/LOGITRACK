<?php require '../plantilla.php'; inicio_pagina('Nuevo envío'); ?>
<?php
// Asistente paso a paso: personas -> paquete -> viaje -> confirmar.
// Se guarda todo junto al final (api/asistente_envio.php, una sola transacción).
$tarjeta = 'bg-surface-container-lowest rounded-xl p-space-lg shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] flex flex-col gap-space-lg';
$input   = 'w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface';
$etiqueta = 'text-label-sm text-on-surface-variant';
$btnPrim = 'bg-primary hover:bg-primary-container text-on-primary px-5 py-2.5 rounded-lg text-label-md transition-colors shadow-sm disabled:opacity-40 disabled:cursor-not-allowed';
$btnSec  = 'bg-surface-container hover:bg-surface-container-high text-on-surface px-5 py-2.5 rounded-lg text-label-md transition-colors';
?>

    <!-- Indicador de pasos -->
    <ol id="pasos" class="grid grid-cols-4 gap-2">
        <?php foreach (['Personas', 'Paquete', 'Viaje', 'Confirmar'] as $i => $nombre): ?>
        <li data-indicador="<?= $i + 1 ?>" class="flex items-center gap-3 rounded-xl px-4 py-3 bg-surface-container-lowest border-2 border-transparent text-on-surface-variant">
            <span class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold bg-surface-container shrink-0"><?= $i + 1 ?></span>
            <span class="text-sm font-semibold"><?= $nombre ?></span>
        </li>
        <?php endforeach; ?>
    </ol>

    <!-- Paso 1: personas -->
    <section data-paso="1" class="<?= $tarjeta ?>">
        <div>
            <h2 class="text-headline-md text-on-surface">¿Quién envía y quién recibe?</h2>
            <p class="text-sm text-on-surface-variant mt-1">Buscalos por DNI, nombre o apellido. Si no están cargados, los das de alta desde acá.</p>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-space-lg">
            <div class="flex flex-col gap-2">
                <span class="text-sm font-semibold text-on-surface flex items-center gap-2"><span class="material-symbols-outlined text-primary text-[20px]">outbox</span>Remitente</span>
                <div id="sel-remitente"></div>
            </div>
            <div class="flex flex-col gap-2">
                <span class="text-sm font-semibold text-on-surface flex items-center gap-2"><span class="material-symbols-outlined text-primary text-[20px]">move_to_inbox</span>Destinatario</span>
                <div id="sel-destinatario"></div>

                <!-- Direcciones del destinatario -->
                <div id="bloque-direcciones" class="flex flex-col gap-2 mt-2" hidden>
                    <span class="<?= $etiqueta ?>">Dirección de entrega</span>
                    <div id="lista-direcciones" class="flex flex-col gap-2"></div>
                    <button type="button" id="btn-otra-direccion" class="text-sm text-primary hover:underline w-fit flex items-center gap-1">
                        <span class="material-symbols-outlined text-[18px]">add_location_alt</span>Agregar otra dirección
                    </button>
                    <form id="form-direccion" class="rounded-xl border border-dashed border-primary/50 bg-[#fffbf5] p-4 grid grid-cols-1 md:grid-cols-2 gap-3" hidden novalidate>
                        <label class="flex flex-col gap-1 text-xs text-on-surface-variant">Calle y número
                            <input name="calle_y_numero" maxlength="200" required class="<?= $input ?>"/></label>
                        <label class="flex flex-col gap-1 text-xs text-on-surface-variant">Localidad
                            <select name="localidad_cod" id="dir-localidad" required class="<?= $input ?>"><option value="">Elegí la localidad</option></select></label>
                        <div class="flex gap-2 md:col-span-2">
                            <button type="submit" class="bg-primary hover:bg-primary-container text-on-primary px-4 py-2 rounded-lg text-sm">Guardar dirección</button>
                            <button type="button" id="btn-cancelar-direccion" class="bg-surface-container hover:bg-surface-container-high px-4 py-2 rounded-lg text-sm">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="flex justify-end">
            <button type="button" class="<?= $btnPrim ?>" data-siguiente="2" id="btn-paso-2">Siguiente: el paquete</button>
        </div>
    </section>

    <!-- Paso 2: paquete -->
    <section data-paso="2" class="<?= $tarjeta ?>" hidden>
        <div>
            <h2 class="text-headline-md text-on-surface">El paquete</h2>
            <p class="text-sm text-on-surface-variant mt-1">Las sucursales vienen sugeridas según la provincia del remitente y la de la dirección de entrega. Podés cambiarlas.</p>
        </div>
        <form id="form-paquete" class="grid grid-cols-1 md:grid-cols-6 gap-space-md items-start" novalidate>
            <label class="flex flex-col gap-1 md:col-span-2 <?= $etiqueta ?>">Peso (kg)
                <input id="input-peso" type="number" min="0.01" max="999999.99" step="0.01" required placeholder="Ej: 2.5" class="<?= $input ?>"/></label>
            <label class="flex flex-col gap-1 md:col-span-2 <?= $etiqueta ?>">Tipo de contenido
                <select id="input-tipo" required class="<?= $input ?>"><option value="">Elegí el tipo</option></select></label>
            <label class="flex flex-col gap-1 md:col-span-2 <?= $etiqueta ?>">Fecha del envío
                <input id="input-fecha" type="date" required class="<?= $input ?>"/></label>
            <label class="flex flex-col gap-1 md:col-span-3 <?= $etiqueta ?>">Sucursal de origen
                <select id="input-suc-orig" required class="<?= $input ?>"><option value="">Elegí la sucursal</option></select>
                <small id="pista-orig" class="text-xs text-green-700"></small>
                <div id="otras-orig" class="flex flex-wrap items-center gap-1.5"></div></label>
            <label class="flex flex-col gap-1 md:col-span-3 <?= $etiqueta ?>">Sucursal de destino
                <select id="input-suc-dest" required class="<?= $input ?>"><option value="">Elegí la sucursal</option></select>
                <small id="pista-dest" class="text-xs text-green-700"></small>
                <div id="otras-dest" class="flex flex-wrap items-center gap-1.5"></div></label>
        </form>
        <div class="flex justify-between">
            <button type="button" class="<?= $btnSec ?>" data-anterior="1">Atrás</button>
            <button type="button" class="<?= $btnPrim ?>" data-siguiente="3">Siguiente: el viaje</button>
        </div>
    </section>

    <!-- Paso 3: viaje -->
    <section data-paso="3" class="<?= $tarjeta ?>" hidden>
        <div>
            <h2 class="text-headline-md text-on-surface">¿Cómo llega a destino?</h2>
            <p class="text-sm text-on-surface-variant mt-1">
                Viajes que todavía no salieron. La llegada estimada de cada viaje es su salida + 24 h.
                Con trasbordo, el segundo viaje sale de donde llega el primero y después de que llegue.
            </p>
        </div>

        <!-- Directos -->
        <div class="flex flex-col gap-2">
            <h3 class="text-sm font-semibold text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-green-600 text-[20px]">trending_flat</span>Directos
                <span id="cuenta-directos" class="text-xs font-normal text-on-surface-variant"></span></h3>
            <div id="lista-directos" class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-space-md"></div>
        </div>

        <!-- Con trasbordo -->
        <div class="flex flex-col gap-2">
            <h3 class="text-sm font-semibold text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-sky-600 text-[20px]">alt_route</span>Con un trasbordo
                <span id="cuenta-trasbordos" class="text-xs font-normal text-on-surface-variant"></span></h3>
            <div id="lista-trasbordos" class="grid grid-cols-1 xl:grid-cols-2 gap-space-md"></div>
        </div>

        <!-- Otras opciones -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-space-md">
            <button type="button" data-opcion-viaje="nuevo" class="opcion-viaje flex items-center gap-3 rounded-xl border-2 border-dashed border-primary/40 px-4 py-3 text-left hover:bg-[#fffbf5]">
                <span class="material-symbols-outlined text-primary">add_road</span>
                <span><span class="block text-sm font-semibold text-on-surface">Crear un viaje nuevo</span>
                      <span class="block text-xs text-on-surface-variant">Directo, con vehículo y chofer libres ese día</span></span>
            </button>
            <button type="button" data-opcion-viaje="ninguno" class="opcion-viaje flex items-center gap-3 rounded-xl border-2 border-dashed border-outline-variant px-4 py-3 text-left hover:bg-surface-container">
                <span class="material-symbols-outlined text-outline">schedule</span>
                <span><span class="block text-sm font-semibold text-on-surface">Asignar más tarde</span>
                      <span class="block text-xs text-on-surface-variant">El envío queda creado, pendiente de viaje</span></span>
            </button>
        </div>

        <!-- Viaje nuevo -->
        <form id="form-viaje-nuevo" class="rounded-xl border border-primary/30 bg-[#fffbf5] p-4 grid grid-cols-1 md:grid-cols-6 gap-3" hidden novalidate>
            <label class="flex flex-col gap-1 md:col-span-3 <?= $etiqueta ?>">Sale de
                <select id="nv-origen" required class="<?= $input ?>"><option value="">Sucursal de origen</option></select></label>
            <label class="flex flex-col gap-1 md:col-span-3 <?= $etiqueta ?>">Llega a
                <select id="nv-destino" required class="<?= $input ?>"><option value="">Sucursal de destino</option></select></label>
            <label class="flex flex-col gap-1 md:col-span-2 <?= $etiqueta ?>">Salida
                <input id="nv-salida" type="datetime-local" required class="<?= $input ?>"/>
                <small id="nv-llegada" class="text-xs text-on-surface-variant"></small></label>
            <label class="flex flex-col gap-1 md:col-span-2 <?= $etiqueta ?>">Vehículo libre ese día
                <select id="nv-vehiculo" required class="<?= $input ?>" disabled><option value="">Primero elegí la salida</option></select>
                <small class="text-xs text-on-surface-variant">Solo los que tienen capacidad para el paquete</small></label>
            <label class="flex flex-col gap-1 md:col-span-2 <?= $etiqueta ?>">Chofer libre ese día
                <select id="nv-chofer" required class="<?= $input ?>" disabled><option value="">Primero elegí el vehículo</option></select>
                <small id="nv-pista-chofer" class="text-xs text-on-surface-variant"></small></label>
        </form>

        <!-- Todos los viajes: el que asigna decide -->
        <details id="bloque-otros" class="rounded-xl border border-surface-container">
            <summary class="cursor-pointer px-4 py-3 text-sm font-semibold text-on-surface">
                Otros viajes programados <span id="cuenta-otros" class="font-normal text-on-surface-variant"></span>
            </summary>
            <div class="px-4 pb-4 flex flex-col gap-3">
                <input id="filtro-otros" type="search" placeholder="Filtrar por sucursal, provincia o número de viaje" class="<?= $input ?>"/>
                <div id="lista-otros" class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-space-md"></div>
            </div>
        </details>

        <div class="flex justify-between">
            <button type="button" class="<?= $btnSec ?>" data-anterior="2">Atrás</button>
            <button type="button" class="<?= $btnPrim ?>" data-siguiente="4">Siguiente: confirmar</button>
        </div>
    </section>

    <!-- Paso 4: confirmar -->
    <section data-paso="4" class="<?= $tarjeta ?>" hidden>
        <div>
            <h2 class="text-headline-md text-on-surface">Revisá y confirmá</h2>
            <p class="text-sm text-on-surface-variant mt-1">Se guarda todo junto: si algo falla (por ejemplo, el viaje se llenó mientras tanto) no se guarda nada.</p>
        </div>
        <dl id="resumen" class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-3 text-sm"></dl>
        <p id="error-confirmar" class="text-sm text-red-700 bg-red-50 rounded-lg px-4 py-3" hidden></p>
        <div class="flex justify-between">
            <button type="button" class="<?= $btnSec ?>" data-anterior="3">Atrás</button>
            <button type="button" class="<?= $btnPrim ?>" id="btn-confirmar">Crear envío</button>
        </div>
    </section>

    <!-- Resultado -->
    <section id="resultado" class="<?= $tarjeta ?> items-center text-center" hidden>
        <span class="material-symbols-outlined text-green-600 text-[56px]">task_alt</span>
        <h2 id="resultado-titulo" class="text-headline-md text-on-surface"></h2>
        <div class="flex flex-wrap justify-center gap-3">
            <a id="link-historial" class="<?= $btnSec ?>" href="#">Ver historial del envío</a>
            <a id="link-viaje" class="<?= $btnSec ?>" href="#">Ver sus viajes</a>
            <a class="<?= $btnPrim ?>" href="nuevo_envio.php">Cargar otro envío</a>
        </div>
    </section>

<?php fin_pagina('nuevo_envio.js', ['../selector_cliente.js']); ?>
