<?php
/**
 * GENERAR TENANT KEYS FALTANTES
 * Script rápido para generar keys a tenants que no lo tienen
 */

require_once 'config.php';

$conn = getDatabaseConnection();

// Buscar tenants sin key
$result = $conn->query("SELECT id, restaurant_name FROM saas_tenants WHERE tenant_key IS NULL OR tenant_key = ''");

if ($result->num_rows > 0) {
    echo "<h2>Generando Tenant Keys faltantes...</h2>";
    
    while ($row = $result->fetch_assoc()) {
        $tenant_id = $row['id'];
        $restaurant_name = $row['restaurant_name'];
        
        // Generar key único
        $tenant_key = 'tenant_' . str_pad($tenant_id, 6, '0', STR_PAD_LEFT) . '_' . bin2hex(random_bytes(4));
        
        // Actualizar
        $stmt = $conn->prepare("UPDATE saas_tenants SET tenant_key = ? WHERE id = ?");
        $stmt->bind_param("si", $tenant_key, $tenant_id);
        $stmt->execute();
        $stmt->close();
        
        echo "<p>✅ Tenant ID $tenant_id ($restaurant_name) → Key: <strong>$tenant_key</strong></p>";
    }
    
    echo "<h3>✅ Proceso completado</h3>";
} else {
    echo "<p>✅ Todos los tenants ya tienen su tenant_key</p>";
}

// Mostrar todos los tenants
echo "<h2>Tenants Actuales:</h2>";
$result = $conn->query("SELECT id, restaurant_name, tenant_key, status FROM saas_tenants ORDER BY id");

echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Restaurante</th><th>Tenant Key</th><th>Estado</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['restaurant_name']}</td>";
    echo "<td><code>{$row['tenant_key']}</code></td>";
    echo "<td>{$row['status']}</td>";
    echo "</tr>";
}

echo "</table>";

$conn->close();
?>
