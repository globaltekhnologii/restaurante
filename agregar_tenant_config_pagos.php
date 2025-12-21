<?php
/**
 * AGREGAR TENANT_ID A CONFIG_PAGOS
 * Script para agregar la columna tenant_id a la tabla config_pagos
 */

require_once 'config.php';

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Agregar tenant_id a config_pagos</title>
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

echo "<h1>🔧 Agregar tenant_id a config_pagos</h1>";

$conn = getDatabaseConnection();

// Verificar si la tabla existe
$check_table = $conn->query("SHOW TABLES LIKE 'config_pagos'");

if ($check_table->num_rows == 0) {
    echo "<div class='error'>❌ La tabla 'config_pagos' no existe.</div>";
    echo "</div></body></html>";
    exit;
}

// Verificar si la columna ya existe
$result = $conn->query("SHOW COLUMNS FROM config_pagos LIKE 'tenant_id'");

if ($result->num_rows > 0) {
    echo "<div class='info'>✅ La columna 'tenant_id' ya existe en config_pagos</div>";
} else {
    echo "<div class='info'>⏳ Agregando columna 'tenant_id' a config_pagos...</div>";
    
    // Agregar la columna
    $sql = "ALTER TABLE config_pagos 
            ADD COLUMN tenant_id INT NOT NULL DEFAULT 1 AFTER id,
            ADD INDEX idx_tenant_id (tenant_id),
            ADD FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id) ON DELETE CASCADE";
    
    if ($conn->query($sql)) {
        echo "<div class='success'>✅ Columna 'tenant_id' agregada exitosamente</div>";
        echo "<div class='success'>✅ Índice creado</div>";
        echo "<div class='success'>✅ Foreign key creada</div>";
    } else {
        echo "<div class='error'>❌ Error: " . $conn->error . "</div>";
    }
}

// Eliminar índice UNIQUE de pasarela si existe
echo "<div class='info'>⏳ Verificando índice único de pasarela...</div>";
$check_unique = $conn->query("SHOW INDEX FROM config_pagos WHERE Key_name = 'pasarela' AND Non_unique = 0");

if ($check_unique->num_rows > 0) {
    echo "<div class='info'>⏳ Eliminando índice único de pasarela...</div>";
    $conn->query("ALTER TABLE config_pagos DROP INDEX pasarela");
    echo "<div class='success'>✅ Índice único eliminado</div>";
}

// Crear índice único compuesto (tenant_id + pasarela)
$check_composite = $conn->query("SHOW INDEX FROM config_pagos WHERE Key_name = 'unique_tenant_pasarela'");

if ($check_composite->num_rows == 0) {
    echo "<div class='info'>⏳ Creando índice único compuesto (tenant_id + pasarela)...</div>";
    $conn->query("ALTER TABLE config_pagos ADD UNIQUE KEY unique_tenant_pasarela (tenant_id, pasarela)");
    echo "<div class='success'>✅ Índice único compuesto creado</div>";
}

// Crear configuraciones por defecto para cada tenant
echo "<h2>📋 Crear Configuraciones por Tenant</h2>";

$tenants = $conn->query("SELECT id, restaurant_name FROM saas_tenants");

while ($tenant = $tenants->fetch_assoc()) {
    $tenant_id = $tenant['id'];
    $restaurant_name = $tenant['restaurant_name'];
    
    echo "<h3>Tenant $tenant_id: $restaurant_name</h3>";
    
    // Verificar si ya tiene configuraciones
    $check = $conn->query("SELECT COUNT(*) as total FROM config_pagos WHERE tenant_id = $tenant_id");
    $total = $check->fetch_assoc()['total'];
    
    if ($total > 0) {
        echo "<div class='info'>✅ Ya tiene $total configuraciones de pago</div>";
    } else {
        // Crear configuraciones por defecto para Bold y Mercado Pago
        $pasarelas = ['bold', 'mercadopago'];
        
        foreach ($pasarelas as $pasarela) {
            $sql = "INSERT INTO config_pagos (tenant_id, pasarela, activa, modo, public_key, secret_key) 
                    VALUES ($tenant_id, '$pasarela', 0, 'sandbox', '', '')";
            
            if ($conn->query($sql)) {
                echo "<div class='success'>✅ Configuración creada para $pasarela</div>";
            } else {
                echo "<div class='error'>❌ Error al crear $pasarela: " . $conn->error . "</div>";
            }
        }
    }
}

// Mostrar configuraciones actuales
echo "<h2>📊 Configuraciones Actuales</h2>";
$result = $conn->query("SELECT cp.*, st.restaurant_name 
                        FROM config_pagos cp 
                        LEFT JOIN saas_tenants st ON cp.tenant_id = st.id 
                        ORDER BY cp.tenant_id, cp.pasarela");

if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Tenant ID</th><th>Restaurante</th><th>Pasarela</th><th>Activa</th><th>Modo</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        $activa = $row['activa'] ? '✅ Sí' : '❌ No';
        echo "<tr>";
        echo "<td>{$row['tenant_id']}</td>";
        echo "<td>{$row['restaurant_name']}</td>";
        echo "<td><strong>{$row['pasarela']}</strong></td>";
        echo "<td>$activa</td>";
        echo "<td>{$row['modo']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='info'>No hay configuraciones de pago</div>";
}

$conn->close();

echo "<div style='margin-top: 30px;'>";
echo "<a href='config_pagos_simple.php' style='display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px;'>← Volver a Configuración de Pagos</a>";
echo "</div>";

echo "</div></body></html>";
?>
