<?php
/**
 * CREAR TABLA RESPALDOS
 */

require_once 'config.php';
$conn = getDatabaseConnection();

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Crear Tabla Respaldos</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #3b82f6; padding-bottom: 10px; }
        .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0; border-radius: 5px; color: #155724; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 20px 0; border-radius: 5px; color: #721c24; }
        .info { background: #d1ecf1; border-left: 4px solid #0c5460; padding: 15px; margin: 20px 0; border-radius: 5px; }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>🔧 Crear Tabla Respaldos</h1>";

// Verificar si la tabla ya existe
$check_table = $conn->query("SHOW TABLES LIKE 'respaldos'");

if ($check_table->num_rows > 0) {
    echo "<div class='info'>✅ La tabla 'respaldos' ya existe</div>";
} else {
    echo "<div class='info'>⏳ Creando tabla 'respaldos'...</div>";
    
    $sql = "CREATE TABLE respaldos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NOT NULL,
        nombre_archivo VARCHAR(255) NOT NULL,
        ruta_archivo VARCHAR(500) NOT NULL,
        tamano_mb DECIMAL(10,2),
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        descripcion TEXT,
        INDEX idx_tenant_id (tenant_id),
        FOREIGN KEY (tenant_id) REFERENCES saas_tenants(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($sql)) {
        echo "<div class='success'>✅ Tabla 'respaldos' creada exitosamente</div>";
    } else {
        echo "<div class='error'>❌ Error: " . $conn->error . "</div>";
    }
}

// Mostrar estructura
echo "<h2>📊 Estructura de la Tabla</h2>";
$result = $conn->query("DESCRIBE respaldos");

if ($result) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Default</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td><strong>{$row['Field']}</strong></td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

$conn->close();

echo "<div style='margin-top: 30px;'>";
echo "<a href='admin_respaldos.php' style='display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px;'>→ Ir a Respaldos</a>";
echo "</div>";

echo "</div></body></html>";
?>
