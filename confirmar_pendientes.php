<?php
require_once 'config.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Confirmar Pedidos Pendientes</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f7fa}";
echo ".success{background:#d4edda;border-left:4px solid #28a745;color:#155724;padding:15px;margin:10px 0;border-radius:5px}";
echo ".info{background:#d1ecf1;border-left:4px solid #17a2b8;color:#0c5460;padding:15px;margin:10px 0;border-radius:5px}";
echo "</style></head><body>";

echo "<h1>✅ Confirmando Pedidos Pendientes</h1>";

$conn = getDatabaseConnection();

// Obtener pedidos pendientes
$result = $conn->query("SELECT id, numero_pedido, nombre_cliente FROM pedidos WHERE estado = 'pendiente'");

if ($result->num_rows > 0) {
    echo "<div class='info'>📋 Pedidos pendientes encontrados: " . $result->num_rows . "</div>";
    
    $confirmados = 0;
    while ($row = $result->fetch_assoc()) {
        $stmt = $conn->prepare("UPDATE pedidos SET estado = 'confirmado' WHERE id = ?");
        $stmt->bind_param("i", $row['id']);
        
        if ($stmt->execute()) {
            echo "<div class='success'>✅ Pedido <strong>" . $row['numero_pedido'] . "</strong> (Cliente: " . htmlspecialchars($row['nombre_cliente']) . ") confirmado exitosamente</div>";
            $confirmados++;
        }
    }
    
    echo "<hr>";
    echo "<h2>🎉 ¡Listo!</h2>";
    echo "<p><strong>$confirmados pedido(s) confirmado(s)</strong></p>";
    echo "<p>Ahora TODOS los chefs (incluyendo el nuevo chef 'emmanuel') verán estos pedidos en su panel.</p>";
    
} else {
    echo "<div class='info'>ℹ️ No hay pedidos pendientes para confirmar</div>";
}

$conn->close();

echo "<hr>";
echo "<p><a href='chef.php' style='padding:10px 20px;background:#667eea;color:white;text-decoration:none;border-radius:5px;'>🍳 Ir al Panel de Chef</a></p>";
echo "<p><a href='ver_todos_pedidos.php' style='padding:10px 20px;background:#48bb78;color:white;text-decoration:none;border-radius:5px;margin-left:10px;'>📋 Ver Todos los Pedidos</a></p>";

echo "</body></html>";
?>
