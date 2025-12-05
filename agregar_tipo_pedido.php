<?php
// Agregar columna tipo_pedido
require_once 'config.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Agregar tipo_pedido</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f7fa}";
echo ".success{background:#d4edda;border-left:4px solid #28a745;color:#155724;padding:15px;margin:10px 0;border-radius:5px}";
echo ".error{background:#f8d7da;border-left:4px solid #dc3545;color:#721c24;padding:15px;margin:10px 0;border-radius:5px}";
echo "</style></head><body>";

echo "<h1>Agregar Columna tipo_pedido</h1>";

$conn = getDatabaseConnection();

// Verificar si ya existe
$result = $conn->query("SHOW COLUMNS FROM pedidos LIKE 'tipo_pedido'");
if ($result->num_rows > 0) {
    echo "<div class='success'>✅ La columna 'tipo_pedido' ya existe</div>";
} else {
    // Agregar la columna
    $sql = "ALTER TABLE pedidos ADD COLUMN tipo_pedido ENUM('mesa', 'domicilio', 'para_llevar') DEFAULT 'mesa'";
    
    if ($conn->query($sql)) {
        echo "<div class='success'>✅ Columna 'tipo_pedido' agregada exitosamente</div>";
        
        // Crear índice
        $conn->query("CREATE INDEX idx_tipo_pedido ON pedidos(tipo_pedido)");
        echo "<div class='success'>✅ Índice creado</div>";
    } else {
        echo "<div class='error'>❌ Error: " . $conn->error . "</div>";
    }
}

// Modificar estado para agregar 'listo_recoger'
$sql = "ALTER TABLE pedidos MODIFY COLUMN estado ENUM('pendiente', 'confirmado', 'preparando', 'en_camino', 'listo_recoger', 'entregado', 'cancelado') DEFAULT 'pendiente'";
if ($conn->query($sql)) {
    echo "<div class='success'>✅ Estado 'listo_recoger' agregado</div>";
}

$conn->close();

echo "<hr>";
echo "<p><a href='verificar_columnas.php' style='padding:10px 20px;background:#667eea;color:white;text-decoration:none;border-radius:5px;'>Verificar Columnas</a></p>";
echo "<p><a href='index.php' style='padding:10px 20px;background:#48bb78;color:white;text-decoration:none;border-radius:5px;margin-left:10px;'>Ir al Menú</a></p>";

echo "</body></html>";
?>
