#include <Arduino.h>
#include <ArduinoJson.h>
#include <DHTesp.h>
#include <PubSubClient.h>
#include <WiFi.h>
#include <painlessMesh.h>

namespace {
constexpr char MESH_PREFIX[] = "RED_MESH_CLIMATICA";
constexpr char MESH_PASSWORD[] = "CAMBIAR_CLAVE_MESH";
constexpr char WIFI_SSID[] = "Filia_parra";
constexpr char WIFI_PASSWORD[] = "Cristina197179";
constexpr char MQTT_HOST[] = "192.168.1.11";
constexpr uint16_t MQTT_PORT = 1883;
constexpr char MQTT_USERNAME[] = "mqtt_user";
constexpr char MQTT_PASSWORD[] = "123";
constexpr char GATEWAY_ID[] = "gateway-01";
constexpr char TELEMETRY_TOPIC[] = "red-mesh/cadena-frio/telemetria";
constexpr char COMMAND_SUBSCRIPTION[] = "red-mesh/cadena-frio/comandos/+";
constexpr uint16_t MESH_PORT = 5555;
constexpr uint8_t MESH_CHANNEL = 11;
constexpr uint8_t DHT_TEMP1_PIN = 4;
constexpr uint8_t RELAY_PIN = 26;
constexpr uint32_t SAMPLE_INTERVAL_MS = 10000;
constexpr uint32_t WIFI_RETRY_INTERVAL_MS = 10000;
constexpr uint32_t MQTT_RETRY_INTERVAL_MS = 5000;
constexpr uint32_t REMOTE_STALE_AFTER_MS = 30000;

painlessMesh mesh;
Scheduler userScheduler;
DHTesp localSensor;
WiFiClient wifiClient;
PubSubClient mqttClient(wifiClient);
float temp2 = NAN;
float humidity2 = NAN;
float temp3 = NAN;
float humidity3 = NAN;
uint32_t lastRemotePacket = 0;
uint32_t lastSample = 0;
uint32_t lastWifiAttempt = 0;
uint32_t lastMqttAttempt = 0;
bool relayEnabled = false;

void setRelay(bool enabled) {
  relayEnabled = enabled;
  digitalWrite(RELAY_PIN, enabled ? HIGH : LOW);
  Serial.printf("Rele del gateway %s\n", enabled ? "ACTIVADO" : "DESACTIVADO");
}

void connectWifi() {
  if (WiFi.status() == WL_CONNECTED) return;
  const uint32_t now = millis();
  if (now - lastWifiAttempt < WIFI_RETRY_INTERVAL_MS) return;
  lastWifiAttempt = now;
  Serial.printf("Conectando WiFi a %s...\n", WIFI_SSID);
  WiFi.reconnect();
}

void mqttCallback(char *topic, uint8_t *payload, unsigned int length) {
  JsonDocument command;
  if (deserializeJson(command, payload, length) != DeserializationError::Ok ||
      strcmp(command["type"] | "", "relay_command") != 0 ||
      !command["enabled"].is<bool>()) {
    Serial.println("Comando MQTT invalido; se descarta.");
    return;
  }

  const char *target = command["target_node_id"] | "";
  const bool enabled = command["enabled"].as<bool>();
  if (strcmp(target, GATEWAY_ID) == 0) {
    setRelay(enabled);
  } else {
    JsonDocument meshCommand;
    meshCommand["type"] = "relay_command";
    meshCommand["target_node_id"] = target;
    meshCommand["enabled"] = enabled;
    String json;
    serializeJson(meshCommand, json);
    mesh.sendBroadcast(json);
    Serial.printf("Comando MQTT %s reenviado por mesh: %s\n", topic, json.c_str());
  }
}

void connectMqtt() {
  if (mqttClient.connected() || WiFi.status() != WL_CONNECTED) return;
  const uint32_t now = millis();
  if (now - lastMqttAttempt < MQTT_RETRY_INTERVAL_MS) return;
  lastMqttAttempt = now;
  const String clientId = String("mesh-gateway-") + String(mesh.getNodeId());

  Serial.printf("Conectando MQTT a %s:%u...\n", MQTT_HOST, MQTT_PORT);
  if (!mqttClient.connect(clientId.c_str(), MQTT_USERNAME, MQTT_PASSWORD)) {
    Serial.printf("Conexion MQTT fallida, estado=%d\n", mqttClient.state());
    return;
  }
  if (!mqttClient.subscribe(COMMAND_SUBSCRIPTION)) {
    Serial.println("No se pudo suscribir al topic de comandos MQTT.");
    mqttClient.disconnect();
    return;
  }
  Serial.printf("MQTT conectado; suscrito a %s\n", COMMAND_SUBSCRIPTION);
}

void receivedCallback(uint32_t from, String &message) {
  JsonDocument packet;
  if (deserializeJson(packet, message) != DeserializationError::Ok ||
      strcmp(packet["type"] | "", "telemetry") != 0 ||
      strcmp(packet["node_id"] | "", "sensor-02") != 0) {
    return;
  }

  const float incomingTemp2 = packet["temp2"] | NAN;
  const float incomingHumidity2 = packet["humidity2"] | NAN;
  const float incomingTemp3 = packet["temp3"] | NAN;
  const float incomingHumidity3 = packet["humidity3"] | NAN;
  if (!isfinite(incomingTemp2) || !isfinite(incomingHumidity2) ||
      !isfinite(incomingTemp3) || !isfinite(incomingHumidity3)) {
    Serial.println("Telemetria mesh incompleta; se descarta.");
    return;
  }

  temp2 = incomingTemp2;
  humidity2 = incomingHumidity2;
  temp3 = incomingTemp3;
  humidity3 = incomingHumidity3;
  lastRemotePacket = millis();
  Serial.printf("Mesh RX desde %u: T2=%.1fC T3=%.1fC\n", from, temp2, temp3);
}

void publishTelemetry() {
  const TempAndHumidity local = localSensor.getTempAndHumidity();
  if (!isfinite(local.temperature) || !isfinite(local.humidity)) {
    Serial.println("Error en DHT11 local; se omite la telemetria.");
    return;
  }

  const bool remoteIsFresh = lastRemotePacket != 0 &&
      millis() - lastRemotePacket <= REMOTE_STALE_AFTER_MS;
  JsonDocument packet;
  packet["node_id"] = GATEWAY_ID;
  packet["temp1"] = local.temperature;
  packet["humidity1"] = local.humidity;
  packet["temp2"] = remoteIsFresh ? temp2 : NAN;
  packet["humidity2"] = remoteIsFresh ? humidity2 : NAN;
  packet["temp3"] = remoteIsFresh ? temp3 : NAN;
  packet["humidity3"] = remoteIsFresh ? humidity3 : NAN;
  JsonArray sensorNodes = packet["sensor_nodes"].to<JsonArray>();
  sensorNodes.add(GATEWAY_ID);
  sensorNodes.add("sensor-02");
  sensorNodes.add("sensor-02");

  String json;
  serializeJson(packet, json);
  if (!mqttClient.connected() || !mqttClient.publish(TELEMETRY_TOPIC, json.c_str())) {
    Serial.printf("No se publico telemetria MQTT: %s\n", json.c_str());
    return;
  }
  Serial.printf("MQTT TX (%s): %s\n", TELEMETRY_TOPIC, json.c_str());
}
}  // namespace

