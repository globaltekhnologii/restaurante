<?php
/**
 * API Handler - Chat con IA
 * Endpoint: /ChatbotSaaS/backend/api/chat_handler.php
 */

// Headers CORS primero
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Suprimir errores de PHP para que no rompan el JSON
error_reporting(0);
ini_set('display_errors', 0);

require_once '../config.php';

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método no permitido', 405);
}

// Obtener datos del request
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['tenant_id']) || !isset($input['message']) || !isset($input['session_id'])) {
    jsonError('Faltan parámetros requeridos: tenant_id, message, session_id');
}

$tenant_id = (int)$input['tenant_id'];
$user_message = trim($input['message']);
$session_id = $input['session_id'];

// Validar tenant
$tenant_check = $conn->query("SELECT id FROM saas_tenants WHERE id = $tenant_id AND status = 'active'");
if ($tenant_check->num_rows === 0) {
    jsonError('Tenant no válido o inactivo', 404);
}

// Obtener configuración del chatbot
$config = getChatbotConfig($conn, $tenant_id);
if (!$config) {
    jsonError('Configuración del chatbot no encontrada', 404);
}

// Obtener menú
$menu_items = getMenuItems($conn, $tenant_id);

// Construir contexto del sistema
$menu_context = buildMenuContext($menu_items, $config);

// Obtener o crear conversación
$conversation = getOrCreateConversation($conn, $tenant_id, $session_id);

// Guardar mensaje del usuario
saveMessage($conn, $conversation['id'], 'user', $user_message);

// Obtener historial de mensajes
$history = getConversationHistory($conn, $conversation['id']);

// Llamar a la API de IA
try {
    $ai_response = callAIProvider($config, $menu_context, $history, $user_message);
    
    // Detectar y procesar orden
    if (preg_match('/\{"ORDER_CONFIRMATION":.*\}/s', $ai_response, $matches)) {
        $json_str = $matches[0];
        $json_data = json_decode($json_str, true);
        
        if (isset($json_data['ORDER_CONFIRMATION'])) {
            $order_data = $json_data['ORDER_CONFIRMATION'];
            $order_res = processChatbotOrder($conn, $tenant_id, $conversation['id'], $order_data);
            
            // Remover JSON de la respuesta visible
            $ai_response = str_replace($json_str, "", $ai_response);
            
            if ($order_res['success']) {
                $ai_response .= "\n\n✅ *¡Pedido #{$order_res['order_id']} confirmado!* Tu orden ha sido enviada a la cocina.";
            } else {
                $ai_response .= "\n\n❌ Hubo un error técnico registrando el pedido. Por favor llama al restaurante.";
            }
        }
    }

    // Guardar respuesta del asistente
    saveMessage($conn, $conversation['id'], 'assistant', $ai_response);
    
    // Responder
    jsonResponse([
        'success' => true,
        'message' => $ai_response,
        'session_id' => $session_id
    ]);
    
} catch (Exception $e) {
    jsonError('Error al procesar la solicitud: ' . $e->getMessage(), 500);
}

// ===== FUNCIONES AUXILIARES =====

function buildMenuContext($menu_items, $config) {
    $context = "Eres {$config['chatbot_name']}, un asistente virtual amigable de {$config['restaurant_name']}.\n\n";
    $context .= "INFORMACIÓN DEL RESTAURANTE:\n";
    $context .= "- Teléfono: {$config['phone']}\n";
    $context .= "- Dirección: {$config['address']}\n";
    $context .= "- Horario: {$config['business_hours']}\n\n";
    
    $context .= "MENÚ DISPONIBLE:\n\n";
    
    // Agrupar por categoría
    $by_category = [];
    foreach ($menu_items as $item) {
        $category = $item['category'] ?: 'Otros';
        if (!isset($by_category[$category])) {
            $by_category[$category] = [];
        }
        $by_category[$category][] = $item;
    }
    
    foreach ($by_category as $category => $items) {
        $context .= strtoupper($category) . ":\n";
        foreach ($items as $item) {
            $price = number_format($item['price'], 0, ',', '.');
            $context .= "- {$item['name']}: \${price}";
            if ($item['description']) {
                $context .= " ({$item['description']})";
            }
            $context .= "\n";
        }
        $context .= "\n";
    }
    
    $context .= "INSTRUCCIONES:\n";
    $context .= "- Ayuda al cliente a elegir del menú\n";
    $context .= "- Sé amigable, conversacional y conciso (máximo 2-3 líneas)\n";
    $context .= "- Sugiere combos o complementos cuando sea apropiado\n";
    
    if ($config['enable_delivery']) {
        $context .= "- Ofrece servicio a domicilio\n";
    }
    if ($config['enable_reservations']) {
        $context .= "- Puedes ayudar con reservaciones\n";
    }
    
    $context .= "- Si el cliente pide algo que NO está en el menú, ofrece alternativas similares\n";
    $context .= "- Al final del pedido, solicita dirección de entrega y método de pago\n";
    $context .= "\n";
    $context .= "IMPORTANTE: Cuando el cliente confirme el pedido (después de dar dirección), incluye este JSON al final:\n";
    $context .= '{\"ORDER_CONFIRMATION\": {\"customer\": {\"name\": \"Nombre\", \"phone\": \"Tel\", \"address\": \"Dir\"}, \"items\": [{\"name\": \"Plato\", \"quantity\": 1, \"price\": 10000}], \"total\": 10000}}' . "\n";
    
    return $context;
}

