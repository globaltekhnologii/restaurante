<?php
// config.php - Configuración de la base de datos
date_default_timezone_set('America/Bogota');

// Database configuration
// Detectar entorno: Google Cloud (App Engine), AWS (EC2/RDS), o Local (XAMPP)
$cloud_connection_name = getenv('CLOUDSQL_CONNECTION_NAME');
$aws_db_host = getenv('DB_HOST');

if ($cloud_connection_name) {
    // Configuración para Google Cloud SQL (Unix Socket)
    define('DB_USER', getenv('DB_USER'));
    define('DB_PASSWORD', getenv('DB_PASSWORD'));
    define('DB_NAME', getenv('DB_NAME'));
    define('DB_SOCKET', '/cloudsql/' . $cloud_connection_name);
    define('DB_HOST', null); // No se usa host con socket
    define('ENVIRONMENT', 'GCP');
} elseif ($aws_db_host && strpos($aws_db_host, 'rds.amazonaws.com') !== false) {
    // Configuración para AWS RDS (TCP/IP)
    define('DB_HOST', $aws_db_host);
    define('DB_USER', getenv('DB_USER'));
    define('DB_PASSWORD', getenv('DB_PASSWORD'));
    define('DB_NAME', getenv('DB_NAME'));
    define('DB_SOCKET', null);
    define('ENVIRONMENT', 'AWS');
} else {
    // Configuración Local (XAMPP/Default)
    define('DB_HOST', $aws_db_host ?: 'localhost');
    define('DB_USER', getenv('DB_USER') ?: 'root');
    define('DB_PASSWORD', getenv('DB_PASSWORD') ?: '');
    define('DB_NAME', getenv('DB_NAME') ?: 'menu_restaurante');
    define('DB_SOCKET', null);
    define('ENVIRONMENT', 'LOCAL');
}

// Configuración de manejo de errores según entorno
if (defined('ENVIRONMENT') && ENVIRONMENT === 'LOCAL') {
    // En desarrollo local mostramos errores
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    // En producción (AWS/GCP) ocultamos errores al usuario
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL);
    ini_set('log_errors', 1);
    // El path del log dependerá del enviroment, por defecto php error log
}

// Configuración de S3 (solo para AWS)
if (defined('ENVIRONMENT') && ENVIRONMENT === 'AWS') {
    define('USE_S3', getenv('AWS_USE_S3') === 'true');
    define('S3_BUCKET', getenv('S3_BUCKET'));
    define('S3_REGION', getenv('S3_REGION') ?: 'us-east-1');
} else {
    define('USE_S3', false);
    define('S3_BUCKET', null);
    define('S3_REGION', null);
}

// Create a database connection
function getDatabaseConnection() {
    if (defined('DB_SOCKET') && DB_SOCKET) {
        // Conexión vía Socket (Nube)
        $connection = new mysqli(null, DB_USER, DB_PASSWORD, DB_NAME, null, DB_SOCKET);
    } else {
        // Conexión vía TCP/IP (Local)
        $connection = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    }
    
    // Check connection
    if ($connection->connect_error) {
        throw new Exception("Error de conexión a BD: " . $connection->connect_error);
    }
    
    $connection->set_charset("utf8mb4");
    return $connection;
}

// Function to securely close the database connection
function closeDatabaseConnection($connection) {
    if ($connection) {
        $connection->close();
    }
}

