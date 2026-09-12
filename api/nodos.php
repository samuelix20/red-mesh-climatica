<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
require_once __DIR__ . '/../controllers/NodoController.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

$metodo = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$body   = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($metodo) {
    case 'GET':
        echo json_encode($id ? NodoController::obtener($id) : NodoController::listar());
        break;
    case 'POST':
        echo json_encode(NodoController::registrar($body ?: $_POST));
        break;
    case 'PUT':
        if (!$id) { http_response_code(400); echo json_encode(['error'=>'Se requiere ?id=']); break; }
        echo json_encode(NodoController::actualizar($id,$body));
        break;
    case 'DELETE':
        if (!$id) { http_response_code(400); echo json_encode(['error'=>'Se requiere ?id=']); break; }
        echo json_encode(NodoController::eliminar($id));
        break;
    default:
        http_response_code(405);
        echo json_encode(['error'=>'Metodo no permitido']);
}