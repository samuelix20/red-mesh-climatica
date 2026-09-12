<?php
require_once __DIR__ . '/../config/database.php';

class Lectura {

    public static function insertar(int $nodoId, float $temp, float $hum, float $bat, ?float $presion=null): int {
        $db = getDB();
        if (!$db) return 0;
        $st = $db->prepare("INSERT INTO lecturas (nodo_id,temperatura,humedad,nivel_bateria,presion) VALUES (?,?,?,?,?)");
        $st->execute([$nodoId,$temp,$hum,$bat,$presion]);
        return (int)$db->lastInsertId();
    }

    public static function porNodo(int $nodoId, int $limite=100): array {
        $db = getDB();
        if (!$db) return self::simuladasPorNodo($nodoId,$limite);
        $st = $db->prepare('SELECT * FROM lecturas WHERE nodo_id=? ORDER BY timestamp DESC LIMIT ?');
        $st->execute([$nodoId,$limite]);
        return $st->fetchAll();
    }

    public static function recientes(int $limite=20): array {
        $db = getDB();
        if (!$db) return [];
        $st = $db->prepare("SELECT l.*,n.nombre nodo_nombre,n.identificador FROM lecturas l JOIN nodos n ON n.id=l.nodo_id ORDER BY l.timestamp DESC LIMIT ?");
        $st->execute([$limite]);
        return $st->fetchAll();
    }

    public static function promedios(): array {
        $db = getDB();
        if (!$db) return ['temp_promedio'=>22.5,'hum_promedio'=>68.0];
        $st = $db->query("SELECT ROUND(AVG(temperatura),1) temp_promedio, ROUND(AVG(humedad),1) hum_promedio
            FROM lecturas WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $r = $st->fetch();
        return ['temp_promedio'=>$r['temp_promedio']??22.5,'hum_promedio'=>$r['hum_promedio']??68.0];
    }

    public static function historialPorNodo(int $nodoId, string $desde='', string $hasta='', int $limite=300): array {
        $db = getDB();
        if (!$db) return self::simuladasPorNodo($nodoId,50);
        $where = 'WHERE nodo_id=?'; $params = [$nodoId];
        if ($desde) { $where .= ' AND timestamp>=?'; $params[] = $desde.' 00:00:00'; }
        if ($hasta) { $where .= ' AND timestamp<=?'; $params[] = $hasta.' 23:59:59'; }
        $st = $db->prepare("SELECT * FROM lecturas $where ORDER BY timestamp ASC LIMIT $limite");
        $st->execute($params);
        return $st->fetchAll();
    }

    public static function paraGrafica(int $nodoId=0, string $rango='6h'): array {
        $db = getDB();
        if (!$db) return self::datosGraficaSimulados($rango);
        $intervalo = match($rango) { '24h'=>'24 HOUR','7d'=>'7 DAY','30d'=>'30 DAY',default=>'6 HOUR' };
        $agrupacion = match($rango) { '7d','30d'=>'%Y-%m-%d %H:00',default=>'%Y-%m-%d %H:%i' };
        $where = $nodoId > 0 ? 'AND nodo_id='.intval($nodoId) : '';
        $st = $db->query("SELECT DATE_FORMAT(timestamp,'$agrupacion') hora,
            ROUND(AVG(temperatura),1) temperatura, ROUND(AVG(humedad),1) humedad,
            MIN(timestamp) ts
            FROM lecturas
            WHERE timestamp >= DATE_SUB(NOW(), INTERVAL $intervalo) $where
            GROUP BY DATE_FORMAT(timestamp,'$agrupacion')
            ORDER BY ts ASC LIMIT 120");
        return $st->fetchAll();
    }

    private static function simuladasPorNodo(int $nodoId, int $puntos=13): array {
        $base_t = 21.0 + ($nodoId * 0.4); $base_h = 64.0 + ($nodoId * 0.8);
        $result = []; $step = max(1, (int)(60/$puntos));
        for ($i=$puntos-1; $i>=0; $i--) {
            $mins = $i * $step;
            $result[] = ['id'=>0,'nodo_id'=>$nodoId,
                'temperatura' => round($base_t + sin($i/3)*1.5 + rand(-3,3)/10, 1),
                'humedad'     => round($base_h + cos($i/4)*2.0 + rand(-2,2)/10, 1),
                'nivel_bateria'=> round(80 - ($i*0.08), 1),
                'timestamp'   => date('Y-m-d H:i:s', strtotime("-{$mins} minutes"))];
        }
        return $result;
    }

    private static function datosGraficaSimulados(string $rango='6h'): array {
        $puntos = match($rango) { '24h'=>24,'7d'=>14,'30d'=>15,default=>13 };
        $result = [];
        for ($i=$puntos-1; $i>=0; $i--) {
            $mins = (int)($i * (360/$puntos));
            $result[] = ['hora'=>date('H:i',strtotime("-{$mins} minutes")),
                'temperatura'=>round(22+sin($i/4)*2.5+rand(-3,3)/5,1),
                'humedad'    =>round(67+cos($i/3)*3  +rand(-2,2)/5,1)];
        }
        return $result;
    }
}