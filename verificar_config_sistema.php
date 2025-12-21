<?php
/**
 * VERIFICAR TENANT_ID EN CONFIGURACION_SISTEMA
 */

session_start();
require_once 'config.php';
require_once 'includes/tenant_context.php';

$conn = getDatabaseConnection();
$tenant_id = getCurrentTenantId();

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Verificar Configuración Sistema</title>
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

echo "<h1>🔍 Verificar configuracion_sistema</h1>";

echo "<div class='info'>";
echo "<strong>Tu sesión:</strong><br>";
echo "Tenant ID: <code>$tenant_id</code><br>";
echo "Usuario: <code>" . ($_SESSION['usuario'] ?? 'No definido') . "</code>";
echo "</div>";

// Verificar si tiene tenant_id
$result = $conn->query("SHOW COLUMNS FROM configuracion_sistema LIKE 'tenant_id'");

if ($result->num_rows > 0) {
    echo "<div class='info'>✅ La tabla tiene columna tenant_id</div>";
} else {
    echo "<div class='info' style='background: #fff3cd; border-color: #ffc107;'>⚠️ La tabla NO tiene columna tenant_id</div>";
}

// Ver TODAS las configuraciones
echo "<h2>📊 TODAS las Configuraciones (Debug)</h2>";
$result = $conn->query("SELECT * FROM configuracion_sistema");

echo "<table>";
echo "<tr><th>ID</th><th>Tenant ID</th><th>Dirección</th><th>Ciudad</th><th>GPS</th></tr>";

while ($row = $result->fetch_assoc()) {
    $highlight = (isset($row['tenant_id']) && $row['tenant_id'] == $tenant_id) ? 'style="background: #d4edda; font-weight: bold;"' : '';
    $tenant = isset($row['tenant_id']) ? $row['tenant_id'] : 'N/A';
    $gps = ($row['latitud_restaurante'] && $row['longitud_restaurante']) ? '✅' : '❌';
    
    echo "<tr $highlight>";
    echo "<td>{$row['id']}</td>";
    echo "<td><strong>Tenant $tenant</strong></td>";
    echo "<td>" . ($row['direccion_restaurante'] ?? '') . "</td>";
    echo "<td>" . ($row['ciudad_restaurante'] ?? '') . "</td>";
    echo "<td>$gps</td>";
    echo "</tr>";
}
echo "</table>";

// Ver lo que DEBERÍA mostrar
echo "<h2>🎯 Lo que DEBERÍA ver tu tenant</h2>";

if ($result->num_rows > 0 && isset($row['tenant_id'])) {
    $sql = "SELECT * FROM configuracion_sistema WHERE tenant_id = $tenant_id";
    echo "<div class='info'><strong>SQL:</strong> <code>$sql</code></div>";
    
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo "<table>";
        echo "<tr><th>Campo</th><th>Valor</th></tr>";
        echo "<tr><td>Dirección</td><td>" . ($row['direccion_restaurante'] ?? 'Vacío') . "</td></tr>";
        echo "<tr><td>Ciudad</td><td>" . ($row['ciudad_restaurante'] ?? 'Vacío') . "</td></tr>";
        echo "<tr><td>País</td><td>" . ($row['pais_restaurante'] ?? 'Vacío') . "</td></tr>";
        echo "<tr><td>Latitud</td><td>" . ($row['latitud_restaurante'] ?? 'NULL') . "</td></tr>";
        echo "<tr><td>Longitud</td><td>" . ($row['longitud_restaurante'] ?? 'NULL') . "</td></tr>";
        echo "</table>";
    } else {
        echo "<div class='info'>❌ No tienes configuración. Se debe crear.</div>";
    }
} else {
    echo "<div class='info' style='background: #fff3cd; border-color: #ffc107;'>";
    echo "⚠️ La tabla no tiene tenant_id. Necesitas ejecutar:<br>";
    echo "<code>agregar_tenant_sistema.php</code>";
    echo "</div>";
}

$conn->close();

echo "<div style='margin-top: 30px;'>";
echo "<a href='admin_configuracion_domicilios.php' style='display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px;'>← Volver</a>";
echo "</div>";

echo "</div></body></html>";
?>
