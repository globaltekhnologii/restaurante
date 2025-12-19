<?php
/**
 * Script de Migración: Tablas para Sistema de Actualizaciones
 * Ejecutar una sola vez para crear las tablas necesarias
 */

require_once __DIR__ . '/../config.php';

$conn = getDatabaseConnection();

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Setup - Sistema de Actualizaciones</title>
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
    <h1>🔄 Setup - Sistema de Actualizaciones</h1>";

try {
    // 1. Tabla system_updates
    echo "<div class='info'>📋 Creando tabla <code>system_updates</code>...</div>";
    
    $sql_updates = "CREATE TABLE IF NOT EXISTS system_updates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        version VARCHAR(20) NOT NULL,
        descripcion TEXT,
        tipo ENUM('critico', 'seguridad', 'feature', 'bugfix') DEFAULT 'bugfix',
        archivo_url VARCHAR(500),
        checksum VARCHAR(64),
        fecha_publicacion DATETIME,
        estado ENUM('disponible', 'descargando', 'aplicando', 'exitoso', 'fallido') DEFAULT 'disponible',
        backup_file VARCHAR(500),
        error_log TEXT,
        fecha_aplicacion DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_version (version),
        INDEX idx_estado (estado)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql_updates)) {
        echo "<div class='success'>✅ Tabla <code>system_updates</code> creada exitosamente</div>";
    }
    
    // 2. Tabla tenant_updates_log
    echo "<div class='info'>📋 Creando tabla <code>tenant_updates_log</code>...</div>";
    
    $sql_log = "CREATE TABLE IF NOT EXISTS tenant_updates_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT,
        update_id INT,
        estado ENUM('pendiente', 'aplicando', 'exitoso', 'fallido') DEFAULT 'pendiente',
        log_detalle TEXT,
        fecha_intento DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_tenant (tenant_id),
        INDEX idx_update (update_id),
        INDEX idx_estado (estado)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql_log)) {
        echo "<div class='success'>✅ Tabla <code>tenant_updates_log</code> creada exitosamente</div>";
    }
    
    echo "<div class='success' style='margin-top: 30px;'>
        <h2>🎉 ¡Instalación Completada!</h2>
        <p><strong>Tablas creadas:</strong></p>
        <ul>
            <li>✅ <code>system_updates</code> - Registro de actualizaciones del sistema</li>
            <li>✅ <code>tenant_updates_log</code> - Log de actualizaciones por tenant</li>
        </ul>
        <p><strong>Próximos pasos:</strong></p>
        <ol>
            <li>Accede al panel de actualizaciones: <a href='../ChatbotSaaS/superadmin/updates.php'>superadmin/updates.php</a></li>
            <li>Configura la URL del servidor de actualizaciones en <code>core/update_manager.php</code></li>
        </ol>
    </div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}

$conn->close();

echo "</body></html>";
?>
