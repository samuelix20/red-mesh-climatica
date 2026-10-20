<?php
header('Content-Type: application/json');
$t = [];
$t['start'] = microtime(true);

require_once __DIR__ . '/../config/database.php';
$t['after_require'] = microtime(true);

$db = getDB();
$t['after_db_connect'] = microtime(true);

if (!$db) { echo json_encode(['error' => 'No DB']); exit; }

// Test 1: Nodo lookup
require_once __DIR__ . '/../models/Nodo.php';
$nodo = Nodo::porIdentificador('NODO-001');
$t['after_nodo_lookup'] = microtime(true);

// Test 2: Configuracion
require_once __DIR__ . '/../models/Configuracion.php';
$cfg = Configuracion::obtenerValores();
$t['after_config'] = microtime(true);

// Test 3: Lectura insertar
require_once __DIR__ . '/../models/Lectura.php';
$lid = Lectura::insertar((int)$nodo['id'], 25.0, 55.0, 100.0, null);
$t['after_lectura_insert'] = microtime(true);

// Test 4: Nodo actualizar
Nodo::actualizarMediciones((int)$nodo['id'], 25.0, 55.0, 100.0, 'activo');
$t['after_nodo_update'] = microtime(true);

// Test 5: Alertas evaluar
require_once __DIR__ . '/../models/Alerta.php';
Alerta::resolverPorNodoYTipo((int)$nodo['id'], 'temperatura_alta');
Alerta::resolverPorNodoYTipo((int)$nodo['id'], 'temperatura_baja');
Alerta::resolverPorNodoYTipo((int)$nodo['id'], 'humedad_fuera_rango');
Alerta::resolverPorNodoYTipo((int)$nodo['id'], 'bateria_baja');
$t['after_alertas'] = microtime(true);

$prev = $t['start'];
$tiempos = [];
foreach ($t as $k => $v) {
    if ($k === 'start') { $prev = $v; continue; }
    $tiempos[$k] = round(($v - $prev) * 1000, 2) . ' ms';
    $prev = $v;
}
$tiempos['TOTAL'] = round(($t['after_alertas'] - $t['start']) * 1000, 2) . ' ms';

echo json_encode(['ok' => true, 'tiempos' => $tiempos], JSON_PRETTY_PRINT);
