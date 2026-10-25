<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../controllers/LecturaController.php';

use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

$host = getenv('MQTT_HOST') ?: '127.0.0.1';
$port = (int)(getenv('MQTT_PORT') ?: 1883);
$username = getenv('MQTT_USERNAME') ?: 'mqtt_user';
$password = getenv('MQTT_PASSWORD') ?: '123';
$topic = 'red-mesh/nodos/+/lecturas';
$lockFile = fopen(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'red-mesh-climatica-consumer.lock', 'c');
if ($lockFile === false) {
    throw new RuntimeException('No se pudo abrir el bloqueo del consumidor MQTT');
}
if (!flock($lockFile, LOCK_EX | LOCK_NB)) {
    fwrite(STDERR, "[MQTT] Ya hay un consumidor activo; se evita iniciar otro.\n");
    exit(0);
}

fwrite(STDOUT, sprintf("=== CONSUMIDOR MQTT RED MESH CLIMATICA ===\n"));
fwrite(STDOUT, sprintf("Destino: %s:%d | Topico: %s\n", $host, $port, $topic));

while (true) {
    try {
        $clientId = 'redmesh-consumer-' . bin2hex(random_bytes(4));
        $client = new MqttClient($host, $port, $clientId);
        $settings = (new ConnectionSettings())
            ->setKeepAliveInterval(60);

        if ($username !== '') {
            $settings = $settings
                ->setUsername($username)
                ->setPassword($password);
        }

        fwrite(STDOUT, sprintf("[%s] Conectando a MQTT en %s:%d...\n", date('H:i:s'), $host, $port));
        $client->connect($settings, true);
        fwrite(STDOUT, sprintf("[%s] Conectado exitosamente. Suscrito a %s\n", date('H:i:s'), $topic));

        $client->subscribe($topic, static function (
            string $receivedTopic,
            string $message,
            bool $retained,
            array $matchedWildcards
        ): void {
            try {
                $payload = json_decode($message, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                error_log('[MQTT] JSON invalido en ' . $receivedTopic . ': ' . $exception->getMessage());
                return;
            }

            if (!is_array($payload)) {
                error_log('[MQTT] El mensaje no contiene un objeto JSON en ' . $receivedTopic);
                return;
            }

            $result = LecturaController::recibir($payload);
            if (!$result['ok']) {
                error_log(sprintf(
                    '[MQTT] Lectura rechazada | topic=%s | error=%s',
                    $receivedTopic,
                    $result['error']
                ));
                return;
            }

            fwrite(STDOUT, sprintf(
                "[%s] [MQTT OK] Nodo: %s | Temp: %s C | Hum: %s %% | Bat: %s %% | ID lectura: %d\n",
                date('H:i:s'),
                $payload['identificador'] ?? ($payload['nodo_id'] ?? 'desconocido'),
                $payload['temperatura'] ?? '?',
                $payload['humedad'] ?? '?',
                $payload['bateria'] ?? '?',
                $result['lectura_id']
            ));
        }, 0);

        $client->loop(true);
    } catch (Throwable $e) {
        fwrite(STDERR, sprintf("[%s] Aviso MQTT: %s. Reintentando en 5s...\n", date('H:i:s'), $e->getMessage()));
        sleep(5);
    }
}
