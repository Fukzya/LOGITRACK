<?php
require_once '../config/session.php';
verificar_sesion();
require_once '../config/db.php';
header('Content-Type: application/json; charset=utf-8');

$metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {

    case 'POST':
        $datos = json_decode(file_get_contents('php://input'), true);

        $fecha_salida     = filter_var($datos['fecha_salida']     ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
        $vehiculo_patente = filter_var($datos['vehiculo_patente'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
        $chofer_legajo    = filter_var($datos['chofer_legajo']    ?? 0,  FILTER_SANITIZE_NUMBER_INT);
        $envios           = isset($datos['envios']) && is_array($datos['envios']) ? $datos['envios'] : [];

        if (!$fecha_salida || !$vehiculo_patente || !$chofer_legajo || empty($envios)) {
            http_response_code(400);
            echo json_encode(['error' => 'Todos los campos son obligatorios y debe haber al menos un envío']);
            break;
        }

        try {
            $pdo->beginTransaction();

            $stmtViaje = $pdo->prepare("INSERT INTO viaje (fecha_salida, vehiculo_patente, chofer_legajo) VALUES (:fecha_salida, :vehiculo_patente, :chofer_legajo)");
            $stmtViaje->bindParam(':fecha_salida',     $fecha_salida,     PDO::PARAM_STR);
            $stmtViaje->bindParam(':vehiculo_patente', $vehiculo_patente, PDO::PARAM_STR);
            $stmtViaje->bindParam(':chofer_legajo',    $chofer_legajo,    PDO::PARAM_INT);
            $stmtViaje->execute();

            $viaje_id  = (int)$pdo->lastInsertId();
            $nro_envio = 0;

            $stmtAsignar = $pdo->prepare("CALL procedimiento_asignar_envio_a_viaje(:viaje_id, :nro_envio)");
            $stmtAsignar->bindParam(':viaje_id',  $viaje_id,  PDO::PARAM_INT);
            $stmtAsignar->bindParam(':nro_envio', $nro_envio, PDO::PARAM_INT);

            foreach ($envios as $nroRaw) {
                $nro_envio = (int)filter_var($nroRaw, FILTER_SANITIZE_NUMBER_INT);
                $stmtAsignar->execute();
            }

            $pdo->commit();

            echo json_encode([
                'ok'               => true,
                'viaje_id'         => $viaje_id,
                'envios_asignados' => count($envios)
            ]);

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            http_response_code(500);
            echo json_encode(['error' => htmlspecialchars($e->getMessage())]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
}
