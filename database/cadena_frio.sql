CREATE DATABASE IF NOT EXISTS cadena_frio_db
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cadena_frio_db;

CREATE TABLE IF NOT EXISTS mediciones (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    node_id VARCHAR(64) NOT NULL,
    temp1 DECIMAL(5,2) NULL,
    temp2 DECIMAL(5,2) NULL,
    temp3 DECIMAL(5,2) NULL,
    temp_promedio DECIMAL(5,2) NOT NULL,
    humedad_prom DECIMAL(5,2) NOT NULL,
    gradiente_temp DECIMAL(7,3) NOT NULL DEFAULT 0,
    estado_rele TINYINT(1) NOT NULL DEFAULT 0,
    timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_mediciones_node_timestamp (node_id, timestamp)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS alertas_ibn (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    node_id VARCHAR(64) NOT NULL,
    tipo_alerta VARCHAR(50) NOT NULL,
    descripcion TEXT NOT NULL,
    accion_tomada VARCHAR(255) NOT NULL,
    timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_alertas_node_timestamp (node_id, timestamp),
    INDEX idx_alertas_tipo_timestamp (tipo_alerta, timestamp)
) ENGINE=InnoDB;
