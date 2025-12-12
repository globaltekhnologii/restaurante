<?php
require_once 'config.php';
$conn = getDatabaseConnection();

echo "<h1>🔍 Auditoría de Esquema de Base de Datos</h1>";
echo "<style>pre { background: #f4f4f4; padding: 10px; border: 1px solid #ddd; }</style>";

function describeTable($conn, $table) {
    echo "<h3>Tabla: $table</h3>";
    $result = $conn->query("DESCRIBE $table");
    if ($result) {
        echo "<table border='1' cellspacing='0' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $key => $val) {
                echo "<td>" . ($val ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red'>Error al describir tabla: " . $conn->error . "</p>";
    }
}

describeTable($conn, 'pedidos');
describeTable($conn, 'pedidos_items');
describeTable($conn, 'usuarios'); // Para verificar roles

$conn->close();
?>
