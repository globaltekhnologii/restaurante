<?php
require_once 'config.php';
$conn = getDatabaseConnection();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Diagnóstico Pedidos</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f5f5;}";
echo "table{width:100%;border-collapse:collapse;background:white;margin:20px 0;}";
echo "th,td{padding:12px;text-align:left;border:1px solid #ddd;}";
echo "th{background:#667eea;color:white;}";
echo ".listo{background:#d4edda;}</style></head><body>";
echo "<h1>🔍 Diagnóstico de Pedidos Listos</h1>";

// Pedidos con estado 'listo'
echo "<h2>Pedidos con estado 'listo'</h2>";
$result = $conn->query("SELECT id, numero_pedido, nombre_cliente, estado, tipo_pedido, domiciliario_id, direccion FROM pedidos WHERE estado = 'listo' ORDER BY fecha_pedido DESC LIMIT 10");

if ($result->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>Número</th><th>Cliente</th><th>Estado</th><th>Tipo Pedido</th><th>Domiciliario ID</th><th>Dirección</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr class='listo'>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['numero_pedido']) . "</td>";
        echo "<td>" . htmlspecialchars($row['nombre_cliente']) . "</td>";
        echo "<td>" . $row['estado'] . "</td>";
        echo "<td><strong>" . ($row['tipo_pedido'] ?? 'NULL') . "</strong></td>";
        echo "<td>" . ($row['domiciliario_id'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars(substr($row['direccion'] ?? '', 0, 50)) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No hay pedidos con estado 'listo'</p>";
}

// Condiciones para aparecer en "Disponibles"
echo "<h2>✅ Condiciones para aparecer en 'Disponibles'</h2>";
echo "<ul>";
echo "<li>estado = 'listo'</li>";
echo "<li>domiciliario_id IS NULL</li>";
echo "<li>tipo_pedido = 'domicilio'</li>";
echo "</ul>";

// Pedidos que cumplen las condiciones
echo "<h2>Pedidos que deberían aparecer en 'Disponibles'</h2>";
$result2 = $conn->query("SELECT id, numero_pedido, nombre_cliente FROM pedidos WHERE domiciliario_id IS NULL AND estado = 'listo' AND tipo_pedido = 'domicilio' ORDER BY fecha_pedido DESC");

if ($result2->num_rows > 0) {
    echo "<p style='color:green;font-weight:bold;'>✅ Encontrados " . $result2->num_rows . " pedidos que cumplen las condiciones:</p>";
    echo "<ul>";
    while ($row = $result2->fetch_assoc()) {
        echo "<li>" . htmlspecialchars($row['numero_pedido']) . " - " . htmlspecialchars($row['nombre_cliente']) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color:red;font-weight:bold;'>❌ No hay pedidos que cumplan TODAS las condiciones</p>";
    echo "<p>Esto significa que los pedidos 'listo' probablemente tienen tipo_pedido NULL o diferente a 'domicilio'</p>";
}

// Solución sugerida
echo "<h2>🔧 Solución</h2>";
echo "<p>Si los pedidos tienen tipo_pedido NULL o incorrecto, ejecuta este script:</p>";
echo "<a href='fix_tipo_pedido.php' style='background:#667eea;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Corregir tipo_pedido</a>";

$conn->close();
echo "</body></html>";
?>
