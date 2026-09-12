<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../controllers/LecturaController.php';

$nodoId = isset($_GET['nodo_id']) ? (int)$_GET['nodo_id'] : 0;
$rango  = $_GET['rango'] ?? '6h';

$datos = LecturaController::paraGrafica($nodoId,$rango);
echo json_encode(['status'=>'ok','total'=>count($datos),'datos'=>$datos]);