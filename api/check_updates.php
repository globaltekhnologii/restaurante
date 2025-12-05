<?php
session_start();
header('Content-Type: application/json');

require_once '../auth_helper.php';
verificarSesion();

require_once '../config.php';

$user_id = $_SESSION['user_id'];
$user_rol = $_SESSION['rol'];

$conn = getDatabaseConnection();

$response = [
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'notifications' => []
];

// Según el rol, obtener diferentes actualizaciones
switch ($user_rol) {
    case 'chef':
        // Pedidos nuevos confirmados
        $result = $conn->query("SELECT COUNT(*) as count FROM pedidos WHERE estado = 'confirmado'");
        $response['new_orders'] = $result->fetch_assoc()['count'];
        
        // Pedidos en preparación
        $result = $conn->query("SELECT COUNT(*) as count FROM pedidos WHERE estado = 'preparando'");
        $response['preparing'] = $result->fetch_assoc()['count'];
        
        // Últimos pedidos (últimos 5 minutos)
        $result = $conn->query("SELECT COUNT(*) as count FROM pedidos WHERE estado = 'confirmado' AND fecha_pedido >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
        $recent = $result->fetch_assoc()['count'];
        
        if ($recent > 0) {
            $response['notifications'][] = [
                'type' => 'new_order',
                'message' => "$recent nuevo(s) pedido(s) confirmado(s)",
                'sound' => 'new_order'
            ];
        }
        break;
        
    case 'domiciliario':
        // Entregas asignadas pendientes
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM pedidos WHERE domiciliario_id = ? AND estado IN ('preparando', 'en_camino')");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $response['my_deliveries'] = $stmt->get_result()->fetch_assoc()['count'];
        
        // Pedidos listos para recoger
        $result = $conn->query("SELECT COUNT(*) as count FROM pedidos WHERE domiciliario_id IS NULL AND estado = 'en_camino' AND direccion IS NOT NULL AND direccion != ''");
        $response['available_deliveries'] = $result->fetch_assoc()['count'];
        
        // Nuevas asignaciones (últimos 5 minutos)
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM pedidos WHERE domiciliario_id = ? AND fecha_pedido >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $recent = $stmt->get_result()->fetch_assoc()['count'];
        
        if ($recent > 0) {
            $response['notifications'][] = [
                'type' => 'new_delivery',
                'message' => "Nueva entrega asignada",
                'sound' => 'alert'
            ];
        }
        break;
        
    case 'mesero':
        // Pedidos del mesero
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM pedidos WHERE usuario_id = ? AND estado IN ('confirmado', 'preparando')");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $response['my_orders'] = $stmt->get_result()->fetch_assoc()['count'];
        
        // Pedidos listos para servir
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM pedidos WHERE usuario_id = ? AND estado = 'en_camino' AND mesa_id IS NOT NULL");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $ready = $stmt->get_result()->fetch_assoc()['count'];
        $response['orders_ready'] = $ready;
        
        if ($ready > 0) {
            $response['notifications'][] = [
                'type' => 'order_ready',
                'message' => "$ready pedido(s) listo(s) para servir",
                'sound' => 'order_ready'
            ];
        }
        break;
        
    case 'admin':
        // Resumen general
        $result = $conn->query("SELECT COUNT(*) as count FROM pedidos WHERE estado = 'pendiente'");
        $response['pending_orders'] = $result->fetch_assoc()['count'];
        
        $result = $conn->query("SELECT COUNT(*) as count FROM pedidos WHERE estado IN ('confirmado', 'preparando')");
        $response['active_orders'] = $result->fetch_assoc()['count'];
        
        $result = $conn->query("SELECT COUNT(*) as count FROM pedidos WHERE estado = 'entregado' AND pagado = 0");
        $response['unpaid_orders'] = $result->fetch_assoc()['count'];
        break;
}

$conn->close();

echo json_encode($response);
?>
