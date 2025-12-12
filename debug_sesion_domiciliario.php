<?php
session_start();
echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Debug Sesión</title>";
echo "<style>body{font-family:Arial;padding:20px;}";
echo "table{border-collapse:collapse;width:100%;margin:20px 0;}";
echo "th,td{border:1px solid #ddd;padding:12px;text-align:left;}";
echo "th{background:#667eea;color:white;}</style></head><body>";
echo "<h1>🔍 Debug de Sesión</h1>";

echo "<h2>Variables de Sesión:</h2>";
echo "<table>";
echo "<tr><th>Variable</th><th>Valor</th></tr>";
foreach ($_SESSION as $key => $value) {
    echo "<tr><td><strong>$key</strong></td><td>" . htmlspecialchars(print_r($value, true)) . "</td></tr>";
}
echo "</table>";

echo "<h2>Test de Verificación de Rol:</h2>";
require_once 'auth_helper.php';

echo "<p><strong>¿Sesión válida?</strong> ";
try {
    verificarSesion();
    echo "<span style='color:green;'>✅ SÍ</span></p>";
} catch (Exception $e) {
    echo "<span style='color:red;'>❌ NO - " . $e->getMessage() . "</span></p>";
}

echo "<p><strong>¿Rol domiciliario válido?</strong> ";
try {
    if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'domiciliario') {
        echo "<span style='color:green;'>✅ SÍ</span></p>";
    } else {
        echo "<span style='color:red;'>❌ NO - Rol actual: " . ($_SESSION['rol'] ?? 'NO DEFINIDO') . "</span></p>";
    }
} catch (Exception $e) {
    echo "<span style='color:red;'>❌ ERROR - " . $e->getMessage() . "</span></p>";
}

echo "<h2>Test de Confirmar Entrega:</h2>";
echo "<p>Simular confirmación del pedido TEST-251208-566 (ID probablemente 37):</p>";
echo "<p><a href='confirmar_entrega.php?id=37' style='background:#28a745;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Probar Confirmar Entrega</a></p>";

echo "<p style='margin-top:20px;'><a href='domiciliario.php' style='background:#667eea;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Volver a Panel</a></p>";

echo "</body></html>";
?>
