<?php
require_once 'config.php';
$conn = getDatabaseConnection();

echo "<h1>🧪 Creando Pedido de Prueba</h1>";

try {
    $conn->begin_transaction();

    // 1. Crear Pedido
    $numero_pedido = 'TEST-' . date('Hi');
    
    $stmt = $conn->prepare("INSERT INTO pedidos (numero_pedido, tipo_pedido, estado, nombre_cliente, telefono, direccion, total, fecha_pedido) VALUES (?, 'domicilio', 'pendiente', 'Usuario Prueba', '555-1234', 'Calle Falsa 123', 15000, NOW())");
    $stmt->bind_param("s", $numero_pedido);
    
    if ($stmt->execute()) {
        $pedido_id = $conn->insert_id;
        echo "✅ Pedido creado con ID: " . $pedido_id . "<br>";
        
        // 2. Insertar Item
        // Asumiendo que existe un plato con ID 1
        $stmt_item = $conn->prepare("INSERT INTO pedidos_items (pedido_id, plato_id, plato_nombre, precio_unitario, cantidad, subtotal) VALUES (?, 1, 'Plato Prueba', 15000, 1, 15000)");
        $stmt_item->bind_param("i", $pedido_id);
        $stmt_item->execute();
        
        echo "✅ Item agregado al pedido.<br>";
        
        $conn->commit();
        echo "<h3>🎉 ÉXITO: Pedido registrado correctamente.</h3>";
        echo "<p>Ahora verifica el debug_domiciliario.php para ver si aparece.</p>";
        
    } else {
        throw new Exception("Error al insertar: " . $stmt->error);
    }
    
} catch (Exception $e) {
    $conn->rollback();
    echo "❌ ERROR: " . $e->getMessage();
}

$conn->close();
?>
