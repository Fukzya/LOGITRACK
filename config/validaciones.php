<?php
/*
 * ============================================================================================
 * QUÉ ES:  las validaciones de envíos y viajes, que se usan en varias APIs (pantallas Envíos,
 *          Viajes, el asistente "Nuevo envío" y "Viaje completo").
 * PARA CAMBIAR: qué datos se piden y sus límites -> "CAMBIAR:" abajo.
 * Guía con ejemplos: docs/como_cambiar.md (buscá "CAMBIAR:" con Ctrl+Shift+F).
 * ============================================================================================
 */
/*
 * Validación de los datos de un envío y de un viaje. La usan los controladores que los crean:
 * envios.php, viajes.php y asistente_envio.php.
 * Solo se controla el FORMATO (obligatorio, número, largo, código bien escrito). Las reglas del
 * negocio están una sola vez, en la base (CHECK, triggers y procedimientos): no se repiten acá.
 */

// CAMBIAR: datos de un envío: el peso máximo, el formato de los códigos, etc.
function datos_envio(array $d): array
{
    $e = [
        'fecha_envio'          => campo_fecha($d, 'fecha_envio', 'La fecha'),
        // Solo el formato (un número): "mayor a cero" es el CHECK ck_peso_positivo de la base.
        'peso_kg'              => campo_decimal($d, 'peso_kg', 'El peso (kg)', 0, 999999.99),
        'tipo_cont_cod'        => campo_codigo($d, 'tipo_cont_cod', 'El tipo de contenido', '/^[A-Z]{3}$/', '3 letras'),
        'remitente_dni'        => campo_entero($d, 'remitente_dni', 'El DNI del remitente', 1, 99999999),
        'destinatario_dni'     => campo_entero($d, 'destinatario_dni', 'El DNI del destinatario', 1, 99999999),
        'sucursal_orig_cod'    => campo_codigo($d, 'sucursal_orig_cod', 'La sucursal de origen', '/^[0-9]{3}$/', '3 dígitos', false),
        'sucursal_dest_cod'    => campo_codigo($d, 'sucursal_dest_cod', 'La sucursal de destino', '/^[0-9]{3}$/', '3 dígitos', false),
        'direccion_entrega_id' => campo_entero($d, 'direccion_entrega_id', 'La dirección de entrega'),
    ];
    // Origen distinto del destino: CHECK ck_sucursales_distintas de la base (mensaje en mensaje_check).
    return $e;
}

// CAMBIAR: datos de un viaje: formato de la patente, de los códigos de sucursal, etc.
function datos_viaje(array $d): array
{
    $v = [
        'fecha_salida'     => campo_fecha_hora($d, 'fecha_salida', 'La fecha de salida'),
        'fecha_llegada'    => campo_fecha_hora($d, 'fecha_llegada', 'La fecha de llegada', false),
        'sucursal_origen_cod'  => campo_codigo($d, 'sucursal_origen_cod', 'La sucursal de origen del viaje', '/^[0-9]{3}$/', '3 dígitos', false),
        'sucursal_destino_cod' => campo_codigo($d, 'sucursal_destino_cod', 'La sucursal de destino del viaje', '/^[0-9]{3}$/', '3 dígitos', false),
        'vehiculo_patente' => campo_codigo($d, 'vehiculo_patente', 'El vehículo', '/^[A-Z0-9]{6,10}$/', 'una patente'),
        'chofer_legajo'    => campo_entero($d, 'chofer_legajo', 'El chofer'),
    ];
    // Las reglas del viaje NO están acá: origen distinto del destino (CHECK ck_viaje_origen_destino),
    // llegada posterior a la salida y no futura, vehículo y chofer activos, libres ese día y con la
    // licencia que corresponde (triggers de viaje + procedimiento_validar_recursos_viaje).
    return $v;
}
