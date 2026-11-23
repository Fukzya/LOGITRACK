<?php
require_once '../config/session.php';
verificar_sesion();
require_once '../config/db.php';

header('Content-Type: application/json');

$metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {

    case 'GET':
        $provincias = $pdo->query("SELECT * FROM provincia")
        ->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($provincias);
        break;

    case 'POST':
        $datos = json_decode(file_get_contents("php://input"), true);
        $consulta = $pdo->prepare("INSERT INTO provincia (provincia_nombre) VALUES (:nombre)");
        $consulta->execute([':nombre' => $datos['provincia_nombre']]);
        echo json_encode([
            'mensaje' => 'Provincia creada',
            'id'      => $pdo->lastInsertId()
        ]);
        break;

    case 'PUT':
        $datos = json_decode(file_get_contents("php://input"), true);
        $consulta = $pdo->prepare("UPDATE provincia SET provincia_nombre = :nombre WHERE provincia_cod = :cod");
        $consulta->execute([
            ':nombre' => $datos['provincia_nombre'],
            ':cod'    => $datos['provincia_cod']
        ]);
        echo json_encode(['mensaje' => 'Provincia actualizada']);
        break;

    case 'DELETE':
        $datos = json_decode(file_get_contents("php://input"), true);
        $consulta = $pdo->prepare("DELETE FROM provincia WHERE provincia_cod = :cod");
        $consulta->execute([':cod' => $datos['provincia_cod']]);
        echo json_encode(['mensaje' => 'Provincia eliminada']);
        break;
}