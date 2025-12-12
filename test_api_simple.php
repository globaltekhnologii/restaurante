<?php
// test_api_simple.php - Prueba simple de API sin sesión
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test Simple de API Reportes</h1>";
echo "<p>Fecha actual: " . date('Y-m-d H:i:s') . "</p>";

// Simular parámetros
$_GET['fecha_inicio'] = date('Y-m-d');
$_GET['fecha_fin'] = date('Y-m-d');

// Simular sesión
$_SESSION['id_usuario'] = 1;
$_SESSION['rol'] = 'admin';

echo "<h2>Probando get_ventas_periodo.php</h2>";
echo "<pre>";

// Capturar la salida
ob_start();
include 'api/get_ventas_periodo.php';
$output = ob_get_clean();

echo "Salida capturada:\n";
echo htmlspecialchars($output);
echo "\n\n";

// Intentar decodificar JSON
$json = json_decode($output, true);
if ($json === null) {
    echo "ERROR: JSON inválido\n";
    echo "Error JSON: " . json_last_error_msg() . "\n";
} else {
    echo "JSON válido:\n";
    print_r($json);
}

echo "</pre>";
?>
