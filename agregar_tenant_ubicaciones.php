<?php
/**
 * AGREGAR TENANT_ID A UBICACION_DOMICILIARIOS
 */

require_once 'config.php';
$conn = getDatabaseConnection();

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Agregar tenant_id a ubicacion_domiciliarios</title>
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

echo "<h1>🔧 Agregar tenant_id a ubicacion_domiciliarios</h1>";

// Verificar si la tabla existe
$check_table = $conn->query("SHOW TABLES LIKE 'ubicacion_domiciliarios'");

if ($check_table->num_rows == 0) {
    echo "<div class='error'>❌ La tabla 'ubicacion_domiciliarios' no existe. Creándola...</div>";
    
    $sql = "CREATE TABLE ubicacion_domiciliarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NOT NULL DEFAULT 1,
        usuario_id INT NOT NULL,
        latitud DECIMAL(10, 8) NOT NULL,
        longitud DECIMAL(11, 8) NOT NULL,
        ultima_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_tenant_id (tenant_id),
        INDEX idx_usuario_id (usuario_id),
        UNIQUE KEY unique_tenant_usuario (tenant_id, usuario_id),
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id) ON DELETE CASCADE,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($sql)) {
        echo "<div class='success'>✅ Tabla creada exitosamente</div>";
    } else {
        echo "<div class='error'>❌ Error: " . $conn->error . "</div>";
    }
} else {
    // Verificar si la columna ya existe
    $result = $conn->query("SHOW COLUMNS FROM ubicacion_domiciliarios LIKE 'tenant_id'");
    
    if ($result->num_rows > 0) {
        echo "<div class='info'>✅ La columna 'tenant_id' ya existe</div>";
    } else {
        echo "<div class='info'>⏳ Agregando columna 'tenant_id'...</div>";
        
        $sql = "ALTER TABLE ubicacion_domiciliarios 
                ADD COLUMN tenant_id INT NOT NULL DEFAULT 1 AFTER id,
                ADD INDEX idx_tenant_ubicacion (tenant_id),
                ADD FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id) ON DELETE CASCADE";
        
        if ($conn->query($sql)) {
            echo "<div class='success'>✅ Columna agregada exitosamente</div>";
            
            // Actualizar registros existentes con tenant_id basado en el usuario
            echo "<div class='info'>⏳ Actualizando registros existentes...</div>";
            
            $sql_update = "UPDATE ubicacion_domiciliarios ud 
                          JOIN usuarios u ON ud.usuario_id = u.id 
                          SET ud.tenant_id = u.tenant_id";
            
            if ($conn->query($sql_update)) {
                echo "<div class='success'>✅ Registros actualizados</div>";
            } else {
                echo "<div class='error'>❌ Error al actualizar: " . $conn->error . "</div>";
            }
        } else {
            echo "<div class='error'>❌ Error: " . $conn->error . "</div>";
        }
    }
}

// Mostrar ubicaciones por tenant
echo "<h2>📊 Ubicaciones por Tenant</h2>";

$tenants = $conn->query("SELECT id, restaurant_name FROM saas_tenants");

echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Tenant ID</th><th>Restaurante</th><th>Domiciliarios con GPS</th></tr>";

while ($tenant = $tenants->fetch_assoc()) {
    $tenant_id = $tenant['id'];
    $restaurant_name = $tenant['restaurant_name'];
    
    $check = $conn->query("SELECT COUNT(*) as total FROM ubicacion_domiciliarios WHERE tenant_id = $tenant_id");
    $total = $check->fetch_assoc()['total'];
    
    echo "<tr>";
    echo "<td><strong>Tenant $tenant_id</strong></td>";
    echo "<td>$restaurant_name</td>";
    echo "<td>$total ubicaciones</td>";
    echo "</tr>";
}
echo "</table>";

$conn->close();

echo "<div style='margin-top: 30px;'>";
echo "<a href='domiciliario.php' style='display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px;'>→ Ir a Panel Domiciliario</a>";
echo "</div>";

echo "</div></body></html>";
?>
