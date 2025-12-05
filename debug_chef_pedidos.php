<?php
require_once 'config.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Diagnóstico Chef - Pedidos</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f7fa}";
echo "table{width:100%;border-collapse:collapse;background:white;margin:20px 0}";
echo "th,td{padding:12px;border:1px solid #ddd;text-align:left}";
echo "th{background:#667eea;color:white}";
echo ".problema{background:#ffe6e6;color:#d32f2f;font-weight:bold}";
echo ".ok{background:#e6ffe6;color:#2e7d32;font-weight:bold}";
echo "</style></head><body>";

echo "<h1>🔍 Diagnóstico: ¿Por qué el chef no ve los pedidos?</h1>";

$conn = getDatabaseConnection();

// 1. Verificar pedidos confirmados
echo "<h2>1️⃣ Pedidos en Estado 'confirmado' o 'preparando'</h2>";
$result = $conn->query("SELECT id, numero_pedido, estado, fecha_pedido, nombre_cliente FROM pedidos WHERE estado IN ('confirmado', 'preparando') ORDER BY fecha_pedido DESC");

if ($result->num_rows > 0) {
    echo "<p class='ok'>✅ Hay " . $result->num_rows . " pedido(s) que los chefs DEBERÍAN ver</p>";
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Número</th><th>Estado</th><th>Cliente</th><th>Fecha</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td><strong>" . $row['numero_pedido'] . "</strong></td>";
        echo "<td>" . ucfirst($row['estado']) . "</td>";
        echo "<td>" . htmlspecialchars($row['nombre_cliente']) . "</td>";
        echo "<td>" . date('d/m/Y H:i', strtotime($row['fecha_pedido'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='problema'>❌ NO hay pedidos en estado 'confirmado' o 'preparando'</p>";
    echo "<p>El panel del chef solo muestra pedidos en estos estados.</p>";
}

// 2. Verificar consulta SQL del chef.php
echo "<hr><h2>2️⃣ Simulación de Consulta del Panel Chef</h2>";
$sql = "SELECT p.*, m.numero_mesa, u.nombre as mesero_nombre 
        FROM pedidos p 
        LEFT JOIN mesas m ON p.mesa_id = m.id 
        LEFT JOIN usuarios u ON p.usuario_id = u.id 
        WHERE p.estado IN ('confirmado', 'preparando') 
        ORDER BY p.fecha_pedido ASC";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<p class='ok'>✅ La consulta SQL devuelve " . $result->num_rows . " pedido(s)</p>";
    echo "<p><strong>Estos son los pedidos que el chef DEBERÍA ver:</strong></p>";
    
    echo "<table>";
    echo "<tr><th>Número Pedido</th><th>Estado</th><th>Mesa</th><th>Mesero</th><th>Items</th></tr>";
    
    while ($pedido = $result->fetch_assoc()) {
        // Obtener items
        $items_sql = "SELECT * FROM pedidos_items WHERE pedido_id = " . $pedido['id'];
        $items_result = $conn->query($items_sql);
        $items_count = $items_result->num_rows;
        
        echo "<tr>";
        echo "<td><strong>" . $pedido['numero_pedido'] . "</strong></td>";
        echo "<td>" . ucfirst($pedido['estado']) . "</td>";
        echo "<td>" . ($pedido['numero_mesa'] ?? 'Domicilio') . "</td>";
        echo "<td>" . ($pedido['mesero_nombre'] ?? 'N/A') . "</td>";
        echo "<td>" . $items_count . " items</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='problema'>❌ La consulta NO devuelve resultados</p>";
}

// 3. Verificar usuarios chef activos
echo "<hr><h2>3️⃣ Usuarios Chef Activos</h2>";
$result = $conn->query("SELECT id, nombre, usuario, activo FROM usuarios WHERE rol = 'chef'");

echo "<table>";
echo "<tr><th>ID</th><th>Nombre</th><th>Usuario</th><th>Estado</th><th>Puede Ver Pedidos</th></tr>";

while ($row = $result->fetch_assoc()) {
    $puede_ver = ($row['activo'] == 1) ? "✅ SÍ" : "❌ NO (está inactivo)";
    $class = ($row['activo'] == 1) ? "ok" : "problema";
    
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['nombre']) . "</td>";
    echo "<td>" . htmlspecialchars($row['usuario']) . "</td>";
    echo "<td>" . ($row['activo'] ? 'Activo' : 'Inactivo') . "</td>";
    echo "<td class='$class'>$puede_ver</td>";
    echo "</tr>";
}
echo "</table>";

// 4. Verificar archivo chef.php
echo "<hr><h2>4️⃣ Verificación del Archivo chef.php</h2>";
$chef_file = 'chef.php';
if (file_exists($chef_file)) {
    echo "<p class='ok'>✅ El archivo chef.php existe</p>";
    
    // Verificar si tiene la consulta correcta
    $content = file_get_contents($chef_file);
    if (strpos($content, "WHERE p.estado IN ('confirmado', 'preparando')") !== false) {
        echo "<p class='ok'>✅ La consulta SQL en chef.php es correcta</p>";
    } else {
        echo "<p class='problema'>⚠️ La consulta SQL en chef.php podría estar incorrecta</p>";
    }
} else {
    echo "<p class='problema'>❌ El archivo chef.php NO existe</p>";
}

$conn->close();

echo "<hr>";
echo "<h2>📊 Conclusión</h2>";
echo "<p><strong>Si hay pedidos confirmados pero el chef no los ve, las posibles causas son:</strong></p>";
echo "<ol>";
echo "<li>El usuario chef está <strong>inactivo</strong> (verificar en tabla arriba)</li>";
echo "<li>El chef no ha iniciado sesión correctamente</li>";
echo "<li>Hay un error de JavaScript o caché en el navegador (presionar Ctrl+F5)</li>";
echo "<li>El archivo chef.php tiene un error de sintaxis</li>";
echo "</ol>";

echo "<p><a href='chef.php' style='padding:10px 20px;background:#667eea;color:white;text-decoration:none;border-radius:5px;'>Ir al Panel de Chef</a></p>";
echo "<p><a href='admin_usuarios.php' style='padding:10px 20px;background:#48bb78;color:white;text-decoration:none;border-radius:5px;margin-left:10px;'>Gestionar Usuarios</a></p>";

echo "</body></html>";
?>
