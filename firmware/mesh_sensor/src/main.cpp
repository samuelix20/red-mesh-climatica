#include <Arduino.h>
#include <ArduinoJson.h>
#include <DHTesp.h>
#include <painlessMesh.h>

namespace {
constexpr char MESH_PREFIX[] = "RED_MESH_CLIMATICA";
constexpr char MESH_PASSWORD[] = "CAMBIAR_CLAVE_MESH";
constexpr char NODE_ID[] = "sensor-02";
constexpr uint16_t MESH_PORT = 5555;
constexpr uint8_t MESH_CHANNEL = 11;
constexpr uint8_t DHT_TEMP2_PIN = 4;
constexpr uint8_t DHT_TEMP3_PIN = 5;
constexpr uint8_t RELAY_PIN = 26;
constexpr uint32_t SAMPLE_INTERVAL_MS = 5000;

painlessMesh mesh;
Scheduler userScheduler;
DHTesp sensor2;
DHTesp sensor3;
uint32_t lastSample = 0;
bool relayEnabled = false;

void setRelay(bool enabled) {
  relayEnabled = enabled;
  digitalWrite(RELAY_PIN, enabled ? HIGH : LOW);
  Serial.printf("Rele %s\n", enabled ? "ACTIVADO" : "DESACTIVADO");
}

void receivedCallback(uint32_t from, String &message) {
  JsonDocument command;
  if (deserializeJson(command, message) != DeserializationError::Ok ||
      strcmp(command["type"] | "", "relay_command") != 0 ||
      strcmp(command["target_node_id"] | "", NODE_ID) != 0 ||
      !command["enabled"].is<bool>()) {
    return;
  }

  setRelay(command["enabled"].as<bool>());
  Serial.printf("Comando mesh recibido desde %u\n", from);
}

void sendTelemetry() {
  const TempAndHumidity reading2 = sensor2.getTempAndHumidity();
  const TempAndHumidity reading3 = sensor3.getTempAndHumidity();
  if (!isfinite(reading2.temperature) || !isfinite(reading2.humidity) ||
      !isfinite(reading3.temperature) || !isfinite(reading3.humidity)) {
    Serial.println("Error de lectura DHT11; se omite este paquete.");
    return;
  }

  JsonDocument packet;
  packet["type"] = "telemetry";
  packet["node_id"] = NODE_ID;
  packet["temp2"] = reading2.temperature;
  packet["humidity2"] = reading2.humidity;
  packet["temp3"] = reading3.temperature;
  packet["humidity3"] = reading3.humidity;

  String json;
  serializeJson(packet, json);
  mesh.sendBroadcast(json);
  Serial.printf("Mesh TX: %s\n", json.c_str());
}
}  // namespace

void setup() {
  Serial.begin(115200);
  pinMode(RELAY_PIN, OUTPUT);
  setRelay(false);

  sensor2.setup(DHT_TEMP2_PIN, DHTesp::DHT11);
  sensor3.setup(DHT_TEMP3_PIN, DHTesp::DHT11);

  mesh.setDebugMsgTypes(ERROR | STARTUP | CONNECTION);
  mesh.init(MESH_PREFIX, MESH_PASSWORD, &userScheduler, MESH_PORT, WIFI_AP_STA, MESH_CHANNEL);
  mesh.setContainsRoot(false);
  mesh.onReceive(&receivedCallback);
  Serial.printf("Nodo mesh sensor iniciado. ID local: %u\n", mesh.getNodeId());
}

void loop() {
  mesh.update();
  const uint32_t now = millis();
  if (now - lastSample >= SAMPLE_INTERVAL_MS) {
    lastSample = now;
    sendTelemetry();
  }
}
