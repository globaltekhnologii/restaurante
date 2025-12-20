<?php
// Test de procesar_pedido_mesero.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Test de Procesar Pedido Mesero</h1>";

// Paso 1: Session
echo "<h2>1. Iniciando sesión...</h2>";
session_start();
echo "✅ Sesión iniciada<br>";

// Simular sesión de mesero
$_SESSION['loggedin'] = TRUE;
$_SESSION['rol'] = 'mesero';
$_SESSION['user_id'] = 1;
echo "✅ Sesión de mesero simulada<br>";

// Paso 2: Cargar archivos
echo "<h2>2. Cargando archivos...</h2>";
try {
    require_once 'auth_helper.php';
    echo "✅ auth_helper.php cargado<br>";
} catch (Exception $e) {
    die("❌ Error en auth_helper.php: " . $e->getMessage());
}

try {
    require_once 'config.php';
    echo "✅ config.php cargado<br>";
} catch (Exception $e) {
    die("❌ Error en config.php: " . $e->getMessage());
}

try {
    require_once 'includes/functions_inventario.php';
    echo "✅ functions_inventario.php cargado<br>";
} catch (Exception $e) {
    die("❌ Error en functions_inventario.php: " . $e->getMessage());
}

// Paso 3: Conexión DB
echo "<h2>3. Conectando a base de datos...</h2>";
try {
    $conn = getDatabaseConnection();
    echo "✅ Conexión exitosa<br>";
} catch (Exception $e) {
    die("❌ Error de conexión: " . $e->getMessage());
}

// Paso 4: Simular datos POST
echo "<h2>4. Simulando datos POST...</h2>";
$_POST['mesa_id'] = 1;
$_POST['tipo_pedido'] = 'mesa';
$_POST['nombre_cliente'] = 'Cliente Test';
$_POST['telefono'] = '1234567890';
$_POST['notas'] = 'Test';
$_POST['items'] = json_encode([
    ['id' => 1, 'nombre' => 'Plato Test', 'precio' => 10000, 'cantidad' => 1]
]);
echo "✅ Datos POST simulados<br>";

// Paso 5: Verificar función validarStockPedido
echo "<h2>5. Verificando función validarStockPedido...</h2>";
if (function_exists('validarStockPedido')) {
    echo "✅ validarStockPedido existe<br>";
} else {
    echo "❌ validarStockPedido NO EXISTE - Este es el problema<br>";
    echo "<p>El archivo includes/functions_inventario.php no tiene esta función.</p>";
}

// Paso 6: Verificar función descontarStockPedido
echo "<h2>6. Verificando función descontarStockPedido...</h2>";
if (function_exists('descontarStockPedido')) {
    echo "✅ descontarStockPedido existe<br>";
} else {
    echo "❌ descontarStockPedido NO EXISTE - Este es el problema<br>";
    echo "<p>El archivo includes/functions_inventario.php no tiene esta función.</p>";
}

$conn->close();

echo "<h2>✅ DIAGNÓSTICO COMPLETADO</h2>";
echo "<p>Si alguna función NO EXISTE, ese es el problema del Error 500.</p>";
?>
