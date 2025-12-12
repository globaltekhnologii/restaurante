<?php
require_once 'config.php';
$conn = getDatabaseConnection();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Log de Cambios</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f5f5;}";
echo "table{width:100%;border-collapse:collapse;background:white;margin:20px 0;}";
echo "th,td{padding:12px;text-align:left;border:1px solid #ddd;}";
echo "th{background:#667eea;color:white;}</style></head><body>";
echo "<h1>📋 Log de Cambios de Estado</h1>";

// Crear tabla si no existe
$conn->query("CREATE TABLE IF NOT EXISTS pedidos_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    estado_anterior VARCHAR(50),
    estado_nuevo VARCHAR(50),
    script VARCHAR(100),
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pedido (pedido_id)
)");

$result = $conn->query("SELECT l.*, p.numero_pedido 
    FROM pedidos_log l 
    LEFT JOIN pedidos p ON l.pedido_id = p.id 
    ORDER BY l.fecha DESC LIMIT 20");

if ($result && $result->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>Fecha</th><th>Pedido</th><th>Estado Anterior</th><th>Estado Nuevo</th><th>Script</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . date('d/m/Y H:i:s', strtotime($row['fecha'])) . "</td>";
        echo "<td><strong>" . ($row['numero_pedido'] ?? 'ID: ' . $row['pedido_id']) . "</strong></td>";
        echo "<td>" . $row['estado_anterior'] . "</td>";
        echo "<td><strong>" . $row['estado_nuevo'] . "</strong></td>";
        echo "<td>" . $row['script'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No hay registros en el log aún. Marca un pedido como listo para ver los cambios aquí.</p>";
}

echo "<p><a href='chef.php' style='background:#ed8936;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Panel Chef</a></p>";

$conn->close();
echo "</body></html>";
?>
