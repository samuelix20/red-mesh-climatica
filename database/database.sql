-- Red Mesh Climatica â€” Schema completo
-- Ejecutar en phpMyAdmin o: mysql -u root red_mesh_climatica < database.sql

CREATE DATABASE IF NOT EXISTS red_mesh_climatica
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE red_mesh_climatica;

DROP TABLE IF EXISTS alertas;
DROP TABLE IF EXISTS lecturas;
DROP TABLE IF EXISTS nodos;
DROP TABLE IF EXISTS configuracion;

-- â”€â”€ nodos â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
CREATE TABLE nodos (
  id               INT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
  nombre           VARCHAR(100)     NOT NULL,
  identificador    VARCHAR(50)      NOT NULL UNIQUE,
  ubicacion        VARCHAR(200)     DEFAULT '',
  latitud          DECIMAL(10,7)    DEFAULT NULL,
  longitud         DECIMAL(10,7)    DEFAULT NULL,
  estado           ENUM('activo','inactivo','alerta','bateria_baja') NOT NULL DEFAULT 'activo',
  bateria          DECIMAL(5,2)     DEFAULT 100.00,
  temperatura_actual DECIMAL(5,2)  DEFAULT NULL,
  humedad_actual   DECIMAL(5,2)    DEFAULT NULL,
  ultima_conexion  TIMESTAMP        NULL DEFAULT NULL,
  fecha_registro   TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- â”€â”€ lecturas â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
CREATE TABLE lecturas (
  id             INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  nodo_id        INT UNSIGNED  NOT NULL,
  temperatura    DECIMAL(5,2)  NOT NULL COMMENT 'Celsius',
  humedad        DECIMAL(5,2)  NOT NULL COMMENT 'Porcentaje',
  nivel_bateria  DECIMAL(5,2)  DEFAULT NULL COMMENT 'Porcentaje',
  presion        DECIMAL(7,2)  DEFAULT NULL COMMENT 'hPa',
  timestamp      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_nodo_ts (nodo_id, timestamp),
  FOREIGN KEY (nodo_id) REFERENCES nodos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- â”€â”€ alertas â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
CREATE TABLE alertas (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nodo_id     INT UNSIGNED DEFAULT NULL,
  tipo        ENUM('temperatura_alta','temperatura_baja','humedad_fuera_rango',
                   'nodo_desconectado','bateria_baja','error_comunicacion') NOT NULL,
  descripcion TEXT NOT NULL,
  nivel       ENUM('info','advertencia','critico') NOT NULL DEFAULT 'info',
  estado      ENUM('activa','reconocida','resuelta')  NOT NULL DEFAULT 'activa',
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_estado (estado),
  INDEX idx_nivel  (nivel),
  FOREIGN KEY (nodo_id) REFERENCES nodos(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- â”€â”€ configuracion â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
CREATE TABLE configuracion (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  clave       VARCHAR(100) NOT NULL UNIQUE,
  valor       VARCHAR(255) NOT NULL,
  descripcion VARCHAR(255) DEFAULT '',
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- â”€â”€ SEED: nodos â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
INSERT INTO nodos (nombre,identificador,ubicacion,latitud,longitud,estado,bateria,temperatura_actual,humedad_actual,ultima_conexion) VALUES
('Nodo 1','NODO-001','Sector Norte â€“ Estacion A', 4.7110,-74.0721,'activo',   82.0, 22.5, 67.0, DATE_SUB(NOW(),INTERVAL 5  MINUTE)),
('Nodo 2','NODO-002','Sector Sur â€“ Estacion B',   4.7050,-74.0680,'activo',   79.0, 23.1, 69.0, DATE_SUB(NOW(),INTERVAL 8  MINUTE)),
('Nodo 3','NODO-003','Sector Este â€“ Estacion C',  4.7130,-74.0650,'activo',   76.0, 21.8, 65.0, DATE_SUB(NOW(),INTERVAL 3  MINUTE)),
('Nodo 4','NODO-004','Sector Oeste â€“ Estacion D', 4.7090,-74.0760,'alerta',   71.0, 24.2, 70.0, DATE_SUB(NOW(),INTERVAL 26 MINUTE)),
('Nodo 5','NODO-005','Sector Centro â€“ Estacion E',4.7070,-74.0700,'activo',   80.0, 22.0, 68.0, DATE_SUB(NOW(),INTERVAL 2  MINUTE)),
('Nodo 6','NODO-006','Sector Sur-Este â€“ Estacion F',4.7020,-74.0640,'inactivo',45.0, NULL, NULL, DATE_SUB(NOW(),INTERVAL 65 MINUTE));

-- â”€â”€ SEED: lecturas (8 puntos por nodo, ultimas 2 horas) â”€â”€â”€
INSERT INTO lecturas (nodo_id,temperatura,humedad,nivel_bateria,timestamp) VALUES
(1,21.8,66.0,83.0,DATE_SUB(NOW(),INTERVAL 80 MINUTE)),(1,22.0,66.3,82.8,DATE_SUB(NOW(),INTERVAL 70 MINUTE)),
(1,22.2,66.7,82.6,DATE_SUB(NOW(),INTERVAL 60 MINUTE)),(1,22.4,67.0,82.4,DATE_SUB(NOW(),INTERVAL 45 MINUTE)),
(1,22.3,66.8,82.2,DATE_SUB(NOW(),INTERVAL 30 MINUTE)),(1,22.5,67.1,82.0,DATE_SUB(NOW(),INTERVAL 15 MINUTE)),
(1,22.5,67.0,82.0,DATE_SUB(NOW(),INTERVAL 5  MINUTE)),
(2,22.8,68.0,80.0,DATE_SUB(NOW(),INTERVAL 80 MINUTE)),(2,23.0,68.3,79.8,DATE_SUB(NOW(),INTERVAL 70 MINUTE)),
(2,23.1,68.5,79.6,DATE_SUB(NOW(),INTERVAL 60 MINUTE)),(2,23.2,69.0,79.4,DATE_SUB(NOW(),INTERVAL 45 MINUTE)),
(2,23.0,68.8,79.2,DATE_SUB(NOW(),INTERVAL 30 MINUTE)),(2,23.1,69.0,79.0,DATE_SUB(NOW(),INTERVAL 15 MINUTE)),
(2,23.1,69.0,79.0,DATE_SUB(NOW(),INTERVAL 8  MINUTE)),
(3,21.5,64.5,77.0,DATE_SUB(NOW(),INTERVAL 80 MINUTE)),(3,21.6,64.8,76.8,DATE_SUB(NOW(),INTERVAL 70 MINUTE)),
(3,21.7,65.0,76.6,DATE_SUB(NOW(),INTERVAL 60 MINUTE)),(3,21.8,65.2,76.4,DATE_SUB(NOW(),INTERVAL 45 MINUTE)),
(3,21.9,65.0,76.2,DATE_SUB(NOW(),INTERVAL 30 MINUTE)),(3,21.8,65.0,76.0,DATE_SUB(NOW(),INTERVAL 15 MINUTE)),
(3,21.8,65.0,76.0,DATE_SUB(NOW(),INTERVAL 3  MINUTE)),
(4,23.5,69.0,72.0,DATE_SUB(NOW(),INTERVAL 80 MINUTE)),(4,23.8,69.5,71.8,DATE_SUB(NOW(),INTERVAL 70 MINUTE)),
(4,24.0,70.0,71.5,DATE_SUB(NOW(),INTERVAL 60 MINUTE)),(4,24.1,70.2,71.3,DATE_SUB(NOW(),INTERVAL 45 MINUTE)),
(4,24.2,70.0,71.1,DATE_SUB(NOW(),INTERVAL 26 MINUTE)),
(5,21.8,67.5,81.0,DATE_SUB(NOW(),INTERVAL 80 MINUTE)),(5,22.0,67.8,80.8,DATE_SUB(NOW(),INTERVAL 70 MINUTE)),
(5,21.9,68.0,80.6,DATE_SUB(NOW(),INTERVAL 60 MINUTE)),(5,22.1,68.2,80.4,DATE_SUB(NOW(),INTERVAL 45 MINUTE)),
(5,22.0,68.0,80.2,DATE_SUB(NOW(),INTERVAL 30 MINUTE)),(5,22.0,68.0,80.0,DATE_SUB(NOW(),INTERVAL 15 MINUTE)),
(5,22.0,68.0,80.0,DATE_SUB(NOW(),INTERVAL 2  MINUTE));

-- â”€â”€ SEED: alertas â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
INSERT INTO alertas (nodo_id,tipo,descripcion,nivel,estado) VALUES
(6,'nodo_desconectado','Nodo 6 sin conexion desde hace mas de 1 hora','critico','activa'),
(4,'temperatura_alta', 'Temperatura de 24.2 C supera el maximo configurado (24 C)','advertencia','activa'),
(4,'bateria_baja',     'Nivel de bateria al 71%, aproximandose al limite minimo (20%)','info','activa');

-- â”€â”€ SEED: configuracion â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
INSERT INTO configuracion (clave,valor,descripcion) VALUES
('temp_max',               '30.0',  'Temperatura maxima permitida (Â°C)'),
('temp_min',               '10.0',  'Temperatura minima permitida (Â°C)'),
('humedad_max',            '90.0',  'Humedad maxima permitida (%)'),
('humedad_min',            '20.0',  'Humedad minima permitida (%)'),
('bateria_minima',         '20.0',  'Nivel minimo de bateria (%)'),
('intervalo_actualizacion','30',    'Intervalo de actualizacion en segundos');