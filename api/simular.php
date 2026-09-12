<?php
/**
 * api/simular.php
 * Genera lecturas climaticas simuladas para todos los nodos activos.
 * GET  /api/simular.php           -> genera 1 ronda de datos
 * GET  /api/simular.php?rondas=N  -> genera N rondas (max 50)
 * GET  /api/simular.php?reset=1   -> elimina lecturas antiguas y genera datos frescos
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../controllers/LecturaController.php';
require_once __DIR__ . '/../models/Nodo.php';

$rondas = min((int)($_GET['rondas'] ?? 1), 50);
$reset  = !empty($_GET['reset']);

if ($reset) {
    $db = getDB();
    if ($db) {
        $db->query('DELETE FROM lecturas');
        $db->query('DELETE FROM alertas');
        $db->query("UPDATE nodos SET temperatura_actual=NULL,humedad_actual=NULL,ultima_conexion=NULL,estado='activo'");
    }
}

$nodos    = Nodo::todos();
$generado = [];

// Bases climaticas por nodo
$bases = [
    1 => ['temp'=>22.0,'hum'=>66.0,'bat'=>82.0],
    2 => ['temp'=>23.0,'hum'=>68.5,'bat'=>79.0],
    3 => ['temp'=>21.5,'hum'=>64.8,'bat'=>76.0],
    4 => ['temp'=>24.0,'hum'=>70.0,'bat'=>71.0],
    5 => ['temp'=>22.0,'hum'=>67.5,'bat'=>80.0],
    6 => ['temp'=>21.0,'hum'=>65.0,'bat'=>45.0],
];

for ($r = 0; $r < $rondas; $r++) {
    foreach ($nodos as $nodo) {
        // Nodo 6 simulado como desconectado en algunas rondas
        if ($nodo['identificador'] === 'NODO-006' && $rondas <= 1) continue;

        $id   = (int)$nodo['id'];
        $base = $bases[$id] ?? ['temp'=>22.0,'hum'=>67.0,'bat'=>75.0];

        $temp = round($base['temp'] + (sin($r/5)*1.8) + ((rand(-15,15))/10), 1);
        $hum  = round($base['hum']  + (cos($r/4)*2.5) + ((rand(-10,10))/10), 1);
        $bat  = round(max(5, $base['bat'] - ($r * 0.05) + ((rand(-5,5))/10)), 1);
        $temp = max(-10, min(60, $temp));
        $hum  = max(0,   min(100,$hum));

        $res = LecturaController::recibir([
            'nodo_id'     => $id,
            'temperatura' => $temp,
            'humedad'     => $hum,
            'bateria'     => $bat,
        ]);

        $generado[] = ['nodo'=>$nodo['nombre'],'temp'=>$temp,'hum'=>$hum,'bat'=>$bat,'resultado'=>$res['ok']?'ok':'error'];
    }
}

echo json_encode([
    'status'   => 'ok',
    'rondas'   => $rondas,
    'total'    => count($generado),
    'datos'    => $generado,
    'mensaje'  => "Se generaron {$rondas} ronda(s) de datos simulados para ".count($nodos)." nodos",
]);