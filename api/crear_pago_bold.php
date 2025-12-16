<?php
/**
 * API Endpoint: Crear Pago con Bold
 * Crea una orden de pago en Bold y retorna la URL de checkout
 */

header('Content-Type: application/json');
require_once '../config.php';
require_once '../includes/bold_client.php';

try {
    // Verificar método POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    // Obtener datos del pedido
    $input = json_decode(file_get_contents('php://input'), true);
    
    $pedidoId = $input['pedido_id'] ?? null;
    $monto = $input['monto'] ?? null;
    
    if (!$pedidoId || !$monto) {
        throw new Exception('Datos incompletos: pedido_id y monto son requeridos');
    }
    
    $conn = getDatabaseConnection();
    
    // Obtener datos del pedido
    $stmt = $conn->prepare("SELECT * FROM pedidos WHERE id = ?");
    $stmt->bind_param("i", $pedidoId);
    $stmt->execute();
    $pedido = $stmt->get_result()->fetch_assoc();
    
    if (!$pedido) {
        throw new Exception('Pedido no encontrado');
    }
    
    // Cargar configuración de URLs
    $envFile = __DIR__ . '/../.env.bold';
    $config = parse_ini_file($envFile);
    
    // Preparar datos para Bold
    $datosOrden = [
        'monto' => $monto,
        'descripcion' => 'Pedido #' . $pedido['numero_pedido'],
        'referencia' => $pedido['numero_pedido'],
        'url_retorno' => $config['BOLD_RETURN_URL'] . '?pedido_id=' . $pedidoId,
        'url_webhook' => $config['BOLD_WEBHOOK_URL'],
        'cliente_nombre' => $pedido['nombre_cliente'],
        'cliente_email' => $pedido['email'] ?? '',
        'cliente_telefono' => $pedido['telefono'],
        'tipo_documento' => $pedido['tipo_documento'] ?? 'CC',
        'numero_documento' => $pedido['numero_documento'] ?? ''
    ];
    
    // Crear orden en Bold
    $bold = new BoldClient();
    $respuesta = $bold->crearOrdenPago($datosOrden);
    
    // Guardar transacción en BD
    $stmt = $conn->prepare("INSERT INTO pagos_bold (pedido_id, bold_transaction_id, bold_order_id, monto, estado, datos_bold) VALUES (?, ?, ?, ?, 'pendiente', ?)");
    
    $transactionId = $respuesta['data']['id'] ?? '';
    $orderId = $respuesta['data']['orderId'] ?? '';
    $datosBold = json_encode($respuesta);
    
    $stmt->bind_param("issds", $pedidoId, $transactionId, $orderId, $monto, $datosBold);
    $stmt->execute();
    
    // Obtener URL de checkout
    $checkoutUrl = $bold->getCheckoutUrl($orderId);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'checkout_url' => $checkoutUrl,
            'transaction_id' => $transactionId,
            'order_id' => $orderId
        ],
        'message' => 'Orden de pago creada exitosamente'
    ]);
    
    $conn->close();
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
