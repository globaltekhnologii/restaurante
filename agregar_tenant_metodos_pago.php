<?php
/**
 * AGREGAR TENANT_ID A METODOS_PAGO_CONFIG
 */

require_once 'config.php';
$conn = getDatabaseConnection();

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Agregar tenant_id a metodos_pago_config</title>
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

echo "<h1>🔧 Agregar tenant_id a metodos_pago_config</h1>";

// Verificar si la tabla existe
$check_table = $conn->query("SHOW TABLES LIKE 'metodos_pago_config'");

if ($check_table->num_rows == 0) {
    echo "<div class='error'>❌ La tabla 'metodos_pago_config' no existe.</div>";
    echo "</div></body></html>";
    exit;
}

// Verificar si la columna ya existe
$result = $conn->query("SHOW COLUMNS FROM metodos_pago_config LIKE 'tenant_id'");

if ($result->num_rows > 0) {
    echo "<div class='info'>✅ La columna 'tenant_id' ya existe</div>";
} else {
    echo "<div class='info'>⏳ Agregando columna 'tenant_id'...</div>";
    
    $sql = "ALTER TABLE metodos_pago_config 
            ADD COLUMN tenant_id INT NOT NULL DEFAULT 1 AFTER id,
            ADD INDEX idx_tenant_id (tenant_id),
            ADD FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id) ON DELETE CASCADE";
    
    if ($conn->query($sql)) {
        echo "<div class='success'>✅ Columna agregada exitosamente</div>";
    } else {
        echo "<div class='error'>❌ Error: " . $conn->error . "</div>";
    }
}

// Crear métodos de pago para cada tenant
echo "<h2>📋 Crear Métodos de Pago por Tenant</h2>";

$tenants = $conn->query("SELECT id, restaurant_name FROM saas_tenants");

while ($tenant = $tenants->fetch_assoc()) {
    $tenant_id = $tenant['id'];
    $restaurant_name = $tenant['restaurant_name'];
    
    echo "<h3>Tenant $tenant_id: $restaurant_name</h3>";
    
    // Verificar si ya tiene métodos
    $check = $conn->query("SELECT COUNT(*) as total FROM metodos_pago_config WHERE tenant_id = $tenant_id");
    $total = $check->fetch_assoc()['total'];
    
    if ($total > 0) {
        echo "<div class='info'>✅ Ya tiene $total métodos de pago</div>";
    } else {
        // Crear métodos por defecto
        $metodos = [
            ['efectivo', 'Efectivo', 1],
            ['nequi', 'Nequi', 2],
            ['daviplata', 'Daviplata', 3],
            ['dale', 'Dale', 4],
            ['bancolombia', 'Bancolombia', 5]
        ];
        
        foreach ($metodos as $metodo) {
            list($metodo_id, $nombre, $orden) = $metodo;
            
            $sql = "INSERT INTO metodos_pago_config (tenant_id, metodo, nombre_display, activo, orden) 
                    VALUES ($tenant_id, '$metodo_id', '$nombre', 1, $orden)";
            
            if ($conn->query($sql)) {
                echo "<div class='success'>✅ Método creado: $nombre</div>";
            } else {
                echo "<div class='error'>❌ Error: " . $conn->error . "</div>";
            }
        }
    }
}

$conn->close();

echo "<div style='margin-top: 30px;'>";
echo "<a href='config_pagos.php' style='display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px;'>→ Ir a Config Pagos</a>";
echo "</div>";

echo "</div></body></html>";
?>
