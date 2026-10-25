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

### Acceder al dashboard por HTTP/3 (QUIC)

El dashboard puede servirse por HTTP/3 con Caddy delante de Apache. Apache y
XAMPP siguen atendiendo en el puerto 80; Caddy escucha solo en `127.0.0.1:8443`
y reenvia las solicitudes a Apache. Ademas de `localhost`, Caddy acepta el
dominio local `red-mesh-climatica.localhost`, que resuelve a esta PC sin editar
el archivo `hosts`. El gateway y los sensores conservan su ruta actual por MQTT;
esta configuracion no afirma ni habilita HTTP/3 en el firmware.

1. Instala Caddy desde WinGet con
   `winget install --id CaddyServer.Caddy --exact --source winget`, y abre una
   terminal nueva para que `caddy.exe` este disponible en `PATH`.
2. Inicia Apache desde el panel de XAMPP y ejecuta
   `C:\xampp\iniciar_http3.bat`; deja abierta esa ventana.
3. La primera vez, instala la CA local de Caddy en el almacen de confianza del
   usuario actual, sin permisos de administrador:

   ```powershell
   certutil -user -addstore -f Root "$env:APPDATA\Caddy\pki\authorities\local\root.crt"
   ```

   Reinicia el navegador si ya estaba abierto o mostraba una advertencia de
   certificado.
4. Abre `https://red-mesh-climatica.localhost:8443/red-mesh-climatica/`.
   La URL anterior `https://localhost:8443/red-mesh-climatica/` sigue
   disponible.
5. En las herramientas de desarrollador del navegador, abre **Network/Red**,
   habilita la columna **Protocol/Protocolo** y recarga. Las solicitudes
   servidas por HTTP/3 deben indicar `h3`. Si indican `h2` o `http/1.1`, QUIC no
   se negocio en esa conexion.

El puerto `8443` se usa para no interferir con los puertos 80 y 443 de Apache.
La configuracion queda limitada a la PC (`127.0.0.1`); no abre el dashboard a
otros equipos de la red ni requiere cambiar la IP usada por MQTT.

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

### Firmware PlatformIO de dos nodos

