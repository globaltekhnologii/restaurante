<?php
echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Test Enlaces</title>";
echo "<style>body{font-family:Arial;padding:20px;}";
echo "a{display:block;margin:10px 0;padding:10px;background:#667eea;color:white;text-decoration:none;border-radius:5px;}</style></head><body>";
echo "<h1>🔍 Prueba de Enlaces</h1>";

echo "<h2>Archivos de Domiciliario:</h2>";
echo "<ul>";
$files = ['tomar_entrega.php', 'salir_entrega.php', 'confirmar_entrega.php', 'domiciliario.php'];
foreach ($files as $file) {
    $exists = file_exists($file);
    $color = $exists ? 'green' : 'red';
    $icon = $exists ? '✅' : '❌';
    echo "<li style='color:$color;'>$icon <strong>$file</strong> - " . ($exists ? "Existe" : "NO existe") . "</li>";
}
echo "</ul>";

echo "<h2>Prueba de Enlaces:</h2>";
echo "<p><a href='tomar_entrega.php?id=1'>Test: tomar_entrega.php?id=1</a></p>";
echo "<p><a href='./tomar_entrega.php?id=1'>Test: ./tomar_entrega.php?id=1</a></p>";
echo "<p><a href='/Restaurante/tomar_entrega.php?id=1'>Test: /Restaurante/tomar_entrega.php?id=1</a></p>";

echo "<h2>Ruta Actual:</h2>";
echo "<p><strong>__FILE__:</strong> " . __FILE__ . "</p>";
echo "<p><strong>__DIR__:</strong> " . __DIR__ . "</p>";
echo "<p><strong>getcwd():</strong> " . getcwd() . "</p>";

echo "</body></html>";
?>
