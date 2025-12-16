<?php
/**
 * Webhook Bold
 * Recibe notificaciones de Bold sobre el estado de los pagos
 */

require_once '../config.php';
require_once '../includes/bold_client.php';

// Log de webhook para debugging
$logFile = __DIR__ . '/../logs/bold_webhook.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

try {
    // Obtener datos del webhook
    $payload = file_get_contents('php://input');
    $signature = $_SERVER['HTTP_X_BOLD_SIGNATURE'] ?? '';
    
    // Log de la petición
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Webhook recibido\n", FILE_APPEND);
    file_put_contents($logFile, "Payload: " . $payload . "\n", FILE_APPEND);
    file_put_contents($logFile, "Signature: " . $signature . "\n\n", FILE_APPEND);
    
    // Validar firma
    $bold = new BoldClient();
    if (!$bold->validarWebhook($payload, $signature)) {
        throw new Exception('Firma de webhook inválida');
    }
    
    // Decodificar datos
    $data = json_decode($payload, true);
    
    if (!$data) {
        throw new Exception('Datos de webhook inválidos');
    }
    
    $conn = getDatabaseConnection();
    
    // Extraer información del pago
    $transactionId = $data['data']['id'] ?? '';
    $estado = $data['data']['status'] ?? '';
    $metodoPago = $data['data']['paymentMethod'] ?? '';
    $datosTarjeta = $data['data']['card']['lastFourDigits'] ?? '';
    
    // Mapear estados de Bold a nuestro sistema
    $estadoMap = [
        'APPROVED' => 'aprobado',
        'DECLINED' => 'rechazado',
        'PENDING' => 'pendiente',
        'CANCELLED' => 'cancelado',
        'REFUNDED' => 'reembolsado'
    ];
    
    $estadoLocal = $estadoMap[$estado] ?? 'pendiente';
    
    // Actualizar pago en BD
    $stmt = $conn->prepare("UPDATE pagos_bold SET estado = ?, metodo_pago = ?, datos_tarjeta = ?, datos_bold = ? WHERE bold_transaction_id = ?");
    $stmt->bind_param("sssss", $estadoLocal, $metodoPago, $datosTarjeta, $payload, $transactionId);
    $stmt->execute();
    
    // Si el pago fue aprobado, actualizar estado del pedido
    if ($estadoLocal === 'aprobado') {
        // Obtener pedido_id
        $stmt = $conn->prepare("SELECT pedido_id FROM pagos_bold WHERE bold_transaction_id = ?");
        $stmt->bind_param("s", $transactionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $pago = $result->fetch_assoc();
        
        if ($pago) {
            // Actualizar estado del pedido a 'pagado'
            $stmt = $conn->prepare("UPDATE pedidos SET estado = 'pagado' WHERE id = ?");
            $stmt->bind_param("i", $pago['pedido_id']);
            $stmt->execute();
            
            file_put_contents($logFile, "Pedido #{$pago['pedido_id']} marcado como pagado\n\n", FILE_APPEND);
        }
    }
    
    $conn->close();
    
    // Responder a Bold
    http_response_code(200);
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    file_put_contents($logFile, "ERROR: " . $e->getMessage() . "\n\n", FILE_APPEND);
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
