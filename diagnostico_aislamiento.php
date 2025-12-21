<?php
/**
 * SCRIPT DE DIAGNÓSTICO DE AISLAMIENTO DE TENANTS
 * Verifica qué datos están viendo los usuarios de cada tenant
 */

session_start();
require_once 'config.php';
require_once 'includes/tenant_context.php';

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Diagnóstico de Aislamiento</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #3b82f6; padding-bottom: 10px; }
        h2 { color: #666; margin-top: 30px; border-bottom: 2px solid #e0e0e0; padding-bottom: 8px; }
        .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 10px 0; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 10px 0; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 10px 0; }
        .info { background: #d1ecf1; border-left: 4px solid #0c5460; padding: 15px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: 600; }
        code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>🔍 Diagnóstico de Aislamiento de Tenants</h1>";

$conn = getDatabaseConnection();

// Información de sesión actual
echo "<h2>📋 Información de Sesión Actual</h2>";
if (isset($_SESSION['tenant_id'])) {
    $current_tenant = $_SESSION['tenant_id'];
    echo "<div class='info'>";
    echo "<strong>Tenant ID en sesión:</strong> $current_tenant<br>";
    echo "<strong>Usuario:</strong> " . ($_SESSION['usuario'] ?? 'No definido') . "<br>";
    echo "<strong>Rol:</strong> " . ($_SESSION['rol'] ?? 'No definido');
    echo "</div>";
} else {
    echo "<div class='warning'>⚠️ No hay tenant_id en la sesión. Esto es un problema.</div>";
    $current_tenant = 1; // Default para continuar diagnóstico
}

// Verificar distribución de datos por tenant
echo "<h2>📊 Distribución de Datos por Tenant</h2>";

$tables = ['platos', 'usuarios', 'pedidos', 'clientes', 'mesas'];

echo "<table>";
echo "<tr><th>Tabla</th><th>Tenant 1</th><th>Tenant 2</th><th>Tenant 3</th><th>Sin Tenant</th><th>Total</th></tr>";

foreach ($tables as $table) {
    $result = $conn->query("SELECT 
        SUM(CASE WHEN tenant_id = 1 THEN 1 ELSE 0 END) as t1,
        SUM(CASE WHEN tenant_id = 2 THEN 1 ELSE 0 END) as t2,
        SUM(CASE WHEN tenant_id = 3 THEN 1 ELSE 0 END) as t3,
        SUM(CASE WHEN tenant_id IS NULL OR tenant_id = 0 THEN 1 ELSE 0 END) as sin_tenant,
        COUNT(*) as total
        FROM $table");
    
    if ($result) {
        $row = $result->fetch_assoc();
        $sin_tenant_class = $row['sin_tenant'] > 0 ? 'style="background: #fee; color: #c00; font-weight: bold;"' : '';
        echo "<tr>";
        echo "<td><strong>$table</strong></td>";
        echo "<td>{$row['t1']}</td>";
        echo "<td>{$row['t2']}</td>";
        echo "<td>{$row['t3']}</td>";
        echo "<td $sin_tenant_class>{$row['sin_tenant']}</td>";
        echo "<td><strong>{$row['total']}</strong></td>";
        echo "</tr>";
    }
}

echo "</table>";

// Verificar qué ve el tenant actual
echo "<h2>👁️ Datos Visibles para Tenant $current_tenant</h2>";

echo "<h3>Platos</h3>";
$result = $conn->query("SELECT COUNT(*) as total FROM platos WHERE tenant_id = $current_tenant");
$platos_count = $result->fetch_assoc()['total'];
echo "<div class='info'>Platos visibles: <strong>$platos_count</strong></div>";

if ($platos_count > 0) {
    $result = $conn->query("SELECT nombre, categoria, precio FROM platos WHERE tenant_id = $current_tenant LIMIT 5");
    echo "<table><tr><th>Nombre</th><th>Categoría</th><th>Precio</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['nombre']}</td><td>{$row['categoria']}</td><td>\${$row['precio']}</td></tr>";
    }
    echo "</table>";
}

echo "<h3>Usuarios</h3>";
$result = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE tenant_id = $current_tenant");
$usuarios_count = $result->fetch_assoc()['total'];
echo "<div class='info'>Usuarios visibles: <strong>$usuarios_count</strong></div>";

if ($usuarios_count > 0) {
    $result = $conn->query("SELECT usuario, nombre, rol FROM usuarios WHERE tenant_id = $current_tenant LIMIT 5");
    echo "<table><tr><th>Usuario</th><th>Nombre</th><th>Rol</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['usuario']}</td><td>{$row['nombre']}</td><td>{$row['rol']}</td></tr>";
    }
    echo "</table>";
}

echo "<h3>Pedidos</h3>";
$result = $conn->query("SELECT COUNT(*) as total FROM pedidos WHERE tenant_id = $current_tenant");
$pedidos_count = $result->fetch_assoc()['total'];
echo "<div class='info'>Pedidos visibles: <strong>$pedidos_count</strong></div>";

echo "<h3>Clientes</h3>";
$result = $conn->query("SELECT COUNT(*) as total FROM clientes WHERE tenant_id = $current_tenant");
$clientes_count = $result->fetch_assoc()['total'];
echo "<div class='info'>Clientes visibles: <strong>$clientes_count</strong></div>";

// Verificar archivos problemáticos
echo "<h2>🔧 Archivos que Pueden Tener Problemas</h2>";

$archivos_criticos = [
    'admin.php' => 'Panel principal de administración',
    'admin_pedidos.php' => 'Gestión de pedidos',
    'admin_usuarios.php' => 'Gestión de usuarios',
    'admin_clientes.php' => 'Gestión de clientes',
    'chef.php' => 'Panel de chef',
    'mesero.php' => 'Panel de mesero',
    'cajero.php' => 'Panel de cajero',
    'domiciliario.php' => 'Panel de domiciliario'
];

echo "<div class='warning'>";
echo "<strong>Archivos a revisar manualmente:</strong><br>";
foreach ($archivos_criticos as $archivo => $descripcion) {
    echo "• <code>$archivo</code> - $descripcion<br>";
}
echo "</div>";

// Recomendaciones
echo "<h2>💡 Recomendaciones</h2>";

if ($platos_count == 0 && $current_tenant > 1) {
    echo "<div class='warning'>⚠️ El Tenant $current_tenant no tiene platos. Esto es normal si es nuevo.</div>";
}

echo "<div class='info'>";
echo "<strong>Para verificar aislamiento correcto:</strong><br>";
echo "1. Login como Tenant 1 → Debe ver sus datos<br>";
echo "2. Crear un plato de prueba<br>";
echo "3. Logout y login como Tenant 2<br>";
echo "4. NO debe ver el plato del Tenant 1<br>";
echo "5. Si lo ve, hay un problema de filtrado";
echo "</div>";

$conn->close();

echo "<div style='margin-top: 30px;'>";
echo "<a href='admin.php' style='display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px;'>← Volver al Panel</a>";
echo "</div>";

echo "</div></body></html>";
?>
