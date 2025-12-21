<?php
/**
 * AGREGAR COLUMNA API_TOKEN A SAAS_TENANTS
 * Ejecutar este script UNA SOLA VEZ
 */

require_once 'config.php';

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Agregar API Token</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #3b82f6; padding-bottom: 10px; }
        .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0; border-radius: 5px; color: #155724; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 20px 0; border-radius: 5px; color: #721c24; }
        .info { background: #d1ecf1; border-left: 4px solid #0c5460; padding: 15px; margin: 20px 0; border-radius: 5px; color: #0c5460; }
        code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: 600; }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>🔑 Agregar Columna API Token</h1>";

$conn = getDatabaseConnection();

// Verificar si la columna ya existe
$result = $conn->query("SHOW COLUMNS FROM saas_tenants LIKE 'api_token'");

if ($result->num_rows > 0) {
    echo "<div class='info'>✅ La columna 'api_token' ya existe en la tabla saas_tenants</div>";
} else {
    echo "<div class='info'>⏳ Agregando columna 'api_token' a la tabla saas_tenants...</div>";
    
    // Agregar la columna
    $sql = "ALTER TABLE saas_tenants 
            ADD COLUMN api_token VARCHAR(100) NULL 
            AFTER tenant_key";
    
    if ($conn->query($sql)) {
        echo "<div class='success'>✅ Columna 'api_token' agregada exitosamente</div>";
    } else {
        echo "<div class='error'>❌ Error al agregar columna: " . $conn->error . "</div>";
    }
}

// Generar tokens para tenants que no lo tienen
echo "<div class='info'>⏳ Generando API tokens para tenants sin token...</div>";

$result = $conn->query("SELECT id, restaurant_name, tenant_key FROM saas_tenants WHERE api_token IS NULL OR api_token = ''");
$count = 0;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $tenant_id = $row['id'];
        $restaurant_name = $row['restaurant_name'];
        
        // Generar token único
        $api_token = bin2hex(random_bytes(32));
        
        // Actualizar
        $stmt = $conn->prepare("UPDATE saas_tenants SET api_token = ? WHERE id = ?");
        $stmt->bind_param("si", $api_token, $tenant_id);
        $stmt->execute();
        $stmt->close();
        
        $count++;
        echo "<div class='success'>✅ Tenant ID $tenant_id ($restaurant_name) → Token generado</div>";
    }
    
    echo "<div class='success'><strong>✅ Proceso completado. $count tenant(s) actualizados.</strong></div>";
} else {
    echo "<div class='info'>✅ Todos los tenants ya tienen su api_token</div>";
}

// Mostrar todos los tenants con sus claves
echo "<h2>📋 Tenants Actuales</h2>";
$result = $conn->query("SELECT id, restaurant_name, tenant_key, api_token, status FROM saas_tenants ORDER BY id");

if ($result->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>Restaurante</th><th>Tenant Key</th><th>API Token</th><th>Estado</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        $token_display = !empty($row['api_token']) ? substr($row['api_token'], 0, 20) . '...' : '<em>Sin token</em>';
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['restaurant_name']}</td>";
        echo "<td><code>{$row['tenant_key']}</code></td>";
        echo "<td><code>{$token_display}</code></td>";
        echo "<td>{$row['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='info'>No hay tenants en el sistema</div>";
}

$conn->close();

echo "<div style='margin-top: 30px;'>";
echo "<a href='ChatbotSaaS/superadmin/generate_tenant_config.php?tenant_id=3' style='display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px; margin-right: 10px;'>🔄 Intentar Descargar Config Tenant 3</a>";
echo "<a href='ChatbotSaaS/superadmin/tenants.php' style='display: inline-block; padding: 12px 24px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px;'>← Volver al Panel</a>";
echo "</div>";

echo "</div></body></html>";
?>
