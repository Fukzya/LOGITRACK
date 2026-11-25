<?php
/* Controlador de viajes: valida, llama al modelo Viaje y responde JSON. */
require_once '../config/api.php';
exigir_permiso_crud('viajes');
require_once '../modelos/Viaje.php';
require_once '../config/validaciones.php';

$viajes = new Viaje($pdo);

$errores = [1452 => 'El vehículo o el chofer elegidos no existen.'];

switch ($_SERVER['REQUEST_METHOD']) {

    case 'GET':
        // Asistente de envío: rutas directas y con trasbordo (procedimiento_buscar_rutas) y la
        // lista de todos los viajes que todavía no salieron.
        if (isset($_GET['rutas'])) {
            responder($viajes->buscarRutas(
                campo_codigo($_GET, 'sucursal_orig', 'La sucursal de origen', '/^[0-9]{3}$/', '3 dígitos', false),
                campo_codigo($_GET, 'sucursal_dest', 'La sucursal de destino', '/^[0-9]{3}$/', '3 dígitos', false),
                campo_decimal($_GET, 'peso', 'El peso (kg)', 0.01, 999999.99)
            ));
        }
        // Pantalla "Detalle de viaje": datos, carga, envíos que lleva y envíos que se pueden agregar.
        if (isset($_GET['detalle'])) {
            $id = campo_entero($_GET, 'detalle', 'El viaje');
            $viaje = $viajes->detalle($id);
            if ($viaje === null) error_api('No existe ese viaje.', 404);
            $viaje['pendientes'] = $viaje['ya_salio'] || $viaje['fecha_llegada'] ? [] : $viajes->enviosPendientes($id);
            responder($viaje);
        }
        // Asistente de envío: vehículos y choferes libres para crear un viaje ese día.
        if (isset($_GET['recursos'])) {
            responder($viajes->recursosLibres(campo_fecha($_GET, 'fecha', 'La fecha')));
        }
        $filtros = [
            'fecha_desde'  => campo_fecha($_GET, 'fecha_desde', 'La fecha desde', false),
            'fecha_hasta'  => campo_fecha($_GET, 'fecha_hasta', 'La fecha hasta', false),
            'chofer'       => campo_entero($_GET, 'chofer_legajo', 'El chofer', 1, PHP_INT_MAX, false),
            'solo_activos' => ($_GET['solo_activos'] ?? '') === '1',
        ];
        responder($viajes->listar($filtros, params_listado('viaje_id', 'DESC')));

    case 'POST':
        $d = leer_json();

        // Cerrar el viaje: procedimiento_cerrar_viaje (su propia transacción). Las reglas (que ya
        // haya salido, que no esté cerrado, llegada posterior a la salida y no futura) las
        // controla la base y su mensaje llega tal cual.
        if (($d['accion'] ?? '') === 'cerrar') {
            $id = campo_entero($d, 'viaje_id', 'El viaje');
            $n  = $viajes->cerrar($id, campo_fecha_hora($d, 'fecha_llegada', 'La fecha de llegada', false));
            responder(['mensaje' => "Viaje $id cerrado. " . ($n === 1 ? '1 envío pasó' : "$n envíos pasaron") . ' a "En sucursal".',
                       'envios_actualizados' => $n]);
        }

        // Agregar envíos a un viaje que todavía no salió: una transacción, la fila del viaje
        // bloqueada (FOR UPDATE) y cada asignación por procedimiento_asignar_envio_a_viaje. Si una
        // falla (capacidad, trasbordo...), ROLLBACK: no se agrega ninguna.
        if (($d['accion'] ?? '') === 'agregar_envios') {
            require_once '../modelos/ViajeEnvio.php';
            $id = campo_entero($d, 'viaje_id', 'El viaje');
            $envios = is_array($d['envios'] ?? null) ? $d['envios'] : [];
            if (!$envios) error_api('Elegí al menos un envío.');
            $envios = array_map(fn($n) => campo_entero(['n' => $n], 'n', 'Cada número de envío'), $envios);
            $asignar = new ViajeEnvio($pdo);

            $pdo->beginTransaction();
            try {
                if ($viajes->bloquearParaAsignar($id) === null) {
                    $pdo->rollBack();
                    error_api('No existe ese viaje.', 404);
                }
                foreach ($envios as $nro) {
                    $asignar->asignarConProcedimiento($id, $nro);
                }
                $pdo->commit();
            } catch (Throwable $t) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                throw $t;
            }
            responder(['mensaje' => count($envios) === 1 ? 'Se agregó 1 envío al viaje.'
                                                          : 'Se agregaron ' . count($envios) . ' envíos al viaje.']);
        }

        $v  = datos_viaje($d);
        $id = intentar(fn() => $viajes->crear($v), $errores);
        responder(['mensaje' => "Viaje $id creado.", 'id' => $id], 201);

    case 'PUT':
        $d  = leer_json();
        $id = campo_entero($d, 'viaje_id', 'El viaje');
        $v  = datos_viaje($d);
        if (!intentar(fn() => $viajes->actualizar($id, $v), $errores)) error_api('No existe ese viaje.', 404);
        responder(['mensaje' => 'Viaje actualizado.']);

    // Si tiene envíos asignados, trigger_before_delete_viaje lo rechaza y su mensaje llega tal cual.
    case 'DELETE':
        $id = campo_entero(leer_json(), 'viaje_id', 'El viaje');
        $ok = intentar(fn() => $viajes->eliminar($id), [1451 => 'No se puede eliminar: el viaje tiene incidentes registrados.']);
        if (!$ok) error_api('No existe ese viaje.', 404);
        responder(['mensaje' => 'Viaje eliminado.']);

    default:
        error_api('Método no permitido.', 405);
}
