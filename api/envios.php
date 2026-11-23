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

        $fecha_desde = trim($_GET['fecha_desde']  ?? '');
        $fecha_hasta = trim($_GET['fecha_hasta']   ?? '');
        $suc_orig    = trim($_GET['sucursal_orig'] ?? '');
        $tipo_cont   = trim($_GET['tipo_cont']     ?? '');

        $order_whitelist = ['nro_envio', 'fecha_envio', 'peso_kg'];
        $order_by  = in_array($_GET['order_by'] ?? '', $order_whitelist) ? $_GET['order_by'] : 'nro_envio';
        $order_dir = strtoupper($_GET['order_dir'] ?? '') === 'ASC' ? 'ASC' : 'DESC';

        $mostrar_inactivos = isset($_GET['mostrar_inactivos'])
            && $_GET['mostrar_inactivos'] === '1'
            && $_SESSION['rol'] === 'admin';

        $condiciones = [];
        $params      = [];

        if (!$mostrar_inactivos) {
            $condiciones[] = 'activo = 1';
        }

        if ($fecha_desde !== '') {
            $condiciones[] = 'fecha_envio >= :fecha_desde';
            $params[':fecha_desde'] = $fecha_desde;
        }
        if ($fecha_hasta !== '') {
            $condiciones[] = 'fecha_envio <= :fecha_hasta';
            $params[':fecha_hasta'] = $fecha_hasta;
        }
        if ($suc_orig !== '') {
            $condiciones[] = 'sucursal_orig_cod = :suc_orig';
            $params[':suc_orig'] = $suc_orig;
        }
        if ($tipo_cont !== '') {
            $condiciones[] = 'tipo_cont_cod = :tipo_cont';
            $params[':tipo_cont'] = $tipo_cont;
        }

        $where = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';

        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM envio $where");
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        $stmtData = $pdo->prepare("SELECT nro_envio, fecha_envio, peso_kg, tipo_cont_cod, remitente_dni, destinatario_dni, sucursal_orig_cod, sucursal_dest_cod, direccion_entrega_id, activo FROM envio $where ORDER BY $order_by $order_dir LIMIT $limite OFFSET $offset");
        $stmtData->execute($params);
        $envios = $stmtData->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'datos'         => $envios,
            'total'         => $total,
            'pagina'        => $pagina,
            'total_paginas' => (int)ceil($total / $limite)
        ]);
        break;

    case 'POST':
        $datos = json_decode(file_get_contents("php://input"), true);

        $fecha        = filter_var($datos['fecha_envio']          ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
        $peso         = filter_var($datos['peso_kg']              ?? '', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $tipo         = filter_var($datos['tipo_cont_cod']        ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
        $remitente    = filter_var($datos['remitente_dni']        ?? 0,  FILTER_SANITIZE_NUMBER_INT);
        $destinatario = filter_var($datos['destinatario_dni']     ?? 0,  FILTER_SANITIZE_NUMBER_INT);
        $suc_orig     = filter_var($datos['sucursal_orig_cod']    ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
        $suc_dest     = filter_var($datos['sucursal_dest_cod']    ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
        $direccion    = filter_var($datos['direccion_entrega_id'] ?? 0,  FILTER_SANITIZE_NUMBER_INT);

        if (!$fecha || !$peso || !$tipo || !$remitente || !$destinatario || !$suc_orig || !$suc_dest || !$direccion) {
            http_response_code(400);
            echo json_encode(['error' => 'Todos los campos son obligatorios']);
            break;
        }

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("CALL procedimiento_crear_envio(:fecha, :peso, :tipo, :dni_rem, :dni_dest, :suc_orig, :suc_dest, :direccion_id)");
            $stmt->bindParam(':fecha',        $fecha,        PDO::PARAM_STR);
            $stmt->bindParam(':peso',         $peso,         PDO::PARAM_STR);
            $stmt->bindParam(':tipo',         $tipo,         PDO::PARAM_STR);
            $stmt->bindParam(':dni_rem',      $remitente,    PDO::PARAM_INT);
            $stmt->bindParam(':dni_dest',     $destinatario, PDO::PARAM_INT);
            $stmt->bindParam(':suc_orig',     $suc_orig,     PDO::PARAM_STR);
            $stmt->bindParam(':suc_dest',     $suc_dest,     PDO::PARAM_STR);
            $stmt->bindParam(':direccion_id', $direccion,    PDO::PARAM_INT);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            $nro_envio = isset($resultado['nro_envio_creado']) ? (int)$resultado['nro_envio_creado'] : 0;
            $stmt->closeCursor();
            $pdo->commit();

            echo json_encode(['ok' => true, 'nro_envio_creado' => $nro_envio]);

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            http_response_code(500);
            echo json_encode(['error' => htmlspecialchars($e->getMessage())]);
        }
        break;

    case 'PUT':
        $datos = json_decode(file_get_contents("php://input"), true);
        $consulta = $pdo->prepare("UPDATE envio SET fecha_envio = :fecha, peso_kg = :peso, 
                                   tipo_cont_cod = :tipo, remitente_dni = :remitente, 
                                   destinatario_dni = :destinatario, sucursal_orig_cod = :suc_orig, 
                                   sucursal_dest_cod = :suc_dest, direccion_entrega_id = :direccion 
                                   WHERE nro_envio = :nro");
        $consulta->execute([
            ':fecha'        => $datos['fecha_envio'],
            ':peso'         => $datos['peso_kg'],
            ':tipo'         => $datos['tipo_cont_cod'],
            ':remitente'    => $datos['remitente_dni'],
            ':destinatario' => $datos['destinatario_dni'],
            ':suc_orig'     => $datos['sucursal_orig_cod'],
            ':suc_dest'     => $datos['sucursal_dest_cod'],
            ':direccion'    => $datos['direccion_entrega_id'],
            ':nro'          => $datos['nro_envio']
        ]);
        echo json_encode(['mensaje' => 'Envío actualizado']);
        break;

    case 'DELETE':
        $datos = json_decode(file_get_contents("php://input"), true);
        $nro = filter_var($datos['nro_envio'] ?? 0, FILTER_SANITIZE_NUMBER_INT);
        if (!$nro) {
            http_response_code(400);
            echo json_encode(['error' => 'Número de envío requerido']);
            break;
        }
        $stmt = $pdo->prepare("UPDATE envio SET activo = 0 WHERE nro_envio = :nro");
        $stmt->bindParam(':nro', $nro, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode(['mensaje' => 'Envío eliminado correctamente']);
        break;
}