void setup() {
  Serial.begin(115200);
  pinMode(RELAY_PIN, OUTPUT);
  setRelay(false);
  localSensor.setup(DHT_TEMP1_PIN, DHTesp::DHT11);

  mesh.setDebugMsgTypes(ERROR | STARTUP | CONNECTION);
  mesh.init(MESH_PREFIX, MESH_PASSWORD, &userScheduler, MESH_PORT, WIFI_AP_STA, MESH_CHANNEL);
  mesh.setRoot(true);
  mesh.setContainsRoot(true);
  mesh.onReceive(&receivedCallback);
  mesh.stationManual(WIFI_SSID, WIFI_PASSWORD);
  mesh.setHostname("red-mesh-gateway");

  mqttClient.setServer(MQTT_HOST, MQTT_PORT);
  mqttClient.setCallback(&mqttCallback);
  mqttClient.setBufferSize(512);
  WiFi.setAutoReconnect(true);
  WiFi.persistent(false);
  Serial.printf("Gateway mesh iniciado. ID local: %u\n", mesh.getNodeId());
}

void loop() {
  mesh.update();
  connectWifi();
  connectMqtt();
  if (mqttClient.connected()) mqttClient.loop();

  const uint32_t now = millis();
  if (now - lastSample >= SAMPLE_INTERVAL_MS) {
    lastSample = now;
    publishTelemetry();
  }
  delay(1);
}
