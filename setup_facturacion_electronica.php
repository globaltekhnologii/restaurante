<?php
// Script de migración para agregar campos de facturación electrónica
require_once 'config.php';

echo "<h2>🔧 Migración: Campos de Facturación Electrónica</h2>";

try {
    $conn = getDatabaseConnection();
    
    // Verificar si los campos ya existen
    $result = $conn->query("SHOW COLUMNS FROM pedidos LIKE 'tipo_documento'");
    
    if ($result->num_rows > 0) {
        echo "<p style='color: orange;'>⚠️ Los campos ya existen en la tabla pedidos.</p>";
    } else {
        echo "<p>Agregando campos a la tabla <strong>pedidos</strong>...</p>";
        
        // Agregar campos para documento de identidad y ciudad
        $sql = "ALTER TABLE pedidos 
                ADD COLUMN tipo_documento VARCHAR(10) NULL AFTER telefono,
                ADD COLUMN numero_documento VARCHAR(50) NULL AFTER tipo_documento,
                ADD COLUMN ciudad_entrega VARCHAR(100) NULL AFTER direccion";
        
        if ($conn->query($sql)) {
            echo "<p style='color: green;'>✅ Campos agregados exitosamente:</p>";
            echo "<ul>";
            echo "<li><strong>tipo_documento</strong> VARCHAR(10) - Tipo de documento (CC, TI, CE, PEP, Pasaporte, NIT)</li>";
            echo "<li><strong>numero_documento</strong> VARCHAR(50) - Número de documento</li>";
            echo "<li><strong>ciudad_entrega</strong> VARCHAR(100) - Ciudad de entrega</li>";
            echo "</ul>";
        } else {
            throw new Exception("Error al agregar campos: " . $conn->error);
        }
    }
    
    // Verificar estructura final
    echo "<h3>📋 Estructura actual de la tabla pedidos:</h3>";
    $result = $conn->query("DESCRIBE pedidos");
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Default</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        $highlight = ($row['Field'] == 'tipo_documento' || $row['Field'] == 'numero_documento') 
                     ? "style='background: #d4edda;'" : "";
        echo "<tr $highlight>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br><h3>✅ Migración completada exitosamente</h3>";
    echo "<p>Ahora puedes proceder con la implementación del autocompletado.</p>";
    echo "<br><a href='checkout.php' style='padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;'>Ir al Checkout</a>";
    echo " <a href='admin.php' style='padding: 10px 20px; background: #51cf66; color: white; text-decoration: none; border-radius: 5px;'>Panel Admin</a>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
