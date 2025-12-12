<?php
require_once 'config.php';
$conn = getDatabaseConnection();

echo "<h2>Verificación de Pedidos</h2>";

// Ver todos los pedidos recientes
$result = $conn->query("SELECT id, numero_pedido, nombre_cliente, tipo_pedido, estado, domiciliario_id, fecha_pedido FROM pedidos ORDER BY id DESC LIMIT 10");

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>ID</th><th>Número</th><th>Cliente</th><th>Tipo Pedido</th><th>Estado</th><th>Domiciliario ID</th><th>Fecha</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['numero_pedido'] . "</td>";
    echo "<td>" . $row['nombre_cliente'] . "</td>";
    echo "<td><strong>" . ($row['tipo_pedido'] ?? 'NULL') . "</strong></td>";
    echo "<td>" . $row['estado'] . "</td>";
    echo "<td>" . ($row['domiciliario_id'] ?? 'NULL') . "</td>";
    echo "<td>" . $row['fecha_pedido'] . "</td>";
    echo "</tr>";
}

echo "</table>";

$conn->close();
?>
