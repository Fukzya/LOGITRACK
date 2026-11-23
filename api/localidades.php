<?php
require_once '../config/session.php';
verificar_sesion();
require_once '../config/db.php';

header('Content-Type: application/json');

$metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {

    case 'GET':
        $order_whitelist = ['localidad_cod', 'localidad_nombre', 'provincia_cod'];
        $order_by  = in_array($_GET['order_by'] ?? '', $order_whitelist) ? $_GET['order_by'] : 'provincia_cod';
        $order_dir = strtoupper($_GET['order_dir'] ?? '') === 'DESC' ? 'DESC' : 'ASC';

        $localidades = $pdo->query("SELECT * FROM localidad ORDER BY $order_by $order_dir")
                           ->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($localidades);
        break;
    case 'POST':
        $datos = json_decode(file_get_contents("php://input"), true);
        $consulta = $pdo->prepare("INSERT INTO localidad (localidad_nombre, provincia_cod) VALUES (:nombre, :provincia)");
        $consulta->execute([
            ':nombre'   => $datos['localidad_nombre'],
            ':provincia' => $datos['provincia_cod']
        ]);
        echo json_encode([
            'mensaje' => 'Localidad creada',
            'id'      => $pdo->lastInsertId()
        ]);
        break;    
    case 'PUT':
        $datos = json_decode(file_get_contents("php://input"), true);
        $consulta = $pdo->prepare("UPDATE localidad SET localidad_nombre = :nombre, provincia_cod = :provincia WHERE localidad_cod = :cod");
        $consulta->execute([
            ':nombre'    => $datos['localidad_nombre'],
            ':provincia' => $datos['provincia_cod'],
            ':cod'       => $datos['localidad_cod']
        ]);
        echo json_encode(['mensaje' => 'Localidad actualizada']);
        break;
    case 'DELETE':
        $datos = json_decode(file_get_contents("php://input"), true);
        $consulta = $pdo->prepare("DELETE FROM localidad WHERE localidad_cod = :cod");
        $consulta->execute([':cod' => $datos['localidad_cod']]);
        echo json_encode(['mensaje' => 'Localidad eliminada']);
        break;
}