<?php
require_once 'config.php';
$conn = getDatabaseConnection();

echo "Table: usuarios\n";
$result = $conn->query("DESCRIBE usuarios");
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . "\n";
}

echo "\nTable: pedidos\n";
$result = $conn->query("DESCRIBE pedidos");
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
$conn->close();
?>
