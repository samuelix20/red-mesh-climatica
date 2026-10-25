<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

$mqttHost = getenv('MQTT_HOST') ?: '127.0.0.1';
$mqttPort = (int)(getenv('MQTT_PORT') ?: 1883);
$mqttUsername = getenv('MQTT_USERNAME') ?: '';
$mqttPassword = getenv('MQTT_PASSWORD') ?: '';
$telemetryTopic = getenv('MQTT_TELEMETRY_TOPIC') ?: 'red-mesh/cadena-frio/telemetria';
$commandPrefix = getenv('MQTT_COMMAND_PREFIX') ?: 'red-mesh/cadena-frio/comandos';
$databaseHost = getenv('DB_HOST') ?: '127.0.0.1';
$databaseName = getenv('DB_NAME') ?: 'cadena_frio_db';
$databaseUser = getenv('DB_USER') ?: 'root';
$databasePassword = getenv('DB_PASS') ?: '';
$temperatureLimit = (float)(getenv('TEMP_CRITICA_C') ?: 8.0);
$riseRateLimit = (float)(getenv('GRADIENTE_CRITICO_C_MIN') ?: 0.5);

function connectDatabase(): PDO
{
    global $databaseHost, $databaseName, $databaseUser, $databasePassword;

    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $databaseHost, $databaseName);
    return new PDO($dsn, $databaseUser, $databasePassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function finiteNumber(mixed $value): ?float
{
    if (!is_numeric($value)) {
        return null;
    }
    $number = (float)$value;
    return is_finite($number) ? $number : null;
}

function processTelemetry(PDO $db, MqttClient $client, string $payloadJson): void
{
    global $temperatureLimit, $riseRateLimit, $commandPrefix;

    $payload = json_decode($payloadJson, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($payload) || !is_string($payload['node_id'] ?? null) ||
        trim($payload['node_id']) === '') {
        throw new UnexpectedValueException('Payload requiere node_id.');
    }
    $nodeId = trim($payload['node_id']);

    $temperatures = [];
    $humidities = [];
    for ($index = 1; $index <= 3; $index++) {
        $temperatures[$index] = finiteNumber($payload["temp{$index}"] ?? null);
        $humidities[$index] = finiteNumber($payload["humidity{$index}"] ?? null);
        if ($temperatures[$index] !== null &&
            ($temperatures[$index] < -40 || $temperatures[$index] > 80)) {
            throw new UnexpectedValueException("temp{$index} fuera de rango.");
        }
        if ($humidities[$index] !== null &&
            ($humidities[$index] < 0 || $humidities[$index] > 100)) {
            throw new UnexpectedValueException("humidity{$index} fuera de rango.");
        }
    }

    $validTemperatures = array_filter($temperatures, static fn(?float $value): bool => $value !== null);
    $validHumidities = array_filter($humidities, static fn(?float $value): bool => $value !== null);
    if ($validTemperatures === [] || $validHumidities === []) {
        throw new UnexpectedValueException('Se requiere al menos una temperatura y una humedad validas.');
    }
    $averageTemperature = array_sum($validTemperatures) / count($validTemperatures);
    $averageHumidity = array_sum($validHumidities) / count($validHumidities);

    $previousQuery = $db->prepare(
        'SELECT temp1, temp2, temp3, temp_promedio,
                TIMESTAMPDIFF(MICROSECOND, timestamp, CURRENT_TIMESTAMP(6)) / 60000000 AS elapsed_minutes
         FROM mediciones WHERE node_id = ? ORDER BY timestamp DESC, id DESC LIMIT 1'
    );
    $previousQuery->execute([$nodeId]);
    $previous = $previousQuery->fetch();

    $gradient = 0.0;
    $risingIndex = null;
    if ($previous !== false) {
        $elapsedMinutes = (float)$previous['elapsed_minutes'];
        if ($elapsedMinutes > 0) {
            $sensorRates = [];
            for ($index = 1; $index <= 3; $index++) {
                $oldTemperature = finiteNumber($previous["temp{$index}"]);
                $newTemperature = $temperatures[$index];
                if ($oldTemperature !== null && $newTemperature !== null) {
                    $sensorRates[$index] = ($newTemperature - $oldTemperature) / $elapsedMinutes;
                }
            }
            if ($sensorRates !== []) {
                $risingIndex = array_search(max($sensorRates), $sensorRates, true);
                $gradient = (float)$sensorRates[$risingIndex];
            }
        }
    }

    $sensorNodes = $payload['sensor_nodes'] ?? [];
    if (!is_array($sensorNodes)) {
        throw new UnexpectedValueException('sensor_nodes debe ser un arreglo.');
    }
    $targetIndex = array_search(max($validTemperatures), $temperatures, true);
    if ($gradient >= $riseRateLimit && $risingIndex !== null &&
        $sensorRates[$risingIndex] >= $riseRateLimit) {
        $targetIndex = $risingIndex;
    }
    $targetNode = $sensorNodes[$targetIndex - 1] ?? $nodeId;
    if (!is_string($targetNode) || !preg_match('/^[A-Za-z0-9_-]{1,64}$/', $targetNode)) {
        throw new UnexpectedValueException('Identificador de nodo de control invalido.');
    }

    $criticalTemperature = max($validTemperatures) > $temperatureLimit;
    $criticalGradient = $gradient >= $riseRateLimit;
    $relayEnabled = $criticalTemperature || $criticalGradient;
    $alertType = $criticalTemperature ? 'temperatura_critica' : 'gradiente_critico';
    $description = $criticalTemperature
        ? sprintf('Temperatura maxima %.2f C supera el limite %.2f C.', max($validTemperatures), $temperatureLimit)
        : sprintf('Elevacion termica %.3f C/min supera el limite %.3f C/min.', $gradient, $riseRateLimit);
    $action = sprintf('Rele %s en %s.', $relayEnabled ? 'activado' : 'desactivado', $targetNode);

    $db->beginTransaction();
    try {
        $insert = $db->prepare(
            'INSERT INTO mediciones
             (node_id, temp1, temp2, temp3, temp_promedio, humedad_prom, gradiente_temp, estado_rele)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $insert->execute([
            $nodeId,
            $temperatures[1],
            $temperatures[2],
            $temperatures[3],
            $averageTemperature,
            $averageHumidity,
            $gradient,
            (int)$relayEnabled,
        ]);

        if ($relayEnabled) {
            $recentAlert = $db->prepare(
                'SELECT id FROM alertas_ibn
                 WHERE node_id = ? AND tipo_alerta = ?
                   AND timestamp >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                 LIMIT 1'
            );
            $recentAlert->execute([$targetNode, $alertType]);
            if ($recentAlert->fetch() === false) {
                $alert = $db->prepare(
                    'INSERT INTO alertas_ibn (node_id, tipo_alerta, descripcion, accion_tomada)
                     VALUES (?, ?, ?, ?)'
                );
                $alert->execute([$targetNode, $alertType, $description, $action]);
            }
        }
        $db->commit();
    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $exception;
    }

    $commandTopic = $commandPrefix . '/' . $targetNode;
    $commandJson = json_encode([
        'type' => 'relay_command',
        'target_node_id' => $targetNode,
        'enabled' => $relayEnabled,
        'reason' => $criticalTemperature ? 'temperature' : ($criticalGradient ? 'thermal_rise' : 'normal'),
    ], JSON_THROW_ON_ERROR);
    $client->publish($commandTopic, $commandJson, 0, false);

    printf(
        "[%s] Guardado nodo=%s Tprom=%.2fC gradiente=%.3fC/min rele=%s destino=%s\n",
        date('H:i:s'),
        $nodeId,
        $averageTemperature,
        $gradient,
        $relayEnabled ? 'ON' : 'OFF',
        $targetNode
    );
}

printf("Listener cadena de frio: MQTT %s:%d, topic=%s, BD=%s\n",
    $mqttHost, $mqttPort, $telemetryTopic, $databaseName);

while (true) {
    $client = null;
    try {
        $db = connectDatabase();
        $client = new MqttClient($mqttHost, $mqttPort, 'cold-chain-' . bin2hex(random_bytes(5)));
        $settings = (new ConnectionSettings())->setKeepAliveInterval(60);
        if ($mqttUsername !== '') {
            $settings = $settings->setUsername($mqttUsername)->setPassword($mqttPassword);
        }
        $client->connect($settings, true);
        printf("[%s] MQTT conectado; suscrito a %s\n", date('H:i:s'), $telemetryTopic);
        $client->subscribe($telemetryTopic, static function (
            string $topic,
            string $message,
            bool $retained,
            array $matchedWildcards
        ) use ($db, $client): void {
            try {
                processTelemetry($db, $client, $message);
            } catch (Throwable $exception) {
                fprintf(STDERR, "[%s] Mensaje rechazado (%s): %s\n",
                    date('H:i:s'), $topic, $exception->getMessage());
            }
        }, 0);
        $client->loop(true);
    } catch (Throwable $exception) {
        fprintf(STDERR, "[%s] Error listener: %s. Reintento en 5 s.\n",
            date('H:i:s'), $exception->getMessage());
        sleep(5);
    } finally {
        if ($client instanceof MqttClient && $client->isConnected()) {
            $client->disconnect();
        }
    }
}
