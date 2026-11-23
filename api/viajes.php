<?php
require_once '../config/session.php';
verificar_sesion();
require_once '../config/db.php';

header('Content-Type: application/json');

$metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {

    case 'GET':
        $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
        $limite = 50;
        $offset = ($pagina - 1) * $limite;

        $fecha_desde   = trim($_GET['fecha_desde']   ?? '');
        $fecha_hasta   = trim($_GET['fecha_hasta']   ?? '');
        $chofer_legajo = trim($_GET['chofer_legajo'] ?? '');
        $solo_activos  = trim($_GET['solo_activos']  ?? '');

        $order_whitelist = ['viaje_id', 'fecha_salida', 'fecha_llegada'];
        $order_by  = in_array($_GET['order_by'] ?? '', $order_whitelist) ? $_GET['order_by'] : 'viaje_id';
        $order_dir = strtoupper($_GET['order_dir'] ?? '') === 'ASC' ? 'ASC' : 'DESC';

        $condiciones = [];
        $params      = [];

        if ($fecha_desde !== '') {
            $condiciones[] = 'fecha_salida >= :fecha_desde';
            $params[':fecha_desde'] = $fecha_desde;
        }
        if ($fecha_hasta !== '') {
            $condiciones[] = 'fecha_salida <= :fecha_hasta';
            $params[':fecha_hasta'] = $fecha_hasta . ' 23:59:59';
        }
        if ($chofer_legajo !== '') {
            $condiciones[] = 'chofer_legajo = :chofer_legajo';
            $params[':chofer_legajo'] = (int)$chofer_legajo;
        }
        if ($solo_activos === '1') {
            $condiciones[] = 'fecha_llegada IS NULL';
        }

        $where = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';

        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM viaje $where");
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        $stmtData = $pdo->prepare("SELECT * FROM viaje $where ORDER BY $order_by $order_dir LIMIT $limite OFFSET $offset");
        $stmtData->execute($params);
        $viajes = $stmtData->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'datos'         => $viajes,
            'total'         => $total,
            'pagina'        => $pagina,
            'total_paginas' => (int)ceil($total / $limite)
        ]);
        break;

    case 'POST':
        $datos = json_decode(file_get_contents("php://input"), true);
        $consulta = $pdo->prepare("INSERT INTO viaje (fecha_salida, fecha_llegada, vehiculo_patente, chofer_legajo) 
                                   VALUES (:salida, :llegada, :patente, :chofer)");
        $consulta->execute([
            ':salida'  => $datos['fecha_salida'],
            ':llegada' => $datos['fecha_llegada'] ?? null,
            ':patente' => $datos['vehiculo_patente'],
            ':chofer'  => $datos['chofer_legajo']
        ]);
        echo json_encode([
            'mensaje' => 'Viaje creado',
            'id'      => $pdo->lastInsertId()
        ]);
        break;

    case 'PUT':
        $datos = json_decode(file_get_contents("php://input"), true);
        $consulta = $pdo->prepare("UPDATE viaje SET fecha_salida = :salida, fecha_llegada = :llegada, 
                                   vehiculo_patente = :patente, chofer_legajo = :chofer 
                                   WHERE viaje_id = :id");
        $consulta->execute([
            ':salida'  => $datos['fecha_salida'],
            ':llegada' => $datos['fecha_llegada'] ?? null,
            ':patente' => $datos['vehiculo_patente'],
            ':chofer'  => $datos['chofer_legajo'],
            ':id'      => $datos['viaje_id']
        ]);
        echo json_encode(['mensaje' => 'Viaje actualizado']);
        break;

    case 'DELETE':
        $datos = json_decode(file_get_contents("php://input"), true);
        $consulta = $pdo->prepare("DELETE FROM viaje WHERE viaje_id = :id");
        $consulta->execute([':id' => $datos['viaje_id']]);
        echo json_encode(['mensaje' => 'Viaje eliminado']);
        break;
}