function getOrCreateConversation($conn, $tenant_id, $session_id) {
    // Buscar conversación existente
    $stmt = $conn->prepare("SELECT * FROM saas_conversations WHERE tenant_id = ? AND session_id = ? AND status = 'active'");
    $stmt->bind_param("is", $tenant_id, $session_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    // Crear nueva conversación
    $stmt = $conn->prepare("INSERT INTO saas_conversations (tenant_id, session_id) VALUES (?, ?)");
    $stmt->bind_param("is", $tenant_id, $session_id);
    $stmt->execute();
    
    return [
        'id' => $conn->insert_id,
        'tenant_id' => $tenant_id,
        'session_id' => $session_id,
        'status' => 'active'
    ];
}

function saveMessage($conn, $conversation_id, $role, $content) {
    $stmt = $conn->prepare("INSERT INTO saas_messages (conversation_id, role, content) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $conversation_id, $role, $content);
    return $stmt->execute();
}

function getConversationHistory($conn, $conversation_id, $limit = 10) {
    $stmt = $conn->prepare("
        SELECT role, content 
        FROM saas_messages 
        WHERE conversation_id = ? 
        ORDER BY created_at DESC 
        LIMIT ?
    ");
    $stmt->bind_param("ii", $conversation_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    
    return array_reverse($messages); // Orden cronológico
}

function callAIProvider($config, $system_prompt, $history, $user_message) {
    $provider = $config['ai_provider'] ?? 'anthropic';
    $api_key = $config['api_key'];
    
    if (!$api_key) {
        throw new Exception('API Key no configurada');
    }
    
    if ($provider === 'anthropic') {
        return callAnthropicAPI($api_key, $system_prompt, $history, $user_message);
    } elseif ($provider === 'openai') {
        return callOpenAIAPI($api_key, $system_prompt, $history, $user_message);
    }
    
    throw new Exception('Proveedor de IA no soportado');
}

function callAnthropicAPI($api_key, $system_prompt, $history, $user_message) {
    $messages = [];
    
    // Agregar historial
    foreach ($history as $msg) {
        $messages[] = [
            'role' => $msg['role'],
            'content' => $msg['content']
        ];
    }
    
    // Agregar mensaje actual
    $messages[] = [
        'role' => 'user',
        'content' => $user_message
    ];
    
    $data = [
        'model' => 'claude-sonnet-4-20250514',
        'max_tokens' => 1000,
        'system' => $system_prompt,
        'messages' => $messages
    ];
    
    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-api-key: ' . $api_key,
        'anthropic-version: 2023-06-01'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200) {
        throw new Exception('Error en API de Anthropic: ' . $response);
    }
    
    $result = json_decode($response, true);
    
    // Extraer texto de la respuesta
    $text = '';
    foreach ($result['content'] as $block) {
        if ($block['type'] === 'text') {
            $text .= $block['text'];
        }
    }
    
    return $text;
}

function callOpenAIAPI($api_key, $system_prompt, $history, $user_message) {
    $messages = [
        ['role' => 'system', 'content' => $system_prompt]
    ];
    
    // Agregar historial
    foreach ($history as $msg) {
        $messages[] = [
            'role' => $msg['role'],
            'content' => $msg['content']
        ];
    }
    
    // Agregar mensaje actual
    $messages[] = [
        'role' => 'user',
        'content' => $user_message
    ];
    
    $data = [
        'model' => 'gpt-4o-mini',
        'messages' => $messages,
        'max_tokens' => 500,
        'temperature' => 0.7
    ];
    
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200) {
        throw new Exception('Error en API de OpenAI: ' . $response);
    }
    
    $result = json_decode($response, true);
    return $result['choices'][0]['message']['content'];
}

function processChatbotOrder($conn, $tenant_id, $conversation_id, $data) {
    $customer = $data['customer'];
    $items = $data['items'];
    $total = $data['total'];
    
    // Generar número de pedido
    $numero_pedido = 'BOT-' . date('ymd') . '-' . rand(100, 999);

    // Insertar en tabla `pedidos` (BD Principal)
    $stmt = $conn->prepare("INSERT INTO pedidos (nombre_cliente, telefono, direccion, total, estado, tipo_pedido, origen, conversation_id, fecha_pedido, numero_pedido) VALUES (?, ?, ?, ?, 'pendiente', 'domicilio', 'chatbot', ?, NOW(), ?)");
    
    $stmt->bind_param("sssdis", 
        $customer['name'], 
        $customer['phone'], 
        $customer['address'], 
        $total,
        $conversation_id,
        $numero_pedido
    );
    
    if ($stmt->execute()) {
        $pedido_id = $conn->insert_id;
        
        // Insertar items
        $stmt_item = $conn->prepare("INSERT INTO pedidos_items (pedido_id, plato_nombre, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
        
        foreach ($items as $item) {
            $subtotal = $item['price'] * $item['quantity'];
            $stmt_item->bind_param("isidd", 
                $pedido_id, 
                $item['name'], 
                $item['quantity'], 
                $item['price'], 
                $subtotal
            );
            $stmt_item->execute();
        }
        
        // Marcar conversación
        $conn->query("UPDATE saas_conversations SET order_placed = 1, status = 'completed' WHERE id = $conversation_id");
        
        return ['success' => true, 'order_id' => $pedido_id];
    }
    
    return ['success' => false, 'error' => $conn->error];
}
?>
