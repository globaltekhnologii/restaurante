<?php
// agregar_columna_imagen.php - Agregar columna imagen a tabla platos
require_once 'config.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Agregar Columna Imagen</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f7fa}";
echo ".success{background:#d4edda;border-left:4px solid #28a745;color:#155724;padding:15px;margin:10px 0;border-radius:5px}";
echo ".error{background:#f8d7da;border-left:4px solid #dc3545;color:#721c24;padding:15px;margin:10px 0;border-radius:5px}";
echo ".info{background:#d1ecf1;border-left:4px solid #17a2b8;color:#0c5460;padding:15px;margin:10px 0;border-radius:5px}";
echo "</style></head><body>";

echo "<h1>🔧 Agregar Columna 'imagen' a Tabla Platos</h1>";

$conn = getDatabaseConnection();

// Verificar si la columna ya existe
echo "<h2>1. Verificando estructura actual...</h2>";
$result = $conn->query("SHOW COLUMNS FROM platos LIKE 'imagen'");

if ($result->num_rows > 0) {
    echo "<div class='info'>ℹ️ La columna 'imagen' ya existe en la tabla platos</div>";
} else {
    echo "<div class='info'>📋 La columna 'imagen' NO existe. Procediendo a agregarla...</div>";
    
    // Agregar la columna
    echo "<h2>2. Agregando columna...</h2>";
    $sql = "ALTER TABLE platos ADD COLUMN imagen VARCHAR(255) DEFAULT NULL AFTER descripcion";
    
    if ($conn->query($sql) === TRUE) {
        echo "<div class='success'>✅ Columna 'imagen' agregada exitosamente</div>";
    } else {
        echo "<div class='error'>❌ Error al agregar columna: " . $conn->error . "</div>";
    }
}

// Mostrar estructura actual
echo "<h2>3. Estructura actual de la tabla platos:</h2>";
$result = $conn->query("DESCRIBE platos");

echo "<table style='width:100%;border-collapse:collapse;background:white'>";
echo "<tr style='background:#667eea;color:white'>";
echo "<th style='padding:10px;border:1px solid #ddd'>Campo</th>";
echo "<th style='padding:10px;border:1px solid #ddd'>Tipo</th>";
echo "<th style='padding:10px;border:1px solid #ddd'>Null</th>";
echo "<th style='padding:10px;border:1px solid #ddd'>Default</th>";
echo "</tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td style='padding:10px;border:1px solid #ddd'><strong>" . $row['Field'] . "</strong></td>";
    echo "<td style='padding:10px;border:1px solid #ddd'>" . $row['Type'] . "</td>";
    echo "<td style='padding:10px;border:1px solid #ddd'>" . $row['Null'] . "</td>";
    echo "<td style='padding:10px;border:1px solid #ddd'>" . ($row['Default'] ?? 'NULL') . "</td>";
    echo "</tr>";
}

echo "</table>";

$conn->close();

echo "<hr>";
echo "<h2>✅ Proceso Completado</h2>";
echo "<p><a href='tomar_pedido_mesero.php?mesa_id=1' style='padding:10px 20px;background:#48bb78;color:white;text-decoration:none;border-radius:5px;'>Probar Tomar Pedido</a></p>";
echo "<p><a href='admin.php' style='padding:10px 20px;background:#667eea;color:white;text-decoration:none;border-radius:5px;margin-left:10px;'>Ir al Panel Admin</a></p>";

echo "</body></html>";
?>
