<?php
// Script para verificar que los campos se agregaron correctamente
require_once 'config.php';

echo "<h2>🔍 Verificación de Campos en la Tabla Pedidos</h2>";

try {
    $conn = getDatabaseConnection();
    
    // Obtener estructura de la tabla
    $result = $conn->query("DESCRIBE pedidos");
    
    echo "<h3>Campos actuales en la tabla 'pedidos':</h3>";
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse; font-family: monospace;'>";
    echo "<tr style='background: #667eea; color: white;'>";
    echo "<th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    $campos_necesarios = ['tipo_documento', 'numero_documento', 'ciudad_entrega'];
    $campos_encontrados = [];
    
    while ($row = $result->fetch_assoc()) {
        $highlight = in_array($row['Field'], $campos_necesarios) ? "style='background: #d4edda; font-weight: bold;'" : "";
        
        if (in_array($row['Field'], $campos_necesarios)) {
            $campos_encontrados[] = $row['Field'];
        }
        
        echo "<tr $highlight>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar si faltan campos
    $campos_faltantes = array_diff($campos_necesarios, $campos_encontrados);
    
    echo "<br><h3>Estado de los Campos Necesarios:</h3>";
    echo "<ul style='font-size: 1.1em;'>";
    
    foreach ($campos_necesarios as $campo) {
        if (in_array($campo, $campos_encontrados)) {
            echo "<li style='color: green;'>✅ <strong>$campo</strong> - Existe</li>";
        } else {
            echo "<li style='color: red;'>❌ <strong>$campo</strong> - NO EXISTE (necesita migración)</li>";
        }
    }
    echo "</ul>";
    
    if (empty($campos_faltantes)) {
        echo "<br><div style='background: #d4edda; padding: 20px; border-left: 5px solid #28a745; margin: 20px 0;'>";
        echo "<h3 style='color: #155724; margin: 0;'>✅ ¡Todo Correcto!</h3>";
        echo "<p style='color: #155724;'>Todos los campos necesarios están presentes. El autocompletado debería funcionar.</p>";
        echo "</div>";
        
        echo "<h3>📝 Cómo Probar el Autocompletado:</h3>";
        echo "<ol style='line-height: 1.8;'>";
        echo "<li>Ve al <a href='index.php' style='color: #667eea; font-weight: bold;'>Menú</a> y agrega productos</li>";
        echo "<li>Ve al <a href='checkout.php' style='color: #667eea; font-weight: bold;'>Checkout</a></li>";
        echo "<li><strong>Escribe un teléfono</strong> (ej: 3177731338)</li>";
        echo "<li><strong>Espera 1-2 segundos</strong> o haz clic fuera del campo</li>";
        echo "<li>Si es un cliente que ya hizo un pedido, verás sus datos autocompletarse</li>";
        echo "<li>Si es nuevo, verás el mensaje: 'Cliente nuevo, completa tus datos'</li>";
        echo "</ol>";
    } else {
        echo "<br><div style='background: #f8d7da; padding: 20px; border-left: 5px solid #dc3545; margin: 20px 0;'>";
        echo "<h3 style='color: #721c24; margin: 0;'>⚠️ Faltan Campos</h3>";
        echo "<p style='color: #721c24;'>Necesitas ejecutar la migración para agregar los campos faltantes.</p>";
        echo "<p><a href='setup_facturacion_electronica.php' style='display: inline-block; padding: 10px 20px; background: #dc3545; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;'>▶ Ejecutar Migración</a></p>";
        echo "</div>";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
