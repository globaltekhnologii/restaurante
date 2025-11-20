<?php
// config.php

// Database configuration
define('DB_HOST', 'localhost'); // Your database host
define('DB_USER', 'root'); // Your database username
define('DB_PASSWORD', 'your_password'); // Your database password
define('DB_NAME', 'your_database_name'); // Your database name

// Create a database connection
function getDatabaseConnection() {
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

    // Check connection
    if ($connection->connect_error) {
        die("Connection failed: " . $connection->connect_error);
    }
    return $connection;
}

// Function to securely close the database connection
function closeDatabaseConnection($connection) {
    if ($connection) {
        $connection->close();
    }
}
?>