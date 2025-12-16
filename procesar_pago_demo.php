<?php
/**
 * Procesar resultado de pago demo
 */
session_start();
require_once 'config.php';

$pedidoId = $_POST['pedido_id'] ?? null;
$resultado = $_POST['resultado'] ?? 'rechazado';

if (!$pedidoId) {
    header('Location: index.php');
    exit;
}

$conn = getDatabaseConnection();

// Actualizar estado del pedido según resultado
if ($resultado === 'aprobado') {
    $stmt = $conn->prepare("UPDATE pedidos SET estado = 'pagado' WHERE id = ?");
    $stmt->bind_param("i", $pedidoId);
    $stmt->execute();
    
    // Registrar pago demo
    $stmt = $conn->prepare("INSERT INTO pagos_bold (pedido_id, bold_transaction_id, bold_order_id, monto, estado, metodo_pago, datos_bold) SELECT id, CONCAT('DEMO-', id), 'DEMO', total, 'aprobado', 'Demo', 'Pago simulado localmente' FROM pedidos WHERE id = ?");
    $stmt->bind_param("i", $pedidoId);
    $stmt->execute();
}

$conn->close();

// Redirigir a confirmación
header("Location: pago_confirmacion.php?pedido_id=" . $pedidoId . "&status=" . $resultado);
exit;
?>
