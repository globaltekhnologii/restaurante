<?php
require_once 'config.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Diagnóstico del Sistema</title>";
echo "<style>
body{font-family:Arial;padding:20px;background:#f5f7fa}
.success{background:#d4edda;border-left:4px solid #28a745;color:#155724;padding:15px;margin:10px 0;border-radius:5px}
.error{background:#f8d7da;border-left:4px solid #dc3545;color:#721c24;padding:15px;margin:10px 0;border-radius:5px}
.info{background:#d1ecf1;border-left:4px solid #17a2b8;color:#0c5460;padding:15px;margin:10px 0;border-radius:5px}
h2{color:#333;margin-top:30px}
table{width:100%;border-collapse:collapse;background:white;margin:10px 0}
th,td{padding:10px;border:1px solid #ddd;text-align:left}
th{background:#667eea;color:white}
</style></head><body>";

echo "<h1>🔍 Diagnóstico Completo del Sistema</h1>";

$conn = getDatabaseConnection();

// 1. Verificar últimos pedidos
echo "<h2>1. Últimos Pedidos Creados</h2>";
$result = $conn->query("SELECT id, numero_pedido, nombre_cliente, estado, fecha_pedido, tipo_pedido FROM pedidos ORDER BY fecha_pedido DESC LIMIT 5");

if ($result->num_rows > 0) {
    echo "<table><tr><th>ID</th><th>Número</th><th>Cliente</th><th>Estado</th><th>Tipo</th><th>Fecha</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $tipo = $row['tipo_pedido'] ?? 'N/A';
        echo "<tr><td>{$row['id']}</td><td>{$row['numero_pedido']}</td><td>{$row['nombre_cliente']}</td>";
        echo "<td><strong>{$row['estado']}</strong></td><td>{$tipo}</td><td>{$row['fecha_pedido']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<div class='error'>❌ No hay pedidos en el sistema</div>";
}

// 2. Verificar pedidos que el chef debería ver
echo "<h2>2. Pedidos que el Chef Debería Ver</h2>";
$result = $conn->query("SELECT COUNT(*) as count FROM pedidos WHERE estado IN ('confirmado', 'preparando')");
$row = $result->fetch_assoc();
$count = $row['count'];

if ($count > 0) {
    echo "<div class='success'>✅ Hay {$count} pedido(s) en estado 'confirmado' o 'preparando'</div>";
    
    $result = $conn->query("SELECT id, numero_pedido, nombre_cliente, estado FROM pedidos WHERE estado IN ('confirmado', 'preparando') ORDER BY fecha_pedido DESC");
    echo "<table><tr><th>ID</th><th>Número</th><th>Cliente</th><th>Estado</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['numero_pedido']}</td><td>{$row['nombre_cliente']}</td><td>{$row['estado']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<div class='error'>❌ No hay pedidos en estado 'confirmado' o 'preparando'</div>";
}

// 3. Verificar usuarios chef activos
echo "<h2>3. Usuarios Chef Activos</h2>";
$result = $conn->query("SELECT id, nombre, usuario, activo FROM usuarios WHERE rol = 'chef'");

if ($result->num_rows > 0) {
    echo "<table><tr><th>ID</th><th>Nombre</th><th>Usuario</th><th>Activo</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $activo = $row['activo'] ? '✅ Sí' : '❌ No';
        echo "<tr><td>{$row['id']}</td><td>{$row['nombre']}</td><td>{$row['usuario']}</td><td>{$activo}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<div class='error'>❌ No hay usuarios con rol 'chef'</div>";
}

// 4. Verificar estructura de tabla pedidos
echo "<h2>4. Estructura de Tabla 'pedidos'</h2>";
$result = $conn->query("SHOW COLUMNS FROM pedidos");
echo "<table><tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Default</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Default']}</td></tr>";
}
echo "</table>";

// 5. Verificar query del chef
echo "<h2>5. Simulación de Query del Chef</h2>";
$sql = "SELECT p.*, 
        GROUP_CONCAT(CONCAT(pi.cantidad, 'x ', pi.plato_nombre) SEPARATOR ', ') as items
        FROM pedidos p
        LEFT JOIN pedidos_items pi ON p.id = pi.pedido_id
        WHERE p.estado IN ('confirmado', 'preparando')
        GROUP BY p.id
        ORDER BY p.fecha_pedido ASC";

$result = $conn->query($sql);
if ($result->num_rows > 0) {
    echo "<div class='success'>✅ Query del chef retorna {$result->num_rows} pedido(s)</div>";
    echo "<table><tr><th>Número</th><th>Cliente</th><th>Items</th><th>Estado</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['numero_pedido']}</td><td>{$row['nombre_cliente']}</td><td>{$row['items']}</td><td>{$row['estado']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<div class='error'>❌ Query del chef no retorna resultados</div>";
}

// 6. Verificar archivo procesar_pedido.php
echo "<h2>6. Verificar procesar_pedido.php</h2>";
$file_content = file_get_contents('procesar_pedido.php');
if (strpos($file_content, "'confirmado'") !== false) {
    echo "<div class='success'>✅ procesar_pedido.php crea pedidos en estado 'confirmado'</div>";
} else if (strpos($file_content, "'pendiente'") !== false) {
    echo "<div class='error'>❌ procesar_pedido.php crea pedidos en estado 'pendiente'</div>";
} else {
    echo "<div class='info'>⚠️ No se pudo determinar el estado inicial</div>";
}

$conn->close();

echo "<hr>";
echo "<h2>📋 Acciones Sugeridas</h2>";
echo "<div class='info'>";
echo "<p><strong>Para probar el flujo completo:</strong></p>";
echo "<ol>";
echo "<li><a href='index.php' target='_blank'>Ir al Menú</a> - Agrega productos al carrito</li>";
echo "<li><a href='carrito.php' target='_blank'>Ver Carrito</a> - Procede al checkout</li>";
echo "<li><a href='chef.php' target='_blank'>Panel del Chef</a> - Verifica que aparezca el pedido</li>";
echo "<li><a href='admin_pedidos.php' target='_blank'>Panel Admin</a> - Gestión de pedidos</li>";
echo "</ol>";
echo "</div>";

echo "</body></html>";
?>
