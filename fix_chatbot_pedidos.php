<?php
/**
 * Script para agregar columnas faltantes en tabla pedidos
 * Necesarias para que el chatbot pueda crear pedidos
 */

require_once 'config.php';
$conn = getDatabaseConnection();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Fix Pedidos Table</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f5f5;}";
echo ".success{color:green;padding:10px;background:#e8f5e9;border-radius:5px;margin:10px 0;}";
echo ".error{color:red;padding:10px;background:#ffebee;border-radius:5px;margin:10px 0;}";
echo ".info{color:#1976d2;padding:10px;background:#e3f2fd;border-radius:5px;margin:10px 0;}";
echo "</style></head><body>";
echo "<h1>🔧 Reparación de Tabla Pedidos para Chatbot</h1>";

try {
    // 1. Verificar si existe la columna tipo_pedido
    echo "<h2>1️⃣ Verificando columna 'tipo_pedido'</h2>";
    $result = $conn->query("SHOW COLUMNS FROM pedidos LIKE 'tipo_pedido'");
    
    if ($result->num_rows == 0) {
        echo "<div class='info'>Agregando columna tipo_pedido...</div>";
        $sql = "ALTER TABLE pedidos ADD COLUMN tipo_pedido ENUM('local', 'domicilio', 'para_llevar') DEFAULT 'local' AFTER estado";
        if ($conn->query($sql)) {
            echo "<div class='success'>✅ Columna tipo_pedido agregada</div>";
        }
    } else {
        echo "<div class='success'>✅ Columna tipo_pedido ya existe</div>";
    }
    
    // 2. Verificar si existe la columna origen
    echo "<h2>2️⃣ Verificando columna 'origen'</h2>";
    $result = $conn->query("SHOW COLUMNS FROM pedidos LIKE 'origen'");
    
    if ($result->num_rows == 0) {
        echo "<div class='info'>Agregando columna origen...</div>";
        $sql = "ALTER TABLE pedidos ADD COLUMN origen VARCHAR(50) DEFAULT 'manual' AFTER tipo_pedido";
        if ($conn->query($sql)) {
            echo "<div class='success'>✅ Columna origen agregada</div>";
        }
    } else {
        echo "<div class='success'>✅ Columna origen ya existe</div>";
    }
    
    // 3. Verificar si existe la columna conversation_id
    echo "<h2>3️⃣ Verificando columna 'conversation_id'</h2>";
    $result = $conn->query("SHOW COLUMNS FROM pedidos LIKE 'conversation_id'");
    
    if ($result->num_rows == 0) {
        echo "<div class='info'>Agregando columna conversation_id...</div>";
        $sql = "ALTER TABLE pedidos ADD COLUMN conversation_id INT NULL AFTER origen";
        if ($conn->query($sql)) {
            echo "<div class='success'>✅ Columna conversation_id agregada</div>";
        }
    } else {
        echo "<div class='success'>✅ Columna conversation_id ya existe</div>";
    }
    
    // 4. Verificar columnas en pedidos_items
    echo "<h2>4️⃣ Verificando tabla pedidos_items</h2>";
    
    // Verificar si usa plato_nombre o nombre_plato
    $result = $conn->query("SHOW COLUMNS FROM pedidos_items LIKE 'plato_nombre'");
    if ($result->num_rows == 0) {
        // Verificar si existe nombre_plato
        $result2 = $conn->query("SHOW COLUMNS FROM pedidos_items LIKE 'nombre_plato'");
        if ($result2->num_rows > 0) {
            echo "<div class='info'>Renombrando nombre_plato a plato_nombre...</div>";
            $sql = "ALTER TABLE pedidos_items CHANGE nombre_plato plato_nombre VARCHAR(100) NOT NULL";
            if ($conn->query($sql)) {
                echo "<div class='success'>✅ Columna renombrada a plato_nombre</div>";
            }
        } else {
            echo "<div class='info'>Agregando columna plato_nombre...</div>";
            $sql = "ALTER TABLE pedidos_items ADD COLUMN plato_nombre VARCHAR(100) NOT NULL AFTER plato_id";
            if ($conn->query($sql)) {
                echo "<div class='success'>✅ Columna plato_nombre agregada</div>";
            }
        }
    } else {
        echo "<div class='success'>✅ Columna plato_nombre ya existe</div>";
    }
    
    // Verificar precio_unitario
    $result = $conn->query("SHOW COLUMNS FROM pedidos_items LIKE 'precio_unitario'");
    if ($result->num_rows == 0) {
        // Verificar si existe precio
        $result2 = $conn->query("SHOW COLUMNS FROM pedidos_items LIKE 'precio'");
        if ($result2->num_rows > 0) {
            echo "<div class='info'>Renombrando precio a precio_unitario...</div>";
            $sql = "ALTER TABLE pedidos_items CHANGE precio precio_unitario DECIMAL(10,2) NOT NULL";
            if ($conn->query($sql)) {
                echo "<div class='success'>✅ Columna renombrada a precio_unitario</div>";
            }
        }
    } else {
        echo "<div class='success'>✅ Columna precio_unitario ya existe</div>";
    }
    
    echo "<div class='success' style='margin-top:30px;'>";
    echo "<h2>🎉 ¡Reparación Completada!</h2>";
    echo "<p>La tabla pedidos ahora está lista para recibir órdenes del chatbot.</p>";
    echo "<p><a href='../../ChatbotSaaS/demo/test_landing.html' style='background:#4caf50;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>🤖 Probar Chatbot</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}

$conn->close();
echo "</body></html>";
?>
