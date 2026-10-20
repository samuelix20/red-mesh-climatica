# Red Mesh Climatica - Monitoreo Autonomo y Respuesta en Emergencias

Sistema web para monitoreo en tiempo real de una red de sensores climaticos distribuidos (arquitectura mesh).

## Stack tecnologico
| Capa         | Tecnologia                  |
|--------------|-----------------------------|
| Backend      | PHP 8+ (patron MVC)         |
| Base de datos| MySQL 8 / MariaDB           |
| Frontend     | HTML5, CSS3, JavaScript     |
| Graficas     | Chart.js (Fase 3)           |
| Mapa         | Leaflet.js (Fase 3)         |

## Arquitectura
`
red-mesh-climatica/
├── config/        → Configuracion de BD
├── controllers/   → Logica de negocio (MVC)
├── models/        → Acceso a datos (MVC)
├── views/         → Plantillas HTML/PHP (MVC)
├── api/           → Endpoints REST
├── assets/        → CSS, JS, imagenes
├── database/      → Script SQL de inicializacion
├── mqtt/          → Consumidor MQTT para guardar lecturas
├── firmware/      → Sketch de ejemplo para ESP32
└── index.php      → Front Controller
`

## Instalacion rapida (XAMPP)
1. Copiar carpeta en C:/xampp/htdocs/
2. Iniciar Apache y MySQL
3. Importar database/database.sql en phpMyAdmin
4. Ajustar credenciales en config/database.php
5. Acceder a: http://localhost/red-mesh-climatica/

## Ingreso de lecturas por MQTT

El ESP32 publica JSON en `red-mesh/nodos/<IDENTIFICADOR>/lecturas`. El consumidor
PHP escucha `red-mesh/nodos/+/lecturas` y procesa cada mensaje con el mismo
`LecturaController` usado por la API HTTP. MQTT es el canal principal; si el ESP32
no logra publicar, envia esa lectura a `api/recibir_datos.php` por HTTP.

### Preparar Mosquitto en Windows

1. Instala Mosquitto en el computador que ejecuta XAMPP y crea un usuario y
   contrasena con `mosquitto_passwd -c C:\mosquitto\passwd mqtt_user`.
2. En el archivo de configuracion de Mosquitto agrega:

   ```conf
   listener 1883 0.0.0.0
   allow_anonymous false
   password_file C:/mosquitto/passwd
   ```

   Ajusta la ruta de `password_file` a la ubicacion real del archivo. Reinicia el
   servicio Mosquitto despues del cambio y permite el puerto TCP 1883 solo en la
   red privada/local. No expongas ese puerto directamente a Internet.
3. Usa la IPv4 local del computador como `MQTT_HOST` en el sketch, por ejemplo la
   misma direccion que ya usas para acceder a la API. El consumidor PHP se conecta
   localmente a `127.0.0.1`.

### Instalar y ejecutar el consumidor

Desde la carpeta del proyecto, instala la dependencia PHP:

```powershell
cd C:\xampp\htdocs\red-mesh-climatica
composer install
```

Configura las variables de entorno `MQTT_USERNAME` y `MQTT_PASSWORD` con las
credenciales creadas en Mosquitto. Opcionalmente configura `MQTT_HOST` (por defecto
`127.0.0.1`) y `MQTT_PORT` (por defecto `1883`). Luego, en una consola que
permanezca abierta, ejecuta:

```powershell
C:\xampp\php\php.exe mqtt\consumer.php
```

El consumidor debe estar ejecutandose para que el backend reciba mensajes MQTT.
Sus errores y lecturas rechazadas se escriben en el registro de errores de PHP;
las lecturas aceptadas se muestran en la consola.

### Programar el ESP32 con PlatformIO

Abre la carpeta `firmware/esp32_mqtt` en VS Code con la extension PlatformIO.
El proyecto incluye `platformio.ini` y `src/main.cpp`; PlatformIO instala las
bibliotecas `DHTesp` y `PubSubClient` al compilar. Antes de cargar el firmware,
completa en `src/main.cpp` las credenciales WiFi/MQTT y confirma que `MQTT_HOST`
y `API_URL` apuntan al computador con Mosquitto y XAMPP. Agrega a la base de datos
el nodo cuyo identificador aparece en `NODE_IDENTIFIER`.

En PlatformIO, compila con **Build** y carga con **Upload**. Abre el monitor serial
a 115200 baudios para ver el estado de WiFi, MQTT y los envios. El archivo
`esp32_mqtt.ino` de esta carpeta es una alternativa para Arduino IDE; configura
allí los mismos valores y agrega manualmente las bibliotecas `DHTesp` y
`PubSubClient`.

La publicacion MQTT usa QoS 0. El respaldo HTTP se activa si el ESP32 no puede
conectarse al broker o publicar; si MQTT publica correctamente pero el consumidor
PHP no esta ejecutandose, esa lectura no se reenvia por HTTP.

## Fases de desarrollo
| Fase | Descripcion                                | Estado      |
|------|--------------------------------------------|-------------|
| 1    | Arquitectura MVC - Estructura de archivos  | Completada  |
| 2    | Base de datos + CRUD + API REST            | Pendiente   |
| 3    | Dashboard tiempo real (Chart.js + Leaflet) | Pendiente   |
| 4    | Sistema de alertas automaticas             | Pendiente   |
| 5    | Autenticacion y roles de usuario           | Pendiente   |
