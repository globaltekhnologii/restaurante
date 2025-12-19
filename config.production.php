<?php
/**
 * Configuración para VPS Hostinger (Producción)
 * RENOMBRAR ESTE ARCHIVO A: config.php
 */

// Entorno
define('ENVIRONMENT', 'PRODUCTION_CLOUD');

// Base de Datos VPS
define('DB_HOST', 'localhost');
define('DB_NAME', 'admin_restaurante_db'); // AJUSTAR: El nombre real en HestiaCP
define('DB_USER', 'admin_restaurante_user'); // AJUSTAR: El usuario real en HestiaCP
define('DB_PASS', 'TU_CONTRASEÑA_AQUI'); // AJUSTAR: La contraseña que creaste

// Rutas VPS (Automáticas - No requiere edición manual)
define('BASE_PATH', __DIR__);
define('BASE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]");

// Seguridad
define('SESSION_SECURE', true); // Se asume HTTPS
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
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', LOG_PATH . '/php_errors.log');

// Configuración de Sesión Segura
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_secure', 1); // Requiere HTTPS
ini_set('session.cookie_samesite', 'Strict');

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
