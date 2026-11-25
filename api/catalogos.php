<?php
/*
 * ============================================================================================
 * QUÉ ES:  las listas de los desplegables (select) de todas las pantallas: provincias,
 *          localidades, sucursales, estados, tipos, choferes, vehículos...
 * PARA CAMBIAR: qué muestra cada opción y en qué orden -> "CAMBIAR:" abajo.
 * Guía con ejemplos: docs/como_cambiar.md (buscá "CAMBIAR:" con Ctrl+Shift+F).
 * ============================================================================================
 */
// Devuelve las opciones para llenar los <select> del front, siempre como [{cod, nombre}].
// GET ?tipo=estados | incidentes | licencias | localidades | provincias | sucursales |
//          tipos_contenido | tipos_vehiculo | choferes | vehiculos
require_once '../config/api.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    error_api('Método no permitido.', 405);
}

// CAMBIAR: una consulta por lista: 'cod' es el valor que se guarda y 'nombre' el texto
//          que ve el usuario. ORDER BY decide el orden de las opciones.
$consultas = [
    'estados'         => 'SELECT estado_cod AS cod, estado_nombre AS nombre FROM estado_envio ORDER BY es_final, estado_nombre',
    'incidentes'      => 'SELECT tipo_incidente_cod AS cod, tipo_incidente_nombre AS nombre FROM tipo_incidente ORDER BY tipo_incidente_nombre',
    'licencias'       => "SELECT licencia_cod AS cod, CONCAT(licencia_cod, ' - ', licencia_descripcion) AS nombre FROM tipo_licencia ORDER BY licencia_cod",
    'localidades'     => "SELECT l.localidad_cod AS cod, CONCAT(l.localidad_nombre, ' (', p.provincia_nombre, ')') AS nombre
                          FROM localidad l JOIN provincia p ON p.provincia_cod = l.provincia_cod ORDER BY nombre",
    'provincias'      => 'SELECT provincia_cod AS cod, provincia_nombre AS nombre FROM provincia ORDER BY provincia_nombre',
    // Solo los activos: no se asignan envíos ni viajes a algo dado de baja.
    // Con la provincia (vista_sucursal_ubicacion). grupo: los <select> las agrupan por provincia.
    'sucursales'      => "SELECT sucursal_cod AS cod, CONCAT(sucursal_cod, ' - ', sucursal_completa) AS nombre,
                                 provincia_cod, provincia_nombre AS grupo
                          FROM vista_sucursal_ubicacion WHERE activo = 1 ORDER BY provincia_nombre, sucursal_cod",
    'tipos_contenido' => 'SELECT tipo_cont_cod AS cod, tipo_cont_nombre AS nombre FROM tipo_contenido ORDER BY tipo_cont_nombre',
    'tipos_vehiculo'  => 'SELECT tipo_veh_cod AS cod, tipo_veh_nombre AS nombre FROM tipo_vehiculo ORDER BY tipo_veh_nombre',
    'choferes'        => "SELECT chofer_legajo AS cod, CONCAT(apellido, ', ', nombre, ' (', chofer_tipo_licencia, ')') AS nombre FROM chofer WHERE activo = 1 ORDER BY apellido, nombre",
    'vehiculos'       => "SELECT vehiculo_patente AS cod, CONCAT(vehiculo_patente, ' - ', marca, ' ', modelo) AS nombre FROM vehiculo WHERE activo = 1 ORDER BY vehiculo_patente",
];

$tipo = $_GET['tipo'] ?? '';
if (!isset($consultas[$tipo])) {
    error_api('Tipo de catálogo inválido.', 400);
}

responder($pdo->query($consultas[$tipo])->fetchAll(PDO::FETCH_ASSOC));
