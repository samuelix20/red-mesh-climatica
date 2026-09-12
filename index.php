<?php
require_once __DIR__ . '/config/database.php';

$pagina = $_GET['pagina'] ?? 'dashboard';
$vistas = [
    'dashboard'     => __DIR__ . '/views/dashboard.php',
    'nodos'         => __DIR__ . '/views/nodos.php',
    'alertas'       => __DIR__ . '/views/alertas.php',
    'historial'     => __DIR__ . '/views/historial.php',
    'configuracion' => __DIR__ . '/views/configuracion.php',
];

if (array_key_exists($pagina, $vistas)) {
    require_once $vistas[$pagina];
} else {
    http_response_code(404);
    echo '<h1 style="font-family:sans-serif;color:#fff;background:#080818;padding:2rem">404 – Pagina no encontrada</h1>';
}