<?php
require_once 'config.php';
$conn = getDatabaseConnection();

echo "<h1>🔍 Diagnóstico General de Últimos Pedidos</h1>";
echo "<style>table { border-collapse: collapse; width: 100%; font-family: sans-serif; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } th { background: #f2f2f2; } .match { background-color: #d4edda; } .no-match { background-color: #fff; }</style>";

// 1. Mostrar quién soy
session_start();
$mi_id = $_SESSION['user_id'] ?? 0;
echo "<h3>👤 Usuario Actual: ID " . $mi_id . "</h3>";

// 2. Mostrar TODOS los últimos pedidos sin filtro
echo "<h3>📦 Últimos 10 Pedidos (Cualquier Tipo)</h3>";
$sql_all = "SELECT id, numero_pedido, tipo_pedido, estado, domiciliario_id, fecha_pedido, total 
            FROM pedidos 
            ORDER BY id DESC LIMIT 10";
$result_all = $conn->query($sql_all);

echo "<table>";
echo "<tr><th>ID</th><th>Número</th><th>Tipo</th><th>Estado</th><th>Domiciliario</th><th>Fecha</th></tr>";

while ($row = $result_all->fetch_assoc()) {
    $class = ($row['tipo_pedido'] == 'domicilio') ? 'match' : 'no-match';
    echo "<tr class='$class'>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td><strong>" . $row['numero_pedido'] . "</strong></td>";
    echo "<td>" . $row['tipo_pedido'] . "</td>";
    echo "<td>" . $row['estado'] . "</td>";
    echo "<td>" . ($row['domiciliario_id'] ?? 'NULL') . "</td>";
    echo "<td>" . $row['fecha_pedido'] . "</td>";
    echo "</tr>";
}
echo "</table>";

$conn->close();
?>
