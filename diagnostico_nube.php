<?php
// diagnostico_nube.php - Herramienta de Debugging para Google App Engine
// ----------------------------------------------------------------------

// 1. Activar reporte de errores TOTAL
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/plain; charset=utf-8');

echo "=== DIAGNÓSTICO DE DESPLIEGUE GOOGLE CLOUD ===\n";
echo "Fecha/Hora: " . date('Y-m-d H:i:s') . "\n";
echo "PHP Version: " . phpversion() . "\n\n";

// 2. Verificar Variables de Entorno
echo "--- 1. Variables de Entorno ---\n";
$env_conn = getenv('CLOUDSQL_CONNECTION_NAME');
$env_db = getenv('DB_NAME');
$env_user = getenv('DB_USER');
$env_pass = getenv('DB_PASSWORD'); // No mostrar completa por seguridad

echo "CLOUDSQL_CONNECTION_NAME: " . ($env_conn ? $env_conn : "❌ NO DEFINIDO") . "\n";
echo "DB_NAME: " . ($env_db ? $env_db : "❌ NO DEFINIDO") . "\n";
echo "DB_USER: " . ($env_user ? $env_user : "❌ NO DEFINIDO") . "\n";
echo "DB_PASSWORD: " . ($env_pass ? "****** (Oculto)" : "❌ NO DEFINIDO") . "\n\n";

// 3. Prueba de Conexión (Raw MySQLi)
echo "--- 2. Prueba de Conexión Directa ---\n";

if ($env_conn) {
    // Intento vía Socket (Estándar App Engine)
    $socket_path = "/cloudsql/" . $env_conn;
    echo "Intentando conectar vía socket: $socket_path ...\n";

    try {
        $conn = new mysqli(null, $env_user, $env_pass, $env_db, null, $socket_path);
        
        if ($conn->connect_error) {
            echo "❌ FALLO CONEXIÓN: " . $conn->connect_error . "\n";
            echo "Errno: " . $conn->connect_errno . "\n";
        } else {
            echo "✅ CONEXIÓN EXITOSA\n";
            echo "Host Info: " . $conn->host_info . "\n";
            echo "Server Info: " . $conn->server_info . "\n";
            $conn->close();
        }
    } catch (Exception $e) {
        echo "❌ EXCEPCIÓN: " . $e->getMessage() . "\n";
    }

} else {
    echo "⚠️ Saltando prueba de socket porque no hay CONNECTION_NAME definido.\n";
}

echo "\n--- Fin del Diagnóstico ---\n";
?>
