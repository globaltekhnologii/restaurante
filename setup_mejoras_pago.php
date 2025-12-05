<?php
// Script para ejecutar mejoras de base de datos
require_once 'config.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Setup Mejoras de Pago</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f7fa}";
echo ".success{background:#d4edda;border-left:4px solid #28a745;color:#155724;padding:15px;margin:10px 0;border-radius:5px}";
echo ".error{background:#f8d7da;border-left:4px solid #dc3545;color:#721c24;padding:15px;margin:10px 0;border-radius:5px}";
echo ".info{background:#d1ecf1;border-left:4px solid #17a2b8;color:#0c5460;padding:15px;margin:10px 0;border-radius:5px}";
echo "</style></head><body>";

echo "<h1>🔧 Configuración: Mejoras de Pago y Tipos de Pedido</h1>";

$conn = getDatabaseConnection();

// Leer y ejecutar el archivo SQL
$sql_file = 'sql/mejoras_pago.sql';
if (!file_exists($sql_file)) {
    echo "<div class='error'>❌ Error: No se encontró el archivo $sql_file</div>";
    exit;
}

$sql = file_get_contents($sql_file);
$statements = array_filter(array_map('trim', explode(';', $sql)));

$success_count = 0;
$error_count = 0;

foreach ($statements as $statement) {
    if (empty($statement) || strpos($statement, '--') === 0) continue;
    
    if ($conn->query($statement) === TRUE) {
        $success_count++;
    } else {
        // Ignorar errores de columnas duplicadas
        if (strpos($conn->error, 'Duplicate column') === false && 
            strpos($conn->error, 'Duplicate key') === false) {
            echo "<div class='error'>❌ Error: " . $conn->error . "</div>";
            echo "<pre style='background:#f0f0f0;padding:10px;border-radius:5px;overflow:auto;'>" . htmlspecialchars($statement) . "</pre>";
            $error_count++;
        }
    }
}

echo "<div class='success'>✅ Ejecutados exitosamente: $success_count comandos SQL</div>";
if ($error_count > 0) {
    echo "<div class='error'>⚠️ Errores encontrados: $error_count</div>";
}

// Verificar columnas agregadas
echo "<h2>📋 Verificación de Cambios</h2>";

$result = $conn->query("SHOW COLUMNS FROM pedidos LIKE 'tipo_pedido'");
if ($result->num_rows > 0) {
    echo "<div class='success'>✅ Columna 'tipo_pedido' agregada</div>";
} else {
    echo "<div class='error'>❌ Columna 'tipo_pedido' NO agregada</div>";
}

$result = $conn->query("SHOW COLUMNS FROM pedidos LIKE 'pago_anticipado'");
if ($result->num_rows > 0) {
    echo "<div class='success'>✅ Columna 'pago_anticipado' agregada</div>";
} else {
    echo "<div class='error'>❌ Columna 'pago_anticipado' NO agregada</div>";
}

$result = $conn->query("SHOW COLUMNS FROM pedidos LIKE 'validacion_automatica'");
if ($result->num_rows > 0) {
    echo "<div class='success'>✅ Columna 'validacion_automatica' agregada</div>";
} else {
    echo "<div class='error'>❌ Columna 'validacion_automatica' NO agregada</div>";
}

$conn->close();

echo "<hr>";
echo "<h2>✅ Configuración Completada</h2>";
echo "<p>Ahora puedes usar las nuevas funcionalidades:</p>";
echo "<ul>";
echo "<li>✅ Prepago con validación manual o automática</li>";
echo "<li>✅ Liberación automática de mesas</li>";
echo "<li>✅ Pedidos para llevar</li>";
echo "</ul>";

echo "<p><a href='index.php' style='padding:10px 20px;background:#667eea;color:white;text-decoration:none;border-radius:5px;'>🏠 Ir al Menú</a></p>";
echo "<p><a href='admin.php' style='padding:10px 20px;background:#48bb78;color:white;text-decoration:none;border-radius:5px;margin-left:10px;'>⚙️ Panel Admin</a></p>";

echo "</body></html>";
?>
