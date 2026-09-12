<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../controllers/LecturaController.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode([
        'ok' => true,
        'servicio' => 'recibir_datos',
        'metodo' => 'POST',
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: GET, POST');
    echo json_encode(['error'=>'Metodo no permitido. Use POST.']);
    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);

if ($raw !== '' && json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    error_log('[ESP32] JSON invalido desde ' . ($_SERVER['REMOTE_ADDR'] ?? 'origen desconocido'));
    echo json_encode(['ok'=>false,'error'=>'JSON invalido']);
    exit;
}

if (!is_array($payload) || empty($payload)) $payload = $_POST;

try {
    $resultado = LecturaController::recibir($payload);
    if (!$resultado['ok']) { http_response_code(400); }

    error_log(sprintf(
        '[ESP32] %s | nodo=%s | temperatura=%s | humedad=%s | bateria=%s',
        $resultado['ok'] ? 'Lectura aceptada' : 'Lectura rechazada',
        $payload['identificador'] ?? ($payload['nodo_id'] ?? 'desconocido'),
        $payload['temperatura'] ?? 'n/a',
        $payload['humedad'] ?? 'n/a',
        $payload['bateria'] ?? 'n/a'
    ));

    echo json_encode($resultado);
} catch (Throwable $e) {
    http_response_code(500);
    error_log('[ESP32] Error procesando lectura: ' . $e->getMessage());
    echo json_encode(['ok'=>false,'error'=>'Error interno procesando la lectura']);
}