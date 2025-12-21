<?php
/**
 * MIGRACIÓN: SOPORTE MULTI-TENENCIA
 * Sistema de Restaurante
 * 
 * IMPORTANTE: 
 * - Hacer BACKUP completo de la base de datos antes de ejecutar
 * - Ejecutar solo UNA VEZ
 * - Eliminar o renombrar este archivo después de ejecutar
 */

require_once 'config.php';

// Seguridad: Solo permitir ejecución en localhost
if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
    die('❌ Este script solo puede ejecutarse desde localhost por seguridad');
}

$conn = getDatabaseConnection();

// HTML Header
echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Migración Multi-Tenencia</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2em;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 1.1em;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            color: #856404;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 8px;
        }
        .warning h3 {
            margin-bottom: 10px;
            color: #856404;
        }
        .warning ul {
            margin-left: 20px;
            margin-top: 10px;
        }
        .warning li {
            margin: 5px 0;
        }
        .step {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 15px 0;
            border-radius: 8px;
        }
        .success {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        .info {
            background: #d1ecf1;
            border-left-color: #17a2b8;
            color: #0c5460;
        }
        .step-title {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 1.1em;
        }
        .step-detail {
            font-size: 0.95em;
            margin-top: 5px;
            opacity: 0.9;
        }
        .final-message {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-top: 30px;
            text-align: center;
        }
        .final-message h2 {
            margin-bottom: 15px;
            font-size: 2em;
        }
        .final-message p {
            font-size: 1.1em;
            line-height: 1.6;
        }
        code {
            background: #f3f4f6;
            padding: 3px 8px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-top: 20px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #764ba2;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔄 Migración Multi-Tenencia</h1>
        <p class='subtitle'>Sistema de Restaurante - Aislamiento de Datos por Tenant</p>
        
        <div class='warning'>
            <h3>⚠️ ADVERTENCIA IMPORTANTE</h3>
            <ul>
                <li><strong>Hacer BACKUP completo</strong> de la base de datos antes de continuar</li>
                <li>Este script modificará la estructura de <strong>8 tablas principales</strong></li>
                <li>Todos los datos existentes se asignarán al <code>tenant_id = 1</code></li>
                <li>La migración es <strong>irreversible</strong> sin el backup</li>
                <li>Ejecutar solo <strong>UNA VEZ</strong></li>
            </ul>
        </div>";

try {
    // Deshabilitar foreign key checks temporalmente
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    $conn->query("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");
    
    // ========================================
    // PASO 1: Verificar/Crear tabla saas_tenants
    // ========================================
    echo "<div class='step info'>
            <div class='step-title'>📋 PASO 1: Verificando tabla saas_tenants...</div>";
    
    $sql_create_tenants = "CREATE TABLE IF NOT EXISTS `saas_tenants` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `restaurant_name` VARCHAR(255) NOT NULL,
        `owner_email` VARCHAR(255) NOT NULL UNIQUE,
        `owner_password` VARCHAR(255) NOT NULL,
        `phone` VARCHAR(50),
        `address` TEXT,
        `plan` ENUM('basic', 'pro', 'enterprise') DEFAULT 'basic',
        `status` ENUM('active', 'suspended', 'cancelled') DEFAULT 'active',
        `subscription_start` DATE NULL,
        `subscription_end` DATE NULL,
        `next_billing_date` DATE NULL,
        `monthly_fee` DECIMAL(10,2) DEFAULT 0.00,
        `tenant_key` VARCHAR(64) NULL UNIQUE,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_email (owner_email),
        INDEX idx_status (status),
        INDEX idx_tenant_key (tenant_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql_create_tenants)) {
        echo "<div class='step-detail'>✅ Tabla saas_tenants verificada/creada</div>";
    }
    echo "</div>";
    
    // ========================================
    // PASO 2: Crear tenant principal
    // ========================================
    echo "<div class='step info'>
            <div class='step-title'>📋 PASO 2: Creando tenant principal...</div>";
    
    $check_tenant = $conn->query("SELECT id FROM saas_tenants WHERE id = 1");
    if ($check_tenant->num_rows === 0) {
        $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO saas_tenants (id, restaurant_name, owner_email, owner_password, plan, status) VALUES (1, 'Restaurante Principal', 'admin@restaurante.com', ?, 'enterprise', 'active')");
        $stmt->bind_param("s", $password_hash);
        $stmt->execute();
        $stmt->close();
        echo "<div class='step-detail'>✅ Tenant principal creado (ID: 1)</div>";
    } else {
        echo "<div class='step-detail'>ℹ️ Tenant principal ya existe</div>";
    }
    echo "</div>";
    
    // ========================================
    // PASO 3-10: Agregar tenant_id a todas las tablas
    // ========================================
    $tables = [
        'platos' => 'Platos del menú',
        'usuarios' => 'Usuarios del sistema',
        'pedidos' => 'Pedidos',
        'clientes' => 'Clientes',
        'mesas' => 'Mesas',
        'ingredientes' => 'Ingredientes',
        'proveedores' => 'Proveedores',
        'configuracion_sistema' => 'Configuración del sistema'
    ];
    
    $paso = 3;
    foreach ($tables as $table => $description) {
        echo "<div class='step'>
                <div class='step-title'>📋 PASO $paso: Procesando tabla <code>$table</code> ($description)...</div>";
        
        // Verificar si la columna ya existe
        $check_column = $conn->query("SHOW COLUMNS FROM $table LIKE 'tenant_id'");
        
        if ($check_column->num_rows === 0) {
            // Agregar columna tenant_id
            $conn->query("ALTER TABLE `$table` ADD COLUMN `tenant_id` INT NOT NULL DEFAULT 1 AFTER `id`");
            $conn->query("ALTER TABLE `$table` ADD INDEX `idx_tenant_$table` (`tenant_id`)");
            
            // Agregar foreign key
            $fk_name = "fk_{$table}_tenant";
            $check_fk = $conn->query("SELECT COUNT(*) as count FROM information_schema.TABLE_CONSTRAINTS 
                WHERE CONSTRAINT_SCHEMA = DATABASE() 
                AND TABLE_NAME = '$table' 
                AND CONSTRAINT_NAME = '$fk_name'");
            $fk_row = $check_fk->fetch_assoc();
            
            if ($fk_row['count'] == 0) {
                $conn->query("ALTER TABLE `$table` ADD CONSTRAINT $fk_name FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id) ON DELETE CASCADE");
            }
            
            // Asignar tenant_id = 1 a datos existentes
            $conn->query("UPDATE `$table` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL OR `tenant_id` = 0");
            
            echo "<div class='step-detail success'>✅ Columna tenant_id agregada y datos migrados</div>";
        } else {
            echo "<div class='step-detail'>ℹ️ Columna tenant_id ya existe</div>";
        }
        
        echo "</div>";
        $paso++;
    }
    
    // ========================================
    // PASO ESPECIAL: Configuración única por tenant
    // ========================================
    echo "<div class='step info'>
            <div class='step-title'>📋 PASO ESPECIAL: Configurando índice único para configuracion_sistema...</div>";
    
    $check_unique = $conn->query("SHOW INDEX FROM configuracion_sistema WHERE Key_name = 'unique_tenant_config'");
    if ($check_unique->num_rows === 0) {
        try {
            $conn->query("ALTER TABLE configuracion_sistema ADD UNIQUE KEY unique_tenant_config (tenant_id)");
            echo "<div class='step-detail'>✅ Índice único agregado (solo una configuración por tenant)</div>";
        } catch (Exception $e) {
            echo "<div class='step-detail'>⚠️ Índice único ya existe o no se pudo crear</div>";
        }
    } else {
        echo "<div class='step-detail'>ℹ️ Índice único ya existe</div>";
    }
    echo "</div>";
    
    // ========================================
    // PASO FINAL: Verificación
    // ========================================
    echo "<div class='step success'>
            <div class='step-title'>🔍 Verificación Final...</div>";
    
    foreach ($tables as $table => $description) {
        $result = $conn->query("SELECT COUNT(*) as count FROM $table WHERE tenant_id = 1");
        $row = $result->fetch_assoc();
        echo "<div class='step-detail'>✅ $description: {$row['count']} registros asignados a tenant_id = 1</div>";
    }
    echo "</div>";
    
    // Restaurar foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    
    // ========================================
    // MENSAJE FINAL
    // ========================================
    echo "<div class='final-message'>
            <h2>🎉 ¡Migración Completada Exitosamente!</h2>
            <p><strong>Todas las tablas ahora tienen soporte multi-tenencia</strong></p>
            <p>Datos existentes asignados a <code>tenant_id = 1</code> (Restaurante Principal)</p>
            <p style='margin-top: 20px;'><strong>Próximos pasos:</strong></p>
            <ol style='text-align: left; display: inline-block; margin-top: 15px;'>
                <li>Actualizar archivos PHP para usar el nuevo sistema</li>
                <li>Probar el sistema con el tenant principal</li>
                <li>Crear nuevos tenants desde el panel de Super Admin</li>
                <li><strong>ELIMINAR o RENOMBRAR este archivo (migration_multitenancy.php)</strong></li>
            </ol>
            <br>
            <a href='admin.php' class='btn'>Ir al Panel de Administración</a>
          </div>";
    
} catch (Exception $e) {
    echo "<div class='step error'>
            <div class='step-title'>❌ ERROR CRÍTICO</div>
            <div class='step-detail'>{$e->getMessage()}</div>
            <div class='step-detail' style='margin-top: 10px;'><strong>IMPORTANTE:</strong> Restaurar el backup de la base de datos</div>
          </div>";
}

$conn->close();

echo "    </div>
</body>
</html>";
?>

