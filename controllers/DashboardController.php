<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Nodo.php';
require_once __DIR__ . '/../models/Lectura.php';
require_once __DIR__ . '/../models/Alerta.php';
require_once __DIR__ . '/../models/Configuracion.php';
require_once __DIR__ . '/../controllers/AlertaController.php';

class DashboardController {

    public static function obtenerResumen(): array {
        Nodo::marcarInactivos();
        $resNodos   = Nodo::resumen();
        $promedios  = Lectura::promedios();
        $estado     = AlertaController::estadoGeneral();
        $alertas    = Alerta::activas(5);
        $nodos      = Nodo::todos();

        $temp = $promedios['temp_promedio'] ?? ($resNodos['temp_promedio'] ?? null);
        $hum  = $promedios['hum_promedio']  ?? ($resNodos['hum_promedio'] ?? null);
        $bat  = $resNodos['bat_promedio']   ?? null;

        return [
            'temp_promedio'      => $temp !== null ? (float)$temp : null,
            'hum_promedio'       => $hum !== null ? (float)$hum : null,
            'bat_promedio'       => $bat !== null ? (float)$bat : null,
            'nodos_total'        => (int)($resNodos['total'] ?? 0),
            'nodos_activos'      => (int)($resNodos['activos'] ?? 0),
            'nodos_en_linea'     => (int)($resNodos['en_linea'] ?? 0),
            'nodos_fuera_linea'  => (int)($resNodos['fuera_linea'] ?? 0),
            'nodos_inactivos'    => (int)($resNodos['inactivos'] ?? 0),
            'nodos_alerta'       => (int)($resNodos['alertas'] ?? 0),
            'alertas_activas'    => Alerta::conteoActivas(),
            'alertas_recientes'  => $alertas,
            'estado_general'     => $estado,
            'nodos'              => $nodos,
            'db_conectada'       => getDB() !== null,
        ];
    }
}