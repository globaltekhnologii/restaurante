<?php
require_once 'config.php';
$conn = getDatabaseConnection();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Historial Pedidos</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f5f5;}";
echo "table{width:100%;border-collapse:collapse;background:white;margin:20px 0;}";
echo "th,td{padding:12px;text-align:left;border:1px solid #ddd;}";
echo "th{background:#667eea;color:white;}";
echo ".en_camino{background:#cce5ff;} .entregado{background:#d4edda;}</style></head><body>";
echo "<h1>📋 Últimos 10 Pedidos (Todos los Estados)</h1>";

$result = $conn->query("SELECT id, numero_pedido, nombre_cliente, estado, tipo_pedido, domiciliario_id, fecha_pedido FROM pedidos ORDER BY fecha_pedido DESC LIMIT 10");

if ($result->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>Número</th><th>Cliente</th><th>Estado</th><th>Tipo</th><th>Domiciliario</th><th>Fecha</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $class = '';
        if ($row['estado'] == 'en_camino') $class = 'en_camino';
        if ($row['estado'] == 'entregado') $class = 'entregado';
        
        echo "<tr class='$class'>";
        echo "<td><strong>" . htmlspecialchars($row['numero_pedido']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($row['nombre_cliente']) . "</td>";
        echo "<td><strong>" . $row['estado'] . "</strong></td>";
        echo "<td>" . ($row['tipo_pedido'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['domiciliario_id'] ?? 'Sin asignar') . "</td>";
        echo "<td>" . date('d/m/Y H:i', strtotime($row['fecha_pedido'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h2>📊 Resumen por Estado</h2>";
$estados = $conn->query("SELECT estado, COUNT(*) as count FROM pedidos GROUP BY estado");
echo "<ul>";
while ($row = $estados->fetch_assoc()) {
    echo "<li><strong>" . $row['estado'] . ":</strong> " . $row['count'] . " pedidos</li>";
}
echo "</ul>";

echo "<p><a href='chef.php' style='background:#ed8936;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Panel Chef</a> ";
echo "<a href='domiciliario.php' style='background:#667eea;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin-left:10px;'>Panel Domiciliario</a></p>";

$conn->close();
echo "</body></html>";
?>
