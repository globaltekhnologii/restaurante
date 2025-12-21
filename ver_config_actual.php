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
    <title>Debug Domicilios</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 20px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #3b82f6; padding-bottom: 10px; }
        .info { background: #d1ecf1; border-left: 4px solid #0c5460; padding: 15px; margin: 20px 0; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: 600; }
        code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>Debug Config Domicilios</h1>";

echo "<div class='info'>";
echo "<strong>Tenant ID:</strong> <code>$tenant_id</code><br>";
echo "<strong>Usuario:</strong> <code>" . ($_SESSION['usuario'] ?? 'No definido') . "</code>";
echo "</div>";

// Consulta configuracion_sistema
$sql_config = "SELECT * FROM configuracion_sistema WHERE tenant_id = $tenant_id";
echo "<h2>Consulta configuracion_sistema</h2>";
echo "<div class='info'><strong>SQL:</strong> <code>$sql_config</code></div>";

$result_config = $conn->query($sql_config);
$config_sistema = $result_config->fetch_assoc();

if ($config_sistema) {
    echo "<table>";
    echo "<tr><th>Campo</th><th>Valor</th></tr>";
    echo "<tr><td>Dirección</td><td>" . ($config_sistema['direccion_restaurante'] ?: 'Vacío') . "</td></tr>";
    echo "<tr><td>Ciudad</td><td>" . ($config_sistema['ciudad_restaurante'] ?: 'Vacío') . "</td></tr>";
    echo "<tr><td>País</td><td>" . ($config_sistema['pais_restaurante'] ?: 'Vacío') . "</td></tr>";
    echo "</table>";
}

$conn->close();

echo "</div></body></html>";
?>
