<?php
require_once 'config.php';
$conn = getDatabaseConnection();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Log de Cambios</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f5f5;}";
echo ".log{background:white;padding:15px;margin:10px 0;border-left:4px solid #667eea;border-radius:5px;}";
echo ".error{border-left-color:#dc3545;background:#fff5f5;}</style></head><body>";
echo "<h1>📝 Creando Sistema de Log para Cambios de Estado</h1>";

// Crear tabla de log si no existe
$sql = "CREATE TABLE IF NOT EXISTS pedidos_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    estado_anterior VARCHAR(50),
    estado_nuevo VARCHAR(50),
    script VARCHAR(100),
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pedido (pedido_id)
)";

if ($conn->query($sql)) {
    echo "<div class='log'>✅ Tabla pedidos_log creada/verificada</div>";
} else {
    echo "<div class='error'>❌ Error: " . $conn->error . "</div>";
}

// Ahora vamos a modificar marcar_listo.php para que registre el cambio
echo "<div class='log'>";
echo "<h3>📋 Próximos pasos:</h3>";
echo "<ol>";
echo "<li>La tabla de log está lista</li>";
echo "<li>Voy a actualizar marcar_listo.php para registrar cambios</li>";
echo "<li>Luego podrás ver exactamente qué está cambiando el estado</li>";
echo "</ol>";
echo "</div>";

echo "<p><a href='ver_log_pedidos.php' style='background:#667eea;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Ver Log de Cambios</a></p>";

$conn->close();
echo "</body></html>";
?>
