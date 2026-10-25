<?php
require_once __DIR__ . '/../config/database.php';

class Nodo {

    const ACTIVO       = 'activo';
    const INACTIVO     = 'inactivo';
    const ALERTA       = 'alerta';
    const BATERIA_BAJA = 'bateria_baja';

    public static function todos(): array {
        $db = getDB();
        return $db ? $db->query('SELECT * FROM nodos ORDER BY nombre')->fetchAll()
                   : self::simulados();
    }

    public static function porId(int $id): ?array {
        $db = getDB();
        if (!$db) { foreach (self::simulados() as $n) if ($n['id']==$id) return $n; return null; }
        $st = $db->prepare('SELECT * FROM nodos WHERE id=?');
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public static function porIdentificador(string $ident): ?array {
        $db = getDB();
        if (!$db) {
            foreach (self::simulados() as $nodo) {
                if ($nodo['identificador'] === $ident) return $nodo;
            }
            return null;
        }
        $st = $db->prepare('SELECT * FROM nodos WHERE identificador=?');
        $st->execute([$ident]);
        return $st->fetch() ?: null;
    }

    public static function resumen(): array {
        $db = getDB();
        if (!$db) return ['total'=>6,'activos'=>4,'en_linea'=>3,'fuera_linea'=>3,
                          'inactivos'=>1,'alertas'=>1,'bateria_baja'=>0,
                          'temp_promedio'=>22.5,'hum_promedio'=>68.0,'bat_promedio'=>72.2];
        $st = $db->query("SELECT COUNT(*) total,
            SUM(estado='activo') activos, SUM(estado='inactivo') inactivos,
            SUM(ultima_conexion IS NOT NULL AND ultima_conexion >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)) en_linea,
            SUM(ultima_conexion IS NULL OR ultima_conexion < DATE_SUB(NOW(), INTERVAL 10 MINUTE)) fuera_linea,
            SUM(estado='alerta') alertas, SUM(estado='bateria_baja') bateria_baja,
            ROUND(AVG(temperatura_actual),1) temp_promedio,
            ROUND(AVG(humedad_actual),1)     hum_promedio,
            ROUND(AVG(bateria),1)            bat_promedio
            FROM nodos");
        return $st->fetch();
    }

    public static function actualizarMediciones(int $id, float $temp, float $hum, float $bat, string $estado='activo'): bool {
        $db = getDB();
        if (!$db) return false;
        $st = $db->prepare("UPDATE nodos SET temperatura_actual=?,humedad_actual=?,bateria=?,estado=?,ultima_conexion=NOW() WHERE id=?");
        return $st->execute([$temp,$hum,$bat,$estado,$id]);
    }

    public static function insertar(array $d): int {
        $db = getDB();
        if (!$db) return 0;
        $st = $db->prepare("INSERT INTO nodos (nombre,identificador,ubicacion,latitud,longitud) VALUES (?,?,?,?,?)");
        $st->execute([$d['nombre'],$d['identificador'],$d['ubicacion']??'',$d['latitud']??null,$d['longitud']??null]);
        return (int)$db->lastInsertId();
    }

    public static function actualizar(int $id, array $d): bool {
        $db = getDB();
        if (!$db) return false;
        $st = $db->prepare("UPDATE nodos SET nombre=?,identificador=?,ubicacion=?,latitud=?,longitud=? WHERE id=?");
        return $st->execute([$d['nombre'],$d['identificador'],$d['ubicacion']??'',$d['latitud']??null,$d['longitud']??null,$id]);
    }

    public static function eliminar(int $id): bool {
        $db = getDB();
        if (!$db) return false;
        return $db->prepare('DELETE FROM nodos WHERE id=?')->execute([$id]);
    }

    public static function marcarInactivos(): void {
        $db = getDB();
        if (!$db) return;
        $db->query("UPDATE nodos SET estado='inactivo'
            WHERE (ultima_conexion IS NULL OR ultima_conexion < DATE_SUB(NOW(), INTERVAL 10 MINUTE))
            AND estado NOT IN ('alerta','bateria_baja')");
    }

    public static function etiquetaEstado(string $e): string {
        return match($e) { 'activo'=>'En línea','inactivo'=>'Desconectado','alerta'=>'Con Alerta','bateria_baja'=>'Batería Baja',default=>'Desconocido' };
    }

    public static function cssEstado(string $e): string {
        return match($e) { 'activo'=>'online','inactivo'=>'offline','alerta'=>'alert','bateria_baja'=>'battery',default=>'offline' };
    }

    public static function simulados(): array {
        $now = fn(string $diff) => date('Y-m-d H:i:s', strtotime($diff));
        return [
            ['id'=>1,'nombre'=>'Nodo 1','identificador'=>'NODO-001','ubicacion'=>'Zona Norte', 'estado'=>'activo',   'bateria'=>82.0,'temperatura_actual'=>22.5,'humedad_actual'=>67.0,'ultima_conexion'=>$now('-5 min'), 'latitud'=>4.711,'longitud'=>-74.072,'fecha_registro'=>$now('-30 days')],
            ['id'=>2,'nombre'=>'Nodo 2','identificador'=>'NODO-002','ubicacion'=>'Zona Sur',   'estado'=>'activo',   'bateria'=>79.0,'temperatura_actual'=>23.1,'humedad_actual'=>69.0,'ultima_conexion'=>$now('-8 min'), 'latitud'=>4.705,'longitud'=>-74.068,'fecha_registro'=>$now('-30 days')],
            ['id'=>3,'nombre'=>'Nodo 3','identificador'=>'NODO-003','ubicacion'=>'Zona Este',  'estado'=>'activo',   'bateria'=>76.0,'temperatura_actual'=>21.8,'humedad_actual'=>65.0,'ultima_conexion'=>$now('-3 min'), 'latitud'=>4.713,'longitud'=>-74.065,'fecha_registro'=>$now('-30 days')],
            ['id'=>4,'nombre'=>'Nodo 4','identificador'=>'NODO-004','ubicacion'=>'Zona Oeste', 'estado'=>'alerta',   'bateria'=>71.0,'temperatura_actual'=>24.2,'humedad_actual'=>70.0,'ultima_conexion'=>$now('-26 min'),'latitud'=>4.709,'longitud'=>-74.076,'fecha_registro'=>$now('-30 days')],
            ['id'=>5,'nombre'=>'Nodo 5','identificador'=>'NODO-005','ubicacion'=>'Zona Centro','estado'=>'activo',   'bateria'=>80.0,'temperatura_actual'=>22.0,'humedad_actual'=>68.0,'ultima_conexion'=>$now('-2 min'), 'latitud'=>4.707,'longitud'=>-74.070,'fecha_registro'=>$now('-30 days')],
            ['id'=>6,'nombre'=>'Nodo 6','identificador'=>'NODO-006','ubicacion'=>'Zona Sur-Este','estado'=>'inactivo','bateria'=>45.0,'temperatura_actual'=>null,'humedad_actual'=>null,'ultima_conexion'=>$now('-65 min'),'latitud'=>4.702,'longitud'=>-74.064,'fecha_registro'=>$now('-30 days')],
        ];
    }
}