<?php
/**
 * Script para crear un tenant específico para el restaurante principal
 * y sincronizar el menú desde la tabla 'platos' a 'saas_menu_items'
 */

// Conexión a BD
$conn = new mysqli("localhost", "root", "", "menu_restaurante");

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

echo "<h1>🔄 Sincronización de Restaurante Principal</h1>";
echo "<style>body { font-family: Arial; padding: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; }</style>";

// 1. Obtener información del restaurante desde config.php
require_once '../config.php';

$nombre_restaurante = defined('NOMBRE_RESTAURANTE') ? NOMBRE_RESTAURANTE : 'Mi Restaurante';
$email_owner = "admin@" . strtolower(str_replace(' ', '', $nombre_restaurante)) . ".com";

echo "<h2>📋 Paso 1: Crear Tenant</h2>";

// 2. Verificar si ya existe un tenant para este restaurante
$stmt = $conn->prepare("SELECT id FROM saas_tenants WHERE restaurant_name = ?");
$stmt->bind_param("s", $nombre_restaurante);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $tenant = $result->fetch_assoc();
    $tenant_id = $tenant['id'];
    echo "<p class='info'>✓ Tenant ya existe (ID: $tenant_id)</p>";
} else {
    // Crear nuevo tenant
    $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO saas_tenants (restaurant_name, owner_email, owner_password, status) VALUES (?, ?, ?, 'active')");
    $stmt->bind_param("sss", $nombre_restaurante, $email_owner, $password_hash);
    
    if ($stmt->execute()) {
        $tenant_id = $conn->insert_id;
        echo "<p class='success'>✓ Tenant creado exitosamente (ID: $tenant_id)</p>";
        echo "<p><strong>Email:</strong> $email_owner<br><strong>Password:</strong> admin123</p>";
    } else {
        die("<p class='error'>❌ Error al crear tenant: " . $stmt->error . "</p>");
    }
}

// 3. Crear/Actualizar configuración del chatbot
echo "<h2>⚙️ Paso 2: Configurar Chatbot</h2>";

$stmt = $conn->prepare("SELECT id FROM saas_chatbot_config WHERE tenant_id = ?");
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$result = $stmt->get_result();

$chatbot_name = $nombre_restaurante . " Bot";
$welcome_message = "¡Hola! 👋 Bienvenido a $nombre_restaurante. ¿En qué puedo ayudarte hoy?";
$primary_color = "#667eea";
$ai_provider = "openai"; // o "anthropic"
$api_key = ""; // Se configurará después

if ($result->num_rows > 0) {
    // Actualizar
    $stmt = $conn->prepare("UPDATE saas_chatbot_config SET chatbot_name = ?, welcome_message = ?, primary_color = ? WHERE tenant_id = ?");
    $stmt->bind_param("sssi", $chatbot_name, $welcome_message, $primary_color, $tenant_id);
    $stmt->execute();
    echo "<p class='info'>✓ Configuración actualizada</p>";
} else {
    // Crear
    $stmt = $conn->prepare("INSERT INTO saas_chatbot_config (tenant_id, chatbot_name, welcome_message, primary_color, ai_provider, api_key, enable_delivery, enable_reservations) VALUES (?, ?, ?, ?, ?, ?, 1, 1)");
    $stmt->bind_param("isssss", $tenant_id, $chatbot_name, $welcome_message, $primary_color, $ai_provider, $api_key);
    $stmt->execute();
    echo "<p class='success'>✓ Configuración creada</p>";
}

// 4. Sincronizar menú desde 'platos' a 'saas_menu_items'
echo "<h2>🍽️ Paso 3: Sincronizar Menú</h2>";

// Limpiar menú anterior de este tenant
$stmt = $conn->prepare("DELETE FROM saas_menu_items WHERE tenant_id = ?");
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
echo "<p class='info'>✓ Menú anterior limpiado</p>";

// Obtener platos
$result = $conn->query("SELECT nombre, descripcion, precio, categoria, imagen_ruta FROM platos ORDER BY categoria, nombre");

$count = 0;
$stmt = $conn->prepare("INSERT INTO saas_menu_items (tenant_id, name, category, price, description, image_url, available) VALUES (?, ?, ?, ?, ?, ?, 1)");

while ($plato = $result->fetch_assoc()) {
    $categoria = $plato['categoria'] ?? 'General';
    $imagen = $plato['imagen_ruta'] ?? '';
    
    $stmt->bind_param("issdss", 
        $tenant_id,
        $plato['nombre'],
        $categoria,
        $plato['precio'],
        $plato['descripcion'],
        $imagen
    );
    
    if ($stmt->execute()) {
        $count++;
    }
}

echo "<p class='success'>✓ $count platos sincronizados</p>";

// 5. Resumen
echo "<h2>✅ Resumen</h2>";
echo "<ul>";
echo "<li><strong>Tenant ID:</strong> $tenant_id</li>";
echo "<li><strong>Restaurante:</strong> $nombre_restaurante</li>";
echo "<li><strong>Email Admin:</strong> $email_owner</li>";
echo "<li><strong>Password:</strong> admin123</li>";
echo "<li><strong>Platos Sincronizados:</strong> $count</li>";
echo "</ul>";

echo "<h2>🔗 Próximos Pasos</h2>";
echo "<ol>";
echo "<li>Configura la API Key en el <a href='/Restaurante/ChatbotSaaS/admin/login.php'>Panel Admin</a></li>";
echo "<li>El widget se integrará automáticamente en tu sitio</li>";
echo "</ol>";

$conn->close();
?>
