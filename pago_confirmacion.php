<?php
/**
 * Página de Confirmación de Pago Bold
 * Muestra el resultado del pago después de que el cliente regresa de Bold
 */

session_start();
require_once 'config.php';
require_once 'includes/info_negocio.php';

$pedidoId = $_GET['pedido_id'] ?? null;
$conn = getDatabaseConnection();

// Obtener datos del pedido y pago
$stmt = $conn->prepare("
    SELECT p.*, pb.estado as estado_pago, pb.metodo_pago, pb.datos_tarjeta, pb.bold_transaction_id
    FROM pedidos p
    LEFT JOIN pagos_bold pb ON p.id = pb.pedido_id
    WHERE p.id = ?
");
$stmt->bind_param("i", $pedidoId);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();

if (!$pedido) {
    header('Location: index.php');
    exit;
}

$estadoPago = $pedido['estado_pago'] ?? 'pendiente';
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Pago - <?php echo htmlspecialchars($info_negocio['nombre_restaurante']); ?></title>
    <link rel="stylesheet" href="css/admin-modern.css">
    <style>
        .confirmation-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .status-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        
        .status-aprobado { color: #28a745; }
        .status-pendiente { color: #ffc107; }
        .status-rechazado { color: #dc3545; }
        
        .order-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: left;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .btn-group {
            margin-top: 30px;
            display: flex;
            gap: 10px;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="confirmation-container">
        <?php if ($estadoPago === 'aprobado'): ?>
            <div class="status-icon status-aprobado">✅</div>
            <h1>¡Pago Exitoso!</h1>
            <p>Tu pago ha sido procesado correctamente.</p>
        <?php elseif ($estadoPago === 'pendiente'): ?>
            <div class="status-icon status-pendiente">⏳</div>
            <h1>Pago Pendiente</h1>
            <p>Tu pago está siendo procesado. Te notificaremos cuando se confirme.</p>
        <?php else: ?>
            <div class="status-icon status-rechazado">❌</div>
            <h1>Pago Rechazado</h1>
            <p>Hubo un problema con tu pago. Por favor, intenta nuevamente.</p>
        <?php endif; ?>
        
        <div class="order-details">
            <h3>Detalles del Pedido</h3>
            <div class="detail-row">
                <span><strong>Número de Pedido:</strong></span>
                <span><?php echo htmlspecialchars($pedido['numero_pedido']); ?></span>
            </div>
            <div class="detail-row">
                <span><strong>Total:</strong></span>
                <span>$<?php echo number_format($pedido['total'], 0, ',', '.'); ?> COP</span>
            </div>
            <div class="detail-row">
                <span><strong>Estado del Pago:</strong></span>
                <span><?php echo ucfirst($estadoPago); ?></span>
            </div>
            <?php if ($pedido['metodo_pago']): ?>
            <div class="detail-row">
                <span><strong>Método de Pago:</strong></span>
                <span><?php echo htmlspecialchars($pedido['metodo_pago']); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($pedido['datos_tarjeta']): ?>
            <div class="detail-row">
                <span><strong>Tarjeta:</strong></span>
                <span>**** <?php echo htmlspecialchars($pedido['datos_tarjeta']); ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="btn-group">
            <a href="index.php" class="btn btn-primary">Volver al Menú</a>
            <?php if ($estadoPago === 'aprobado'): ?>
                <a href="ver_comprobante_pago.php?id=<?php echo $pedidoId; ?>" class="btn btn-secondary">Ver Comprobante</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
