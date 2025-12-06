<?php
require_once 'config.php';
$conn = getDatabaseConnection();

$numero_pedido = 'TEST-' . date('Hi');
$stmt = $conn->prepare("INSERT INTO pedidos (nombre_cliente, telefono, direccion, total, estado, tipo_pedido, origen, conversation_id, fecha_pedido, pagado, numero_pedido) VALUES (?, ?, ?, ?, 'pendiente', 'domicilio', 'chatbot', 999, NOW(), 0, ?)");

$nombre = "Test Chatbot User";
$tel = "1234567890";
$dir = "Calle Test 123";
$total = 25000;

$stmt->bind_param("sssds", $nombre, $tel, $dir, $total, $numero_pedido);

if ($stmt->execute()) {
    echo "✅ Pedido de prueba insertado correctamente. ID: " . $conn->insert_id . "\n";
    echo "Ahora revisa el panel de cajero para ver si aparece como '🤖 Chatbot'.";
} else {
    echo "❌ Error insertando: " . $stmt->error;
}
$conn->close();
?>
