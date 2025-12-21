<?php
/**
 * AGREGAR COLUMNA TENANT_KEY A SAAS_TENANTS
 * Ejecutar este script UNA SOLA VEZ si la columna tenant_key no existe
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config.php'; // Corregido: sin ../

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Agregar Tenant Key</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #3b82f6; padding-bottom: 10px; }
        .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0; border-radius: 5px; color: #155724; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 20px 0; border-radius: 5px; color: #721c24; }
        .info { background: #d1ecf1; border-left: 4px solid #0c5460; padding: 15px; margin: 20px 0; border-radius: 5px; color: #0c5460; }
        code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>🔑 Agregar Columna Tenant Key</h1>";

$conn = getDatabaseConnection();

// Verificar si la columna ya existe
$result = $conn->query("SHOW COLUMNS FROM saas_tenants LIKE 'tenant_key'");

if ($result->num_rows > 0) {
    echo "<div class='info'>✅ La columna 'tenant_key' ya existe en la tabla saas_tenants</div>";
} else {
    echo "<div class='info'>⏳ Agregando columna 'tenant_key' a la tabla saas_tenants...</div>";
    
    // Agregar la columna
    $sql = "ALTER TABLE saas_tenants 
            ADD COLUMN tenant_key VARCHAR(100) NULL UNIQUE 
            AFTER monthly_fee";
    
    if ($conn->query($sql)) {
        echo "<div class='success'>✅ Columna 'tenant_key' agregada exitosamente</div>";
        
        // Generar keys para tenants existentes
        echo "<div class='info'>⏳ Generando tenant_key para restaurantes existentes...</div>";
        
        $result = $conn->query("SELECT id FROM saas_tenants WHERE tenant_key IS NULL");
        $count = 0;
        
        while ($row = $result->fetch_assoc()) {
            $tenant_id = $row['id'];
            $tenant_key = 'tenant_' . uniqid() . '_' . bin2hex(random_bytes(8));
            
            $stmt = $conn->prepare("UPDATE saas_tenants SET tenant_key = ? WHERE id = ?");
            $stmt->bind_param("si", $tenant_key, $tenant_id);
            $stmt->execute();
            $stmt->close();
            
            $count++;
            echo "<div class='success'>✅ Tenant ID $tenant_id → Key: <code>$tenant_key</code></div>";
        }
        
        echo "<div class='success'><strong>✅ Proceso completado. $count tenant(s) actualizados.</strong></div>";
        
    } else {
        echo "<div class='error'>❌ Error al agregar columna: " . $conn->error . "</div>";
    }
}

// Mostrar tenants actuales con sus keys
echo "<h2>📋 Tenants Actuales</h2>";
$result = $conn->query("SELECT id, restaurant_name, tenant_key, status FROM saas_tenants ORDER BY id");

if ($result->num_rows > 0) {
    echo "<table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>";
    echo "<tr style='background: #f8f9fa;'>";
    echo "<th style='padding: 12px; text-align: left; border-bottom: 2px solid #ddd;'>ID</th>";
    echo "<th style='padding: 12px; text-align: left; border-bottom: 2px solid #ddd;'>Restaurante</th>";
    echo "<th style='padding: 12px; text-align: left; border-bottom: 2px solid #ddd;'>Tenant Key</th>";
    echo "<th style='padding: 12px; text-align: left; border-bottom: 2px solid #ddd;'>Estado</th>";
    echo "</tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td style='padding: 12px; border-bottom: 1px solid #ddd;'>{$row['id']}</td>";
        echo "<td style='padding: 12px; border-bottom: 1px solid #ddd;'>{$row['restaurant_name']}</td>";
        echo "<td style='padding: 12px; border-bottom: 1px solid #ddd;'><code>{$row['tenant_key']}</code></td>";
        echo "<td style='padding: 12px; border-bottom: 1px solid #ddd;'>{$row['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='info'>No hay tenants en el sistema</div>";
}

$conn->close();

echo "<div style='margin-top: 30px;'>";
echo "<a href='superadmin/tenants.php' style='display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px;'>← Volver al Panel</a>";
echo "</div>";

echo "</div></body></html>";
?>
