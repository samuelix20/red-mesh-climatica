<?php
/**
 * config/database.php
 * Conexion PDO reutilizable. Retorna null si no hay BD disponible
 * para permitir el modo de datos simulados.
 */
define('DB_HOST',    'localhost');
define('DB_NAME',    'red_mesh_climatica');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

function getDB(): ?PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            error_log('[Red Mesh] BD Error: ' . $e->getMessage());
            return null;
        }
    }
    return $pdo;
}

function dbConnected(): bool { return getDB() !== null; }