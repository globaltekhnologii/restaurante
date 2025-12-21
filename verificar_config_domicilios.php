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
    <title>Verificar Config Domicilios</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #3b82f6; padding-bottom: 10px; }
        .info { background: #d1ecf1; border-left: 4px solid #0c5460; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0; border-radius: 5px; color: #155724; }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>🔍 Verificar configuracion_domicilios</h1>";

echo "<div class='info'>";
echo "<strong>Tenant ID:</strong> $tenant_id<br>";
echo "<strong>Usuario:</strong> " . ($_SESSION['usuario'] ?? 'No definido');
echo "</div>";

// Verificar si existe registro
$sql = "SELECT * FROM configuracion_domicilios WHERE tenant_id = $tenant_id";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $config = $result->fetch_assoc();
    echo "<div class='success'>✅ Registro existe</div>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Campo</th><th>Valor</th></tr>";
    echo "<tr><td>ID</td><td>{$config['id']}</td></tr>";
    echo "<tr><td>Tenant ID</td><td>{$config['tenant_id']}</td></tr>";
    echo "<tr><td>Tarifa Base</td><td>\${$config['tarifa_base']}</td></tr>";
    echo "<tr><td>Costo/km</td><td>\${$config['costo_por_km']}</td></tr>";
    echo "<tr><td>Distancia Máxima</td><td>{$config['distancia_maxima']} km</td></tr>";
    echo "</table>";
} else {
    echo "<div class='info' style='background: #fff3cd; border-color: #ffc107;'>⚠️ NO existe registro para este tenant</div>";
    echo "<p>Creando registro...</p>";
    
    $sql = "INSERT INTO configuracion_domicilios (tenant_id, tarifa_base, costo_por_km, distancia_maxima, usar_rangos) 
            VALUES ($tenant_id, 5000, 1000, 10, 0)";
    
    if ($conn->query($sql)) {
        echo "<div class='success'>✅ Registro creado exitosamente</div>";
    } else {
        echo "<div class='info' style='background: #f8d7da; border-color: #dc3545;'>❌ Error: " . $conn->error . "</div>";
    }
}

$conn->close();

echo "<div style='margin-top: 30px;'>";
echo "<a href='admin_configuracion_domicilios.php' style='display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px;'>→ Volver a Configuración</a>";
echo "</div>";

echo "</div></body></html>";
?>
