<?php
/**
 * Script de Migración: Tabla de Logs de Auditoría
 */

require_once 'config.php';

$conn = getDBConnection();

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Setup - Logs de Auditoría</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: #22c55e; padding: 10px; background: #f0fdf4; border-left: 4px solid #22c55e; margin: 10px 0; }
        .error { color: #ef4444; padding: 10px; background: #fef2f2; border-left: 4px solid #ef4444; margin: 10px 0; }
        .info { color: #3b82f6; padding: 10px; background: #eff6ff; border-left: 4px solid #3b82f6; margin: 10px 0; }
        h1 { color: #1f2937; }
        code { background: #f3f4f6; padding: 2px 6px; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>📝 Setup - Logs de Auditoría</h1>";

try {
    echo "<div class='info'>📋 Creando tabla <code>audit_logs</code>...</div>";
    
    $sql_audit = "CREATE TABLE IF NOT EXISTS audit_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT,
        admin_email VARCHAR(255),
        action VARCHAR(100) NOT NULL,
        entity_type VARCHAR(50),
        entity_id INT,
        details TEXT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_admin (admin_id),
        INDEX idx_action (action),
        INDEX idx_entity (entity_type, entity_id),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql_audit)) {
        echo "<div class='success'>✅ Tabla <code>audit_logs</code> creada exitosamente</div>";
    }
    
    echo "<div class='success' style='margin-top: 30px;'>
        <h2>🎉 ¡Instalación Completada!</h2>
        <p><strong>Tabla creada:</strong></p>
        <ul>
            <li>✅ <code>audit_logs</code> - Registro de todas las acciones críticas</li>
        </ul>
        <p><strong>Acciones que se registrarán:</strong></p>
        <ul>
            <li>Creación, edición y eliminación de tenants</li>
            <li>Cambios de planes y suscripciones</li>
            <li>Suspensiones y activaciones</li>
            <li>Aplicación de actualizaciones</li>
        </ul>
    </div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}

$conn->close();

echo "</body></html>";
?>
