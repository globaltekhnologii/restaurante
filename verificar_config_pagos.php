<?php
session_start();
require_once 'config.php';
require_once 'includes/tenant_context.php';

$conn = getDatabaseConnection();
$tenant_id = getCurrentTenantId();

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Verificar Config Pagos</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 20px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #3b82f6; padding-bottom: 10px; }
        .info { background: #d1ecf1; border-left: 4px solid #0c5460; padding: 15px; margin: 20px 0; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: 600; }
        code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>🔍 Verificar Configuración de Pagos</h1>";

echo "<div class='info'>";
echo "<strong>Tu sesión actual:</strong><br>";
echo "Tenant ID: <code>$tenant_id</code><br>";
echo "Usuario: <code>" . ($_SESSION['usuario'] ?? 'No definido') . "</code>";
echo "</div>";

// Ver todas las configuraciones
echo "<h2>📊 TODAS las Configuraciones (Debug)</h2>";
$result = $conn->query("SELECT cp.*, st.restaurant_name 
                        FROM config_pagos cp 
                        LEFT JOIN saas_tenants st ON cp.tenant_id = st.id 
                        ORDER BY cp.tenant_id, cp.pasarela");

echo "<table>";
echo "<tr><th>ID</th><th>Tenant ID</th><th>Restaurante</th><th>Pasarela</th><th>Activa</th><th>Modo</th><th>Tiene Keys</th></tr>";

while ($row = $result->fetch_assoc()) {
    $highlight = ($row['tenant_id'] == $tenant_id) ? 'style="background: #d4edda; font-weight: bold;"' : '';
    $activa = $row['activa'] ? '✅' : '❌';
    $tiene_keys = (!empty($row['public_key']) || !empty($row['secret_key'])) ? '✅' : '❌';
    
    echo "<tr $highlight>";
    echo "<td>{$row['id']}</td>";
    echo "<td><strong>Tenant {$row['tenant_id']}</strong></td>";
    echo "<td>{$row['restaurant_name']}</td>";
    echo "<td>{$row['pasarela']}</td>";
    echo "<td>$activa</td>";
    echo "<td>{$row['modo']}</td>";
    echo "<td>$tiene_keys</td>";
    echo "</tr>";
}
echo "</table>";

// Ver lo que debería mostrar config_pagos_simple.php
echo "<h2>🎯 Lo que DEBERÍA ver config_pagos_simple.php</h2>";
$sql = "SELECT * FROM config_pagos WHERE tenant_id = $tenant_id ORDER BY pasarela";
echo "<div class='info'><strong>SQL:</strong> <code>$sql</code></div>";

$result = $conn->query($sql);
$total = $result->num_rows;

echo "<div class='info'><strong>Resultado:</strong> $total configuraciones para tu tenant</div>";

if ($total > 0) {
    echo "<table>";
    echo "<tr><th>Pasarela</th><th>Activa</th><th>Modo</th><th>Tiene Keys</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        $activa = $row['activa'] ? '✅ Sí' : '❌ No';
        $tiene_keys = (!empty($row['public_key']) || !empty($row['secret_key'])) ? '✅ Sí' : '❌ No';
        
        echo "<tr>";
        echo "<td><strong>{$row['pasarela']}</strong></td>";
        echo "<td>$activa</td>";
        echo "<td>{$row['modo']}</td>";
        echo "<td>$tiene_keys</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='info'>❌ No tienes configuraciones de pago. Se deben crear.</div>";
}

$conn->close();

echo "<div style='margin-top: 30px;'>";
echo "<a href='config_pagos_simple.php' style='display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px;'>← Volver a Config Pagos</a>";
echo "</div>";

echo "</div></body></html>";
?>
