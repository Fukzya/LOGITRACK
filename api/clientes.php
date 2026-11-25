<?php
/*
 * ============================================================================================
 * QUÉ ES:  la API ("controlador") de los clientes (DNI, nombre, mail, teléfono y localidad).
 *          La pantalla le pide datos y le manda altas, cambios y bajas. Este archivo revisa
 *          que los datos sean válidos y que el rol tenga permiso, y llama al modelo
 *          modelos/Cliente.php, que es el único que habla con la base. Responde en JSON.
 * PARA CAMBIAR:
 *   - Qué datos se piden, si son obligatorios o su largo máximo -> "CAMBIAR:" acá abajo.
 *   - Quién puede hacer qué (operador / admin)                 -> config/permisos.php
 *   - Qué trae la consulta (columnas, filtros, orden)           -> modelos/Cliente.php
 *   - Lo que se ve (textos, columnas, botones)                  -> paginas/ (pantalla Clientes)
 *   - Los mensajes de error de la base (reglas de fechas, capacidad...) vienen de los
 *     triggers y procedimientos: se cambian con un script nuevo en database/cambios/.
 * Guía con ejemplos: docs/como_cambiar.md (buscá "CAMBIAR:" con Ctrl+Shift+F).
 * ============================================================================================
 */
/*
 * Controlador de clientes: valida, llama al modelo Cliente y responde JSON.
 * No contiene SQL: eso vive en modelos/Cliente.php.
 */
require_once '../config/api.php';
exigir_permiso_crud('clientes');
require_once '../modelos/Cliente.php';
require_once '../modelos/Direccion.php';

$clientes = new Cliente($pdo);

// CAMBIAR: qué datos pide el alta y la edición. En cada campo_...(): el nombre del dato,
//          el nombre que ve el usuario en los mensajes y los límites (largo máximo, mínimo y máximo).
//          Si agregás un dato acá, agregalo también al modelo (SQL) y a la pantalla (campos).
function datos_cliente(array $d): array
{
    return [
        'nombre'        => campo_texto($d, 'nombre', 'El nombre', 80),
        'apellido'      => campo_texto($d, 'apellido', 'El apellido', 80),
        'mail'          => campo_email($d, 'mail', 'El mail'),
        'telefono'      => campo_telefono($d, 'telefono', 'El teléfono'),
        'localidad_cod' => campo_entero($d, 'localidad_cod', 'La localidad'),
    ];
}

switch ($_SERVER['REQUEST_METHOD']) {

    case 'GET':
        $filtros = [
            'dni'      => campo_entero($_GET, 'dni', 'El DNI', 1, 99999999, false),
            'nombre'   => trim($_GET['nombre'] ?? ''),
            'apellido' => trim($_GET['apellido'] ?? ''),
            'buscar'   => mb_substr(trim($_GET['buscar'] ?? ''), 0, 60),
        ];
        responder($clientes->listar($filtros, params_listado('cliente_dni'), ver_inactivos()));

    case 'POST':
        $d       = leer_json();
        $dni     = campo_entero($d, 'cliente_dni', 'El DNI', 1000000, 99999999);
        $cliente = ['cliente_dni' => $dni] + datos_cliente($d);
        // Opcional (lo usa el asistente de envío): la primera dirección del cliente, en su localidad.
        $calle   = campo_texto($d, 'calle_y_numero', 'La calle y número', 200, false);

        // Cliente y dirección en una transacción: o se guardan los dos o ninguno.
        $pdo->beginTransaction();
        try {
            $clientes->crear($cliente);
            $idDir = null;
            if ($calle !== null) {
                $idDir = (new Direccion($pdo))->crear(['calle_y_numero' => $calle,
                                                       'localidad_cod'  => $cliente['localidad_cod'],
                                                       'cliente_dni'    => $dni]);
            }
            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            if (codigo_mysql($e) === 1062) error_api("Ya existe un cliente con DNI $dni.", 409);
            if (codigo_mysql($e) === 1452) error_api('La localidad elegida no existe.', 400);
            throw $e;
        }
        responder(['mensaje' => 'Cliente creado.', 'cliente_dni' => $dni, 'direccion_id' => $idDir], 201);

    case 'PUT':
        $d   = leer_json();
        $dni = campo_entero($d, 'cliente_dni', 'El DNI', 1, 99999999);
        if (!empty($d['reactivar'])) {
            if (!$clientes->cambiarActivo($dni, true)) error_api('No existe un cliente con ese DNI.', 404);
            responder(['mensaje' => 'Cliente reactivado.']);
        }
        if (!$clientes->actualizar($dni, datos_cliente($d))) error_api('No existe un cliente activo con ese DNI.', 404);
        responder(['mensaje' => 'Cliente actualizado.']);

    case 'DELETE':
        $dni = campo_entero(leer_json(), 'cliente_dni', 'El DNI', 1, 99999999);
        if (!$clientes->cambiarActivo($dni, false)) error_api('No existe un cliente con ese DNI.', 404);
        responder(['mensaje' => 'Cliente dado de baja.']);

    default:
        error_api('Método no permitido.', 405);
}
