<?php
require_once 'config.php';
$conn = getDatabaseConnection();

$tables = [];
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_row()) {
    $tables[] = $row[0];
}

echo "Tablas encontradas:\n";
foreach ($tables as $table) {
    echo "- $table\n";
    $cols = $conn->query("DESCRIBE $table");
    while ($col = $cols->fetch_assoc()) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
}
$conn->close();
?>
