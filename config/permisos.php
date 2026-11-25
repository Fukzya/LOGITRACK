<?php
/*
 * Permisos por rol de la APLICACIÓN (primera barrera; la segunda son los GRANT de la base, script 016).
 *
 * Única tabla de permisos: la usan las APIs (exigir_permiso / exigir_permiso_crud) y las pantallas
 * (la plantilla la pasa al JS para ocultar los botones que el rol no puede usar).
 *
 *  - admin:    puede todo lo que hace la app.
 *  - operador: solo lo que figura abajo. Lo que no está, está prohibido (lista blanca).
 *
 * Acciones: ver (GET), alta (POST), editar (PUT), baja (DELETE: lógica o física),
 * reactivar (PUT con reactivar) y acciones especiales con nombre (POST con 'accion').
 */
require_once __DIR__ . '/session.php';

const PERMISOS_OPERADOR = [
    // Operación diaria: puede cargar y corregir, pero no dar de baja.
    'envios'           => ['ver', 'alta', 'editar'],
    'historial_estado' => ['ver', 'alta'],                       // inmutable: nadie edita ni borra
    'viajes'           => ['ver', 'alta', 'editar', 'cerrar', 'agregar_envios'],
    'viaje_envio'      => ['ver', 'alta', 'baja'],               // asignar y quitar (los triggers frenan viajes que salieron)
    'incidentes'       => ['ver', 'alta', 'editar'],
    'clientes'         => ['ver', 'alta', 'editar'],
    'direcciones'      => ['ver', 'alta', 'editar'],
    // Recursos y catálogos: solo consulta.
    'sucursales'       => ['ver'],
    'choferes'         => ['ver'],
    'vehiculos'        => ['ver'],
    'provincias'       => ['ver'],
    'localidades'      => ['ver'],
    'tipo_contenido'   => ['ver'],
    'tipo_vehiculo'    => ['ver'],
    // usuarios, sesiones y auditoria no figuran: solo admin.
];

function puede(string $entidad, string $accion): bool
{
    if (es_admin()) return true;
    return in_array($accion, PERMISOS_OPERADOR[$entidad] ?? [], true);
}

/** Corta con 403 si el rol de la sesión no puede hacer $accion sobre $entidad. */
function exigir_permiso(string $entidad, string $accion): void
{
    if (puede($entidad, $accion)) return;
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Acceso denegado: tu rol (' . ($_SESSION['rol'] ?? 'sin rol') . ') no tiene permiso para esta operación.']);
    exit();
}

/** Para las APIs CRUD: deduce la acción del método HTTP (y del cuerpo, para reactivar o acciones con nombre). */
function exigir_permiso_crud(string $entidad): void
{
    $metodo = $_SERVER['REQUEST_METHOD'];
    $cuerpo = in_array($metodo, ['POST', 'PUT', 'DELETE'], true)
            ? (json_decode(file_get_contents('php://input'), true) ?: []) : [];
    $accion = match ($metodo) {
        'GET'    => 'ver',
        'POST'   => is_string($cuerpo['accion'] ?? null) ? $cuerpo['accion'] : 'alta',
        'PUT'    => !empty($cuerpo['reactivar']) ? 'reactivar' : 'editar',
        'DELETE' => 'baja',
        default  => 'ninguna',
    };
    exigir_permiso($entidad, $accion);
}

/** Lo que el rol de la sesión puede hacer, para el JS de las pantallas ('*' = todo). */
function permisos_del_rol(): array|string
{
    return es_admin() ? '*' : PERMISOS_OPERADOR;
}
