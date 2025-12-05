<?php
session_start();
require_once 'auth_helper.php';
verificarSesion();

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin_pedidos.php");
    exit;
}

$pedido_id = intval($_POST['pedido_id']);
$metodo_pago = $_POST['metodo_pago'] ?? '';
$monto = floatval($_POST['monto']);
$notas = $_POST['notas'] ?? '';
$usuario_id = $_SESSION['user_id'];

// Obtener referencia según el método de pago
$referencia_pago = '';
if ($metodo_pago !== 'efectivo') {
    $referencia_pago = $_POST['referencia_' . $metodo_pago] ?? '';
}

$conn = getDatabaseConnection();

// Verificar que el pedido existe
$stmt = $conn->prepare("SELECT * FROM pedidos WHERE id = ?");
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();

if (!$pedido) {
    die("Error: Pedido no encontrado");
}

// Verificar que no esté ya pagado
if ($pedido['pagado']) {
    header("Location: ver_pedido.php?id=$pedido_id&error=" . urlencode("Este pedido ya está pagado"));
    exit;
}

// Generar número de transacción
$numero_transaccion = 'TXN-' . date('Ymd') . '-' . str_pad($pedido_id, 4, '0', STR_PAD_LEFT) . '-' . rand(1000, 9999);

// Iniciar transacción
$conn->begin_transaction();

try {
    // Insertar pago
    $stmt = $conn->prepare("INSERT INTO pagos (pedido_id, numero_transaccion, metodo_pago, referencia_pago, monto, usuario_id, notas) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssdis", $pedido_id, $numero_transaccion, $metodo_pago, $referencia_pago, $monto, $usuario_id, $notas);
    $stmt->execute();
    
    // Actualizar pedido como pagado
    $stmt = $conn->prepare("UPDATE pedidos SET pagado = 1 WHERE id = ?");
    $stmt->bind_param("i", $pedido_id);
    $stmt->execute();
    
    // NUEVO: Liberar mesa si el pedido es de tipo mesa
    if ($pedido['mesa_id']) {
        $stmt_mesa = $conn->prepare("UPDATE mesas SET ocupada = 0 WHERE id = ?");
        $stmt_mesa->bind_param("i", $pedido['mesa_id']);
        $stmt_mesa->execute();
        $stmt_mesa->close();
    }
    
    // Confirmar transacción
    $conn->commit();
    
    // Redirigir al comprobante
    header("Location: ver_comprobante_pago.php?pedido_id=$pedido_id&success=1");
    exit;
    
} catch (Exception $e) {
    // Revertir cambios
    $conn->rollback();
    header("Location: registrar_pago.php?pedido_id=$pedido_id&error=" . urlencode("Error al procesar el pago: " . $e->getMessage()));
    exit;
}

$conn->close();
?>
