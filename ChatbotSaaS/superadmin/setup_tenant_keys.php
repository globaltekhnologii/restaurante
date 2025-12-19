<?php
/**
 * Script de Migración: Sistema de Tenant ID
 * Añade campos necesarios para identificación y sincronización de tenants
 */

require_once 'config.php';

$conn = getDBConnection();

echo "=== Migración: Sistema de Tenant ID ===\n\n";

// 1. Añadir columnas a saas_tenants
echo "1. Añadiendo columnas a saas_tenants...\n";

$sql = "ALTER TABLE saas_tenants
    ADD COLUMN IF NOT EXISTS tenant_key VARCHAR(64) UNIQUE AFTER id,
    ADD COLUMN IF NOT EXISTS api_token VARCHAR(128) UNIQUE AFTER tenant_key,
    ADD COLUMN IF NOT EXISTS last_sync TIMESTAMP NULL AFTER updated_at";

if ($conn->query($sql)) {
    echo "   ✓ Columnas añadidas exitosamente\n";
} else {
    echo "   ✗ Error: " . $conn->error . "\n";
}

// 2. Generar claves únicas para tenants existentes
echo "\n2. Generando claves únicas para tenants existentes...\n";

$result = $conn->query("SELECT id FROM saas_tenants WHERE tenant_key IS NULL OR tenant_key = ''");

if ($result && $result->num_rows > 0) {
    $count = 0;
    while ($row = $result->fetch_assoc()) {
        $tenantId = $row['id'];
        
        // Generar tenant_key único
        $tenantKey = 'tenant_' . str_pad($tenantId, 6, '0', STR_PAD_LEFT) . '_' . substr(md5(uniqid()), 0, 8);
        
        // Generar api_token único
        $apiToken = hash('sha256', 'api_' . $tenantId . '_' . uniqid() . '_' . mt_rand());
        
        $stmt = $conn->prepare("UPDATE saas_tenants SET tenant_key = ?, api_token = ? WHERE id = ?");
        $stmt->bind_param("ssi", $tenantKey, $apiToken, $tenantId);
        
        if ($stmt->execute()) {
            echo "   ✓ Tenant ID $tenantId: $tenantKey\n";
            $count++;
        } else {
            echo "   ✗ Error en tenant ID $tenantId: " . $stmt->error . "\n";
        }
        
        $stmt->close();
    }
    echo "   Total: $count tenants actualizados\n";
} else {
    echo "   ℹ No hay tenants sin claves\n";
}

// 3. Crear tabla de sincronización (opcional, para historial)
echo "\n3. Creando tabla de historial de sincronización...\n";

$sql = "CREATE TABLE IF NOT EXISTS tenant_sync_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    sync_type ENUM('usage', 'config', 'limits', 'manual') DEFAULT 'usage',
    data JSON,
    status ENUM('success', 'failed') DEFAULT 'success',
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tenant (tenant_id),
    INDEX idx_created (created_at),
    FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql)) {
    echo "   ✓ Tabla tenant_sync_log creada\n";
} else {
    echo "   ✗ Error: " . $conn->error . "\n";
}

// 4. Mostrar resumen
echo "\n=== Resumen ===\n";
$result = $conn->query("SELECT COUNT(*) as total FROM saas_tenants");
$total = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as with_keys FROM saas_tenants WHERE tenant_key IS NOT NULL AND tenant_key != ''");
$withKeys = $result->fetch_assoc()['with_keys'];

echo "Total de tenants: $total\n";
echo "Tenants con claves: $withKeys\n";

if ($total == $withKeys) {
    echo "\n✅ Migración completada exitosamente\n";
} else {
    echo "\n⚠️ Algunos tenants no tienen claves asignadas\n";
}

$conn->close();
?>
