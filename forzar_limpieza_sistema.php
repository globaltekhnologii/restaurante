<?php
/**
 * FORZAR LIMPIEZA DE CONFIGURACION_SISTEMA
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
    <title>Limpiar Config Sistema</title>
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

echo "<h1>🧹 Limpiar Configuración Sistema</h1>";

echo "<div class='info'>";
echo "<strong>Tenant ID:</strong> $tenant_id<br>";
echo "<strong>Usuario:</strong> " . ($_SESSION['usuario'] ?? 'No definido');
echo "</div>";

// Limpiar TODOS los campos de configuracion_sistema
$sql = "UPDATE configuracion_sistema 
        SET direccion_restaurante = '',
            ciudad_restaurante = '',
            pais_restaurante = '',
            latitud_restaurante = NULL,
            longitud_restaurante = NULL,
            telefono_restaurante = '',
            email_restaurante = '',
            horario_apertura = '',
            horario_cierre = ''
        WHERE tenant_id = $tenant_id";

if ($conn->query($sql)) {
    echo "<div class='success'>✅ Configuración del sistema limpiada</div>";
}

// Mostrar estado
echo "<h2>📊 Estado Actual</h2>";
$result = $conn->query("SELECT * FROM configuracion_sistema WHERE tenant_id = $tenant_id");

if ($row = $result->fetch_assoc()) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Campo</th><th>Valor</th></tr>";
    echo "<tr><td>Dirección</td><td>" . ($row['direccion_restaurante'] ?: '❌ Vacío') . "</td></tr>";
    echo "<tr><td>Ciudad</td><td>" . ($row['ciudad_restaurante'] ?: '❌ Vacío') . "</td></tr>";
    echo "<tr><td>País</td><td>" . ($row['pais_restaurante'] ?: '❌ Vacío') . "</td></tr>";
    echo "<tr><td>Latitud</td><td>" . ($row['latitud_restaurante'] ?: '❌ NULL') . "</td></tr>";
    echo "<tr><td>Longitud</td><td>" . ($row['longitud_restaurante'] ?: '❌ NULL') . "</td></tr>";
    echo "</table>";
}

$conn->close();

echo "<div style='margin-top: 30px;'>";
echo "<a href='logout.php' style='display: inline-block; padding: 12px 24px; background: #dc3545; color: white; text-decoration: none; border-radius: 5px; margin-right: 10px;'>🚪 Cerrar Sesión</a>";
echo "<a href='admin_configuracion_domicilios.php' style='display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px;'>→ Ver Configuración</a>";
echo "</div>";

echo "</div></body></html>";
?>
