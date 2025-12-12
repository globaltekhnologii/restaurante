<?php
require_once 'config.php';
$conn = getDatabaseConnection();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Estructura Tabla</title>";
echo "<style>body{font-family:Arial;padding:20px;}table{border-collapse:collapse;width:100%;}";
echo "th,td{border:1px solid #ddd;padding:8px;text-align:left;}th{background:#667eea;color:white;}</style></head><body>";
echo "<h1>Estructura de pedidos_items</h1>";

$result = $conn->query("DESCRIBE pedidos_items");
echo "<table><tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td><strong>" . $row['Field'] . "</strong></td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";
$conn->close();
echo "</body></html>";
?>
