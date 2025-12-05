<?php
require_once 'config.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Verificar Usuarios Chef</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f7fa}";
echo "table{width:100%;border-collapse:collapse;background:white;margin:20px 0}";
echo "th,td{padding:12px;border:1px solid #ddd;text-align:left}";
echo "th{background:#667eea;color:white}";
echo ".activo{color:green;font-weight:bold}";
echo ".inactivo{color:red;font-weight:bold}";
echo "</style></head><body>";

echo "<h1>👨‍🍳 Usuarios Chef en el Sistema</h1>";

$conn = getDatabaseConnection();

// Obtener todos los usuarios con rol chef
$result = $conn->query("SELECT id, nombre, usuario, rol, activo, fecha_creacion FROM usuarios WHERE rol = 'chef' ORDER BY id");

if ($result->num_rows > 0) {
    echo "<p>Total de chefs registrados: <strong>" . $result->num_rows . "</strong></p>";
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Usuario</th><th>Rol</th><th>Estado</th><th>Fecha Creación</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        $estado_class = $row['activo'] ? 'activo' : 'inactivo';
        $estado_text = $row['activo'] ? '✅ Activo' : '❌ Inactivo';
        
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['nombre']) . "</td>";
        echo "<td>" . htmlspecialchars($row['usuario']) . "</td>";
        echo "<td>" . $row['rol'] . "</td>";
        echo "<td class='$estado_class'>$estado_text</td>";
        echo "<td>" . $row['fecha_creacion'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p style='color:red'>❌ No hay usuarios con rol 'chef' en la base de datos</p>";
}

// Verificar pedidos activos
echo "<hr>";
echo "<h2>📋 Pedidos Activos (que los chefs deberían ver)</h2>";

$result = $conn->query("SELECT COUNT(*) as count FROM pedidos WHERE estado IN ('confirmado', 'preparando')");
$count = $result->fetch_assoc()['count'];

echo "<p>Pedidos pendientes/en preparación: <strong>$count</strong></p>";

if ($count > 0) {
    $result = $conn->query("SELECT id, numero_pedido, estado, fecha_pedido FROM pedidos WHERE estado IN ('confirmado', 'preparando') ORDER BY fecha_pedido DESC LIMIT 10");
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Número</th><th>Estado</th><th>Fecha</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['numero_pedido'] . "</td>";
        echo "<td>" . ucfirst($row['estado']) . "</td>";
        echo "<td>" . date('d/m/Y H:i', strtotime($row['fecha_pedido'])) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

$conn->close();

echo "<hr>";
echo "<h2>ℹ️ Información Importante</h2>";
echo "<p><strong>El panel de chef muestra TODOS los pedidos pendientes, sin importar qué chef inició sesión.</strong></p>";
echo "<p>Esto es correcto porque en una cocina, todos los chefs deben ver todos los pedidos que necesitan prepararse.</p>";
echo "<p>Si un chef no ve pedidos, verifica:</p>";
echo "<ul>";
echo "<li>✅ Que el usuario esté <strong>activo</strong></li>";
echo "<li>✅ Que haya iniciado sesión correctamente</li>";
echo "<li>✅ Que existan pedidos en estado 'confirmado' o 'preparando'</li>";
echo "</ul>";

echo "<p><a href='chef.php' style='padding:10px 20px;background:#667eea;color:white;text-decoration:none;border-radius:5px;'>Ir al Panel de Chef</a></p>";
echo "<p><a href='admin_usuarios.php' style='padding:10px 20px;background:#48bb78;color:white;text-decoration:none;border-radius:5px;margin-left:10px;'>Gestionar Usuarios</a></p>";

echo "</body></html>";
?>
