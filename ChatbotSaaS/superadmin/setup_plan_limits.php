<?php
/**
 * Script de Migración: Añadir Límites por Plan
 * Añade columnas para controlar límites según el plan del tenant
 */

require_once 'config.php';

$conn = getDBConnection();

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Setup - Límites por Plan</title>
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
    <h1>📊 Setup - Límites por Plan</h1>";

try {
    echo "<div class='info'>📋 Verificando columnas en <code>saas_tenants</code>...</div>";
    
    // Verificar si las columnas ya existen
    $check = $conn->query("SHOW COLUMNS FROM saas_tenants LIKE 'max_users'");
    
    if ($check->num_rows == 0) {
        echo "<div class='info'>➕ Añadiendo columnas de límites...</div>";
        
        $sql_alter = "ALTER TABLE saas_tenants 
            ADD COLUMN max_users INT DEFAULT 5 COMMENT 'Máximo de usuarios permitidos',
            ADD COLUMN max_menu_items INT DEFAULT 50 COMMENT 'Máximo de items en el menú',
            ADD COLUMN max_storage_mb INT DEFAULT 500 COMMENT 'Almacenamiento máximo en MB',
            ADD COLUMN current_users INT DEFAULT 0 COMMENT 'Usuarios actuales',
            ADD COLUMN current_menu_items INT DEFAULT 0 COMMENT 'Items actuales en menú',
            ADD COLUMN current_storage_mb DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Almacenamiento usado en MB'";
        
        if ($conn->query($sql_alter)) {
            echo "<div class='success'>✅ Columnas de límites añadidas exitosamente</div>";
        }
        
        // Actualizar límites según el plan existente
        echo "<div class='info'>🔄 Configurando límites por defecto según planes...</div>";
        
        // Plan Basic
        $conn->query("UPDATE saas_tenants SET 
            max_users = 5, 
            max_menu_items = 50, 
            max_storage_mb = 500 
            WHERE plan = 'basic'");
        
        // Plan Pro
        $conn->query("UPDATE saas_tenants SET 
            max_users = 15, 
            max_menu_items = 200, 
            max_storage_mb = 2000 
            WHERE plan = 'pro'");
        
        // Plan Enterprise
        $conn->query("UPDATE saas_tenants SET 
            max_users = 999, 
            max_menu_items = 999, 
            max_storage_mb = 10000 
            WHERE plan = 'enterprise'");
        
        echo "<div class='success'>✅ Límites configurados por plan</div>";
        
    } else {
        echo "<div class='info'>ℹ️ Las columnas de límites ya existen</div>";
    }
    
    echo "<div class='success' style='margin-top: 30px;'>
        <h2>🎉 ¡Configuración Completada!</h2>
        <p><strong>Límites por Plan:</strong></p>
        <ul>
            <li><strong>Basic:</strong> 5 usuarios, 50 platos, 500 MB</li>
            <li><strong>Pro:</strong> 15 usuarios, 200 platos, 2 GB</li>
            <li><strong>Enterprise:</strong> Ilimitado</li>
        </ul>
        <p><strong>Próximos pasos:</strong></p>
        <ol>
            <li>Los límites se aplicarán automáticamente al crear/editar tenants</li>
            <li>Se mostrarán indicadores de uso en el panel de tenants</li>
        </ol>
    </div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}

$conn->close();

echo "</body></html>";
?>
