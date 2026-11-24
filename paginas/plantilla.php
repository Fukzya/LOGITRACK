<?php
/*
 * Encabezado y pie comunes de todas las pantallas (capa Vista).
 * Se ejecuta en el servidor antes de mandar nada al navegador:
 *   - sin sesión iniciada, redirige al login;
 *   - las pantallas solo para admin redirigen al menú si entra un operador.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../login.html');
    exit;
}
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

$esAdmin = ($_SESSION['rol'] ?? '') === 'admin';
$usuarioActivo = $_SESSION['username'] ?? 'Usuario';
$rolActivo = $_SESSION['rol'] ?? 'operador';

function inicio_pagina(string $titulo, bool $soloAdmin = false, string $seccionActiva = '', string $icono = ''): void
{
    global $esAdmin, $usuarioActivo, $rolActivo;
    if ($soloAdmin && !$esAdmin) {
        header('Location: ../../index.php');
        exit;
    }

    // Auto-detectar la sección activa según el directorio si no se indicó
    if ($seccionActiva === '') {
        $seccionActiva = basename(dirname($_SERVER['PHP_SELF'] ?? ''));
    }

    $iconosDefault = [
        'nuevo_envio'      => 'add_box',
        'envios'           => 'local_shipping',
        'viajes'           => 'route',
        'viaje_envio'      => 'link',
        'historial_estado' => 'history',
        'incidentes'       => 'warning',
        'vehiculos'        => 'directions_car',
        'tipo_vehiculo'    => 'category',
        'choferes'         => 'badge',
        'clientes'         => 'group',
        'sucursales'       => 'store',
        'direcciones'      => 'location_on',
        'provincias'       => 'map',
        'localidades'      => 'pin_drop',
        'tipo_contenido'   => 'inventory_2',
        'usuarios'         => 'manage_accounts',
    ];

    if ($icono === '') {
        $icono = $iconosDefault[$seccionActiva] ?? 'dashboard';
    }

    $activoClass = "flex items-center gap-3 px-3 py-2 rounded-lg bg-primary text-on-primary font-bold transition-colors";
    $normalClass = "flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-[#3d241c] hover:text-white transition-colors";

    ?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>LogiTrack - <?= htmlspecialchars($titulo) ?></title>
    <style>
        [hidden] { display: none !important; }
        @layer base {
            html, body { margin: 0; padding: 0; }
            body { overscroll-behavior: none; }
            main > :first-child { margin-top: 0 !important; }
            main > :last-child { margin-bottom: 0 !important; }
        }
        /* Scrollbar horizontal y vertical elegante */
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

        /* Paginación dinámica generada por crud.js */
        #paginacion {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.875rem 1.5rem;
            background-color: #fef3c7;
            border-bottom: 1px solid #f0e6dc;
            font-size: 0.875rem;
            font-weight: 500;
            color: #1c1917;
        }
        #paginacion button {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.375rem 0.875rem;
            border-radius: 0.5rem;
            background-color: #faf6f0;
            color: #464555;
            font-size: 0.875rem;
            font-weight: 500;
            border: none;
            cursor: pointer;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            transition: all 0.15s ease;
        }
        #paginacion button:hover:not(:disabled) {
            color: #c2410c;
            background-color: #ffffff;
        }
        #paginacion button:disabled {
            opacity: 0.45;
            cursor: not-allowed;
        }

        /* Estilos de tabla dinámica generada por crud.js */
        table tbody tr {
            transition: background-color 0.15s ease;
            border-bottom: 1px solid #f5efe6;
        }
        table tbody tr:hover {
            background-color: rgba(254, 243, 199, 0.45);
        }
        table tbody td {
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            line-height: 1.25rem;
            vertical-align: middle;
        }
        table tbody td small {
            font-size: 0.75rem;
            color: #777587;
            display: block;
            margin-top: 0.125rem;
        }

        /* Badges de estado dinámicos */
        table tbody span[style*="color:green"] {
            display: inline-block;
            padding: 0.125rem 0.625rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            background-color: #fef3c7;
            color: #78350f !important;
        }
        table tbody span[style*="color:red"] {
            display: inline-block;
            padding: 0.125rem 0.625rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            background-color: #ffdad6;
            color: #93000a !important;
        }

        /* Botones de acción generados dinámicamente */
        table tbody button[data-accion] {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.25rem 0.625rem;
            font-size: 0.75rem;
            font-weight: 500;
            line-height: 1rem;
            border-radius: 0.25rem;
            border: none;
            cursor: pointer;
            transition: all 0.15s ease;
            margin: 0 0.25rem;
            white-space: nowrap;
        }
        table tbody button[data-accion="editar"] {
            background-color: #f5efe6;
            color: #1c1917;
        }
        table tbody button[data-accion="editar"]:hover {
            background-color: #f0e6dc;
        }
        table tbody button[data-accion="baja"],
        table tbody button[data-accion="eliminar"],
        table tbody button[data-accion="quitar"] {
            background-color: #ffdad6;
            color: #93000a;
        }
        table tbody button[data-accion="baja"]:hover,
        table tbody button[data-accion="eliminar"]:hover,
        table tbody button[data-accion="quitar"]:hover {
            background-color: #fecaca;
        }
        table tbody button[data-accion="historial"] {
            background-color: #ffedd5;
            color: #9a3412;
        }
        table tbody button[data-accion="historial"]:hover {
            background-color: #fed7aa;
        }
        table tbody button[data-accion="reactivar"] {
            background-color: #dcfce7;
            color: #166534;
        }
        table tbody button[data-accion="reactivar"]:hover {
            background-color: #bbf7d0;
        }
        table tbody td:has(> button[data-accion]) {
            text-align: center;
            white-space: nowrap;
            min-width: 170px;
        }
    </style>
    <script src="../../assets/tailwind.js"></script>
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
    <link href="../../assets/material-symbols.css" rel="stylesheet"/>
    <link href="../../assets/inter.css" rel="stylesheet"/>
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
                    <a class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-[#3d241c] hover:text-white transition-colors" href="../../index.php">
                        <span class="material-symbols-outlined text-[20px]">dashboard</span>Menú Principal
                    </a>
                </div>

                <div class="flex flex-col gap-1">
                    <span class="px-3 text-xs uppercase tracking-wider text-orange-400/70 font-headline-sm mb-1">Operaciones</span>
                    <a class="<?= $seccionActiva === 'nuevo_envio' ? $activoClass : $normalClass ?>" data-path="nuevo-envio" href="../nuevo_envio/nuevo_envio.php">
                        <span class="material-symbols-outlined text-[20px]">add_box</span>Nuevo envío guiado
                    </a>
                    <a class="<?= $seccionActiva === 'envios' ? $activoClass : $normalClass ?>" data-path="envios" href="../envios/envios.php">
                        <span class="material-symbols-outlined text-[20px]">local_shipping</span>Envíos
                    </a>
                    <a class="<?= $seccionActiva === 'viajes' ? $activoClass : $normalClass ?>" data-path="viajes" href="../viajes/viajes.php">
                        <span class="material-symbols-outlined text-[20px]">route</span>Viajes
                    </a>
                    <a class="<?= $seccionActiva === 'viaje_envio' ? $activoClass : $normalClass ?>" data-path="viaje-envio" href="../viaje_envio/viaje_envio.php">
                        <span class="material-symbols-outlined text-[20px]">link</span>Viaje-Envío
                    </a>
                    <a class="<?= $seccionActiva === 'historial_estado' ? $activoClass : $normalClass ?>" data-path="historial-de-estados" href="../historial_estado/historial_estado.php">
                        <span class="material-symbols-outlined text-[20px]">history</span>Historial de Estados
                    </a>
                    <a class="<?= $seccionActiva === 'incidentes' ? $activoClass : $normalClass ?>" data-path="incidentes" href="../incidentes/incidentes.php">
                        <span class="material-symbols-outlined text-[20px]">warning</span>Incidentes
                    </a>
                </div>

                <div class="flex flex-col gap-1">
                    <span class="px-3 text-xs uppercase tracking-wider text-orange-400/70 font-headline-sm mb-1">Flota y Personal</span>
                    <a class="<?= $seccionActiva === 'vehiculos' ? $activoClass : $normalClass ?>" data-path="vehiculos" href="../vehiculos/vehiculos.php">
                        <span class="material-symbols-outlined text-[20px]">directions_car</span>Vehículos
                    </a>
                    <a class="<?= $seccionActiva === 'tipo_vehiculo' ? $activoClass : $normalClass ?>" data-path="tipos-de-vehiculo" href="../tipo_vehiculo/tipo_vehiculo.php">
                        <span class="material-symbols-outlined text-[20px]">category</span>Tipos de Vehículo
                    </a>
                    <a class="<?= $seccionActiva === 'choferes' ? $activoClass : $normalClass ?>" data-path="choferes" href="../choferes/choferes.php">
                        <span class="material-symbols-outlined text-[20px]">badge</span>Choferes
                    </a>
                </div>

                <div class="flex flex-col gap-1">
                    <span class="px-3 text-xs uppercase tracking-wider text-orange-400/70 font-headline-sm mb-1">Clientes y Red</span>
                    <a class="<?= $seccionActiva === 'clientes' ? $activoClass : $normalClass ?>" data-path="clientes" href="../clientes/clientes.php">
                        <span class="material-symbols-outlined text-[20px]">group</span>Clientes
                    </a>
                    <a class="<?= $seccionActiva === 'sucursales' ? $activoClass : $normalClass ?>" data-path="sucursales" href="../sucursales/sucursales.php">
                        <span class="material-symbols-outlined text-[20px]">store</span>Sucursales
                    </a>
                    <a class="<?= $seccionActiva === 'direcciones' ? $activoClass : $normalClass ?>" data-path="direcciones" href="../direcciones/direcciones.php">
                        <span class="material-symbols-outlined text-[20px]">location_on</span>Direcciones
                    </a>
                    <a class="<?= $seccionActiva === 'provincias' ? $activoClass : $normalClass ?>" data-path="provincias" href="../provincias/provincias.php">
                        <span class="material-symbols-outlined text-[20px]">map</span>Provincias
                    </a>
                    <a class="<?= $seccionActiva === 'localidades' ? $activoClass : $normalClass ?>" data-path="localidades" href="../localidades/localidades.php">
                        <span class="material-symbols-outlined text-[20px]">pin_drop</span>Localidades
                    </a>
                </div>

                <div class="flex flex-col gap-1">
                    <span class="px-3 text-xs uppercase tracking-wider text-orange-400/70 font-headline-sm mb-1">Configuración</span>
                    <a class="<?= $seccionActiva === 'tipo_contenido' ? $activoClass : $normalClass ?>" data-path="tipos-de-contenido" href="../tipo_contenido/tipo_contenido.php">
                        <span class="material-symbols-outlined text-[20px]">inventory_2</span>Tipos de Contenido
                    </a>
                    <?php if ($esAdmin): ?>
                    <a class="<?= $seccionActiva === 'usuarios' ? $activoClass : $normalClass ?>" data-path="gestion-de-usuarios" href="../usuarios/usuarios.php">
                        <span class="material-symbols-outlined text-[20px]">manage_accounts</span>Gestión de usuarios
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
                    <span class="text-sm font-medium text-white"><?= htmlspecialchars($usuarioActivo) ?></span>
                    <span class="text-xs text-orange-300/70"><?= htmlspecialchars($rolActivo) ?></span>
                </div>
            </div>
            <a class="text-orange-300/70 hover:text-white transition-colors" href="../../login.html" onclick="cerrarSesion(event)" title="Cerrar sesión">
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
                    <span class="text-xs font-semibold uppercase text-primary"><?= htmlspecialchars($rolActivo) ?></span>
                </div>
            </div>
        </header>

        <main class="relative pt-16 bg-[#faf6f0] min-h-screen p-8">
            <div class="flex flex-col w-full gap-space-xl pb-16">
                <!-- Top Navigation & Header -->
                <div class="flex flex-col gap-space-sm">
                    <a class="group inline-flex items-center gap-1 text-sm font-label-md text-primary no-underline w-fit" href="../../index.php">
                        <span class="material-symbols-outlined text-[16px] transition-transform group-hover:-translate-x-0.5">arrow_back</span>
                        <span class="group-hover:underline">Volver al menú</span>
                    </a>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-primary text-[32px]"><?= htmlspecialchars($icono) ?></span>
                            <h1 class="text-headline-xl text-on-surface"><?= htmlspecialchars($titulo) ?></h1>
                        </div>
                    </div>
                </div>
<?php
}

