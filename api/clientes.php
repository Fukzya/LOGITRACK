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

        $nombre   = trim($_GET['nombre']   ?? '');
        $apellido = trim($_GET['apellido'] ?? '');
        $dni      = trim($_GET['dni']      ?? '');

        $mostrar_inactivos = isset($_GET['mostrar_inactivos'])
            && $_GET['mostrar_inactivos'] === '1'
            && $_SESSION['rol'] === 'admin';

        $order_whitelist = ['cliente_dni', 'nombre', 'apellido'];
        $order_by  = in_array($_GET['order_by'] ?? '', $order_whitelist) ? $_GET['order_by'] : 'cliente_dni';
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
        if ($dni !== '') {
            $condiciones[] = 'cliente_dni = :dni';
            $params[':dni'] = (int)$dni;
        }

        $where = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';

        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM cliente $where");
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        $stmtData = $pdo->prepare("SELECT cliente_dni, nombre, apellido, mail, telefono, localidad_cod, activo FROM cliente $where ORDER BY $order_by $order_dir LIMIT $limite OFFSET $offset");
        $stmtData->execute($params);
        $clientes = $stmtData->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'datos'         => $clientes,
            'total'         => $total,
            'pagina'        => $pagina,
            'total_paginas' => (int)ceil($total / $limite)
        ]);
        break;

    case 'POST':
        $datos = json_decode(file_get_contents("php://input"), true);
        $consulta = $pdo->prepare("INSERT INTO cliente (cliente_dni, nombre, apellido, mail, telefono, localidad_cod) 
                                   VALUES (:dni, :nombre, :apellido, :mail, :telefono, :localidad)");
        $consulta->execute([
            ':dni'       => $datos['cliente_dni'],
            ':nombre'    => $datos['nombre'],
            ':apellido'  => $datos['apellido'],
            ':mail'      => $datos['mail'],
            ':telefono'  => $datos['telefono'],
            ':localidad' => $datos['localidad_cod']
        ]);
        echo json_encode([
            'mensaje' => 'Cliente creado',
            'dni'     => $datos['cliente_dni']
        ]);
        break;

    case 'PUT':
        $datos = json_decode(file_get_contents("php://input"), true);

        if (!empty($datos['reactivar'])) {
            $dni = filter_var($datos['cliente_dni'] ?? 0, FILTER_SANITIZE_NUMBER_INT);
            if (!$dni) {
                http_response_code(400);
                echo json_encode(['error' => 'DNI del cliente requerido']);
                break;
            }
            $stmt = $pdo->prepare("UPDATE cliente SET activo = 1 WHERE cliente_dni = :dni");
            $stmt->bindParam(':dni', $dni, PDO::PARAM_INT);
            $stmt->execute();
            echo json_encode(['mensaje' => 'Cliente reactivado correctamente']);
            break;
        }

        $consulta = $pdo->prepare("UPDATE cliente SET nombre = :nombre, apellido = :apellido,
                                   mail = :mail, telefono = :telefono, localidad_cod = :localidad
                                   WHERE cliente_dni = :dni");
        $consulta->execute([
            ':nombre'    => $datos['nombre'],
            ':apellido'  => $datos['apellido'],
            ':mail'      => $datos['mail'],
            ':telefono'  => $datos['telefono'],
            ':localidad' => $datos['localidad_cod'],
            ':dni'       => $datos['cliente_dni']
        ]);
        echo json_encode(['mensaje' => 'Cliente actualizado']);
        break;

    case 'DELETE':
        $datos = json_decode(file_get_contents("php://input"), true);
        $dni = filter_var($datos['cliente_dni'] ?? 0, FILTER_SANITIZE_NUMBER_INT);
        if (!$dni) {
            http_response_code(400);
            echo json_encode(['error' => 'DNI del cliente requerido']);
            break;
        }
        $stmt = $pdo->prepare("UPDATE cliente SET activo = 0 WHERE cliente_dni = :dni");
        $stmt->bindParam(':dni', $dni, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode(['mensaje' => 'Cliente eliminado correctamente']);
        break;
}