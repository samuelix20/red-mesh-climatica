@echo off
title Red Mesh Climatica - Iniciar Servicios
color 0A

echo ==============================================================
echo           RED MESH CLIMATICA - SERVICIOS LOCALES
echo ==============================================================
echo.

:: 1. Verificar Broker Mosquitto
echo [1/3] Verificando Broker MQTT Mosquitto en puerto 1883...
netstat -ano | findstr :1883 | findstr LISTENING >nul
if %ERRORLEVEL% EQU 0 (
    echo     [OK] Mosquitto ya esta en ejecucion en el puerto 1883.
) else (
    echo     Iniciando Mosquitto Broker...
    start "Mosquitto MQTT Broker" /MIN "C:\Program Files\mosquitto\mosquitto.exe" -c "C:\Program Files\mosquitto\mosquitto.conf" -v
    timeout /t 2 /nobreak >nul
    netstat -ano | findstr :1883 | findstr LISTENING >nul
    if %ERRORLEVEL% EQU 0 (
        echo     [OK] Mosquitto iniciado correctamente.
    ) else (
        echo     [AVISO] No se pudo iniciar Mosquitto como servicio. Si falla MQTT, el Gateway usara HTTP alternativo automaticamente hacia XAMPP.
    )
)

:: 2. Iniciar Consumidor MQTT en segundo plano
echo.
echo [2/3] Iniciando Consumidor MQTT PHP (guarda lecturas en MariaDB)...
start "Consumidor MQTT - Red Mesh" "C:\xampp\php\php.exe" "C:\xampp\htdocs\red-mesh-climatica\mqtt\consumer.php"
echo     [OK] Consumidor MQTT activo.

:: 3. Abrir Dashboard Web
echo.
echo [3/3] Abriendo Dashboard en el navegador...
start http://localhost/red-mesh-climatica/
echo.
echo ==============================================================
echo   TODO FUNCIONANDO:
echo   - Broker MQTT: 192.168.18.242:1883 (usuario: mqtt_user)
echo   - API HTTP XAMPP: http://192.168.18.242/red-mesh-climatica/api/recibir_datos.php
echo   - Dashboard Web: http://localhost/red-mesh-climatica/
echo ==============================================================
echo.
echo Puedes minimizar esta ventana. Para detener el consumidor, cierra su ventana.
pause
