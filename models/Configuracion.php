<?php
require_once __DIR__ . '/../config/database.php';

class Configuracion {

    private static array $defaults = [
        'temp_max'                => 30.0,
        'temp_min'                => 10.0,
        'humedad_max'             => 80.0,
        'humedad_min'             => 20.0,
        'bateria_minima'          => 20.0,
        'intervalo_actualizacion' => 30,
    ];

    public static function obtenerValores(): array {
        $db = getDB();
        if (!$db) return self::$defaults;
        $stmt = $db->query('SELECT clave, valor FROM configuracion');
        $config = self::$defaults;
        foreach ($stmt->fetchAll() as $row) {
            $config[$row['clave']] = is_numeric($row['valor']) ? (float)$row['valor'] : $row['valor'];
        }
        return $config;
    }

    public static function obtener(string $clave): mixed {
        return self::obtenerValores()[$clave] ?? (self::$defaults[$clave] ?? null);
    }

    public static function guardar(string $clave, string $valor): bool {
        $db = getDB();
        if (!$db) return false;
        $stmt = $db->prepare("
            INSERT INTO configuracion (clave, valor)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE valor = VALUES(valor), updated_at = NOW()
        ");
        return $stmt->execute([$clave, $valor]);
    }

    public static function guardarMultiples(array $datos): bool {
        foreach ($datos as $k => $v) { if (!self::guardar($k, (string)$v)) return false; }
        return true;
    }

    public static function obtenerTodas(): array {
        $db = getDB();
        if (!$db) {
            $result = [];
            $desc = ['temp_max'=>'Temperatura maxima (°C)','temp_min'=>'Temperatura minima (°C)',
                     'humedad_max'=>'Humedad maxima (%)','humedad_min'=>'Humedad minima (%)',
                     'bateria_minima'=>'Bateria minima (%)','intervalo_actualizacion'=>'Intervalo (seg)'];
            foreach (self::$defaults as $k => $v)
                $result[] = ['clave'=>$k,'valor'=>$v,'descripcion'=>$desc[$k]??''];
            return $result;
        }
        $stmt = $db->query('SELECT * FROM configuracion ORDER BY clave');
        return $stmt->fetchAll();
    }
}