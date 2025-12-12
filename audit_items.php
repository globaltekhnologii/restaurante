<?php
require_once 'config.php';
$conn = getDatabaseConnection();

echo "<h1>🔍 Auditoría de Items</h1>";

// 1. Verificar columnas de pedidos_items
$result = $conn->query("DESCRIBE pedidos_items");
$cols = [];
while ($row = $result->fetch_assoc()) {
    $cols[] = $row['Field'];
}

echo "<h3>Columnas en pedidos_items:</h3>";
echo "<ul>";
foreach ($cols as $col) {
    echo "<li>$col</li>";
}
echo "</ul>";

// Verificar conflicto nombre_plato vs plato_nombre
if (in_array('nombre_plato', $cols) && !in_array('plato_nombre', $cols)) {
    echo "<p style='color:blue'>ℹ️ La tabla usa <strong>nombre_plato</strong>.</p>";
} elseif (!in_array('nombre_plato', $cols) && in_array('plato_nombre', $cols)) {
    echo "<p style='color:blue'>ℹ️ La tabla usa <strong>plato_nombre</strong>.</p>";
} else {
    echo "<p style='color:red'>⚠️ Conflicto o faltan columnas.</p>";
}

$conn->close();
?>
