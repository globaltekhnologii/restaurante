<?php
require_once 'config.php';
$conn = getDatabaseConnection();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Fix Tipo Pedido</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f5f5;}";
echo ".success{background:#d4edda;border-left:4px solid #28a745;color:#155724;padding:15px;margin:10px 0;border-radius:5px}";
echo ".info{background:#d1ecf1;border-left:4px solid #17a2b8;color:#0c5460;padding:15px;margin:10px 0;border-radius:5px}";
echo "</style></head><body>";
echo "<h1>🔧 Corrigiendo tipo_pedido</h1>";

// Actualizar pedidos con dirección pero sin tipo_pedido correcto
$sql = "UPDATE pedidos SET tipo_pedido = 'domicilio' WHERE (tipo_pedido IS NULL OR tipo_pedido = '') AND direccion IS NOT NULL AND direccion != ''";
$result = $conn->query($sql);

if ($result) {
    $affected = $conn->affected_rows;
    echo "<div class='success'>✅ Se actualizaron $affected pedidos a tipo_pedido = 'domicilio'</div>";
} else {
    echo "<div class='error'>❌ Error: " . $conn->error . "</div>";
}

// Verificar pedidos listos ahora
$check = $conn->query("SELECT COUNT(*) as count FROM pedidos WHERE domiciliario_id IS NULL AND estado = 'listo' AND tipo_pedido = 'domicilio'");
$count = $check->fetch_assoc()['count'];

echo "<div class='info'>📊 Ahora hay $count pedidos 'listo' disponibles para domiciliarios</div>";

echo "<p><a href='domiciliario.php' style='background:#667eea;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Ir al Panel de Domiciliarios</a></p>";
echo "<p><a href='diagnostico_pedidos_listo.php' style='background:#6c757d;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin-left:10px;'>Ver Diagnóstico</a></p>";

$conn->close();
echo "</body></html>";
?>
