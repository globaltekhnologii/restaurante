<?php
// ChatbotSaaS/backend/api/create_order.php

// Asegurar que solo se pueda llamar internamente o con autenticación
// Por ahora, asumimos que se llama vía include/curl desde chat_handler con los datos ya validados
// O lo recibimos por POST

require_once '../../backend/config.php';

// Si se recibe JSON por POST
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    die(json_encode(['error' => 'No data received']));
}

$tenant_id = $input['tenant_id'];
$conversation_id = $input['conversation_id'];
$customer = $input['customer']; // {name, phone, address}
$items = $input['items']; // [{name, quantity, price}, ...]
$total = $input['total'];

// 1. Conectar a la BD del Restaurante (Sistema Principal)
// Asumimos que la BD es 'menu_restaurante' según config.php
$conn = getDBConnection();

// 2. Insertar en tabla `pedidos`
$stmt = $conn->prepare("INSERT INTO pedidos (cliente_nombre, cliente_telefono, cliente_direccion, total, estado, tipo_pedido, origen, conversation_id, fecha_pedido) VALUES (?, ?, ?, ?, 'pendiente', 'domicilio', 'chatbot', ?, NOW())");

$stmt->bind_param("sssdsi", 
    $customer['name'], 
    $customer['phone'], 
    $customer['address'], 
    $total,
    $conversation_id
);

if ($stmt->execute()) {
    $pedido_id = $conn->insert_id;
    
    // 3. Insertar items en `pedidos_items`
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
    
    // 4. Actualizar conversación (marcar como orden realizada)
    $stmt_conv = $conn->prepare("UPDATE saas_conversations SET order_placed = 1, status = 'completed' WHERE id = ?");
    $stmt_conv->bind_param("i", $conversation_id);
    $stmt_conv->execute();
    
    echo json_encode(['success' => true, 'order_id' => $pedido_id]);
    
} else {
    echo json_encode(['error' => 'Error creating order: ' . $conn->error]);
}

$conn->close();
?>
