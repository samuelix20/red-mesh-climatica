<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../controllers/DashboardController.php';

$inicio = microtime(true);

try {
	$datos = DashboardController::obtenerResumen();
	$duracion = round((microtime(true) - $inicio) * 1000, 2);

	error_log(sprintf(
		'[Dashboard] Resumen generado | nodos=%d activos=%d alertas=%d | %sms',
		$datos['nodos_total'] ?? 0,
		$datos['nodos_activos'] ?? 0,
		$datos['alertas_activas'] ?? 0,
		$duracion
	));

	echo json_encode($datos);
} catch (Throwable $e) {
	error_log('[Dashboard] Error: ' . $e->getMessage());
	http_response_code(500);
	echo json_encode(['ok' => false, 'error' => 'No se pudo cargar el dashboard']);
}