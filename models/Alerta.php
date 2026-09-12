<?php
require_once __DIR__ . '/../config/database.php';

class Alerta {

    const TIPO_TEMP_ALTA    = 'temperatura_alta';
    const TIPO_TEMP_BAJA    = 'temperatura_baja';
    const TIPO_HUMEDAD      = 'humedad_fuera_rango';
    const TIPO_DESCONECTADO = 'nodo_desconectado';
    const TIPO_BATERIA      = 'bateria_baja';
    const TIPO_ERROR        = 'error_comunicacion';

    const NIVEL_INFO        = 'info';
    const NIVEL_ADVERT      = 'advertencia';
    const NIVEL_CRITICO     = 'critico';

    public static function insertar(int $nodoId, string $tipo, string $desc, string $nivel='advertencia'): int {
        $db = getDB();
        if (!$db) return 0;
        // No duplicar alertas activas del mismo tipo para el mismo nodo
        $st = $db->prepare("SELECT id FROM alertas WHERE nodo_id=? AND tipo=? AND estado='activa' LIMIT 1");
        $st->execute([$nodoId,$tipo]);
        if ($st->fetch()) return 0;
        $st = $db->prepare("INSERT INTO alertas (nodo_id,tipo,descripcion,nivel) VALUES (?,?,?,?)");
        $st->execute([$nodoId,$tipo,$desc,$nivel]);
        return (int)$db->lastInsertId();
    }

    public static function activas(int $limite=50): array {
        $db = getDB();
        if (!$db) return self::simuladas();
        $st = $db->prepare("SELECT a.*,n.nombre nodo_nombre,n.identificador
            FROM alertas a LEFT JOIN nodos n ON n.id=a.nodo_id
            WHERE a.estado='activa'
            ORDER BY FIELD(a.nivel,'critico','advertencia','info'), a.created_at DESC LIMIT ?");
        $st->execute([$limite]);
        return $st->fetchAll();
    }

    public static function historial(int $limite=100): array {
        $db = getDB();
        if (!$db) return self::simuladas();
        $st = $db->prepare("SELECT a.*,n.nombre nodo_nombre,n.identificador
            FROM alertas a LEFT JOIN nodos n ON n.id=a.nodo_id
            ORDER BY a.created_at DESC LIMIT ?");
        $st->execute([$limite]);
        return $st->fetchAll();
    }

    public static function conteoActivas(): int {
        $db = getDB();
        return $db ? (int)$db->query("SELECT COUNT(*) FROM alertas WHERE estado='activa'")->fetchColumn() : 3;
    }

    public static function reconocer(int $id): bool {
        $db = getDB();
        if (!$db) return false;
        return $db->prepare("UPDATE alertas SET estado='reconocida',updated_at=NOW() WHERE id=?")->execute([$id]);
    }

    public static function resolver(int $id): bool {
        $db = getDB();
        if (!$db) return false;
        return $db->prepare("UPDATE alertas SET estado='resuelta',updated_at=NOW() WHERE id=?")->execute([$id]);
    }

    public static function resolverPorNodoYTipo(int $nodoId, string $tipo): void {
        $db = getDB();
        if (!$db) return;
        $db->prepare("UPDATE alertas SET estado='resuelta',updated_at=NOW()
            WHERE nodo_id=? AND tipo=? AND estado IN ('activa','reconocida')")->execute([$nodoId,$tipo]);
    }

    public static function simuladas(): array {
        return [
            ['id'=>1,'nodo_id'=>6,'nodo_nombre'=>'Nodo 6','identificador'=>'NODO-006','tipo'=>'nodo_desconectado',
             'descripcion'=>'Nodo 6 sin conexion desde hace mas de 1 hora','nivel'=>'critico','estado'=>'activa',
             'created_at'=>date('Y-m-d H:i:s',strtotime('-65 min')),'updated_at'=>date('Y-m-d H:i:s',strtotime('-65 min'))],
            ['id'=>2,'nodo_id'=>4,'nodo_nombre'=>'Nodo 4','identificador'=>'NODO-004','tipo'=>'temperatura_alta',
             'descripcion'=>'Temperatura de 24.2°C supera el maximo configurado (30°C)','nivel'=>'advertencia','estado'=>'activa',
             'created_at'=>date('Y-m-d H:i:s',strtotime('-26 min')),'updated_at'=>date('Y-m-d H:i:s',strtotime('-26 min'))],
            ['id'=>3,'nodo_id'=>4,'nodo_nombre'=>'Nodo 4','identificador'=>'NODO-004','tipo'=>'bateria_baja',
             'descripcion'=>'Nivel de bateria al 71%, aproximandose al limite minimo','nivel'=>'info','estado'=>'activa',
             'created_at'=>date('Y-m-d H:i:s',strtotime('-30 min')),'updated_at'=>date('Y-m-d H:i:s',strtotime('-30 min'))],
        ];
    }

    public static function etiquetaTipo(string $t): string {
        return match($t) {
            'temperatura_alta'    => 'Temperatura Alta',
            'temperatura_baja'    => 'Temperatura Baja',
            'humedad_fuera_rango' => 'Humedad Fuera de Rango',
            'nodo_desconectado'   => 'Nodo Desconectado',
            'bateria_baja'        => 'Batería Baja',
            'error_comunicacion'  => 'Error de Comunicación',
            default               => $t,
        };
    }

    public static function iconoNivel(string $nivel): string {
        return match($nivel) { 'critico'=>'🔴','advertencia'=>'🟡','info'=>'🟢',default=>'⚪' };
    }

    public static function cssNivel(string $nivel): string {
        return match($nivel) { 'critico'=>'red','advertencia'=>'orange','info'=>'green',default=>'green' };
    }
}