<?php
require_once 'config.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Todos los Pedidos</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f7fa}";
echo "table{width:100%;border-collapse:collapse;background:white;margin:20px 0}";
echo "th,td{padding:12px;border:1px solid #ddd;text-align:left}";
echo "th{background:#667eea;color:white}";
echo ".pendiente{background:#fff3cd}";
echo ".confirmado{background:#d1ecf1}";
echo ".preparando{background:#fff3e0}";
echo ".en_camino{background:#e3f2fd}";
echo ".entregado{background:#d4edda}";
echo ".cancelado{background:#f8d7da}";
echo "</style></head><body>";

echo "<h1>📋 TODOS los Pedidos en el Sistema</h1>";

$conn = getDatabaseConnection();

$result = $conn->query("SELECT id, numero_pedido, estado, nombre_cliente, total, fecha_pedido FROM pedidos ORDER BY fecha_pedido DESC LIMIT 20");

if ($result->num_rows > 0) {
    echo "<p>Total de pedidos (últimos 20): <strong>" . $result->num_rows . "</strong></p>";
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Número</th><th>Cliente</th><th>Estado</th><th>Total</th><th>Fecha</th><th>Acción</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        $estado_class = strtolower(str_replace('_', '', $row['estado']));
        
        echo "<tr class='$estado_class'>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td><strong>" . $row['numero_pedido'] . "</strong></td>";
        echo "<td>" . htmlspecialchars($row['nombre_cliente']) . "</td>";
        echo "<td><strong>" . ucfirst(str_replace('_', ' ', $row['estado'])) . "</strong></td>";
        echo "<td>$" . number_format($row['total'], 2) . "</td>";
        echo "<td>" . date('d/m/Y H:i', strtotime($row['fecha_pedido'])) . "</td>";
        
        // Botón para cambiar estado
        if ($row['estado'] == 'pendiente') {
            echo "<td><a href='?confirmar=" . $row['id'] . "' style='padding:5px 10px;background:#28a745;color:white;text-decoration:none;border-radius:3px;'>Confirmar</a></td>";
        } else {
            echo "<td>-</td>";
        }
        
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red'>❌ NO hay pedidos en el sistema</p>";
}

// Procesar confirmación
if (isset($_GET['confirmar'])) {
    $pedido_id = intval($_GET['confirmar']);
    $stmt = $conn->prepare("UPDATE pedidos SET estado = 'confirmado' WHERE id = ?");
    $stmt->bind_param("i", $pedido_id);
    if ($stmt->execute()) {
        echo "<script>alert('Pedido confirmado exitosamente'); window.location.href='ver_todos_pedidos.php';</script>";
    }
}

echo "<hr>";
echo "<h2>📊 Resumen por Estado</h2>";

$estados = ['pendiente', 'confirmado', 'preparando', 'en_camino', 'entregado', 'cancelado'];
echo "<table>";
echo "<tr><th>Estado</th><th>Cantidad</th></tr>";

foreach ($estados as $estado) {
    $result = $conn->query("SELECT COUNT(*) as count FROM pedidos WHERE estado = '$estado'");
    $count = $result->fetch_assoc()['count'];
    echo "<tr class='$estado'>";
    echo "<td><strong>" . ucfirst(str_replace('_', ' ', $estado)) . "</strong></td>";
    echo "<td>$count</td>";
    echo "</tr>";
}
echo "</table>";

$conn->close();

echo "<hr>";
echo "<p><strong>IMPORTANTE:</strong> El panel del chef solo muestra pedidos en estado <strong>'confirmado'</strong> o <strong>'preparando'</strong>.</p>";
echo "<p>Si tus pedidos están en estado 'pendiente', usa el botón 'Confirmar' arriba para que aparezcan en el panel del chef.</p>";

echo "<p><a href='chef.php' style='padding:10px 20px;background:#667eea;color:white;text-decoration:none;border-radius:5px;'>Ir al Panel de Chef</a></p>";

echo "</body></html>";
?>
