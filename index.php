<?php
require_once __DIR__ . '/config/session.php';
verificar_sesion_pagina('');
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");

$esAdmin = ($_SESSION['rol'] ?? '') === 'admin';
$username = $_SESSION['username'] ?? 'Usuario';
$rol = $_SESSION['rol'] ?? 'operador';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>LogiTrack - Menú Principal</title>
    <style>
        [hidden] { display: none !important; }
        @layer base {
            html, body { margin: 0; padding: 0; }
            body { overscroll-behavior: none; }
            main > :first-child { margin-top: 0 !important; }
            main > :last-child { margin-bottom: 0 !important; }
        }
        /* Scrollbar elegante */
        ::-webkit-scrollbar {
            width: 7px;
            height: 7px;
        }
        ::-webkit-scrollbar-track {
            background: #faf6f0;
        }
        ::-webkit-scrollbar-thumb {
            background: #d8dadc;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #bec6e0;
        }
    </style>
    <script src="assets/tailwind.js"></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "tertiary-fixed-dim": "#ffb690",
                        "secondary-fixed-dim": "#bec6e0",
                        "on-tertiary-container": "#ffd2bd",
                        "on-tertiary": "#ffffff",
                        "tertiary-fixed": "#ffdbca",
                        "secondary": "#565e74",
                        "primary": "#c2410c",
                        "surface-container-highest": "#e0e3e5",
                        "on-secondary-fixed-variant": "#3f465c",
                        "background": "#faf6f0",
                        "on-primary-fixed": "#0f0069",
                        "secondary-container": "#ffedd5",
                        "inverse-surface": "#2d3133",
                        "tertiary-container": "#a04500",
                        "on-secondary": "#ffffff",
                        "secondary-fixed": "#dae2fd",
                        "error-container": "#ffdad6",
                        "on-error": "#ffffff",
                        "surface": "#faf6f0",
                        "on-error-container": "#93000a",
                        "on-primary": "#ffffff",
                        "outline-variant": "#c7c4d8",
                        "surface-bright": "#faf6f0",
                        "on-primary-fixed-variant": "#3323cc",
                        "surface-container": "#f5efe6",
                        "surface-container-low": "#fef3c7",
                        "surface-container-high": "#f0e6dc",
                        "surface-dim": "#d8dadc",
                        "primary-container": "#ea580c",
                        "surface-variant": "#e0e3e5",
                        "inverse-primary": "#c3c0ff",
                        "error": "#ba1a1a",
                        "on-tertiary-fixed": "#341100",
                        "primary-fixed-dim": "#c3c0ff",
                        "on-surface-variant": "#464555",
                        "on-background": "#191c1e",
                        "surface-container-lowest": "#ffffff",
                        "on-primary-container": "#ffedd5",
                        "inverse-on-surface": "#eff1f3",
                        "on-surface": "#1c1917",
                        "on-secondary-container": "#9a3412",
                        "outline": "#777587",
                        "tertiary": "#d97706",
                        "on-secondary-fixed": "#131b2e",
                        "primary-fixed": "#e2dfff",
                        "surface-tint": "#c2410c",
                        "on-tertiary-fixed-variant": "#783200"
                    },
                    borderRadius: {
                        "DEFAULT": "0.125rem",
                        "lg": "0.25rem",
                        "xl": "0.5rem",
                        "full": "0.75rem"
                    },
                    spacing: {
                        "space-lg": "1.5rem",
                        "space-xs": "0.25rem",
                        "margin": "1.5rem",
                        "space-md": "1rem",
                        "space-sm": "0.5rem",
                        "gutter": "1.5rem",
                        "space-xl": "2rem"
                    },
                    fontFamily: {
                        "label-md": ["Inter", "sans-serif"],
                        "body-sm": ["Inter", "sans-serif"],
                        "label-sm": ["Inter", "sans-serif"],
                        "headline-sm": ["Inter", "sans-serif"],
                        "headline-lg": ["Inter", "sans-serif"],
                        "headline-md": ["Inter", "sans-serif"],
                        "headline-xl": ["Inter", "sans-serif"],
                        "body-md": ["Inter", "sans-serif"],
                        "body-lg": ["Inter", "sans-serif"]
                    },
                    fontSize: {
                        "label-md": ["14px", { "lineHeight": "20px", "fontWeight": "500" }],
                        "body-sm": ["12px", { "lineHeight": "16px", "fontWeight": "400" }],
                        "label-sm": ["12px", { "lineHeight": "16px", "fontWeight": "500" }],
                        "headline-sm": ["16px", { "lineHeight": "24px", "fontWeight": "600" }],
                        "headline-lg": ["24px", { "lineHeight": "32px", "letterSpacing": "-0.01em", "fontWeight": "600" }],
                        "headline-md": ["20px", { "lineHeight": "28px", "fontWeight": "600" }],
                        "headline-xl": ["30px", { "lineHeight": "36px", "letterSpacing": "-0.02em", "fontWeight": "600" }],
                        "body-md": ["14px", { "lineHeight": "20px", "fontWeight": "400" }],
                        "body-lg": ["16px", { "lineHeight": "24px", "fontWeight": "400" }]
                    }
                }
            }
        };
    </script>
    <link href="assets/material-symbols.css" rel="stylesheet"/>
    <link href="assets/inter.css" rel="stylesheet"/>
