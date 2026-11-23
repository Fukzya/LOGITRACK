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

        $nro_envio    = trim($_GET['nro_envio']    ?? '');
        $estado       = trim($_GET['estado']       ?? '');
        $fecha_desde  = trim($_GET['fecha_desde']  ?? '');
        $fecha_hasta  = trim($_GET['fecha_hasta']  ?? '');
        $sucursal_cod = trim($_GET['sucursal_cod'] ?? '');

        $order_whitelist = ['id_historial', 'fecha_hora', 'nro_envio'];
        $order_by  = in_array($_GET['order_by'] ?? '', $order_whitelist) ? $_GET['order_by'] : 'id_historial';
        $order_dir = strtoupper($_GET['order_dir'] ?? '') === 'ASC' ? 'ASC' : 'DESC';

        $condiciones = [];
        $params      = [];

        if ($nro_envio !== '') {
            $condiciones[] = 'nro_envio = :nro_envio';
            $params[':nro_envio'] = (int)$nro_envio;
        }
        if ($estado !== '') {
            $condiciones[] = 'estado = :estado';
            $params[':estado'] = $estado;
        }
        if ($fecha_desde !== '') {
            $condiciones[] = 'fecha_hora >= :fecha_desde';
            $params[':fecha_desde'] = $fecha_desde;
        }
        if ($fecha_hasta !== '') {
            $condiciones[] = 'fecha_hora <= :fecha_hasta';
            $params[':fecha_hasta'] = $fecha_hasta . ' 23:59:59';
        }
        if ($sucursal_cod !== '') {
            $condiciones[] = 'sucursal_cod = :sucursal_cod';
            $params[':sucursal_cod'] = $sucursal_cod;
        }

        $where = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';

        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM historial_estado $where");
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        $stmtData = $pdo->prepare("SELECT * FROM historial_estado $where ORDER BY $order_by $order_dir LIMIT $limite OFFSET $offset");
        $stmtData->execute($params);
        $historial = $stmtData->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'datos'         => $historial,
            'total'         => $total,
            'pagina'        => $pagina,
            'total_paginas' => (int)ceil($total / $limite)
        ]);
        break;

    case 'POST':
        $datos = json_decode(file_get_contents("php://input"), true);
        $consulta = $pdo->prepare("INSERT INTO historial_estado (nro_envio, sucursal_cod, estado, fecha_hora, observacion) 
                                   VALUES (:envio, :sucursal, :estado, :fecha, :observacion)");
        $consulta->execute([
            ':envio'       => $datos['nro_envio'],
            ':sucursal'    => $datos['sucursal_cod'],
            ':estado'      => $datos['estado'],
            ':fecha'       => $datos['fecha_hora'],
            ':observacion' => $datos['observacion'] ?? null
        ]);
        echo json_encode([
            'mensaje' => 'Estado registrado',
            'id'      => $pdo->lastInsertId()
        ]);
        break;

    case 'PUT':
        $datos = json_decode(file_get_contents("php://input"), true);
        $consulta = $pdo->prepare("UPDATE historial_estado SET nro_envio = :envio, sucursal_cod = :sucursal, 
                                   estado = :estado, fecha_hora = :fecha, observacion = :observacion 
                                   WHERE id_historial = :id");
        $consulta->execute([
            ':envio'       => $datos['nro_envio'],
            ':sucursal'    => $datos['sucursal_cod'],
            ':estado'      => $datos['estado'],
            ':fecha'       => $datos['fecha_hora'],
            ':observacion' => $datos['observacion'] ?? null,
            ':id'          => $datos['id_historial']
        ]);
        echo json_encode(['mensaje' => 'Estado actualizado']);
        break;

    case 'DELETE':
        $datos = json_decode(file_get_contents("php://input"), true);
        $consulta = $pdo->prepare("DELETE FROM historial_estado WHERE id_historial = :id");
        $consulta->execute([':id' => $datos['id_historial']]);
        echo json_encode(['mensaje' => 'Estado eliminado']);
        break;
}