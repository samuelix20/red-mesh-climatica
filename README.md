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
└── index.php      → Front Controller
`

## Instalacion rapida (XAMPP)
1. Copiar carpeta en C:/xampp/htdocs/
2. Iniciar Apache y MySQL
3. Importar database/database.sql en phpMyAdmin
4. Ajustar credenciales en config/database.php
5. Acceder a: http://localhost/red-mesh-climatica/

## Fases de desarrollo
| Fase | Descripcion                                | Estado      |
|------|--------------------------------------------|-------------|
| 1    | Arquitectura MVC - Estructura de archivos  | Completada  |
| 2    | Base de datos + CRUD + API REST            | Pendiente   |
| 3    | Dashboard tiempo real (Chart.js + Leaflet) | Pendiente   |
| 4    | Sistema de alertas automaticas             | Pendiente   |
| 5    | Autenticacion y roles de usuario           | Pendiente   |
