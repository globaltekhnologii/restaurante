<?php
/**
 * ELIMINAR ÍNDICE ÚNICO DE PASARELA
 */

require_once 'config.php';
$conn = getDatabaseConnection();

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Eliminar Índice Único</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #3b82f6; padding-bottom: 10px; }
        .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0; border-radius: 5px; color: #155724; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 20px 0; border-radius: 5px; color: #721c24; }
        .info { background: #d1ecf1; border-left: 4px solid #0c5460; padding: 15px; margin: 20px 0; border-radius: 5px; }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>🔧 Eliminar Índice Único de Pasarela</h1>";

// Ver todos los índices
echo "<h2>📋 Índices Actuales</h2>";
$result = $conn->query("SHOW INDEX FROM config_pagos");

echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Key Name</th><th>Column</th><th>Unique</th></tr>";

$indices_unicos = [];
while ($row = $result->fetch_assoc()) {
    $unique = $row['Non_unique'] == 0 ? '✅ SÍ' : '❌ NO';
    echo "<tr>";
    echo "<td><strong>{$row['Key_name']}</strong></td>";
    echo "<td>{$row['Column_name']}</td>";
    echo "<td>$unique</td>";
    echo "</tr>";
    
    if ($row['Non_unique'] == 0 && $row['Key_name'] != 'PRIMARY') {
        $indices_unicos[] = $row['Key_name'];
    }
}
echo "</table>";

// Eliminar todos los índices únicos excepto PRIMARY
echo "<h2>🗑️ Eliminar Índices Únicos</h2>";

$indices_unicos = array_unique($indices_unicos);

foreach ($indices_unicos as $index_name) {
    echo "<div class='info'>⏳ Eliminando índice: <strong>$index_name</strong>...</div>";
    
    $sql = "ALTER TABLE config_pagos DROP INDEX `$index_name`";
    
    if ($conn->query($sql)) {
        echo "<div class='success'>✅ Índice <strong>$index_name</strong> eliminado</div>";
    } else {
        echo "<div class='error'>❌ Error: " . $conn->error . "</div>";
    }
}

// Crear índice único compuesto
echo "<h2>🔐 Crear Índice Único Compuesto</h2>";

$check = $conn->query("SHOW INDEX FROM config_pagos WHERE Key_name = 'unique_tenant_pasarela'");

if ($check->num_rows > 0) {
    echo "<div class='info'>✅ El índice único compuesto ya existe</div>";
} else {
    echo "<div class='info'>⏳ Creando índice único compuesto (tenant_id, pasarela)...</div>";
    
    $sql = "ALTER TABLE config_pagos ADD UNIQUE KEY unique_tenant_pasarela (tenant_id, pasarela)";
    
    if ($conn->query($sql)) {
        echo "<div class='success'>✅ Índice único compuesto creado</div>";
    } else {
        echo "<div class='error'>❌ Error: " . $conn->error . "</div>";
    }
}

// Mostrar índices finales
echo "<h2>📊 Índices Finales</h2>";
$result = $conn->query("SHOW INDEX FROM config_pagos");

echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Key Name</th><th>Column</th><th>Unique</th></tr>";

while ($row = $result->fetch_assoc()) {
    $unique = $row['Non_unique'] == 0 ? '✅ SÍ' : '❌ NO';
    echo "<tr>";
    echo "<td><strong>{$row['Key_name']}</strong></td>";
    echo "<td>{$row['Column_name']}</td>";
    echo "<td>$unique</td>";
    echo "</tr>";
}
echo "</table>";

$conn->close();

echo "<div style='margin-top: 30px;'>";
echo "<a href='crear_config_pagos_tenant.php' style='display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px;'>→ Crear Configuraciones</a>";
echo "</div>";

echo "</div></body></html>";
?>
