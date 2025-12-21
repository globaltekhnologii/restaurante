<?php
session_start();
require_once 'config.php';
require_once 'includes/tenant_context.php';

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Verificar Clientes por Tenant</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 20px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #3b82f6; padding-bottom: 10px; }
        .info { background: #d1ecf1; border-left: 4px solid #0c5460; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: 600; }
        code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>🔍 Verificación de Clientes por Tenant</h1>";

$conn = getDatabaseConnection();
$tenant_id = getCurrentTenantId();

echo "<div class='info'>";
echo "<strong>Tu sesión actual:</strong><br>";
echo "Tenant ID: <code>$tenant_id</code><br>";
echo "Usuario: <code>" . ($_SESSION['usuario'] ?? 'No definido') . "</code><br>";
echo "Rol: <code>" . ($_SESSION['rol'] ?? 'No definido') . "</code>";
echo "</div>";

// Ver distribución de clientes por tenant
echo "<h2>📊 Distribución de Clientes por Tenant</h2>";
$result = $conn->query("SELECT tenant_id, COUNT(*) as total FROM clientes WHERE activo = 1 GROUP BY tenant_id ORDER BY tenant_id");

echo "<table>";
echo "<tr><th>Tenant ID</th><th>Total Clientes Activos</th></tr>";

while ($row = $result->fetch_assoc()) {
    $highlight = ($row['tenant_id'] == $tenant_id) ? 'style="background: #d4edda; font-weight: bold;"' : '';
    echo "<tr $highlight>";
    echo "<td>Tenant {$row['tenant_id']}</td>";
    echo "<td>{$row['total']}</td>";
    echo "</tr>";
}
echo "</table>";

// Ver clientes del tenant actual
echo "<h2>👥 Clientes de tu Tenant (Tenant $tenant_id)</h2>";
$result = $conn->query("SELECT id, nombre, apellido, telefono, email, fecha_registro FROM clientes WHERE tenant_id = $tenant_id AND activo = 1 ORDER BY fecha_registro DESC");

if ($result->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Teléfono</th><th>Email</th><th>Fecha Registro</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['nombre']} {$row['apellido']}</td>";
        echo "<td>{$row['telefono']}</td>";
        echo "<td>{$row['email']}</td>";
        echo "<td>{$row['fecha_registro']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='info'>No tienes clientes registrados en este tenant.</div>";
}

// Ver TODOS los clientes (para debug)
echo "<h2>🔍 TODOS los Clientes (Debug)</h2>";
$result = $conn->query("SELECT id, tenant_id, nombre, apellido, telefono FROM clientes WHERE activo = 1 ORDER BY tenant_id, id");

echo "<table>";
echo "<tr><th>ID</th><th>Tenant ID</th><th>Nombre</th><th>Teléfono</th></tr>";

while ($row = $result->fetch_assoc()) {
    $highlight = ($row['tenant_id'] == $tenant_id) ? 'style="background: #d4edda;"' : '';
    echo "<tr $highlight>";
    echo "<td>{$row['id']}</td>";
    echo "<td><strong>Tenant {$row['tenant_id']}</strong></td>";
    echo "<td>{$row['nombre']} {$row['apellido']}</td>";
    echo "<td>{$row['telefono']}</td>";
    echo "</tr>";
}
echo "</table>";

// Verificar qué ve admin_clientes.php
echo "<h2>🎯 Consulta que usa admin_clientes.php</h2>";
$where = "WHERE tenant_id = $tenant_id AND activo = 1";
$sql = "SELECT COUNT(*) as total FROM clientes $where";
$total = $conn->query($sql)->fetch_assoc()['total'];

echo "<div class='info'>";
echo "<strong>SQL:</strong> <code>$sql</code><br>";
echo "<strong>Resultado:</strong> $total clientes";
echo "</div>";

if ($total != $result->num_rows) {
    echo "<div class='warning'>⚠️ Hay una discrepancia en los números. Puede haber un problema de caché.</div>";
}

$conn->close();

echo "<div style='margin-top: 30px;'>";
echo "<a href='admin_clientes.php' style='display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px;'>← Volver a Clientes</a>";
echo "</div>";

echo "</div></body></html>";
?>
