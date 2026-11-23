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

        $viaje_id    = trim($_GET['viaje_id']    ?? '');
        $tipo        = trim($_GET['tipo']        ?? '');
        $fecha_desde = trim($_GET['fecha_desde'] ?? '');
        $fecha_hasta = trim($_GET['fecha_hasta'] ?? '');

        $order_whitelist = ['incidente_id', 'viaje_id', 'fecha_hora', 'tipo_incidente'];
        $order_by  = in_array($_GET['order_by'] ?? '', $order_whitelist) ? $_GET['order_by'] : 'fecha_hora';
        $order_dir = strtoupper($_GET['order_dir'] ?? '') === 'ASC' ? 'ASC' : 'DESC';

        $condiciones = [];
        $params      = [];

        if ($viaje_id !== '') {
            $condiciones[] = 'viaje_id = :viaje_id';
            $params[':viaje_id'] = (int)$viaje_id;
        }
        if ($tipo !== '') {
            $condiciones[] = 'tipo_incidente = :tipo';
            $params[':tipo'] = $tipo;
        }
        if ($fecha_desde !== '') {
            $condiciones[] = 'fecha_hora >= :fecha_desde';
            $params[':fecha_desde'] = $fecha_desde . ' 00:00:00';
        }
        if ($fecha_hasta !== '') {
            $condiciones[] = 'fecha_hora <= :fecha_hasta';
            $params[':fecha_hasta'] = $fecha_hasta . ' 23:59:59';
        }

        $where = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';

        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM incidente $where");
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        $stmtData = $pdo->prepare("SELECT * FROM incidente $where ORDER BY $order_by $order_dir LIMIT $limite OFFSET $offset");
        $stmtData->execute($params);
        $incidentes = $stmtData->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'datos'         => $incidentes,
            'total'         => $total,
            'pagina'        => $pagina,
            'total_paginas' => (int)ceil($total / $limite)
        ]);
        break;

    case 'POST':
        $datos = json_decode(file_get_contents("php://input"), true);
        $consulta = $pdo->prepare("INSERT INTO incidente (viaje_id, fecha_hora, tipo_incidente, descripcion) 
                                   VALUES (:viaje, :fecha, :tipo, :descripcion)");
        $consulta->execute([
            ':viaje'       => $datos['viaje_id'],
            ':fecha'       => $datos['fecha_hora'],
            ':tipo'        => $datos['tipo_incidente'],
            ':descripcion' => $datos['descripcion'] ?? null
        ]);
        echo json_encode([
            'mensaje' => 'Incidente registrado',
            'id'      => $pdo->lastInsertId()
        ]);
        break;

    case 'PUT':
        $datos = json_decode(file_get_contents("php://input"), true);
        $consulta = $pdo->prepare("UPDATE incidente SET viaje_id = :viaje, fecha_hora = :fecha, 
                                   tipo_incidente = :tipo, descripcion = :descripcion 
                                   WHERE incidente_id = :id");
        $consulta->execute([
            ':viaje'       => $datos['viaje_id'],
            ':fecha'       => $datos['fecha_hora'],
            ':tipo'        => $datos['tipo_incidente'],
            ':descripcion' => $datos['descripcion'] ?? null,
            ':id'          => $datos['incidente_id']
        ]);
        echo json_encode(['mensaje' => 'Incidente actualizado']);
        break;

    case 'DELETE':
        $datos = json_decode(file_get_contents("php://input"), true);
        $consulta = $pdo->prepare("DELETE FROM incidente WHERE incidente_id = :id");
        $consulta->execute([':id' => $datos['incidente_id']]);
        echo json_encode(['mensaje' => 'Incidente eliminado']);
        break;
}   