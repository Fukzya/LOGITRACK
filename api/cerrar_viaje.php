<?php
require_once '../config/session.php';
verificar_sesion();
require_once '../config/db.php';
header('Content-Type: application/json; charset=utf-8');

$metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {

    case 'PUT':
        $datos = json_decode(file_get_contents('php://input'), true);

        $viaje_id      = filter_var($datos['viaje_id']      ?? 0,  FILTER_SANITIZE_NUMBER_INT);
        $fecha_llegada = filter_var($datos['fecha_llegada'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);

        if (!$viaje_id || !$fecha_llegada) {
            http_response_code(400);
            echo json_encode(['error' => 'viaje_id y fecha_llegada son obligatorios']);
            break;
        }

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("CALL procedimiento_cerrar_viaje(:viaje_id, :fecha_llegada)");
            $stmt->bindParam(':viaje_id',      $viaje_id,      PDO::PARAM_INT);
            $stmt->bindParam(':fecha_llegada', $fecha_llegada, PDO::PARAM_STR);
            $stmt->execute();

            $pdo->commit();

            echo json_encode(['ok' => true, 'mensaje' => 'Viaje cerrado correctamente']);

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
