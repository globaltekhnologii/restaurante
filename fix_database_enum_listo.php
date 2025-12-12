<?php
require_once 'config.php';
$conn = getDatabaseConnection();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Fix Database ENUM</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f5f5;}";
echo ".success{background:#d4edda;border-left:4px solid #28a745;color:#155724;padding:15px;margin:10px 0;border-radius:5px}";
echo ".error{background:#f8d7da;border-left:4px solid #dc3545;color:#721c24;padding:15px;margin:10px 0;border-radius:5px}";
echo ".info{background:#d1ecf1;border-left:4px solid #17a2b8;color:#0c5460;padding:15px;margin:10px 0;border-radius:5px}";
echo "</style></head><body>";
echo "<h1>🔧 Reparando ENUM de Estado en Base de Datos</h1>";

try {
    // Modificar el ENUM para incluir 'listo' y remover 'listo_recoger'
    $sql = "ALTER TABLE pedidos MODIFY COLUMN estado ENUM('pendiente','confirmado','preparando','listo','en_camino','entregado','cancelado') DEFAULT 'pendiente'";
    
    if ($conn->query($sql)) {
        echo "<div class='success'>";
        echo "<h2>✅ ENUM Actualizado Exitosamente</h2>";
        echo "<p>La columna 'estado' ahora incluye el valor 'listo'</p>";
        echo "</div>";
        
        // Verificar la nueva estructura
        $check = $conn->query("SHOW COLUMNS FROM pedidos LIKE 'estado'");
        $col = $check->fetch_assoc();
        echo "<div class='info'>";
        echo "<h3>📋 Nueva Estructura:</h3>";
        echo "<pre>" . $col['Type'] . "</pre>";
        echo "</div>";
        
        // Ahora corregir los pedidos con estado vacío
        echo "<h2>🔄 Corrigiendo Pedidos con Estado Vacío</h2>";
        $update = $conn->query("UPDATE pedidos SET estado = 'listo' WHERE (estado IS NULL OR estado = '') AND tipo_pedido = 'domicilio'");
        $affected = $conn->affected_rows;
        
        echo "<div class='success'>";
        echo "<h3>✅ Pedidos Corregidos: $affected</h3>";
        echo "</div>";
        
        // Mostrar pedidos corregidos
        $result = $conn->query("SELECT id, numero_pedido, estado FROM pedidos WHERE estado = 'listo' ORDER BY fecha_pedido DESC LIMIT 5");
        if ($result->num_rows > 0) {
            echo "<h3>Últimos pedidos en estado 'listo':</h3>";
            echo "<ul>";
            while ($row = $result->fetch_assoc()) {
                echo "<li><strong>" . $row['numero_pedido'] . "</strong> - Estado: " . $row['estado'] . "</li>";
            }
            echo "</ul>";
        }
        
    } else {
        echo "<div class='error'>❌ Error: " . $conn->error . "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Excepción: " . $e->getMessage() . "</div>";
}

echo "<hr>";
echo "<h2>✅ Siguiente Paso</h2>";
echo "<p><a href='domiciliario.php' style='background:#667eea;color:white;padding:15px 30px;text-decoration:none;border-radius:5px;font-size:18px;'>Ver Panel de Domiciliarios</a></p>";
echo "<p style='margin-top:20px;'><a href='chef.php' style='background:#ed8936;color:white;padding:15px 30px;text-decoration:none;border-radius:5px;font-size:18px;'>Ver Panel de Chef</a></p>";

$conn->close();
echo "</body></html>";
?>
