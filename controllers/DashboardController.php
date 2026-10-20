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

        return [
            'temp_promedio'      => $promedios['temp_promedio'],
            'hum_promedio'       => $promedios['hum_promedio'],
            'bat_promedio'       => $resNodos['bat_promedio']  ?? 72.0,
            'nodos_total'        => $resNodos['total']         ?? 6,
            'nodos_activos'      => $resNodos['activos']       ?? 5,
            'nodos_inactivos'    => $resNodos['inactivos']     ?? 1,
            'nodos_alerta'       => $resNodos['alertas']       ?? 1,
            'alertas_activas'    => Alerta::conteoActivas(),
            'alertas_recientes'  => $alertas,
            'estado_general'     => $estado,
            'nodos'              => $nodos,
            'db_conectada'       => getDB() !== null,
        ];
    }
}