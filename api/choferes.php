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

        $nombre        = trim($_GET['nombre']        ?? '');
        $apellido      = trim($_GET['apellido']      ?? '');
        $tipo_licencia = trim($_GET['tipo_licencia'] ?? '');

        $mostrar_inactivos = isset($_GET['mostrar_inactivos'])
            && $_GET['mostrar_inactivos'] === '1'
            && $_SESSION['rol'] === 'admin';

        $order_whitelist = ['chofer_legajo', 'nombre', 'apellido', 'chofer_tipo_licencia'];
        $order_by  = in_array($_GET['order_by'] ?? '', $order_whitelist) ? $_GET['order_by'] : 'chofer_legajo';
        $order_dir = strtoupper($_GET['order_dir'] ?? '') === 'DESC' ? 'DESC' : 'ASC';

        $condiciones = [];
        $params      = [];

        if (!$mostrar_inactivos) {
            $condiciones[] = 'activo = 1';
        }
        if ($nombre !== '') {
            $condiciones[] = 'nombre LIKE :nombre';
            $params[':nombre'] = '%' . $nombre . '%';
        }
        if ($apellido !== '') {
            $condiciones[] = 'apellido LIKE :apellido';
            $params[':apellido'] = '%' . $apellido . '%';
        }
        if ($tipo_licencia !== '') {
            $condiciones[] = 'chofer_tipo_licencia = :tipo_licencia';
            $params[':tipo_licencia'] = $tipo_licencia;
        }

        $where = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';

        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM chofer $where");
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        $stmtData = $pdo->prepare("SELECT chofer_legajo, nombre, apellido, chofer_tipo_licencia, activo FROM chofer $where ORDER BY $order_by $order_dir LIMIT $limite OFFSET $offset");
        $stmtData->execute($params);
        $choferes = $stmtData->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'datos'         => $choferes,
            'total'         => $total,
            'pagina'        => $pagina,
            'total_paginas' => (int)ceil($total / $limite)
        ]);
        break;

    case 'POST':
        $datos = json_decode(file_get_contents("php://input"), true);
        $consulta = $pdo->prepare("INSERT INTO chofer (nombre, apellido, chofer_tipo_licencia)
                                   VALUES (:nombre, :apellido, :licencia)");
        $consulta->execute([
            ':nombre'   => $datos['nombre'],
            ':apellido' => $datos['apellido'],
            ':licencia' => $datos['chofer_tipo_licencia']
        ]);
        echo json_encode([
            'mensaje' => 'Chofer creado',
            'id'      => $pdo->lastInsertId()
        ]);
        break;

    case 'PUT':
        $datos = json_decode(file_get_contents("php://input"), true);

        if (!empty($datos['reactivar'])) {
            $legajo = filter_var($datos['chofer_legajo'] ?? 0, FILTER_SANITIZE_NUMBER_INT);
            if (!$legajo) {
                http_response_code(400);
                echo json_encode(['error' => 'Legajo requerido']);
                break;
            }
            $stmt = $pdo->prepare("UPDATE chofer SET activo = 1 WHERE chofer_legajo = :legajo");
            $stmt->bindParam(':legajo', $legajo, PDO::PARAM_INT);
            $stmt->execute();
            echo json_encode(['mensaje' => 'Chofer reactivado correctamente']);
            break;
        }

        $consulta = $pdo->prepare("UPDATE chofer SET nombre = :nombre, apellido = :apellido,
                                   chofer_tipo_licencia = :licencia
                                   WHERE chofer_legajo = :legajo");
        $consulta->execute([
            ':nombre'   => $datos['nombre'],
            ':apellido' => $datos['apellido'],
            ':licencia' => $datos['chofer_tipo_licencia'],
            ':legajo'   => $datos['chofer_legajo']
        ]);
        echo json_encode(['mensaje' => 'Chofer actualizado']);
        break;

    case 'DELETE':
        $datos  = json_decode(file_get_contents("php://input"), true);
        $legajo = filter_var($datos['chofer_legajo'] ?? 0, FILTER_SANITIZE_NUMBER_INT);
        if (!$legajo) {
            http_response_code(400);
            echo json_encode(['error' => 'Legajo del chofer requerido']);
            break;
        }
        $stmt = $pdo->prepare("UPDATE chofer SET activo = 0 WHERE chofer_legajo = :legajo");
        $stmt->bindParam(':legajo', $legajo, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode(['mensaje' => 'Chofer dado de baja correctamente']);
        break;
}
