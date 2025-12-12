<?php
require_once 'config.php';
$conn = getDatabaseConnection();

$result = $conn->query("SHOW COLUMNS FROM pedidos_items");

echo "Columnas de la tabla pedidos_items:\n\n";
while ($row = $result->fetch_assoc()) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
}

$conn->close();
?>
