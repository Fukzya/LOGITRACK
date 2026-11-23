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

        $patente     = trim($_GET['patente']      ?? '');
        $marca       = trim($_GET['marca']        ?? '');
        $tipo_veh_cod = trim($_GET['tipo_veh_cod'] ?? '');

        $mostrar_inactivos = isset($_GET['mostrar_inactivos'])
            && $_GET['mostrar_inactivos'] === '1'
            && $_SESSION['rol'] === 'admin';

        $order_whitelist = ['vehiculo_patente', 'marca', 'modelo', 'anio', 'tipo_veh_cod'];
        $order_by  = in_array($_GET['order_by'] ?? '', $order_whitelist) ? $_GET['order_by'] : 'vehiculo_patente';
        $order_dir = strtoupper($_GET['order_dir'] ?? '') === 'DESC' ? 'DESC' : 'ASC';

        $condiciones = [];
        $params      = [];

        if (!$mostrar_inactivos) {
            $condiciones[] = 'activo = 1';
        }
        if ($patente !== '') {
            $condiciones[] = 'vehiculo_patente LIKE :patente';
            $params[':patente'] = '%' . $patente . '%';
        }
        if ($marca !== '') {
            $condiciones[] = 'marca LIKE :marca';
            $params[':marca'] = '%' . $marca . '%';
        }
        if ($tipo_veh_cod !== '') {
            $condiciones[] = 'tipo_veh_cod = :tipo_veh_cod';
            $params[':tipo_veh_cod'] = $tipo_veh_cod;
        }

        $where = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';

        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM vehiculo $where");
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        $stmtData = $pdo->prepare("SELECT vehiculo_patente, marca, modelo, anio, tipo_veh_cod, activo FROM vehiculo $where ORDER BY $order_by $order_dir LIMIT $limite OFFSET $offset");
        $stmtData->execute($params);
        $vehiculos = $stmtData->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'datos'         => $vehiculos,
            'total'         => $total,
            'pagina'        => $pagina,
            'total_paginas' => (int)ceil($total / $limite)
        ]);
        break;

    case 'POST':
        $datos = json_decode(file_get_contents("php://input"), true);
        $consulta = $pdo->prepare("INSERT INTO vehiculo (vehiculo_patente, marca, modelo, anio, tipo_veh_cod)
                                   VALUES (:patente, :marca, :modelo, :anio, :tipo)");
        $consulta->execute([
            ':patente' => $datos['vehiculo_patente'],
            ':marca'   => $datos['marca'],
            ':modelo'  => $datos['modelo'],
            ':anio'    => $datos['anio'],
            ':tipo'    => $datos['tipo_veh_cod']
        ]);
        echo json_encode([
            'mensaje' => 'Vehículo creado',
            'patente' => $datos['vehiculo_patente']
        ]);
        break;

    case 'PUT':
        $datos = json_decode(file_get_contents("php://input"), true);

        if (!empty($datos['reactivar'])) {
            $patente = trim($datos['vehiculo_patente'] ?? '');
            if ($patente === '') {
                http_response_code(400);
                echo json_encode(['error' => 'Patente requerida']);
                break;
            }
            $stmt = $pdo->prepare("UPDATE vehiculo SET activo = 1 WHERE vehiculo_patente = :patente");
            $stmt->bindParam(':patente', $patente, PDO::PARAM_STR);
            $stmt->execute();
            echo json_encode(['mensaje' => 'Vehículo reactivado correctamente']);
            break;
        }

        $consulta = $pdo->prepare("UPDATE vehiculo SET marca = :marca, modelo = :modelo,
                                   anio = :anio, tipo_veh_cod = :tipo
                                   WHERE vehiculo_patente = :patente");
        $consulta->execute([
            ':marca'   => $datos['marca'],
            ':modelo'  => $datos['modelo'],
            ':anio'    => $datos['anio'],
            ':tipo'    => $datos['tipo_veh_cod'],
            ':patente' => $datos['vehiculo_patente']
        ]);
        echo json_encode(['mensaje' => 'Vehículo actualizado']);
        break;

    case 'DELETE':
        $datos   = json_decode(file_get_contents("php://input"), true);
        $patente = trim($datos['vehiculo_patente'] ?? '');
        if ($patente === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Patente requerida']);
            break;
        }
        $stmt = $pdo->prepare("UPDATE vehiculo SET activo = 0 WHERE vehiculo_patente = :patente");
        $stmt->bindParam(':patente', $patente, PDO::PARAM_STR);
        $stmt->execute();
        echo json_encode(['mensaje' => 'Vehículo dado de baja correctamente']);
        break;
}
