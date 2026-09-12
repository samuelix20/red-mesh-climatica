<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../controllers/AlertaController.php';
require_once __DIR__ . '/../models/Alerta.php';

$accion = $_GET['accion'] ?? '';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$tipo   = $_GET['tipo'] ?? 'activas';

if ($accion === 'reconocer' && $id) { echo json_encode(['ok'=>AlertaController::reconocer($id)]); exit; }
if ($accion === 'resolver'  && $id) { echo json_encode(['ok'=>AlertaController::resolver($id)]);  exit; }
if ($accion === 'estado')            { echo json_encode(AlertaController::estadoGeneral());         exit; }

$alertas = $tipo === 'historial' ? Alerta::historial(100) : Alerta::activas(50);
echo json_encode(['status'=>'ok','total'=>count($alertas),'alertas'=>$alertas]);