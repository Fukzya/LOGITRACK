<?php
require_once '../config/session.php';
verificar_sesion();
require_once '../config/db.php';

header('Content-Type: application/json');

$metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {

    case 'GET':
        $tipos = $pdo->query("SELECT * FROM tipo_contenido")
                     ->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($tipos);
        break;

    case 'POST':
        $datos = json_decode(file_get_contents("php://input"), true);
        $consulta = $pdo->prepare("INSERT INTO tipo_contenido (tipo_cont_cod, tipo_cont_nombre, tipo_cont_requiere_seguro) 
                                   VALUES (:cod, :nombre, :seguro)");
        $consulta->execute([
            ':cod'    => $datos['tipo_cont_cod'],
            ':nombre' => $datos['tipo_cont_nombre'],
            ':seguro' => $datos['tipo_cont_requiere_seguro']
        ]);
        echo json_encode([
            'mensaje' => 'Tipo de contenido creado',
            'cod'     => $datos['tipo_cont_cod']
        ]);
        break;

    case 'PUT':
        $datos = json_decode(file_get_contents("php://input"), true);
        $consulta = $pdo->prepare("UPDATE tipo_contenido SET tipo_cont_nombre = :nombre, 
                                   tipo_cont_requiere_seguro = :seguro 
                                   WHERE tipo_cont_cod = :cod");
        $consulta->execute([
            ':nombre' => $datos['tipo_cont_nombre'],
            ':seguro' => $datos['tipo_cont_requiere_seguro'],
            ':cod'    => $datos['tipo_cont_cod']
        ]);
        echo json_encode(['mensaje' => 'Tipo de contenido actualizado']);
        break;

    case 'DELETE':
        $datos = json_decode(file_get_contents("php://input"), true);
        $consulta = $pdo->prepare("DELETE FROM tipo_contenido WHERE tipo_cont_cod = :cod");
        $consulta->execute([':cod' => $datos['tipo_cont_cod']]);
        echo json_encode(['mensaje' => 'Tipo de contenido eliminado']);
        break;
}