/**
 * Ruta de un script con su fecha de modificación (?v=...). Cuando el archivo cambia, cambia la URL
 * y el navegador descarga la versión nueva en lugar de usar la que tenía en caché.
 */
function con_version(string $ruta): string
{
    $archivo = realpath(dirname($_SERVER['SCRIPT_FILENAME']) . '/' . $ruta);
    return htmlspecialchars($ruta . ($archivo ? '?v=' . filemtime($archivo) : ''));
}

/**
 * $js: el script propio de la pantalla (ej. 'clientes.js').
 * $extras: componentes compartidos que usa esa pantalla (ej. ['../selector_cliente.js']).
 */
function fin_pagina(string $js, array $extras = []): void
{
    global $esAdmin;
    ?>
            </div>
        </main>
    </div>

    <script>const esAdmin = <?= $esAdmin ? 'true' : 'false' ?>;</script>
    <script src="<?= con_version('../comun.js') ?>"></script>
    <script src="<?= con_version('../catalogos.js') ?>"></script>
    <script src="<?= con_version('../crud.js') ?>"></script>
<?php foreach ($extras as $extra): ?>
    <script src="<?= con_version($extra) ?>"></script>
<?php endforeach; ?>
    <script src="<?= con_version($js) ?>"></script>
</body>
</html>
<?php
}
