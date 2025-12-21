<?php
/**
 * AGREGAR TENANT_ID A CONFIGURACION_DOMICILIOS
 * Script para agregar la columna tenant_id a la tabla configuracion_domicilios
 */

require_once 'config.php';

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Agregar tenant_id a configuracion_domicilios</title>
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

echo "<h1>🔧 Agregar tenant_id a configuracion_domicilios</h1>";

$conn = getDatabaseConnection();

// Verificar si la tabla existe
$check_table = $conn->query("SHOW TABLES LIKE 'configuracion_domicilios'");

if ($check_table->num_rows == 0) {
    echo "<div class='error'>❌ La tabla 'configuracion_domicilios' no existe. Créala primero.</div>";
    echo "</div></body></html>";
    exit;
}

// Verificar si la columna ya existe
$result = $conn->query("SHOW COLUMNS FROM configuracion_domicilios LIKE 'tenant_id'");

if ($result->num_rows > 0) {
    echo "<div class='info'>✅ La columna 'tenant_id' ya existe en configuracion_domicilios</div>";
} else {
    echo "<div class='info'>⏳ Agregando columna 'tenant_id' a configuracion_domicilios...</div>";
    
    // Agregar la columna
    $sql = "ALTER TABLE configuracion_domicilios 
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

// Verificar configuraciones existentes
echo "<h2>📋 Configuraciones Actuales</h2>";
$result = $conn->query("SELECT * FROM configuracion_domicilios");

if ($result->num_rows > 0) {
    echo "<div class='info'>";
    echo "<strong>Registros encontrados:</strong> " . $result->num_rows . "<br><br>";
    
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']} | Tenant ID: {$row['tenant_id']} | ";
        echo "Tarifa Base: \${$row['tarifa_base']} | ";
        echo "Costo/km: \${$row['costo_por_km']}<br>";
    }
    echo "</div>";
} else {
    echo "<div class='info'>No hay configuraciones de domicilio. Se creará una por defecto para cada tenant.</div>";
    
    // Crear configuración por defecto para cada tenant
    $tenants = $conn->query("SELECT id FROM saas_tenants");
    
    while ($tenant = $tenants->fetch_assoc()) {
        $tenant_id = $tenant['id'];
        
        // Verificar si ya existe configuración para este tenant
        $check = $conn->query("SELECT id FROM configuracion_domicilios WHERE tenant_id = $tenant_id");
        
        if ($check->num_rows == 0) {
            $sql = "INSERT INTO configuracion_domicilios 
                    (tenant_id, tarifa_base, costo_por_km, distancia_maxima, usar_rangos, activo) 
                    VALUES ($tenant_id, 5000, 1000, 10, 0, 1)";
            
            if ($conn->query($sql)) {
                echo "<div class='success'>✅ Configuración creada para Tenant $tenant_id</div>";
            }
        }
    }
}

// Agregar índice único para tenant_id si no existe
echo "<h2>🔐 Verificando Índice Único</h2>";
$check_unique = $conn->query("SHOW INDEX FROM configuracion_domicilios WHERE Key_name = 'unique_tenant_config'");

if ($check_unique->num_rows == 0) {
    echo "<div class='info'>⏳ Agregando índice único para tenant_id...</div>";
    
    $sql = "ALTER TABLE configuracion_domicilios ADD UNIQUE KEY unique_tenant_config (tenant_id)";
    
    if ($conn->query($sql)) {
        echo "<div class='success'>✅ Índice único creado (un solo registro por tenant)</div>";
    } else {
        echo "<div class='error'>❌ Error al crear índice único: " . $conn->error . "</div>";
    }
} else {
    echo "<div class='info'>✅ Índice único ya existe</div>";
}

$conn->close();

echo "<div style='margin-top: 30px;'>";
echo "<a href='admin_configuracion_domicilios.php' style='display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px;'>← Volver a Configuración</a>";
echo "</div>";

echo "</div></body></html>";
?>