El firmware completo de malla esta en `firmware/mesh_sensor` (ESP32 #2) y
`firmware/mesh_gateway` (ESP32 #1); abre cada carpeta por separado en VS Code
con la extension PlatformIO. Ambos proyectos instalan `painlessMesh`,
`ArduinoJson` y `DHTesp`; el gateway tambien instala `PubSubClient`.

Antes de compilar, configura en `src/main.cpp`:

- En los dos nodos, el mismo `MESH_PREFIX`, `MESH_PASSWORD` y `MESH_CHANNEL`.
- En el gateway, `WIFI_SSID`, `WIFI_PASSWORD`, la IP local del equipo XAMPP en
  `MQTT_HOST`, usuario/contrasena MQTT y la red WiFi a la que se conectara.
- Pines DHT11 y rele segun tu placa/cableado. El ejemplo usa GPIO 4 en el gateway,
  GPIO 4 y 5 en el nodo sensor y GPIO 26 para el rele de cada nodo. Usa un modulo
  de rele compatible con 3.3 V; no conectes cargas de red directamente al ESP32.

El gateway agrega la lectura local (temp1) con las dos lecturas recibidas por
mesh (temp2 y temp3) y publica el JSON en
`red-mesh/cadena-frio/telemetria`. Si el nodo sensor no envia datos recientes, sus
dos campos quedan `null`. El listener publica la orden de rele en
`red-mesh/cadena-frio/comandos/<node_id>`; el gateway la aplica localmente o la
reenvia por mesh al nodo indicado. La orden no es retenida para evitar reactivar
un rele con un comando antiguo al reconectar.

#### Resolver subrayados rojos de `Arduino.h` en VS Code

Los mensajes de la captura (`no se puede abrir el archivo origen`) son del
IntelliSense de C/C++, no necesariamente errores de compilacion. Estos proyectos
PlatformIO viven en subcarpetas distintas; si se abre la raiz del repositorio como
un unico proyecto, IntelliSense no siempre encuentra el framework Arduino ni las
bibliotecas instaladas por PlatformIO.

1. Instala/habilita las extensiones **PlatformIO IDE** y **C/C++** de Microsoft.
2. En VS Code usa **File > Open Workspace from File...** y abre
   `red-mesh-mesh.code-workspace` en la raiz del repositorio. La workspace incluye
   los dos proyectos PlatformIO con sus respectivas configuraciones.
3. Para cada proyecto, selecciona su `platformio.ini` y ejecuta **PlatformIO:
   Build**. Espera a que termine la descarga/compilacion de dependencias.
4. Si persisten los subrayados, ejecuta **PlatformIO: Rebuild IntelliSense
   Index** desde la paleta de comandos, espera a que termine y luego ejecuta
   **C/C++: Reset IntelliSense Database**. Recarga la ventana de VS Code si hace
   falta.

Comprueba el resultado real en la terminal con **PlatformIO: Build** para
`mesh_sensor` y `mesh_gateway`. Si ambos reportan `SUCCESS`, los errores restantes
son del indice IntelliSense. Si Build falla, corrige el primer error de la
terminal; los mensajes rojos de `#include` por si solos no identifican un fallo
del firmware.

## Listener IBN para cadena de frio

Este flujo usa su propia base `cadena_frio_db` y no cambia las tablas del dashboard
actual (`red_mesh_climatica`). Importa `database/cadena_frio.sql` desde
phpMyAdmin. Con Apache/MySQL y Mosquitto en ejecucion, configura en la consola
PowerShell las credenciales que coincidan con el broker y ejecuta el listener:

```powershell
cd C:\xampp\htdocs\red-mesh-climatica
$env:MQTT_HOST = "127.0.0.1"
$env:MQTT_PORT = "1883"
$env:MQTT_USERNAME = "mqtt_user"
$env:MQTT_PASSWORD = "TU_CLAVE_MQTT"
$env:DB_HOST = "127.0.0.1"
$env:DB_NAME = "cadena_frio_db"
$env:DB_USER = "root"
$env:DB_PASS = ""
C:\xampp\php\php.exe mqtt\mqtt_listener.php
```

`DB_PASS` debe ajustarse si MySQL tiene contrasena. Los umbrales por defecto son
8 °C y 0.5 °C/min; se pueden cambiar con `TEMP_CRITICA_C` y
`GRADIENTE_CRITICO_C_MIN`. El listener calcula el gradiente de cada sensor contra
la ultima medicion persistida, decide si activar el rele, escribe mediciones y
alertas con PDO, y envia el comando MQTT. Requiere que la dependencia
`php-mqtt/client` ya instalada por Composer este disponible en `vendor`.

### Prueba de extremo a extremo

1. Importa `database/cadena_frio.sql` y verifica que existan `mediciones` y
   `alertas_ibn` bajo `cadena_frio_db`.
2. En PlatformIO, compila y carga primero `firmware/mesh_sensor` al ESP32 #2 y
   despues `firmware/mesh_gateway` al ESP32 #1. Abre ambos monitores seriales a
   115200 baudios. Confirma que el sensor transmite `Mesh TX`, el gateway ve
   `Mesh RX` y que el gateway se conecta a WiFi y MQTT.
3. Ejecuta el listener en otra consola. Debe informar que esta suscrito al topic
   de telemetria. Las publicaciones del gateway deben aparecer como `Guardado`.
4. Para probar sin hardware, publica una muestra con Mosquitto (usa el mismo
   usuario y clave del broker):

   ```powershell
   mosquitto_pub -h 127.0.0.1 -p 1883 -u mqtt_user -P TU_CLAVE_MQTT `
     -t red-mesh/cadena-frio/telemetria `
     -m '{"node_id":"gateway-01","temp1":9.2,"humidity1":70,"temp2":7.1,"humidity2":68,"temp3":7.4,"humidity3":69,"sensor_nodes":["gateway-01","sensor-02","sensor-02"]}'
   ```

   Con temperatura mayor a 8 °C se debe guardar una medicion, generar una alerta
   (maximo una del mismo tipo/nodo en cinco minutos) y publicar el comando
   `enabled:true`. Para observar comandos en otra consola:

   ```powershell
   mosquitto_sub -h 127.0.0.1 -p 1883 -u mqtt_user -P TU_CLAVE_MQTT `
     -t "red-mesh/cadena-frio/comandos/#" -v
   ```

5. En phpMyAdmin o consola MySQL comprueba los registros:

   ```sql
   SELECT * FROM cadena_frio_db.mediciones ORDER BY id DESC LIMIT 10;
   SELECT * FROM cadena_frio_db.alertas_ibn ORDER BY id DESC LIMIT 10;
   ```

   La prueba simulada verifica PHP/MQTT/MySQL, no la malla física ni la activación
   real del rele. Prueba primero con carga desconectada y verifica el estado
   eléctrico del módulo antes de conectar el sistema de refrigeración.

## Fases de desarrollo
| Fase | Descripcion                                | Estado      |
|------|--------------------------------------------|-------------|
| 1    | Arquitectura MVC - Estructura de archivos  | Completada  |
| 2    | Base de datos + CRUD + API REST            | Pendiente   |
| 3    | Dashboard tiempo real (Chart.js + Leaflet) | Pendiente   |
| 4    | Sistema de alertas automaticas             | Pendiente   |
| 5    | Autenticacion y roles de usuario           | Pendiente   |
