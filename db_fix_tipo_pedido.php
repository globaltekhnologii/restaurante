<?php
require_once 'config.php';
$conn = getDatabaseConnection();

echo "<h1>🛠️ Corrección de Base de Datos</h1>";

// 1. Agregar columna tipo_pedido
echo "<h3>Agregando columna 'tipo_pedido' a la tabla 'pedidos'...</h3>";
try {
    // Verificar si ya existe (aunque la auditoría dijo que no)
    $result = $conn->query("SHOW COLUMNS FROM pedidos LIKE 'tipo_pedido'");
    
    if ($result->num_rows == 0) {
        // Agregar columna
        $sql = "ALTER TABLE pedidos ADD COLUMN tipo_pedido ENUM('mesa', 'domicilio', 'para_llevar') DEFAULT 'mesa' AFTER fecha_pedido";
        
        if ($conn->query($sql)) {
            echo "<p style='color:green'>✅ Columna 'tipo_pedido' agregada correctamente.</p>";
            
            // Actualizar pedidos existentes para inferir tipo
            // Si tiene mesa_id > 0 es 'mesa', si no es 'domicilio'
            $conn->query("UPDATE pedidos SET tipo_pedido = 'domicilio' WHERE mesa_id IS NULL OR mesa_id = 0");
            $conn->query("UPDATE pedidos SET tipo_pedido = 'mesa' WHERE mesa_id > 0");
            echo "<p style='color:blue'>ℹ️ Pedidos antiguos actualizados.</p>";
            
        } else {
            throw new Exception("Error al agregar columna: " . $conn->error);
        }
    } else {
        echo "<p style='color:orange'>⚠️ La columna ya existe.</p>";
        
        // Verificar el tipo
        $row = $result->fetch_assoc();
        echo "Tipo actual: " . $row['Type'] . "<br>";
    }
    
    // 2. Verificar estructura final
    echo "<h3>Estructura Final:</h3>";
    $final = $conn->query("DESCRIBE pedidos");
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Default</th></tr>";
    while ($row = $final->fetch_assoc()) {
        $style = ($row['Field'] == 'tipo_pedido') ? "background: #d4edda; font-weight: bold;" : "";
        echo "<tr style='$style'><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Default']}</td></tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error Fatal: " . $e->getMessage() . "</p>";
}

$conn->close();
?>
