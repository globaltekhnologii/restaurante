<?php
require_once 'config.php';
$conn = getDatabaseConnection();

$pedido_id = 43; // El ID de mi pedido de prueba

echo "<h1>👨‍🍳 Simulando Chef: Marcar Pedido $pedido_id como Listo</h1>";

// 1. Verificar estado actual
$res = $conn->query("SELECT estado, numero_pedido FROM pedidos WHERE id = $pedido_id");
$row = $res->fetch_assoc();
echo "Estado actual: <strong>" . $row['estado'] . "</strong><br>";

// 2. Actualizar a 'listo'
$stmt = $conn->prepare("UPDATE pedidos SET estado = 'listo' WHERE id = ?");
$stmt->bind_param("i", $pedido_id);

if ($stmt->execute()) {
    echo "✅ Pedido actualizado a 'listo'.<br>";
    
    // 3. Verificar nuevo estado
    $res = $conn->query("SELECT estado FROM pedidos WHERE id = $pedido_id");
    echo "Nuevo estado: <strong>" . $res->fetch_assoc()['estado'] . "</strong><br>";
    
    echo "<h3>🚀 Ahora revisa debug_domiciliario.php</h3>";
    echo "<p>Debería aparecer como 'Sí (Listo y sin asignar)'</p>";
} else {
    echo "❌ Error al actualizar: " . $stmt->error;
}

$conn->close();
?>
