<?php
// debug_procesar_pago.php - Versión de depuración de procesar_pago.php
session_start();
require_once 'auth_helper.php';
verificarSesion();

require_once 'config.php';

echo "<h1>Debug: Procesar Pago</h1>";
echo "<style>body { font-family: Arial; padding: 20px; } .ok { color: green; } .error { color: red; } pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; }</style>";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<p class='error'>Este script debe ser llamado vía POST</p>";
    echo "<p>Datos recibidos vía GET:</p>";
    echo "<pre>" . print_r($_GET, true) . "</pre>";
    exit;
}

echo "<h2>1. Datos Recibidos</h2>";
echo "<pre>";
echo "POST data:\n";
print_r($_POST);
echo "\nSesión:\n";
print_r($_SESSION);
echo "</pre>";

$pedido_id = intval($_POST['pedido_id']);
$metodo_pago = $_POST['metodo_pago'] ?? '';
$monto = floatval($_POST['monto']);
$notas = $_POST['notas'] ?? '';
$usuario_id = $_SESSION['user_id'];

echo "<h2>2. Variables Procesadas</h2>";
echo "<pre>";
echo "pedido_id: $pedido_id\n";
echo "metodo_pago: $metodo_pago\n";
echo "monto: $monto\n";
echo "notas: $notas\n";
echo "usuario_id: $usuario_id\n";
echo "</pre>";

// Obtener referencia según el método de pago
$referencia_pago = '';
if ($metodo_pago !== 'efectivo') {
    $referencia_pago = $_POST['referencia_' . $metodo_pago] ?? '';
}
echo "<p>Referencia de pago: <strong>$referencia_pago</strong></p>";

$conn = getDatabaseConnection();

echo "<h2>3. Verificando Pedido</h2>";
$stmt = $conn->prepare("SELECT * FROM pedidos WHERE id = ?");
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();

if (!$pedido) {
    echo "<p class='error'>✗ Error: Pedido no encontrado</p>";
    exit;
}

echo "<p class='ok'>✓ Pedido encontrado</p>";
echo "<pre>" . print_r($pedido, true) . "</pre>";

if ($pedido['pagado']) {
    echo "<p class='error'>✗ Este pedido ya está pagado</p>";
    exit;
}

echo "<h2>4. Verificando Tabla de Pagos</h2>";
$result = $conn->query("SHOW TABLES LIKE 'pagos'");
if ($result->num_rows == 0) {
    echo "<p class='error'>✗ ERROR CRÍTICO: La tabla 'pagos' NO existe</p>";
    echo "<p><strong>Solución:</strong> Ejecuta el archivo sql/pagos.sql en phpMyAdmin</p>";
    exit;
}
echo "<p class='ok'>✓ Tabla 'pagos' existe</p>";

// Verificar estructura de la tabla
echo "<h3>Estructura de la tabla pagos:</h3>";
$structure = $conn->query("DESCRIBE pagos");
echo "<table border='1' cellpadding='5'><tr><th>Campo</th><th>Tipo</th></tr>";
while ($row = $structure->fetch_assoc()) {
    echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td></tr>";
}
echo "</table>";

// Generar número de transacción
$numero_transaccion = 'TXN-' . date('Ymd') . '-' . str_pad($pedido_id, 4, '0', STR_PAD_LEFT) . '-' . rand(1000, 9999);
echo "<p>Número de transacción generado: <strong>$numero_transaccion</strong></p>";

echo "<h2>5. Intentando Insertar Pago</h2>";
$conn->begin_transaction();

try {
    // Insertar pago
    $stmt = $conn->prepare("INSERT INTO pagos (pedido_id, numero_transaccion, metodo_pago, referencia_pago, monto, usuario_id, notas) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        throw new Exception("Error preparando statement: " . $conn->error);
    }
    
    $stmt->bind_param("isssdis", $pedido_id, $numero_transaccion, $metodo_pago, $referencia_pago, $monto, $usuario_id, $notas);
    
    if (!$stmt->execute()) {
        throw new Exception("Error ejecutando INSERT: " . $stmt->error);
    }
    
    $pago_id = $conn->insert_id;
    echo "<p class='ok'>✓ Pago insertado correctamente (ID: $pago_id)</p>";
    
    // Actualizar pedido como pagado
    $stmt = $conn->prepare("UPDATE pedidos SET pagado = 1 WHERE id = ?");
    $stmt->bind_param("i", $pedido_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Error actualizando pedido: " . $stmt->error);
    }
    
    echo "<p class='ok'>✓ Pedido marcado como pagado</p>";
    
    // Liberar mesa si existe
    if ($pedido['mesa_id']) {
        $stmt_mesa = $conn->prepare("UPDATE mesas SET ocupada = 0 WHERE id = ?");
        $stmt_mesa->bind_param("i", $pedido['mesa_id']);
        
        if (!$stmt_mesa->execute()) {
            throw new Exception("Error liberando mesa: " . $stmt_mesa->error);
        }
        
        echo "<p class='ok'>✓ Mesa liberada (ID: {$pedido['mesa_id']})</p>";
        $stmt_mesa->close();
    }
    
    // Confirmar transacción
    $conn->commit();
    
    echo "<h2 class='ok'>✅ PAGO PROCESADO EXITOSAMENTE</h2>";
    echo "<p>Pago ID: <strong>$pago_id</strong></p>";
    echo "<p>Número de transacción: <strong>$numero_transaccion</strong></p>";
    echo "<p><a href='ver_comprobante_pago.php?pedido_id=$pedido_id'>Ver Comprobante</a></p>";
    echo "<p><a href='mesero.php'>Volver al Panel</a></p>";
    
} catch (Exception $e) {
    $conn->rollback();
    echo "<h2 class='error'>❌ ERROR AL PROCESAR PAGO</h2>";
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    echo "<pre>";
    echo "Stack trace:\n";
    echo $e->getTraceAsString();
    echo "</pre>";
}

$conn->close();
?>
