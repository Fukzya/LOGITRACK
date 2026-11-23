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

        $calle        = trim($_GET['calle']        ?? '');
        $cliente_dni  = trim($_GET['cliente_dni']  ?? '');
        $localidad_cod = trim($_GET['localidad_cod'] ?? '');

        $order_whitelist = ['direccion_id', 'calle_y_numero', 'localidad_cod', 'cliente_dni'];
        $order_by  = in_array($_GET['order_by'] ?? '', $order_whitelist) ? $_GET['order_by'] : 'direccion_id';
        $order_dir = strtoupper($_GET['order_dir'] ?? '') === 'DESC' ? 'DESC' : 'ASC';

        $mostrar_inactivos = isset($_GET['mostrar_inactivos'])
            && $_GET['mostrar_inactivos'] === '1'
            && $_SESSION['rol'] === 'admin';

        $condiciones = [];
        $params      = [];

        if (!$mostrar_inactivos) {
            $condiciones[] = 'activo = 1';
        }

        if ($calle !== '') {
            $condiciones[] = 'calle_y_numero LIKE :calle';
            $params[':calle'] = '%' . $calle . '%';
        }
        if ($cliente_dni !== '') {
            $condiciones[] = 'cliente_dni = :cliente_dni';
            $params[':cliente_dni'] = (int)$cliente_dni;
        }
        if ($localidad_cod !== '') {
            $condiciones[] = 'localidad_cod = :localidad_cod';
            $params[':localidad_cod'] = (int)$localidad_cod;
        }

        $where = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';

        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM direccion $where");
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        $stmtData = $pdo->prepare("SELECT * FROM direccion $where ORDER BY $order_by $order_dir LIMIT $limite OFFSET $offset");
        $stmtData->execute($params);
        $direcciones = $stmtData->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'datos'         => $direcciones,
            'total'         => $total,
            'pagina'        => $pagina,
            'total_paginas' => (int)ceil($total / $limite)
        ]);
        break;

    case 'POST':
        $datos = json_decode(file_get_contents("php://input"), true);
        $consulta = $pdo->prepare("INSERT INTO direccion (calle_y_numero, localidad_cod, cliente_dni) 
                                   VALUES (:calle, :localidad, :dni)");
        $consulta->execute([
            ':calle'     => $datos['calle_y_numero'],
            ':localidad' => $datos['localidad_cod'],
            ':dni'       => $datos['cliente_dni']
        ]);
        echo json_encode([
            'mensaje' => 'Dirección creada',
            'id'      => $pdo->lastInsertId()
        ]);
        break;

    case 'PUT':
        $datos = json_decode(file_get_contents("php://input"), true);

        if (!empty($datos['reactivar'])) {
            $id = filter_var($datos['direccion_id'] ?? 0, FILTER_SANITIZE_NUMBER_INT);
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de dirección requerido']);
                break;
            }
            $stmt = $pdo->prepare("UPDATE direccion SET activo = 1 WHERE direccion_id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            echo json_encode(['mensaje' => 'Dirección reactivada correctamente']);
            break;
        }

        $consulta = $pdo->prepare("UPDATE direccion SET calle_y_numero = :calle,
                                   localidad_cod = :localidad, cliente_dni = :dni
                                   WHERE direccion_id = :id");
        $consulta->execute([
            ':calle'     => $datos['calle_y_numero'],
            ':localidad' => $datos['localidad_cod'],
            ':dni'       => $datos['cliente_dni'],
            ':id'        => $datos['direccion_id']
        ]);
        echo json_encode(['mensaje' => 'Dirección actualizada']);
        break;

    case 'DELETE':
        $datos = json_decode(file_get_contents("php://input"), true);
        $id = filter_var($datos['direccion_id'] ?? 0, FILTER_SANITIZE_NUMBER_INT);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de dirección requerido']);
            break;
        }
        $stmt = $pdo->prepare("UPDATE direccion SET activo = 0 WHERE direccion_id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode(['mensaje' => 'Dirección eliminada correctamente']);
        break;
}