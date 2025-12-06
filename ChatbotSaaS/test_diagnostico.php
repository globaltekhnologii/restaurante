<?php
/**
 * Test directo del API - Diagnóstico
 */

echo "<h1>Test API Chatbot</h1>";

// Test 1: Conexión a BD
echo "<h2>1. Test Conexión BD</h2>";
$conn = new mysqli("localhost", "root", "", "menu_restaurante");
if ($conn->connect_error) {
    echo "❌ Error: " . $conn->connect_error;
} else {
    echo "✅ Conexión exitosa<br>";
    
    // Test 2: Verificar tenant
    echo "<h2>2. Test Tenant</h2>";
    $result = $conn->query("SELECT * FROM saas_tenants WHERE id = 1");
    if ($result && $result->num_rows > 0) {
        $tenant = $result->fetch_assoc();
        echo "✅ Tenant encontrado: " . $tenant['restaurant_name'] . "<br>";
    } else {
        echo "❌ Tenant no encontrado<br>";
    }
    
    // Test 3: Verificar config
    echo "<h2>3. Test Configuración</h2>";
    $result = $conn->query("SELECT * FROM saas_chatbot_config WHERE tenant_id = 1");
    if ($result && $result->num_rows > 0) {
        $config = $result->fetch_assoc();
        echo "✅ Config encontrada<br>";
        echo "Chatbot Name: " . $config['chatbot_name'] . "<br>";
        echo "AI Provider: " . $config['ai_provider'] . "<br>";
        echo "API Key configurada: " . (empty($config['api_key']) ? "❌ NO" : "✅ SÍ") . "<br>";
    } else {
        echo "❌ Configuración no encontrada<br>";
    }
    
    // Test 4: Verificar menú
    echo "<h2>4. Test Menú</h2>";
    $result = $conn->query("SELECT COUNT(*) as total FROM saas_menu_items WHERE tenant_id = 1");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "✅ Items en menú: " . $row['total'] . "<br>";
    }
}

// Test 5: Simular llamada al API
echo "<h2>5. Test Llamada API</h2>";
echo "<p>Simulando POST a chat_handler.php...</p>";

$url = 'http://localhost/Restaurante/ChatbotSaaS/backend/api/chat_handler.php';
$data = json_encode([
    'tenant_id' => 1,
    'message' => 'Hola',
    'session_id' => 'test_' . time()
]);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<strong>HTTP Code:</strong> " . $http_code . "<br>";
if ($error) {
    echo "<strong>cURL Error:</strong> " . $error . "<br>";
}
echo "<strong>Response:</strong><br>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

// Intentar decodificar JSON
$json = json_decode($response, true);
if ($json) {
    echo "<strong>JSON Decodificado:</strong><br>";
    echo "<pre>" . print_r($json, true) . "</pre>";
} else {
    echo "❌ La respuesta NO es JSON válido<br>";
}
?>
