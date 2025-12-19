<?php
/**
 * Configuración para VPS Hostinger (Producción)
 * RENOMBRAR ESTE ARCHIVO A: config.php
 */

// 1. SILENCIAR TODO EL RUIDO INMEDIATAMENTE
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Entorno
define('ENVIRONMENT', 'PRODUCTION_CLOUD');

// Base de Datos VPS
define('DB_HOST', 'localhost');
define('DB_NAME', 'user_restaurante_db');
define('DB_USER', 'user_restaurante_user');
define('DB_PASS', 'Vibercall11?');

// Rutas VPS (Automáticas - No requiere edición manual)
define('BASE_PATH', __DIR__);
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('BASE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $host);

// Seguridad
define('SESSION_SECURE', true);
define('CSRF_ENABLED', true);

// Logs y Backups (Automáticos)
define('LOG_PATH', __DIR__ . '/logs');
define('BACKUP_PATH', __DIR__ . '/backups');

// Sincronización
define('SYNC_ENABLED', true);
define('OFFLINE_MODE', false);
define('MASTER_SERVER', true);

// Email
define('ADMIN_EMAIL', 'globaltekhnologii@gmail.com');

// Zona horaria
date_default_timezone_set('America/Bogota');

// Errores (Mostrar solo logs, no en pantalla)
ini_set('error_log', LOG_PATH . '/php_errors.log');

// Configuración de Sesión Segura (Solo si no ha iniciado sesión)
if (session_status() == PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_secure', 1); // Requiere HTTPS
    ini_set('session.cookie_samesite', 'Lax');
}

/**
 * FUNCIONES GLOBALES DE CONEXIÓN
 * Requeridas por el sistema legacy
 */
function getDatabaseConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        error_log("Connection failed: " . $conn->connect_error);
        // Mostrar error genérico en producción
        die("Error de conexión a la base de datos."); 
    }
    $conn->set_charset("utf8mb4");
    // FIX: Permitir GROUP BY flexible para reportes
    $conn->query("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
    return $conn;
}

// Alias por compatibilidad
function getDBConnection() {
    return getDatabaseConnection();
}

function closeDatabaseConnection($conn) {
    if ($conn) $conn->close();
}
?>
