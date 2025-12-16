<?php
// Script de migración para Bold Payment Gateway
require_once 'config.php';

echo "<h2>🔧 Migración: Tabla de Pagos Bold</h2>";

try {
    $conn = getDatabaseConnection();
    
    // Verificar si la tabla ya existe
    $result = $conn->query("SHOW TABLES LIKE 'pagos_bold'");
    
    if ($result->num_rows > 0) {
        echo "<p style='color: orange;'>⚠️ La tabla 'pagos_bold' ya existe.</p>";
    } else {
        echo "<p>Creando tabla 'pagos_bold'...</p>";
        
        $sql = "CREATE TABLE pagos_bold (
            id INT AUTO_INCREMENT PRIMARY KEY,
            pedido_id INT NOT NULL,
            bold_transaction_id VARCHAR(100),
            bold_order_id VARCHAR(100),
            monto DECIMAL(10,2),
            estado ENUM('pendiente', 'aprobado', 'rechazado', 'cancelado', 'reembolsado') DEFAULT 'pendiente',
            metodo_pago VARCHAR(50),
            datos_tarjeta VARCHAR(100),
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            datos_bold TEXT,
            FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
            INDEX idx_transaction (bold_transaction_id),
            INDEX idx_pedido (pedido_id),
            INDEX idx_estado (estado)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        if ($conn->query($sql)) {
            echo "<p style='color: green;'>✅ Tabla 'pagos_bold' creada exitosamente!</p>";
        } else {
            throw new Exception("Error al crear tabla: " . $conn->error);
        }
    }
    
    // Mostrar estructura de la tabla
    echo "<h3>📋 Estructura de la tabla pagos_bold:</h3>";
    $result = $conn->query("DESCRIBE pagos_bold");
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse; font-family: monospace;'>";
    echo "<tr style='background: #667eea; color: white;'>";
    echo "<th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br><h3>✅ Migración completada</h3>";
    echo "<p>La tabla está lista para almacenar transacciones de Bold.</p>";
    echo "<br><a href='admin.php' style='padding: 10px 20px; background: #51cf66; color: white; text-decoration: none; border-radius: 5px;'>Panel Admin</a>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
