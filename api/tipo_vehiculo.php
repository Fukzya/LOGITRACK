<?php
require_once '../config/session.php';
verificar_sesion();
require_once '../config/db.php';

header('Content-Type: application/json');

$metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {

    case 'GET':
        $tipos = $pdo->query("SELECT * FROM tipo_vehiculo")
                     ->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($tipos);
        break;

    case 'POST':
        $datos = json_decode(file_get_contents("php://input"), true);
        $consulta = $pdo->prepare("INSERT INTO tipo_vehiculo (tipo_veh_cod, tipo_veh_nombre, tipo_veh_capacidad_kg, tipo_veh_requiere_lic_especial) 
                                   VALUES (:cod, :nombre, :capacidad, :licencia)");
        $consulta->execute([
            ':cod'      => $datos['tipo_veh_cod'],
            ':nombre'   => $datos['tipo_veh_nombre'],
            ':capacidad' => $datos['tipo_veh_capacidad_kg'],
            ':licencia' => $datos['tipo_veh_requiere_lic_especial']
        ]);
        echo json_encode([
            'mensaje' => 'Tipo de vehículo creado',
            'cod'     => $datos['tipo_veh_cod']
        ]);
        break;

    case 'PUT':
        $datos = json_decode(file_get_contents("php://input"), true);
        $consulta = $pdo->prepare("UPDATE tipo_vehiculo SET tipo_veh_nombre = :nombre, 
                                   tipo_veh_capacidad_kg = :capacidad, 
                                   tipo_veh_requiere_lic_especial = :licencia 
                                   WHERE tipo_veh_cod = :cod");
        $consulta->execute([
            ':nombre'   => $datos['tipo_veh_nombre'],
            ':capacidad' => $datos['tipo_veh_capacidad_kg'],
            ':licencia' => $datos['tipo_veh_requiere_lic_especial'],
            ':cod'      => $datos['tipo_veh_cod']
        ]);
        echo json_encode(['mensaje' => 'Tipo de vehículo actualizado']);
        break;

    case 'DELETE':
        $datos = json_decode(file_get_contents("php://input"), true);
        $consulta = $pdo->prepare("DELETE FROM tipo_vehiculo WHERE tipo_veh_cod = :cod");
        $consulta->execute([':cod' => $datos['tipo_veh_cod']]);
        echo json_encode(['mensaje' => 'Tipo de vehículo eliminado']);
        break;
}