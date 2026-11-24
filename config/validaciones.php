<?php
/*
 * Validación de los datos de un envío y de un viaje. La usan los controladores que los crean:
 * envios.php, viajes.php y asistente_envio.php (así la regla está en un solo lugar).
 */

function datos_envio(array $d): array
{
    $e = [
        'fecha_envio'          => campo_fecha($d, 'fecha_envio', 'La fecha'),
        'peso_kg'              => campo_decimal($d, 'peso_kg', 'El peso (kg)', 0.01, 999999.99),
        'tipo_cont_cod'        => campo_codigo($d, 'tipo_cont_cod', 'El tipo de contenido', '/^[A-Z]{3}$/', '3 letras'),
        'remitente_dni'        => campo_entero($d, 'remitente_dni', 'El DNI del remitente', 1, 99999999),
        'destinatario_dni'     => campo_entero($d, 'destinatario_dni', 'El DNI del destinatario', 1, 99999999),
        'sucursal_orig_cod'    => campo_codigo($d, 'sucursal_orig_cod', 'La sucursal de origen', '/^[0-9]{3}$/', '3 dígitos', false),
        'sucursal_dest_cod'    => campo_codigo($d, 'sucursal_dest_cod', 'La sucursal de destino', '/^[0-9]{3}$/', '3 dígitos', false),
        'direccion_entrega_id' => campo_entero($d, 'direccion_entrega_id', 'La dirección de entrega'),
    ];
    // Misma regla que el CHECK ck_sucursales_distintas de la base, avisada antes de llegar a ella.
    if ($e['sucursal_orig_cod'] === $e['sucursal_dest_cod']) {
        throw new ValidacionException('La sucursal de origen y la de destino tienen que ser distintas.');
    }
    return $e;
}

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
    // Misma regla que el CHECK ck_viaje_origen_destino.
    if ($v['sucursal_origen_cod'] === $v['sucursal_destino_cod']) {
        throw new ValidacionException('El viaje tiene que ir de una sucursal a otra distinta.');
    }
    // Misma regla que trigger_before_update_viaje, también para el alta.
    if ($v['fecha_llegada'] !== null && $v['fecha_llegada'] <= $v['fecha_salida']) {
        throw new ValidacionException('La fecha de llegada tiene que ser posterior a la de salida.');
    }
    return $v;
}
