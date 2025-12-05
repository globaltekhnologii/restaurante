<?php
// Verificar que las columnas existen
require_once 'config.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Verificar Columnas</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f7fa}";
echo ".success{background:#d4edda;border-left:4px solid #28a745;color:#155724;padding:15px;margin:10px 0;border-radius:5px}";
echo ".error{background:#f8d7da;border-left:4px solid #dc3545;color:#721c24;padding:15px;margin:10px 0;border-radius:5px}";
echo "</style></head><body>";

echo "<h1>✅ Verificación de Columnas - Base de Datos</h1>";

$conn = getDatabaseConnection();

$columnas_requeridas = [
    'tipo_pedido',
    'metodo_pago_seleccionado',
    'pago_anticipado',
    'pago_validado',
    'referencia_pago_anticipado',
    'validacion_automatica'
];

$todas_ok = true;

foreach ($columnas_requeridas as $columna) {
    $result = $conn->query("SHOW COLUMNS FROM pedidos LIKE '$columna'");
    if ($result->num_rows > 0) {
        echo "<div class='success'>✅ Columna '$columna' existe</div>";
    } else {
        echo "<div class='error'>❌ Columna '$columna' NO existe</div>";
        $todas_ok = false;
    }
}

if ($todas_ok) {
    echo "<hr>";
    echo "<h2>🎉 ¡Todo listo!</h2>";
    echo "<p>Todas las columnas necesarias están creadas. Ahora puedes usar:</p>";
    echo "<ul>";
    echo "<li>✅ Prepago con validación manual o automática</li>";
    echo "<li>✅ Pedidos para llevar</li>";
    echo "<li>✅ Liberación automática de mesas</li>";
    echo "</ul>";
    echo "<p><a href='index.php' style='padding:10px 20px;background:#667eea;color:white;text-decoration:none;border-radius:5px;'>Ir al Menú</a></p>";
} else {
    echo "<hr>";
    echo "<p>Algunas columnas faltan. Ejecuta el setup nuevamente.</p>";
}

$conn->close();
echo "</body></html>";
?>
