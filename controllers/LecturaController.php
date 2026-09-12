<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Lectura.php';
require_once __DIR__ . '/../models/Nodo.php';
require_once __DIR__ . '/../models/Configuracion.php';
require_once __DIR__ . '/../controllers/AlertaController.php';

class LecturaController {

    public static function recibir(array $p): array {
        foreach (['temperatura','humedad','bateria'] as $f)
            if (!isset($p[$f])) return ['ok'=>false,'error'=>"Campo requerido: {$f}"];

        if (!isset($p['nodo_id']) && empty($p['identificador']))
            return ['ok'=>false,'error'=>'Campo requerido: nodo_id o identificador'];

        $nodo = isset($p['nodo_id'])
            ? Nodo::porId((int)$p['nodo_id'])
            : Nodo::porIdentificador(trim((string)$p['identificador']));
        if (!$nodo) {
            $referencia = isset($p['nodo_id']) ? 'ID '.(int)$p['nodo_id'] : 'identificador '.(string)$p['identificador'];
            return ['ok'=>false,'error'=>"Nodo {$referencia} no encontrado"];
        }

        $nodoId = (int)$nodo['id'];
        $temp   = (float)$p['temperatura'];
        $hum    = (float)$p['humedad'];
        $bat    = (float)$p['bateria'];
        $presion = isset($p['presion']) ? (float)$p['presion'] : null;

        if ($temp<-50||$temp>70) return ['ok'=>false,'error'=>'Temperatura fuera de rango valido (-50 a 70°C)'];
        if ($hum<0||$hum>100)   return ['ok'=>false,'error'=>'Humedad fuera de rango valido (0-100%)'];
        if ($bat<0||$bat>100)   return ['ok'=>false,'error'=>'Bateria fuera de rango valido (0-100%)'];

        $cfg    = Configuracion::obtenerValores();
        $estado = 'activo';
        if ($bat < $cfg['bateria_minima'])           $estado = 'bateria_baja';
        if ($temp>$cfg['temp_max']||$temp<$cfg['temp_min']||$hum>$cfg['humedad_max']||$hum<$cfg['humedad_min']) $estado = 'alerta';

        $lecturaId = Lectura::insertar($nodoId,$temp,$hum,$bat,$presion);
        Nodo::actualizarMediciones($nodoId,$temp,$hum,$bat,$estado);
        AlertaController::evaluar($nodoId,$temp,$hum,$bat);

        return ['ok'=>true,'lectura_id'=>$lecturaId,'estado'=>$estado,'mensaje'=>'Datos procesados correctamente'];
    }

    public static function historial(int $nodoId, string $desde='', string $hasta='', int $limite=300): array {
        return Lectura::historialPorNodo($nodoId,$desde,$hasta,$limite);
    }

    public static function paraGrafica(int $nodoId=0, string $rango='6h'): array {
        return Lectura::paraGrafica($nodoId,$rango);
    }
}