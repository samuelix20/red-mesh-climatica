<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Configuracion.php';

class ConfiguracionController {

    public static function obtener(): array { return Configuracion::obtenerValores(); }

    public static function guardar(array $datos): array {
        $campos = ['temp_max','temp_min','humedad_max','humedad_min','bateria_minima','intervalo_actualizacion'];
        $filtrado = [];
        foreach ($campos as $c) {
            if (isset($datos[$c]) && is_numeric($datos[$c])) $filtrado[$c] = $datos[$c];
        }
        if (empty($filtrado)) return ['ok'=>false,'error'=>'No se enviaron valores validos'];
        return Configuracion::guardarMultiples($filtrado)
            ? ['ok'=>true,'mensaje'=>'Configuracion guardada correctamente']
            : ['ok'=>false,'error'=>'Error al guardar configuracion'];
    }
}