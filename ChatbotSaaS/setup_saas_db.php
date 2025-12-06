<?php
/**
 * Script de Instalación - Base de Datos SaaS Chatbot
 * Ejecutar una sola vez para crear las tablas necesarias
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
    <title>Setup SaaS Chatbot</title>
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
    <h1>🚀 Instalación SaaS Chatbot</h1>";

try {
    // 1. Tabla de Tenants (Restaurantes Clientes)
    echo "<div class='info'>📋 Creando tabla <code>saas_tenants</code>...</div>";
    $sql_tenants = "CREATE TABLE IF NOT EXISTS saas_tenants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        restaurant_name VARCHAR(255) NOT NULL,
        owner_email VARCHAR(255) NOT NULL UNIQUE,
        owner_password VARCHAR(255) NOT NULL,
        phone VARCHAR(50),
        address TEXT,
        business_hours VARCHAR(100),
        plan ENUM('basic', 'pro', 'enterprise') DEFAULT 'basic',
        status ENUM('active', 'suspended', 'cancelled') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_email (owner_email),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql_tenants)) {
        echo "<div class='success'>✅ Tabla <code>saas_tenants</code> creada exitosamente</div>";
    }

    // 2. Tabla de Configuración del Chatbot
    echo "<div class='info'>📋 Creando tabla <code>saas_chatbot_config</code>...</div>";
    $sql_config = "CREATE TABLE IF NOT EXISTS saas_chatbot_config (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NOT NULL,
        chatbot_name VARCHAR(100) DEFAULT 'AsistenteBot',
        welcome_message TEXT,
        primary_color VARCHAR(7) DEFAULT '#f97316',
        enable_reservations BOOLEAN DEFAULT TRUE,
        enable_delivery BOOLEAN DEFAULT TRUE,
        enable_whatsapp BOOLEAN DEFAULT FALSE,
        whatsapp_number VARCHAR(50),
        ai_provider ENUM('anthropic', 'openai') DEFAULT 'anthropic',
        api_key VARCHAR(255),
        system_prompt TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id) ON DELETE CASCADE,
        INDEX idx_tenant (tenant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql_config)) {
        echo "<div class='success'>✅ Tabla <code>saas_chatbot_config</code> creada exitosamente</div>";
    }

    // 3. Tabla de Menú (Items por Tenant)
    echo "<div class='info'>📋 Creando tabla <code>saas_menu_items</code>...</div>";
    $sql_menu = "CREATE TABLE IF NOT EXISTS saas_menu_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        category VARCHAR(100),
        price DECIMAL(10, 2) NOT NULL,
        description TEXT,
        image_url VARCHAR(500),
        available BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id) ON DELETE CASCADE,
        INDEX idx_tenant (tenant_id),
        INDEX idx_category (category),
        INDEX idx_available (available)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql_menu)) {
        echo "<div class='success'>✅ Tabla <code>saas_menu_items</code> creada exitosamente</div>";
    }

    // 4. Tabla de Conversaciones
    echo "<div class='info'>📋 Creando tabla <code>saas_conversations</code>...</div>";
    $sql_conversations = "CREATE TABLE IF NOT EXISTS saas_conversations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NOT NULL,
        session_id VARCHAR(100) NOT NULL,
        customer_phone VARCHAR(50),
        customer_name VARCHAR(255),
        status ENUM('active', 'completed', 'abandoned') DEFAULT 'active',
        order_placed BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id) ON DELETE CASCADE,
        INDEX idx_tenant (tenant_id),
        INDEX idx_session (session_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql_conversations)) {
        echo "<div class='success'>✅ Tabla <code>saas_conversations</code> creada exitosamente</div>";
    }

    // 5. Tabla de Mensajes
    echo "<div class='info'>📋 Creando tabla <code>saas_messages</code>...</div>";
    $sql_messages = "CREATE TABLE IF NOT EXISTS saas_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        conversation_id INT NOT NULL,
        role ENUM('user', 'assistant') NOT NULL,
        content TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (conversation_id) REFERENCES saas_conversations(id) ON DELETE CASCADE,
        INDEX idx_conversation (conversation_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql_messages)) {
        echo "<div class='success'>✅ Tabla <code>saas_messages</code> creada exitosamente</div>";
    }

    // 6. Insertar datos de prueba
    echo "<div class='info'>📋 Insertando datos de prueba...</div>";
    
    // Verificar si ya existe el tenant de prueba
    $check = $conn->query("SELECT id FROM saas_tenants WHERE owner_email = 'demo@restaurante.com'");
    
    if ($check->num_rows == 0) {
        // Crear tenant de prueba
        $password_hash = password_hash('demo123', PASSWORD_DEFAULT);
        $sql_demo_tenant = "INSERT INTO saas_tenants 
            (restaurant_name, owner_email, owner_password, phone, address, business_hours, plan) 
            VALUES 
            ('Restaurante Demo', 'demo@restaurante.com', '$password_hash', '+57 300 123 4567', 
             'Calle 123 #45-67, Bogotá', '9:00 AM - 10:00 PM', 'pro')";
        
        if ($conn->query($sql_demo_tenant)) {
            $tenant_id = $conn->insert_id;
            echo "<div class='success'>✅ Tenant de prueba creado (ID: $tenant_id)</div>";
            
            // Configuración del chatbot
            $sql_demo_config = "INSERT INTO saas_chatbot_config 
                (tenant_id, chatbot_name, welcome_message, primary_color, enable_reservations, enable_delivery) 
                VALUES 
                ($tenant_id, 'DemoBot', '¡Hola! 👋 Soy DemoBot, tu asistente virtual. ¿En qué puedo ayudarte?', 
                 '#f97316', TRUE, TRUE)";
            
            if ($conn->query($sql_demo_config)) {
                echo "<div class='success'>✅ Configuración del chatbot creada</div>";
            }
            
            // Menú de ejemplo
            $menu_items = [
                ['Pizza Margarita', 'Platos', 25000, 'Deliciosa pizza con tomate y mozzarella'],
                ['Hamburguesa Clásica', 'Platos', 18000, 'Carne, lechuga, tomate y queso'],
                ['Coca-Cola', 'Bebidas', 3000, 'Refresco 500ml'],
                ['Limonada Natural', 'Bebidas', 5000, 'Limonada fresca'],
                ['Brownie', 'Postres', 10000, 'Brownie con helado']
            ];
            
            foreach ($menu_items as $item) {
                $sql_item = "INSERT INTO saas_menu_items (tenant_id, name, category, price, description) 
                            VALUES ($tenant_id, '{$item[0]}', '{$item[1]}', {$item[2]}, '{$item[3]}')";
                $conn->query($sql_item);
            }
            echo "<div class='success'>✅ Menú de ejemplo creado (5 items)</div>";
        }
    } else {
        echo "<div class='info'>ℹ️ Ya existe un tenant de prueba</div>";
    }

    echo "<div class='success' style='margin-top: 30px;'>
        <h2>🎉 ¡Instalación Completada!</h2>
        <p><strong>Credenciales de Prueba:</strong></p>
        <ul>
            <li>Email: <code>demo@restaurante.com</code></li>
            <li>Password: <code>demo123</code></li>
        </ul>
        <p><strong>Próximos pasos:</strong></p>
        <ol>
            <li>Accede al panel admin: <a href='admin/dashboard.php'>admin/dashboard.php</a></li>
            <li>Configura tu API Key de Anthropic o OpenAI</li>
            <li>Personaliza el chatbot</li>
        </ol>
    </div>";

} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}

$conn->close();

echo "</body></html>";
?>
