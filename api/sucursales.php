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

        $nombre = trim($_GET['nombre'] ?? '');

        $mostrar_inactivos = isset($_GET['mostrar_inactivos'])
            && $_GET['mostrar_inactivos'] === '1'
            && $_SESSION['rol'] === 'admin';

        $order_whitelist = ['sucursal_cod', 'sucursal_nombre', 'sucursal_localidad_cod'];
        $order_by  = in_array($_GET['order_by'] ?? '', $order_whitelist) ? $_GET['order_by'] : 'sucursal_cod';
        $order_dir = strtoupper($_GET['order_dir'] ?? '') === 'DESC' ? 'DESC' : 'ASC';

        $condiciones = [];
        $params      = [];

        if (!$mostrar_inactivos) {
            $condiciones[] = 'activo = 1';
        }
        if ($nombre !== '') {
            $condiciones[] = 'sucursal_nombre LIKE :nombre';
            $params[':nombre'] = '%' . $nombre . '%';
        }

        $where = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';

        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM sucursal $where");
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        $stmtData = $pdo->prepare("SELECT sucursal_cod, sucursal_nombre, sucursal_localidad_cod, direccion, activo FROM sucursal $where ORDER BY $order_by $order_dir LIMIT $limite OFFSET $offset");
        $stmtData->execute($params);
        $sucursales = $stmtData->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'datos'         => $sucursales,
            'total'         => $total,
            'pagina'        => $pagina,
            'total_paginas' => (int)ceil($total / $limite)
        ]);
        break;

    case 'POST':
        $datos = json_decode(file_get_contents("php://input"), true);
        $consulta = $pdo->prepare("INSERT INTO sucursal (sucursal_cod, sucursal_nombre, sucursal_localidad_cod, direccion)
                                   VALUES (:cod, :nombre, :localidad, :direccion)");
        $consulta->execute([
            ':cod'       => $datos['sucursal_cod'],
            ':nombre'    => $datos['sucursal_nombre'],
            ':localidad' => $datos['sucursal_localidad_cod'],
            ':direccion' => $datos['direccion']
        ]);
        echo json_encode([
            'mensaje' => 'Sucursal creada',
            'cod'     => $datos['sucursal_cod']
        ]);
        break;

    case 'PUT':
        $datos = json_decode(file_get_contents("php://input"), true);

        if (!empty($datos['reactivar'])) {
            $cod = trim($datos['sucursal_cod'] ?? '');
            if ($cod === '') {
                http_response_code(400);
                echo json_encode(['error' => 'Código de sucursal requerido']);
                break;
            }
            $stmt = $pdo->prepare("UPDATE sucursal SET activo = 1 WHERE sucursal_cod = :cod");
            $stmt->bindParam(':cod', $cod, PDO::PARAM_STR);
            $stmt->execute();
            echo json_encode(['mensaje' => 'Sucursal reactivada correctamente']);
            break;
        }

        $consulta = $pdo->prepare("UPDATE sucursal SET sucursal_nombre = :nombre,
                                   sucursal_localidad_cod = :localidad, direccion = :direccion
                                   WHERE sucursal_cod = :cod");
        $consulta->execute([
            ':nombre'    => $datos['sucursal_nombre'],
            ':localidad' => $datos['sucursal_localidad_cod'],
            ':direccion' => $datos['direccion'],
            ':cod'       => $datos['sucursal_cod']
        ]);
        echo json_encode(['mensaje' => 'Sucursal actualizada']);
        break;

    case 'DELETE':
        $datos = json_decode(file_get_contents("php://input"), true);
        $cod   = trim($datos['sucursal_cod'] ?? '');
        if ($cod === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Código de sucursal requerido']);
            break;
        }
        $stmt = $pdo->prepare("UPDATE sucursal SET activo = 0 WHERE sucursal_cod = :cod");
        $stmt->bindParam(':cod', $cod, PDO::PARAM_STR);
        $stmt->execute();
        echo json_encode(['mensaje' => 'Sucursal dada de baja correctamente']);
        break;
}
