<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../controllers/ConfiguracionController.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode(['status'=>'ok','configuracion'=>ConfiguracionController::obtener()]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    echo json_encode(ConfiguracionController::guardar($datos));
} else {
    http_response_code(405);
    echo json_encode(['error'=>'Metodo no permitido']);
}