</head>
<body class="bg-[#faf6f0] font-body text-on-surface">

    <!-- Menú lateral izquierdo -->
    <aside class="fixed left-0 top-0 h-full w-72 bg-[#2d1810] z-50 flex flex-col justify-between py-6 px-4 overflow-y-auto text-orange-200">
        <div class="flex flex-col gap-6">
            <div class="flex items-center gap-3 px-3">
                <div class="w-8 h-8 rounded-lg bg-primary flex items-center justify-center text-on-primary font-bold">L</div>
                <span class="text-headline-sm text-white tracking-tight">LogiTrack</span>
            </div>
            <nav class="flex flex-col gap-5 text-sm" data-active-classes="bg-primary text-on-primary font-bold rounded-lg">
                <div>
                    <a class="flex items-center gap-3 px-3 py-2 rounded-lg bg-primary text-on-primary font-bold transition-colors" href="index.php">
                        <span class="material-symbols-outlined text-[20px]">dashboard</span>Menú Principal
                    </a>
                </div>

                <div class="flex flex-col gap-1">
                    <span class="px-3 text-xs uppercase tracking-wider text-orange-400/70 font-headline-sm mb-1">Operaciones</span>
                    <a class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-[#3d241c] hover:text-white transition-colors" data-path="nuevo-envio" href="paginas/nuevo_envio/nuevo_envio.php">
                        <span class="material-symbols-outlined text-[20px]">add_box</span>Nuevo envío guiado
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-[#3d241c] hover:text-white transition-colors" data-path="envios" href="paginas/envios/envios.php">
                        <span class="material-symbols-outlined text-[20px]">local_shipping</span>Envíos
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-[#3d241c] hover:text-white transition-colors" data-path="viajes" href="paginas/viajes/viajes.php">
                        <span class="material-symbols-outlined text-[20px]">route</span>Viajes
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-[#3d241c] hover:text-white transition-colors" data-path="viaje-envio" href="paginas/viaje_envio/viaje_envio.php">
                        <span class="material-symbols-outlined text-[20px]">link</span>Viaje-Envío
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-[#3d241c] hover:text-white transition-colors" data-path="historial-de-estados" href="paginas/historial_estado/historial_estado.php">
                        <span class="material-symbols-outlined text-[20px]">history</span>Historial de Estados
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-[#3d241c] hover:text-white transition-colors" data-path="incidentes" href="paginas/incidentes/incidentes.php">
                        <span class="material-symbols-outlined text-[20px]">warning</span>Incidentes
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-[#3d241c] hover:text-white transition-colors" data-path="resumen-por-sucursal" href="paginas/resumen_sucursales/resumen_sucursales.php">
                        <span class="material-symbols-outlined text-[20px]">monitoring</span>Resumen por sucursal
                    </a>
                </div>

                <div class="flex flex-col gap-1">
                    <span class="px-3 text-xs uppercase tracking-wider text-orange-400/70 font-headline-sm mb-1">Flota y Personal</span>
                    <a class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-[#3d241c] hover:text-white transition-colors" data-path="vehiculos" href="paginas/vehiculos/vehiculos.php">
                        <span class="material-symbols-outlined text-[20px]">directions_car</span>Vehículos
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-[#3d241c] hover:text-white transition-colors" data-path="tipos-de-vehiculo" href="paginas/tipo_vehiculo/tipo_vehiculo.php">
                        <span class="material-symbols-outlined text-[20px]">category</span>Tipos de Vehículo
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-[#3d241c] hover:text-white transition-colors" data-path="choferes" href="paginas/choferes/choferes.php">
                        <span class="material-symbols-outlined text-[20px]">badge</span>Choferes
                    </a>
                </div>

                <div class="flex flex-col gap-1">
                    <span class="px-3 text-xs uppercase tracking-wider text-orange-400/70 font-headline-sm mb-1">Clientes y Red</span>
                    <a class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-[#3d241c] hover:text-white transition-colors" data-path="clientes" href="paginas/clientes/clientes.php">
                        <span class="material-symbols-outlined text-[20px]">group</span>Clientes
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-[#3d241c] hover:text-white transition-colors" data-path="sucursales" href="paginas/sucursales/sucursales.php">
                        <span class="material-symbols-outlined text-[20px]">store</span>Sucursales
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-[#3d241c] hover:text-white transition-colors" data-path="direcciones" href="paginas/direcciones/direcciones.php">
                        <span class="material-symbols-outlined text-[20px]">location_on</span>Direcciones
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-[#3d241c] hover:text-white transition-colors" data-path="provincias" href="paginas/provincias/provincias.php">
                        <span class="material-symbols-outlined text-[20px]">map</span>Provincias
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-[#3d241c] hover:text-white transition-colors" data-path="localidades" href="paginas/localidades/localidades.php">
                        <span class="material-symbols-outlined text-[20px]">pin_drop</span>Localidades
                    </a>
                </div>

                <div class="flex flex-col gap-1">
                    <span class="px-3 text-xs uppercase tracking-wider text-orange-400/70 font-headline-sm mb-1">Configuración</span>
                    <a class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-[#3d241c] hover:text-white transition-colors" data-path="tipos-de-contenido" href="paginas/tipo_contenido/tipo_contenido.php">
                        <span class="material-symbols-outlined text-[20px]">inventory_2</span>Tipos de Contenido
                    </a>
                    <?php if ($esAdmin): ?>
                    <a class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-[#3d241c] hover:text-white transition-colors" data-path="gestion-de-usuarios" href="paginas/usuarios/usuarios.php">
                        <span class="material-symbols-outlined text-[20px]">manage_accounts</span>Gestión de usuarios
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-[#3d241c] hover:text-white transition-colors" data-path="sesiones" href="paginas/sesiones/sesiones.php">
                        <span class="material-symbols-outlined text-[20px]">devices</span>Sesiones
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-[#3d241c] hover:text-white transition-colors" data-path="auditoria" href="paginas/auditoria/auditoria.php">
                        <span class="material-symbols-outlined text-[20px]">policy</span>Auditoría
                    </a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>

        <div class="pt-4 border-t border-[#3d241c] flex items-center justify-between px-2">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center">
                    <span class="material-symbols-outlined text-on-primary text-[18px]">person</span>
                </div>
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-white"><?= htmlspecialchars($username) ?></span>
                    <span class="text-xs text-orange-300/70"><?= htmlspecialchars($rol) ?></span>
                </div>
            </div>
            <a class="text-orange-300/70 hover:text-white transition-colors" href="login.html" onclick="cerrarSesion(event)" title="Cerrar sesión">
                <span class="material-symbols-outlined text-[20px]">logout</span>
            </a>
        </div>
    </aside>

    <!-- Contenido principal -->
    <div class="pl-72">
        <header class="fixed top-0 left-72 right-0 h-16 bg-surface/80 backdrop-blur-xl shadow-[0_1px_8px_rgba(0,0,0,0.04)] z-40 flex items-center justify-end px-8">
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center">
                        <span class="material-symbols-outlined text-on-primary text-[18px]">person</span>
                    </div>
                    <span class="text-xs font-semibold uppercase text-primary"><?= htmlspecialchars($rol) ?></span>
                </div>
            </div>
        </header>

        <main class="relative pt-16 bg-[#faf6f0] min-h-screen p-8">
            <div class="flex flex-col w-full gap-space-xl pb-16">

                <!-- Header de bienvenida -->
                <div class="flex flex-col gap-2">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-headline-xl text-on-surface">Menú Principal</h1>
                            <p class="text-sm text-on-surface-variant mt-1">Bienvenido a LogiTrack. Seleccione un módulo para gestionar las operaciones y catálogos del sistema.</p>
                        </div>
                        <div class="flex items-center gap-2 bg-surface-container px-4 py-2 rounded-xl">
                            <span class="material-symbols-outlined text-primary text-[20px]">account_circle</span>
                            <span class="text-sm text-on-surface">Sesión: <strong class="font-semibold"><?= htmlspecialchars($username) ?></strong> (<?= ucfirst(htmlspecialchars($rol)) ?>)</span>
                        </div>
                    </div>
                </div>

                <!-- Acceso rápido: asistente paso a paso (envío + viaje en un solo recorrido) -->
                <a href="paginas/nuevo_envio/nuevo_envio.php" class="group flex items-center gap-5 rounded-2xl bg-primary text-on-primary p-6 shadow-md hover:bg-primary-container transition-colors">
                    <div class="w-14 h-14 rounded-xl bg-white/15 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-[32px]">add_box</span>
                    </div>
                    <div class="flex-1">
                        <h2 class="text-headline-md">Nuevo envío</h2>
                        <p class="text-sm text-on-primary-container mt-1">Paso a paso: buscás al remitente y al destinatario por nombre o DNI, cargás el paquete y elegís el viaje. Sin códigos.</p>
                    </div>
                    <span class="material-symbols-outlined text-[28px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
                </a>

                <!-- Tablero: números que salen de las vistas (api/tablero.php) -->
                <div class="flex flex-col gap-4">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[22px]">monitoring</span>
                        <h2 class="text-headline-md text-on-surface">Hoy</h2>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-space-md">
                        <a href="paginas/envios/envios.php?solo_pendientes=1" class="bg-surface-container-lowest rounded-xl p-5 shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] border border-transparent hover:border-primary/40 hover:shadow-md transition-all">
                            <p class="text-xs text-on-surface-variant">Envíos pendientes</p>
                            <p class="text-headline-xl text-on-surface mt-1" id="tablero-pendientes">…</p>
                            <p class="text-xs text-on-surface-variant mt-1" id="tablero-pendientes-mes"></p>
                        </a>
                        <a href="paginas/viajes/viajes.php?solo_activos=1" class="bg-surface-container-lowest rounded-xl p-5 shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] border border-transparent hover:border-primary/40 hover:shadow-md transition-all">
                            <p class="text-xs text-on-surface-variant">Viajes en curso</p>
                            <p class="text-headline-xl text-on-surface mt-1" id="tablero-en-curso">…</p>
                            <p class="text-xs text-on-surface-variant mt-1" id="tablero-atrasados"></p>
                        </a>
                        <a id="tablero-link-proximos" href="paginas/viajes/viajes.php" class="bg-surface-container-lowest rounded-xl p-5 shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] border border-transparent hover:border-primary/40 hover:shadow-md transition-all">
                            <p class="text-xs text-on-surface-variant">Viajes en los próximos 7 días</p>
                            <p class="text-headline-xl text-on-surface mt-1" id="tablero-proximos">…</p>
                            <p class="text-xs text-on-surface-variant mt-1">Programados, todavía no salieron</p>
                        </a>
                        <a href="paginas/clientes/clientes.php" class="bg-surface-container-lowest rounded-xl p-5 shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] border border-transparent hover:border-primary/40 hover:shadow-md transition-all">
                            <p class="text-xs text-on-surface-variant">Clientes con más envíos</p>
                            <ol class="text-sm text-on-surface mt-2 flex flex-col gap-0.5" id="tablero-clientes"></ol>
                        </a>
                    </div>
                </div>

                <!-- Sección: Operaciones -->
                <div class="flex flex-col gap-4">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[22px]">local_shipping</span>
                        <h2 class="text-headline-md text-on-surface">Operaciones</h2>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-space-md">
                        <a href="paginas/envios/envios.php" class="group bg-surface-container-lowest rounded-xl p-5 shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] border border-transparent hover:border-primary/40 hover:shadow-md transition-all flex flex-col justify-between">
                            <div class="flex items-start justify-between">
                                <div class="w-10 h-10 rounded-lg bg-[#fef3c7] flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors">
                                    <span class="material-symbols-outlined text-[22px]">local_shipping</span>
                                </div>
                                <span class="material-symbols-outlined text-outline group-hover:text-primary group-hover:translate-x-1 transition-all text-[20px]">arrow_forward</span>
                            </div>
                            <div class="mt-4">
                                <h3 class="text-headline-sm text-on-surface group-hover:text-primary transition-colors">Envíos</h3>
                                <p class="text-xs text-on-surface-variant mt-1">Gestión, alta, control y búsqueda de despachos y paquetes de clientes.</p>
                            </div>
                        </a>

                        <a href="paginas/viajes/viajes.php" class="group bg-surface-container-lowest rounded-xl p-5 shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] border border-transparent hover:border-primary/40 hover:shadow-md transition-all flex flex-col justify-between">
                            <div class="flex items-start justify-between">
                                <div class="w-10 h-10 rounded-lg bg-[#fef3c7] flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors">
                                    <span class="material-symbols-outlined text-[22px]">route</span>
                                </div>
                                <span class="material-symbols-outlined text-outline group-hover:text-primary group-hover:translate-x-1 transition-all text-[20px]">arrow_forward</span>
                            </div>
                            <div class="mt-4">
                                <h3 class="text-headline-sm text-on-surface group-hover:text-primary transition-colors">Viajes</h3>
                                <p class="text-xs text-on-surface-variant mt-1">Planificación y seguimiento de traslados entre sucursales con vehículos y choferes.</p>
                            </div>
                        </a>

                        <a href="paginas/viaje_envio/viaje_envio.php" class="group bg-surface-container-lowest rounded-xl p-5 shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] border border-transparent hover:border-primary/40 hover:shadow-md transition-all flex flex-col justify-between">
                            <div class="flex items-start justify-between">
                                <div class="w-10 h-10 rounded-lg bg-[#fef3c7] flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors">
                                    <span class="material-symbols-outlined text-[22px]">link</span>
                                </div>
                                <span class="material-symbols-outlined text-outline group-hover:text-primary group-hover:translate-x-1 transition-all text-[20px]">arrow_forward</span>
                            </div>
                            <div class="mt-4">
                                <h3 class="text-headline-sm text-on-surface group-hover:text-primary transition-colors">Viaje-Envío</h3>
                                <p class="text-xs text-on-surface-variant mt-1">Carga y vinculación de paquetes transportados en cada viaje logístico.</p>
                            </div>
                        </a>

                        <a href="paginas/historial_estado/historial_estado.php" class="group bg-surface-container-lowest rounded-xl p-5 shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] border border-transparent hover:border-primary/40 hover:shadow-md transition-all flex flex-col justify-between">
                            <div class="flex items-start justify-between">
                                <div class="w-10 h-10 rounded-lg bg-[#fef3c7] flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors">
                                    <span class="material-symbols-outlined text-[22px]">history</span>
                                </div>
                                <span class="material-symbols-outlined text-outline group-hover:text-primary group-hover:translate-x-1 transition-all text-[20px]">arrow_forward</span>
                            </div>
                            <div class="mt-4">
                                <h3 class="text-headline-sm text-on-surface group-hover:text-primary transition-colors">Historial de Estados</h3>
                                <p class="text-xs text-on-surface-variant mt-1">Línea de tiempo, eventos y trazabilidad en los cambios de estado de los envíos.</p>
                            </div>
                        </a>

                        <a href="paginas/incidentes/incidentes.php" class="group bg-surface-container-lowest rounded-xl p-5 shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] border border-transparent hover:border-primary/40 hover:shadow-md transition-all flex flex-col justify-between">
                            <div class="flex items-start justify-between">
                                <div class="w-10 h-10 rounded-lg bg-[#fef3c7] flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors">
                                    <span class="material-symbols-outlined text-[22px]">warning</span>
                                </div>
                                <span class="material-symbols-outlined text-outline group-hover:text-primary group-hover:translate-x-1 transition-all text-[20px]">arrow_forward</span>
                            </div>
                            <div class="mt-4">
                                <h3 class="text-headline-sm text-on-surface group-hover:text-primary transition-colors">Incidentes</h3>
                                <p class="text-xs text-on-surface-variant mt-1">Registro de demoras, desvíos o eventualidades que afecten los viajes en curso.</p>
                            </div>
                        </a>

                        <a href="paginas/resumen_sucursales/resumen_sucursales.php" class="group bg-surface-container-lowest rounded-xl p-5 shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] border border-transparent hover:border-primary/40 hover:shadow-md transition-all flex flex-col justify-between">
                            <div class="flex items-start justify-between">
                                <div class="w-10 h-10 rounded-lg bg-[#fef3c7] flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors">
                                    <span class="material-symbols-outlined text-[22px]">monitoring</span>
                                </div>
                                <span class="material-symbols-outlined text-outline group-hover:text-primary group-hover:translate-x-1 transition-all text-[20px]">arrow_forward</span>
                            </div>
                            <div class="mt-4">
                                <h3 class="text-headline-sm text-on-surface group-hover:text-primary transition-colors">Resumen por sucursal</h3>
                                <p class="text-xs text-on-surface-variant mt-1">Envíos despachados, entregas, viajes y envíos pendientes de cada sucursal en un período.</p>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Sección: Flota y Personal -->
                <div class="flex flex-col gap-4">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[22px]">badge</span>
                        <h2 class="text-headline-md text-on-surface">Flota y Personal</h2>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-space-md">
                        <a href="paginas/vehiculos/vehiculos.php" class="group bg-surface-container-lowest rounded-xl p-5 shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] border border-transparent hover:border-primary/40 hover:shadow-md transition-all flex flex-col justify-between">
                            <div class="flex items-start justify-between">
                                <div class="w-10 h-10 rounded-lg bg-[#fef3c7] flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors">
                                    <span class="material-symbols-outlined text-[22px]">directions_car</span>
                                </div>
                                <span class="material-symbols-outlined text-outline group-hover:text-primary group-hover:translate-x-1 transition-all text-[20px]">arrow_forward</span>
                            </div>
                            <div class="mt-4">
                                <h3 class="text-headline-sm text-on-surface group-hover:text-primary transition-colors">Vehículos</h3>
                                <p class="text-xs text-on-surface-variant mt-1">Parque automotor, patentes, marcas, modelos y estado operativo de las unidades.</p>
                            </div>
                        </a>

                        <a href="paginas/tipo_vehiculo/tipo_vehiculo.php" class="group bg-surface-container-lowest rounded-xl p-5 shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] border border-transparent hover:border-primary/40 hover:shadow-md transition-all flex flex-col justify-between">
                            <div class="flex items-start justify-between">
                                <div class="w-10 h-10 rounded-lg bg-[#fef3c7] flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors">
                                    <span class="material-symbols-outlined text-[22px]">category</span>
                                </div>
                                <span class="material-symbols-outlined text-outline group-hover:text-primary group-hover:translate-x-1 transition-all text-[20px]">arrow_forward</span>
                            </div>
                            <div class="mt-4">
                                <h3 class="text-headline-sm text-on-surface group-hover:text-primary transition-colors">Tipos de Vehículo</h3>
                                <p class="text-xs text-on-surface-variant mt-1">Clasificación de vehículos por tamaño, capacidad de carga y requerimientos técnicos.</p>
                            </div>
                        </a>

                        <a href="paginas/choferes/choferes.php" class="group bg-surface-container-lowest rounded-xl p-5 shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] border border-transparent hover:border-primary/40 hover:shadow-md transition-all flex flex-col justify-between">
                            <div class="flex items-start justify-between">
                                <div class="w-10 h-10 rounded-lg bg-[#fef3c7] flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors">
                                    <span class="material-symbols-outlined text-[22px]">badge</span>
                                </div>
                                <span class="material-symbols-outlined text-outline group-hover:text-primary group-hover:translate-x-1 transition-all text-[20px]">arrow_forward</span>
                            </div>
                            <div class="mt-4">
                                <h3 class="text-headline-sm text-on-surface group-hover:text-primary transition-colors">Choferes</h3>
                                <p class="text-xs text-on-surface-variant mt-1">Nómina de conductores autorizados, licencias y habilitaciones vigentes.</p>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Sección: Clientes y Red -->
                <div class="flex flex-col gap-4">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[22px]">store</span>
                        <h2 class="text-headline-md text-on-surface">Clientes y Red</h2>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-space-md">
                        <a href="paginas/clientes/clientes.php" class="group bg-surface-container-lowest rounded-xl p-5 shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] border border-transparent hover:border-primary/40 hover:shadow-md transition-all flex flex-col justify-between">
                            <div class="flex items-start justify-between">
                                <div class="w-10 h-10 rounded-lg bg-[#fef3c7] flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors">
                                    <span class="material-symbols-outlined text-[22px]">group</span>
                                </div>
                                <span class="material-symbols-outlined text-outline group-hover:text-primary group-hover:translate-x-1 transition-all text-[20px]">arrow_forward</span>
                            </div>
                            <div class="mt-4">
                                <h3 class="text-headline-sm text-on-surface group-hover:text-primary transition-colors">Clientes</h3>
                                <p class="text-xs text-on-surface-variant mt-1">Base de remitentes y destinatarios, documentos, teléfonos y correos electrónicos.</p>
                            </div>
                        </a>

                        <a href="paginas/sucursales/sucursales.php" class="group bg-surface-container-lowest rounded-xl p-5 shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] border border-transparent hover:border-primary/40 hover:shadow-md transition-all flex flex-col justify-between">
                            <div class="flex items-start justify-between">
                                <div class="w-10 h-10 rounded-lg bg-[#fef3c7] flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors">
                                    <span class="material-symbols-outlined text-[22px]">store</span>
                                </div>
                                <span class="material-symbols-outlined text-outline group-hover:text-primary group-hover:translate-x-1 transition-all text-[20px]">arrow_forward</span>
                            </div>
                            <div class="mt-4">
                                <h3 class="text-headline-sm text-on-surface group-hover:text-primary transition-colors">Sucursales</h3>
                                <p class="text-xs text-on-surface-variant mt-1">Centros operativos, depósitos y puntos de recepción y despacho de la red.</p>
                            </div>
                        </a>

                        <a href="paginas/direcciones/direcciones.php" class="group bg-surface-container-lowest rounded-xl p-5 shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] border border-transparent hover:border-primary/40 hover:shadow-md transition-all flex flex-col justify-between">
                            <div class="flex items-start justify-between">
                                <div class="w-10 h-10 rounded-lg bg-[#fef3c7] flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors">
                                    <span class="material-symbols-outlined text-[22px]">location_on</span>
                                </div>
                                <span class="material-symbols-outlined text-outline group-hover:text-primary group-hover:translate-x-1 transition-all text-[20px]">arrow_forward</span>
                            </div>
                            <div class="mt-4">
                                <h3 class="text-headline-sm text-on-surface group-hover:text-primary transition-colors">Direcciones</h3>
                                <p class="text-xs text-on-surface-variant mt-1">Catálogo de domicilios de entrega asignados a clientes y localidades.</p>
                            </div>
                        </a>

                        <a href="paginas/provincias/provincias.php" class="group bg-surface-container-lowest rounded-xl p-5 shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] border border-transparent hover:border-primary/40 hover:shadow-md transition-all flex flex-col justify-between">
                            <div class="flex items-start justify-between">
                                <div class="w-10 h-10 rounded-lg bg-[#fef3c7] flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors">
                                    <span class="material-symbols-outlined text-[22px]">map</span>
                                </div>
                                <span class="material-symbols-outlined text-outline group-hover:text-primary group-hover:translate-x-1 transition-all text-[20px]">arrow_forward</span>
                            </div>
                            <div class="mt-4">
                                <h3 class="text-headline-sm text-on-surface group-hover:text-primary transition-colors">Provincias</h3>
                                <p class="text-xs text-on-surface-variant mt-1">Jurisdicciones territoriales donde opera la red de distribución de LogiTrack.</p>
                            </div>
                        </a>

                        <a href="paginas/localidades/localidades.php" class="group bg-surface-container-lowest rounded-xl p-5 shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] border border-transparent hover:border-primary/40 hover:shadow-md transition-all flex flex-col justify-between">
                            <div class="flex items-start justify-between">
                                <div class="w-10 h-10 rounded-lg bg-[#fef3c7] flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors">
                                    <span class="material-symbols-outlined text-[22px]">pin_drop</span>
                                </div>
                                <span class="material-symbols-outlined text-outline group-hover:text-primary group-hover:translate-x-1 transition-all text-[20px]">arrow_forward</span>
                            </div>
                            <div class="mt-4">
                                <h3 class="text-headline-sm text-on-surface group-hover:text-primary transition-colors">Localidades</h3>
                                <p class="text-xs text-on-surface-variant mt-1">Ciudades y localidades de cada provincia.</p>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Sección: Configuración -->
                <div class="flex flex-col gap-4">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[22px]">settings</span>
                        <h2 class="text-headline-md text-on-surface">Configuración</h2>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-space-md">
                        <a href="paginas/tipo_contenido/tipo_contenido.php" class="group bg-surface-container-lowest rounded-xl p-5 shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] border border-transparent hover:border-primary/40 hover:shadow-md transition-all flex flex-col justify-between">
                            <div class="flex items-start justify-between">
                                <div class="w-10 h-10 rounded-lg bg-[#fef3c7] flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors">
                                    <span class="material-symbols-outlined text-[22px]">inventory_2</span>
                                </div>
                                <span class="material-symbols-outlined text-outline group-hover:text-primary group-hover:translate-x-1 transition-all text-[20px]">arrow_forward</span>
                            </div>
                            <div class="mt-4">
                                <h3 class="text-headline-sm text-on-surface group-hover:text-primary transition-colors">Tipos de Contenido</h3>
                                <p class="text-xs text-on-surface-variant mt-1">Clasificación de las cargas (estándar, frágil, peligrosa) y si requieren seguro.</p>
                            </div>
                        </a>

                        <?php if ($esAdmin): ?>
                        <a href="paginas/usuarios/usuarios.php" class="group bg-surface-container-lowest rounded-xl p-5 shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] border border-transparent hover:border-primary/40 hover:shadow-md transition-all flex flex-col justify-between">
                            <div class="flex items-start justify-between">
                                <div class="w-10 h-10 rounded-lg bg-[#fef3c7] flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors">
                                    <span class="material-symbols-outlined text-[22px]">manage_accounts</span>
                                </div>
                                <span class="material-symbols-outlined text-outline group-hover:text-primary group-hover:translate-x-1 transition-all text-[20px]">arrow_forward</span>
                            </div>
                            <div class="mt-4">
                                <h3 class="text-headline-sm text-on-surface group-hover:text-primary transition-colors">Gestión de usuarios</h3>
                                <p class="text-xs text-on-surface-variant mt-1">Alta de operadores, asignación de roles (admin/operador) y restablecimiento de claves.</p>
                            </div>
                        </a>

                        <a href="paginas/sesiones/sesiones.php" class="group bg-surface-container-lowest rounded-xl p-5 shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] border border-transparent hover:border-primary/40 hover:shadow-md transition-all flex flex-col justify-between">
                            <div class="flex items-start justify-between">
                                <div class="w-10 h-10 rounded-lg bg-[#fef3c7] flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors">
                                    <span class="material-symbols-outlined text-[22px]">devices</span>
                                </div>
                                <span class="material-symbols-outlined text-outline group-hover:text-primary group-hover:translate-x-1 transition-all text-[20px]">arrow_forward</span>
                            </div>
                            <div class="mt-4">
                                <h3 class="text-headline-sm text-on-surface group-hover:text-primary transition-colors">Sesiones</h3>
                                <p class="text-xs text-on-surface-variant mt-1">Quién está conectado ahora, historial de ingresos y conexiones abiertas en la base.</p>
                            </div>
                        </a>

                        <a href="paginas/auditoria/auditoria.php" class="group bg-surface-container-lowest rounded-xl p-5 shadow-[0_1px_3px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.03)] border border-transparent hover:border-primary/40 hover:shadow-md transition-all flex flex-col justify-between">
                            <div class="flex items-start justify-between">
                                <div class="w-10 h-10 rounded-lg bg-[#fef3c7] flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors">
                                    <span class="material-symbols-outlined text-[22px]">policy</span>
                                </div>
                                <span class="material-symbols-outlined text-outline group-hover:text-primary group-hover:translate-x-1 transition-all text-[20px]">arrow_forward</span>
                            </div>
                            <div class="mt-4">
                                <h3 class="text-headline-sm text-on-surface group-hover:text-primary transition-colors">Auditoría</h3>
                                <p class="text-xs text-on-surface-variant mt-1">Quién cambió qué en envíos, viajes y usuarios, con el valor anterior y el nuevo.</p>
                            </div>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <!-- Script de autenticación y utilidades -->
    <script src="index.js?v=<?= filemtime(__DIR__ . '/index.js') ?>"></script>
</body>
</html>
