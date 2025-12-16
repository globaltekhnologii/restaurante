<?php
/**
 * API: Crear Pago con Mercado Pago
 */
header('Content-Type: application/json');
require_once '../config.php';
require_once '../includes/mercadopago_client.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $pedidoId = $input['pedido_id'] ?? null;
    $monto = $input['monto'] ?? null;
    
    if (!$pedidoId || !$monto) {
        throw new Exception('Datos incompletos');
    }
    
    $conn = getDatabaseConnection();
    $stmt = $conn->prepare("SELECT * FROM pedidos WHERE id = ?");
    $stmt->bind_param("i", $pedidoId);
    $stmt->execute();
    $pedido = $stmt->get_result()->fetch_assoc();
    
    if (!$pedido) {
        throw new Exception('Pedido no encontrado');
    }
    
    $datosPreferencia = [
        'monto' => $monto,
        'descripcion' => 'Pedido #' . $pedido['numero_pedido'],
        'referencia' => $pedido['numero_pedido'],
        'url_retorno' => 'http://localhost/Restaurante/pago_confirmacion.php?pedido_id=' . $pedidoId,
        'url_webhook' => 'http://localhost/Restaurante/api/webhook_mercadopago.php',
        'cliente_nombre' => $pedido['nombre_cliente'],
        'cliente_email' => $pedido['email'] ?? '',
        'cliente_telefono' => $pedido['telefono'],
        'tipo_documento' => $pedido['tipo_documento'] ?? 'CC',
        'numero_documento' => $pedido['numero_documento'] ?? ''
    ];
    
    $mp = new MercadoPagoClient();
    $respuesta = $mp->crearPreferencia($datosPreferencia);
    
    // Guardar en BD
    $stmt = $conn->prepare("INSERT INTO pagos_bold (pedido_id, bold_transaction_id, bold_order_id, monto, estado, datos_bold) VALUES (?, ?, ?, ?, 'pendiente', ?)");
    $preferenceId = $respuesta['id'] ?? '';
    $initPoint = $respuesta['init_point'] ?? '';
    $datosMp = json_encode($respuesta);
    $stmt->bind_param("issds", $pedidoId, $preferenceId, $initPoint, $monto, $datosMp);
    $stmt->execute();
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'init_point' => $initPoint,
            'preference_id' => $preferenceId
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
