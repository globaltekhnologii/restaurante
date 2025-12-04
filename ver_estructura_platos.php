<?php
// ver_estructura_platos.php - Ver estructura real de la tabla platos
require_once 'config.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Estructura Platos</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f7fa}";
echo "table{width:100%;border-collapse:collapse;background:white;margin:20px 0}";
echo "th,td{padding:10px;border:1px solid #ddd;text-align:left}";
echo "th{background:#667eea;color:white}";
echo "code{background:#f0f0f0;padding:2px 6px;border-radius:3px}";
echo "</style></head><body>";

echo "<h1>📊 Estructura de la Tabla Platos</h1>";

$conn = getDatabaseConnection();

// Mostrar todas las columnas
echo "<h2>Columnas de la tabla:</h2>";
$result = $conn->query("DESCRIBE platos");

echo "<table>";
echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td><strong>" . $row['Field'] . "</strong></td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
    echo "<td>" . $row['Extra'] . "</td>";
    echo "</tr>";
}

echo "</table>";

// Mostrar un plato de ejemplo con TODOS sus datos
echo "<h2>Ejemplo de un plato (todos los campos):</h2>";
$result = $conn->query("SELECT * FROM platos LIMIT 1");

if ($result->num_rows > 0) {
    $plato = $result->fetch_assoc();
    
    echo "<table>";
    echo "<tr><th>Campo</th><th>Valor</th></tr>";
    
    foreach ($plato as $campo => $valor) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($campo) . "</strong></td>";
        echo "<td>" . (is_null($valor) ? '<em>NULL</em>' : '<code>' . htmlspecialchars($valor) . '</code>') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No hay platos en la base de datos</p>";
}

// Contar platos
$count = $conn->query("SELECT COUNT(*) as total FROM platos")->fetch_assoc()['total'];
echo "<p><strong>Total de platos:</strong> $count</p>";

$conn->close();

echo "</body></html>";
?>
