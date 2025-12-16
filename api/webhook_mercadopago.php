<?php
/**
 * Webhook Mercado Pago
 */
require_once '../config.php';

$logFile = __DIR__ . '/../logs/mercadopago_webhook.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

try {
    $input = file_get_contents('php://input');
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Webhook MP\n" . $input . "\n\n", FILE_APPEND);
    
    $data = json_decode($input, true);
    
    if ($data['type'] === 'payment') {
        $paymentId = $data['data']['id'];
        
        require_once '../includes/mercadopago_client.php';
        $mp = new MercadoPagoClient();
        $payment = $mp->consultarPago($paymentId);
        
        $estado = $payment['status'];
        $estadoMap = [
            'approved' => 'aprobado',
            'rejected' => 'rechazado',
            'pending' => 'pendiente',
            'cancelled' => 'cancelado'
        ];
        
        $estadoLocal = $estadoMap[$estado] ?? 'pendiente';
        
        $conn = getDatabaseConnection();
        $externalRef = $payment['external_reference'];
        
        // Buscar pedido
        $stmt = $conn->prepare("SELECT id FROM pedidos WHERE numero_pedido = ?");
        $stmt->bind_param("s", $externalRef);
        $stmt->execute();
        $pedido = $stmt->get_result()->fetch_assoc();
        
        if ($pedido && $estadoLocal === 'aprobado') {
            $stmt = $conn->prepare("UPDATE pedidos SET estado = 'pagado' WHERE id = ?");
            $stmt->bind_param("i", $pedido['id']);
            $stmt->execute();
        }
        
        $conn->close();
    }
    
    http_response_code(200);
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    file_put_contents($logFile, "ERROR: " . $e->getMessage() . "\n\n", FILE_APPEND);
    http_response_code(400);
}
?>
