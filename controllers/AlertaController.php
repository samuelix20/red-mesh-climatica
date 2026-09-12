<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Alerta.php';
require_once __DIR__ . '/../models/Configuracion.php';

class AlertaController {

    public static function evaluar(int $nodoId, float $temp, float $hum, float $bat): void {
        $cfg = Configuracion::obtenerValores();

        if ($temp > $cfg['temp_max'])
            Alerta::insertar($nodoId,Alerta::TIPO_TEMP_ALTA,
                "Temperatura de {$temp}°C supera el maximo ({$cfg['temp_max']}°C)",Alerta::NIVEL_CRITICO);
        else Alerta::resolverPorNodoYTipo($nodoId,Alerta::TIPO_TEMP_ALTA);

        if ($temp < $cfg['temp_min'])
            Alerta::insertar($nodoId,Alerta::TIPO_TEMP_BAJA,
                "Temperatura de {$temp}°C bajo el minimo ({$cfg['temp_min']}°C)",Alerta::NIVEL_ADVERT);
        else Alerta::resolverPorNodoYTipo($nodoId,Alerta::TIPO_TEMP_BAJA);

        if ($hum > $cfg['humedad_max'] || $hum < $cfg['humedad_min'])
            Alerta::insertar($nodoId,Alerta::TIPO_HUMEDAD,
                "Humedad de {$hum}% fuera del rango ({$cfg['humedad_min']}% – {$cfg['humedad_max']}%)",Alerta::NIVEL_ADVERT);
        else Alerta::resolverPorNodoYTipo($nodoId,Alerta::TIPO_HUMEDAD);

        if ($bat < $cfg['bateria_minima'])
            Alerta::insertar($nodoId,Alerta::TIPO_BATERIA,
                "Bateria al {$bat}%, bajo el minimo ({$cfg['bateria_minima']}%)",
                $bat < 10 ? Alerta::NIVEL_CRITICO : Alerta::NIVEL_ADVERT);
        else Alerta::resolverPorNodoYTipo($nodoId,Alerta::TIPO_BATERIA);
    }

    public static function marcarDesconectado(int $nodoId, string $nombre): void {
        Alerta::insertar($nodoId,Alerta::TIPO_DESCONECTADO,
            "Nodo {$nombre} sin conexion – No se han recibido datos",Alerta::NIVEL_CRITICO);
    }

    public static function estadoGeneral(): array {
        $activas      = Alerta::activas();
        $criticas     = array_values(array_filter($activas, fn($a)=>$a['nivel']==='critico'));
        $advertencias = array_values(array_filter($activas, fn($a)=>$a['nivel']==='advertencia'));
        if ($criticas)     return ['estado'=>'critico',    'mensaje'=>'Sistema con alertas criticas', 'css'=>'red',   'total'=>count($activas),'criticas'=>count($criticas),'advertencias'=>count($advertencias)];
        if ($advertencias) return ['estado'=>'advertencia','mensaje'=>'Sistema con advertencias',     'css'=>'orange','total'=>count($activas),'criticas'=>0,'advertencias'=>count($advertencias)];
        return              ['estado'=>'normal',    'mensaje'=>'Operacion Normal',              'css'=>'green', 'total'=>count($activas),'criticas'=>0,'advertencias'=>0];
    }

    public static function listarActivas(): array { return Alerta::activas(); }
    public static function reconocer(int $id): bool { return Alerta::reconocer($id); }
    public static function resolver(int $id): bool  { return Alerta::resolver($id); }
}