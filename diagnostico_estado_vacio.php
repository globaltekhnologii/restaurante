<?php
require_once 'config.php';
$conn = getDatabaseConnection();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Diagnóstico Estado Vacío</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f5f5;}";
echo "table{width:100%;border-collapse:collapse;background:white;margin:20px 0;}";
echo "th,td{padding:12px;text-align:left;border:1px solid #ddd;}";
echo "th{background:#dc3545;color:white;}</style></head><body>";
echo "<h1>🔍 Diagnóstico: Estados Vacíos</h1>";

// Verificar pedidos con estado NULL o vacío
$result = $conn->query("SELECT id, numero_pedido, nombre_cliente, estado, tipo_pedido, fecha_pedido 
    FROM pedidos 
    WHERE estado IS NULL OR estado = '' 
    ORDER BY fecha_pedido DESC 
    LIMIT 10");

echo "<h2>❌ Pedidos con Estado NULL o Vacío</h2>";
if ($result->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>Número</th><th>Cliente</th><th>Estado (DB)</th><th>Tipo</th><th>Fecha</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td><strong>" . htmlspecialchars($row['numero_pedido']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($row['nombre_cliente']) . "</td>";
        echo "<td><strong style='color:red;'>" . ($row['estado'] === '' ? 'VACÍO' : 'NULL') . "</strong></td>";
        echo "<td>" . ($row['tipo_pedido'] ?? 'NULL') . "</td>";
        echo "<td>" . date('d/m/Y H:i', strtotime($row['fecha_pedido'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div style='background:#fff3cd;padding:15px;margin:20px 0;border-left:4px solid #ffc107;'>";
    echo "<h3>⚠️ PROBLEMA IDENTIFICADO</h3>";
    echo "<p>Hay pedidos con estado NULL o vacío. Esto explica por qué no aparecen en ningún panel.</p>";
    echo "<p><strong>Causa probable:</strong> Algún UPDATE está sobrescribiendo el estado con un valor vacío.</p>";
    echo "</div>";
    
    echo "<h3>🔧 Solución Inmediata</h3>";
    echo "<p>Voy a corregir estos pedidos asignándoles el estado 'listo':</p>";
    echo "<form method='POST'>";
    echo "<button type='submit' name='fix' style='background:#28a745;color:white;padding:15px 30px;border:none;border-radius:5px;cursor:pointer;font-size:16px;'>Corregir Estados Vacíos</button>";
    echo "</form>";
    
} else {
    echo "<p style='color:green;'>✅ No hay pedidos con estado vacío</p>";
}

// Si se envió el formulario
if (isset($_POST['fix'])) {
    $update = $conn->query("UPDATE pedidos SET estado = 'listo' WHERE (estado IS NULL OR estado = '') AND tipo_pedido = 'domicilio'");
    $affected = $conn->affected_rows;
    echo "<div style='background:#d4edda;padding:15px;margin:20px 0;border-left:4px solid #28a745;'>";
    echo "<h3>✅ Corrección Aplicada</h3>";
    echo "<p>Se actualizaron <strong>$affected pedidos</strong> a estado 'listo'</p>";
    echo "<p><a href='domiciliario.php' style='background:#667eea;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Ver Panel Domiciliarios</a></p>";
    echo "</div>";
}

// Verificar estructura de la columna estado
echo "<h2>📋 Estructura de la columna 'estado'</h2>";
$structure = $conn->query("SHOW COLUMNS FROM pedidos LIKE 'estado'");
$col = $structure->fetch_assoc();
echo "<pre>";
print_r($col);
echo "</pre>";

$conn->close();
echo "</body></html>";
?>
