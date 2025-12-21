<?php
/**
 * CREAR CONFIGURACIONES DE PAGO PARA TENANT
 * Script rápido para crear configuraciones vacías
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
    <title>Crear Config Pagos</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #3b82f6; padding-bottom: 10px; }
        .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0; border-radius: 5px; color: #155724; }
        .info { background: #d1ecf1; border-left: 4px solid #0c5460; padding: 15px; margin: 20px 0; border-radius: 5px; }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>🔧 Crear Configuraciones de Pago</h1>";

echo "<div class='info'>";
echo "<strong>Tenant ID:</strong> $tenant_id<br>";
echo "<strong>Usuario:</strong> " . ($_SESSION['usuario'] ?? 'No definido');
echo "</div>";

// Verificar si ya tiene configuraciones
$check = $conn->query("SELECT COUNT(*) as total FROM config_pagos WHERE tenant_id = $tenant_id");
$total = $check->fetch_assoc()['total'];

if ($total > 0) {
    echo "<div class='info'>✅ Ya tienes $total configuraciones de pago</div>";
} else {
    echo "<div class='info'>⏳ Creando configuraciones vacías para Bold y Mercado Pago...</div>";
    
    $pasarelas = ['bold', 'mercadopago'];
    
    foreach ($pasarelas as $pasarela) {
        $sql = "INSERT INTO config_pagos (tenant_id, pasarela, activa, modo, public_key, secret_key) 
                VALUES ($tenant_id, '$pasarela', 0, 'sandbox', '', '')";
        
        if ($conn->query($sql)) {
            echo "<div class='success'>✅ Configuración creada para <strong>$pasarela</strong></div>";
        } else {
            echo "<div class='error'>❌ Error: " . $conn->error . "</div>";
        }
    }
    
    echo "<div class='success'><strong>✅ Proceso completado!</strong></div>";
}

// Mostrar configuraciones actuales
echo "<h2>📋 Tus Configuraciones</h2>";
$result = $conn->query("SELECT * FROM config_pagos WHERE tenant_id = $tenant_id ORDER BY pasarela");

if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Pasarela</th><th>Activa</th><th>Modo</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        $activa = $row['activa'] ? '✅ Sí' : '❌ No';
        echo "<tr>";
        echo "<td><strong>{$row['pasarela']}</strong></td>";
        echo "<td>$activa</td>";
        echo "<td>{$row['modo']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

$conn->close();

echo "<div style='margin-top: 30px;'>";
echo "<a href='config_pagos_simple.php' style='display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px;'>→ Ir a Configurar Pagos</a>";
echo "</div>";

echo "</div></body></html>";
?>
