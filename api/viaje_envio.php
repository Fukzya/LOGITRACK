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

        $viaje_id  = trim($_GET['viaje_id']  ?? '');
        $nro_envio = trim($_GET['nro_envio'] ?? '');

        $order_whitelist = ['viaje_id', 'nro_envio'];
        $order_by  = in_array($_GET['order_by'] ?? '', $order_whitelist) ? $_GET['order_by'] : 'viaje_id';
        $order_dir = strtoupper($_GET['order_dir'] ?? '') === 'DESC' ? 'DESC' : 'ASC';

        $condiciones = [];
        $params      = [];

        if ($viaje_id !== '') {
            $condiciones[] = 'viaje_id = :viaje_id';
            $params[':viaje_id'] = (int)$viaje_id;
        }
        if ($nro_envio !== '') {
            $condiciones[] = 'nro_envio = :nro_envio';
            $params[':nro_envio'] = (int)$nro_envio;
        }

        $where = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';

        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM viaje_envio $where");
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        $stmtData = $pdo->prepare("SELECT * FROM viaje_envio $where ORDER BY $order_by $order_dir LIMIT $limite OFFSET $offset");
        $stmtData->execute($params);
        $viaje_envios = $stmtData->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'datos'         => $viaje_envios,
            'total'         => $total,
            'pagina'        => $pagina,
            'total_paginas' => (int)ceil($total / $limite)
        ]);
        break;

    case 'POST':
        $datos = json_decode(file_get_contents("php://input"), true);
        $consulta = $pdo->prepare("INSERT INTO viaje_envio (viaje_id, nro_envio) 
                                   VALUES (:viaje, :envio)");
        $consulta->execute([
            ':viaje' => $datos['viaje_id'],
            ':envio' => $datos['nro_envio']
        ]);
        echo json_encode(['mensaje' => 'Envío asignado al viaje']);
        break;

    case 'DELETE':
        $datos = json_decode(file_get_contents("php://input"), true);
        $consulta = $pdo->prepare("DELETE FROM viaje_envio WHERE viaje_id = :viaje AND nro_envio = :envio");
        $consulta->execute([
            ':viaje' => $datos['viaje_id'],
            ':envio' => $datos['nro_envio']
        ]);
        echo json_encode(['mensaje' => 'Envío desasignado del viaje']);
        break;
}