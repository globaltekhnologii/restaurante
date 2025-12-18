<?php
// Script directo para agregar el campo ciudad_entrega
require_once 'config.php';

echo "<h2>🔧 Agregando campo ciudad_entrega</h2>";

try {
    $conn = getDatabaseConnection();
    
    // Verificar si el campo ya existe
    $result = $conn->query("SHOW COLUMNS FROM pedidos LIKE 'ciudad_entrega'");
    
    if ($result->num_rows > 0) {
        echo "<p style='color: orange;'>⚠️ El campo 'ciudad_entrega' ya existe.</p>";
    } else {
        echo "<p>Agregando campo 'ciudad_entrega' a la tabla pedidos...</p>";
        
        $sql = "ALTER TABLE pedidos ADD COLUMN ciudad_entrega VARCHAR(100) NULL AFTER direccion";
        
        if ($conn->query($sql)) {
            echo "<p style='color: green;'>✅ Campo 'ciudad_entrega' agregado exitosamente!</p>";
        } else {
            throw new Exception("Error: " . $conn->error);
        }
    }
    
    // Verificar todos los campos necesarios
    echo "<h3>Verificando campos necesarios:</h3>";
    $campos = ['tipo_documento', 'numero_documento', 'ciudad_entrega'];
    
    foreach ($campos as $campo) {
        $result = $conn->query("SHOW COLUMNS FROM pedidos LIKE '$campo'");
        if ($result->num_rows > 0) {
            echo "<p style='color: green;'>✅ $campo - Existe</p>";
        } else {
            echo "<p style='color: red;'>❌ $campo - NO existe</p>";
        }
    }
    
    echo "<br><h3>✅ Listo para usar</h3>";
    echo "<p><a href='checkout.php' style='padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;'>Ir al Checkout</a></p>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
