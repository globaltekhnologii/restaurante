<?php
/**
 * Script de Instalación - Super Administrador SaaS
 * Ejecutar una sola vez para crear las tablas de gestión de tenants y suscripciones
 */

// Conexión a la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "menu_restaurante";

$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("❌ Error de conexión: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Setup Super Admin SaaS</title>
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
    <h1>🚀 Instalación Panel Super Administrador</h1>";

try {
    // 1. Tabla de Super Administradores
    echo "<div class='info'>📋 Creando tabla <code>saas_super_admins</code>...</div>";
    $sql_super_admins = "CREATE TABLE IF NOT EXISTS saas_super_admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        name VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql_super_admins)) {
        echo "<div class='success'>✅ Tabla <code>saas_super_admins</code> creada exitosamente</div>";
    }

    // 2. Tabla de Pagos/Suscripciones
    echo "<div class='info'>📋 Creando tabla <code>saas_payments</code>...</div>";
    $sql_payments = "CREATE TABLE IF NOT EXISTS saas_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        payment_date DATE NOT NULL,
        payment_method ENUM('efectivo', 'transferencia', 'tarjeta', 'otro') DEFAULT 'transferencia',
        status ENUM('pendiente', 'completado', 'fallido', 'reembolsado') DEFAULT 'completado',
        reference_number VARCHAR(100),
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id) ON DELETE CASCADE,
        INDEX idx_tenant (tenant_id),
        INDEX idx_payment_date (payment_date),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql_payments)) {
        echo "<div class='success'>✅ Tabla <code>saas_payments</code> creada exitosamente</div>";
    }

    // 3. Alterar tabla saas_tenants para agregar campos de suscripción
    echo "<div class='info'>📋 Actualizando tabla <code>saas_tenants</code>...</div>";
    
    // Verificar si las columnas ya existen
    $check_columns = $conn->query("SHOW COLUMNS FROM saas_tenants LIKE 'subscription_end'");
    
    if ($check_columns->num_rows == 0) {
        $sql_alter_tenants = "ALTER TABLE saas_tenants 
            ADD COLUMN subscription_start DATE DEFAULT NULL AFTER plan,
            ADD COLUMN subscription_end DATE DEFAULT NULL AFTER subscription_start,
            ADD COLUMN next_billing_date DATE DEFAULT NULL AFTER subscription_end,
            ADD COLUMN monthly_fee DECIMAL(10, 2) DEFAULT 0.00 AFTER next_billing_date";
        
        if ($conn->query($sql_alter_tenants)) {
            echo "<div class='success'>✅ Tabla <code>saas_tenants</code> actualizada con campos de suscripción</div>";
        }
    } else {
        echo "<div class='info'>ℹ️ Los campos de suscripción ya existen en <code>saas_tenants</code></div>";
    }

    // 4. Insertar Super Administrador por defecto
    echo "<div class='info'>📋 Creando super administrador por defecto...</div>";
    
    // Verificar si ya existe
    $check_admin = $conn->query("SELECT id FROM saas_super_admins WHERE email = 'admin@saas.com'");
    
    if ($check_admin->num_rows == 0) {
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        $sql_insert_admin = "INSERT INTO saas_super_admins (email, password, name) 
                            VALUES ('admin@saas.com', '$admin_password', 'Super Administrador')";
        
        if ($conn->query($sql_insert_admin)) {
            echo "<div class='success'>✅ Super Administrador creado exitosamente</div>";
        }
    } else {
        echo "<div class='info'>ℹ️ Ya existe un super administrador</div>";
    }

    // 5. Actualizar tenant de prueba con datos de suscripción
    echo "<div class='info'>📋 Actualizando tenant de prueba con datos de suscripción...</div>";
    
    $subscription_start = date('Y-m-d');
    $subscription_end = date('Y-m-d', strtotime('+30 days'));
    $next_billing = date('Y-m-d', strtotime('+30 days'));
    
    $sql_update_demo = "UPDATE saas_tenants 
                       SET subscription_start = '$subscription_start',
                           subscription_end = '$subscription_end',
                           next_billing_date = '$next_billing',
                           monthly_fee = 50000.00
                       WHERE owner_email = 'demo@restaurante.com'";
    
    if ($conn->query($sql_update_demo)) {
        echo "<div class='success'>✅ Tenant de prueba actualizado con suscripción activa</div>";
    }

    echo "<div class='success' style='margin-top: 30px;'>
        <h2>🎉 ¡Instalación Completada!</h2>
        <p><strong>Credenciales Super Admin:</strong></p>
        <ul>
            <li>Email: <code>admin@saas.com</code></li>
            <li>Password: <code>admin123</code></li>
        </ul>
        <p><strong>Próximos pasos:</strong></p>
        <ol>
            <li>Accede al panel super admin: <a href='superadmin/login.php'>superadmin/login.php</a></li>
            <li>Gestiona restaurantes clientes (tenants)</li>
            <li>Controla suscripciones y pagos</li>
        </ol>
        <p><strong>Tablas creadas:</strong></p>
        <ul>
            <li>✅ <code>saas_super_admins</code> - Administradores del sistema</li>
            <li>✅ <code>saas_payments</code> - Historial de pagos</li>
            <li>✅ <code>saas_tenants</code> - Actualizada con campos de suscripción</li>
        </ul>
    </div>";

} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}

$conn->close();

echo "</body></html>";
?>
