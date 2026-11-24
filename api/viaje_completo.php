<?php
/*
 * Crea un viaje y le asigna varios envíos de una vez, en UNA transacción.
 * POST {fecha_salida, sucursal_origen_cod, sucursal_destino_cod, vehiculo_patente, chofer_legajo,
 *       envios: [nro, nro, ...]}
 * Cada asignación pasa por procedimiento_asignar_envio_a_viaje y por el trigger
 * trigger_before_insert_viaje_envio (capacidad, tramos). Si una falla, ROLLBACK: no queda el viaje.
 */
require_once '../config/api.php';
require_once '../config/validaciones.php';
require_once '../modelos/Viaje.php';
require_once '../modelos/ViajeEnvio.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_api('Método no permitido.', 405);
}

$d = leer_json();
$v = datos_viaje($d);
$envios = is_array($d['envios'] ?? null) ? $d['envios'] : [];
if (!$envios) error_api('Tiene que haber al menos un envío.');
$envios = array_map(fn($n) => campo_entero(['n' => $n], 'n', 'Cada número de envío'), $envios);

$viajes  = new Viaje($pdo);
$asignar = new ViajeEnvio($pdo);

$pdo->beginTransaction();
try {
    $id = $viajes->crear($v);
    foreach ($envios as $nro) {
        $asignar->asignarConProcedimiento($id, $nro);
    }
    $pdo->commit();
} catch (Throwable $t) {
    $pdo->rollBack();
    if ($t instanceof PDOException && codigo_mysql($t) === 1452) {
        error_api('El vehículo, el chofer, una sucursal o alguno de los envíos no existen.');
    }
    throw $t;
}
responder(['mensaje' => "Viaje $id creado con " . count($envios) . ' envío(s).', 'viaje_id' => $id], 201);
