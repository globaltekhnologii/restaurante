<?php
/**
 * LIMPIAR DATOS DE CONFIGURACIÓN DEL TENANT
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
    <title>Limpiar Configuración</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #3b82f6; padding-bottom: 10px; }
        .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0; border-radius: 5px; color: #155724; }
        .info { background: #d1ecf1; border-left: 4px solid #0c5460; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 5px; }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>🧹 Limpiar Configuración del Tenant</h1>";

echo "<div class='info'>";
echo "<strong>Tenant ID:</strong> $tenant_id<br>";
echo "<strong>Usuario:</strong> " . ($_SESSION['usuario'] ?? 'No definido');
echo "</div>";

if ($tenant_id == 1) {
    echo "<div class='warning'>⚠️ No puedes limpiar el Tenant 1 (desarrollo)</div>";
    echo "</div></body></html>";
    exit;
}

// Limpiar configuracion_domicilios
echo "<h2>🗺️ Limpiar Configuración de Domicilios GPS</h2>";

$sql = "UPDATE configuracion_domicilios 
        SET tarifa_base = 5000,
            costo_por_km = 1000,
            distancia_maxima = 10,
            usar_rangos = 0
        WHERE tenant_id = $tenant_id";

if ($conn->query($sql)) {
    echo "<div class='success'>✅ Tarifas restablecidas a valores por defecto</div>";
}

// Limpiar configuracion_sistema
echo "<h2>⚙️ Limpiar Configuración General</h2>";

$sql = "UPDATE configuracion_sistema 
        SET latitud_restaurante = NULL,
            longitud_restaurante = NULL
        WHERE tenant_id = $tenant_id";

if ($conn->query($sql)) {
    echo "<div class='success'>✅ Coordenadas GPS eliminadas</div>";
}

// Limpiar metodos_pago_config
echo "<h2>💳 Limpiar Métodos de Pago</h2>";

$sql = "UPDATE metodos_pago_config 
        SET numero_cuenta = '',
            nombre_titular = '',
            qr_imagen = NULL
        WHERE tenant_id = $tenant_id";

if ($conn->query($sql)) {
    echo "<div class='success'>✅ QR y números de cuenta eliminados</div>";
}

// Mostrar estado final
echo "<h2>📊 Estado Final</h2>";

echo "<h3>Configuración de Domicilios:</h3>";
$result = $conn->query("SELECT * FROM configuracion_domicilios WHERE tenant_id = $tenant_id");
if ($row = $result->fetch_assoc()) {
    echo "<div class='info'>";
    echo "Tarifa Base: \${$row['tarifa_base']}<br>";
    echo "Costo/km: \${$row['costo_por_km']}<br>";
    echo "Distancia Máxima: {$row['distancia_maxima']} km";
    echo "</div>";
}

echo "<h3>Coordenadas GPS:</h3>";
$result = $conn->query("SELECT latitud_restaurante, longitud_restaurante FROM configuracion_sistema WHERE tenant_id = $tenant_id");
if ($row = $result->fetch_assoc()) {
    $lat = $row['latitud_restaurante'] ?? 'No configurada';
    $lon = $row['longitud_restaurante'] ?? 'No configurada';
    echo "<div class='info'>";
    echo "Latitud: $lat<br>";
    echo "Longitud: $lon";
    echo "</div>";
}

echo "<h3>Métodos de Pago:</h3>";
$result = $conn->query("SELECT metodo, numero_cuenta, nombre_titular FROM metodos_pago_config WHERE tenant_id = $tenant_id");
echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Método</th><th>Número</th><th>Titular</th></tr>";
while ($row = $result->fetch_assoc()) {
    $numero = empty($row['numero_cuenta']) ? '❌ Vacío' : $row['numero_cuenta'];
    $titular = empty($row['nombre_titular']) ? '❌ Vacío' : $row['nombre_titular'];
    echo "<tr>";
    echo "<td>{$row['metodo']}</td>";
    echo "<td>$numero</td>";
    echo "<td>$titular</td>";
    echo "</tr>";
}
echo "</table>";

$conn->close();

echo "<div style='margin-top: 30px;'>";
echo "<a href='admin_configuracion_domicilios.php' style='display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px; margin-right: 10px;'>→ Config Domicilios</a>";
echo "<a href='config_pagos.php' style='display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px;'>→ Config Pagos</a>";
echo "</div>";

echo "</div></body></html>";
?>
