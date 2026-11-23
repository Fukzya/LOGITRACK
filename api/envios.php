<?php
/* Controlador de envíos: valida, llama al modelo Envio y responde JSON. */
require_once '../config/api.php';
require_once '../modelos/Envio.php';

$envios = new Envio($pdo);

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

$errores = [1452 => 'El remitente, el destinatario, una sucursal, el tipo de contenido o la dirección indicados no existen.'];

switch ($_SERVER['REQUEST_METHOD']) {

    case 'GET':
        $filtros = [
            'fecha_desde'   => campo_fecha($_GET, 'fecha_desde', 'La fecha desde', false),
            'fecha_hasta'   => campo_fecha($_GET, 'fecha_hasta', 'La fecha hasta', false),
            'sucursal_orig' => trim($_GET['sucursal_orig'] ?? ''),
            'tipo'          => trim($_GET['tipo_cont'] ?? ''),
        ];
        responder($envios->listar($filtros, params_listado('nro_envio', 'DESC'), ver_inactivos()));

    case 'POST':
        $e   = datos_envio(leer_json());
        $nro = intentar(fn() => $envios->crear($e), $errores);
        responder(['mensaje' => "Envío $nro creado.", 'nro_envio_creado' => $nro], 201);

    case 'PUT':
        $d   = leer_json();
        $nro = campo_entero($d, 'nro_envio', 'El número de envío');
        if (!empty($d['reactivar'])) {
            if (!$envios->cambiarActivo($nro, true)) error_api('No existe ese envío.', 404);
            responder(['mensaje' => 'Envío reactivado.']);
        }
        $e = datos_envio($d);
        if (!intentar(fn() => $envios->actualizar($nro, $e), $errores)) error_api('No existe un envío activo con ese número.', 404);
        responder(['mensaje' => 'Envío actualizado.']);

    case 'DELETE':
        $nro = campo_entero(leer_json(), 'nro_envio', 'El número de envío');
        if (!$envios->cambiarActivo($nro, false)) error_api('No existe ese envío.', 404);
        responder(['mensaje' => 'Envío dado de baja.']);

    default:
        error_api('Método no permitido.', 405);
}
