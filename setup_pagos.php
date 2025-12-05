<?php
// Script para ejecutar la configuración de pagos en la base de datos
require_once 'config.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Configurar Base de Datos de Pagos</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f7fa}";
echo ".success{background:#d4edda;border-left:4px solid #28a745;color:#155724;padding:15px;margin:10px 0;border-radius:5px}";
echo ".error{background:#f8d7da;border-left:4px solid #dc3545;color:#721c24;padding:15px;margin:10px 0;border-radius:5px}";
echo ".info{background:#d1ecf1;border-left:4px solid #17a2b8;color:#0c5460;padding:15px;margin:10px 0;border-radius:5px}";
echo "</style></head><body>";

echo "<h1>🔧 Configuración de Base de Datos - Sistema de Pagos</h1>";

$conn = getDatabaseConnection();

// Leer y ejecutar el archivo SQL
$sql_file = 'sql/pagos.sql';
if (!file_exists($sql_file)) {
    echo "<div class='error'>❌ Error: No se encontró el archivo $sql_file</div>";
    exit;
}

$sql = file_get_contents($sql_file);
$statements = explode(';', $sql);

$success_count = 0;
$error_count = 0;

foreach ($statements as $statement) {
    $statement = trim($statement);
    if (empty($statement)) continue;
    
    if ($conn->query($statement) === TRUE) {
        $success_count++;
    } else {
        echo "<div class='error'>❌ Error: " . $conn->error . "</div>";
        echo "<pre style='background:#f0f0f0;padding:10px;border-radius:5px;overflow:auto;'>" . htmlspecialchars($statement) . "</pre>";
        $error_count++;
    }
}

echo "<div class='success'>✅ Ejecutados exitosamente: $success_count comandos SQL</div>";
if ($error_count > 0) {
    echo "<div class='error'>⚠️ Errores encontrados: $error_count</div>";
}

// Verificar tablas creadas
echo "<h2>📋 Verificación de Tablas</h2>";

$tables = ['pagos', 'metodos_pago_config'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "<div class='success'>✅ Tabla '$table' creada correctamente</div>";
        
        // Mostrar estructura
        $result = $conn->query("DESCRIBE $table");
        echo "<table style='width:100%;border-collapse:collapse;background:white;margin:10px 0'>";
        echo "<tr style='background:#667eea;color:white'>";
        echo "<th style='padding:10px;border:1px solid #ddd'>Campo</th>";
        echo "<th style='padding:10px;border:1px solid #ddd'>Tipo</th>";
        echo "<th style='padding:10px;border:1px solid #ddd'>Null</th>";
        echo "<th style='padding:10px;border:1px solid #ddd'>Default</th>";
        echo "</tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td style='padding:10px;border:1px solid #ddd'><strong>" . $row['Field'] . "</strong></td>";
            echo "<td style='padding:10px;border:1px solid #ddd'>" . $row['Type'] . "</td>";
            echo "<td style='padding:10px;border:1px solid #ddd'>" . $row['Null'] . "</td>";
            echo "<td style='padding:10px;border:1px solid #ddd'>" . ($row['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='error'>❌ Tabla '$table' NO fue creada</div>";
    }
}

// Mostrar métodos de pago configurados
echo "<h2>💳 Métodos de Pago Configurados</h2>";
$result = $conn->query("SELECT * FROM metodos_pago_config ORDER BY orden");
if ($result->num_rows > 0) {
    echo "<table style='width:100%;border-collapse:collapse;background:white'>";
    echo "<tr style='background:#667eea;color:white'>";
    echo "<th style='padding:10px;border:1px solid #ddd'>Método</th>";
    echo "<th style='padding:10px;border:1px solid #ddd'>Nombre</th>";
    echo "<th style='padding:10px;border:1px solid #ddd'>Activo</th>";
    echo "</tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td style='padding:10px;border:1px solid #ddd'>" . $row['metodo'] . "</td>";
        echo "<td style='padding:10px;border:1px solid #ddd'>" . $row['nombre_display'] . "</td>";
        echo "<td style='padding:10px;border:1px solid #ddd'>" . ($row['activo'] ? '✅ Sí' : '❌ No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

$conn->close();

echo "<hr>";
echo "<h2>✅ Configuración Completada</h2>";
echo "<p><a href='config_pagos.php' style='padding:10px 20px;background:#667eea;color:white;text-decoration:none;border-radius:5px;'>📱 Configurar Métodos de Pago</a></p>";
echo "<p><a href='admin.php' style='padding:10px 20px;background:#48bb78;color:white;text-decoration:none;border-radius:5px;margin-left:10px;'>🏠 Ir al Panel Admin</a></p>";

echo "</body></html>";
?>
