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

$clientId = 'redmesh-' . bin2hex(random_bytes(6));
$client = new MqttClient($host, $port, $clientId);
$settings = new ConnectionSettings();

if ($username !== '') {
    $settings = $settings
        ->setUsername($username)
        ->setPassword($password);
}

fwrite(STDOUT, sprintf("Conectando a MQTT en %s:%d\n", $host, $port));
$client->connect($settings, true);
fwrite(STDOUT, sprintf("Suscrito a %s\n", $topic));

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
        "[MQTT] Lectura guardada | nodo=%s | lectura_id=%d\n",
        $payload['identificador'] ?? ($payload['nodo_id'] ?? 'desconocido'),
        $result['lectura_id']
    ));
}, 0);

$client->loop(true);
$client->disconnect();
