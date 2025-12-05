<?php
// Script de prueba para cambiar estado directamente
require_once 'config.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Prueba de Cambio de Estado</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f7fa}";
echo ".success{background:#d4edda;border-left:4px solid #28a745;color:#155724;padding:15px;margin:10px 0;border-radius:5px}";
echo ".error{background:#f8d7da;border-left:4px solid #dc3545;color:#721c24;padding:15px;margin:10px 0;border-radius:5px}";
echo ".info{background:#d1ecf1;border-left:4px solid #17a2b8;color:#0c5460;padding:15px;margin:10px 0;border-radius:5px}";
echo "</style></head><body>";

echo "<h1>🧪 Prueba de Cambio de Estado a Entregado</h1>";

$conn = getDatabaseConnection();

// Buscar un pedido en estado "en_camino"
$result = $conn->query("SELECT id, numero_pedido, estado, hora_entrega FROM pedidos WHERE estado = 'en_camino' LIMIT 1");

if ($result->num_rows > 0) {
    $pedido = $result->fetch_assoc();
    
    echo "<div class='info'>";
    echo "<strong>Pedido encontrado:</strong><br>";
    echo "ID: " . $pedido['id'] . "<br>";
    echo "Número: " . $pedido['numero_pedido'] . "<br>";
    echo "Estado actual: " . $pedido['estado'] . "<br>";
    echo "Hora entrega actual: " . ($pedido['hora_entrega'] ?? 'NULL') . "<br>";
    echo "</div>";
    
    if (isset($_GET['confirmar'])) {
        // Intentar cambiar a entregado
        $stmt = $conn->prepare("UPDATE pedidos SET estado = 'entregado', hora_entrega = NOW(), fecha_actualizacion = NOW() WHERE id = ?");
        $stmt->bind_param("i", $pedido['id']);
        
        if ($stmt->execute()) {
            echo "<div class='success'>✅ Estado cambiado exitosamente a 'entregado'</div>";
            
            // Verificar el cambio
            $result2 = $conn->query("SELECT estado, hora_entrega FROM pedidos WHERE id = " . $pedido['id']);
            $pedido_actualizado = $result2->fetch_assoc();
            
            echo "<div class='info'>";
            echo "<strong>Estado después del cambio:</strong><br>";
            echo "Estado: " . $pedido_actualizado['estado'] . "<br>";
            echo "Hora entrega: " . $pedido_actualizado['hora_entrega'] . "<br>";
            echo "</div>";
            
            echo "<p><a href='admin_pedidos.php' style='padding:10px 20px;background:#667eea;color:white;text-decoration:none;border-radius:5px;'>Ver en Admin Pedidos</a></p>";
        } else {
            echo "<div class='error'>❌ Error al cambiar estado: " . $stmt->error . "</div>";
        }
    } else {
        echo "<p><a href='?confirmar=1' style='padding:15px 30px;background:#28a745;color:white;text-decoration:none;border-radius:5px;font-size:1.2em;'>✅ Cambiar a Entregado</a></p>";
    }
    
} else {
    echo "<div class='info'>ℹ️ No hay pedidos en estado 'en_camino' para probar</div>";
    echo "<p>Crea un pedido y márcalo como 'en_camino' primero.</p>";
}

$conn->close();

echo "<hr>";
echo "<p><a href='admin_pedidos.php' style='padding:10px 20px;background:#667eea;color:white;text-decoration:none;border-radius:5px;'>Volver a Admin Pedidos</a></p>";
echo "<p><a href='ver_todos_pedidos.php' style='padding:10px 20px;background:#48bb78;color:white;text-decoration:none;border-radius:5px;margin-left:10px;'>Ver Todos los Pedidos</a></p>";

echo "</body></html>";
?>
