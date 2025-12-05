<?php
// config.php - Configuración de la base de datos
date_default_timezone_set('America/Bogota');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'menu_restaurante');

// Create a database connection
function getDatabaseConnection() {
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    
    // Check connection
    if ($connection->connect_error) {
        die("Connection failed: " . $connection->connect_error);
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
?>