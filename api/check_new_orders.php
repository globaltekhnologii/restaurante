<?php
// api/check_new_orders.php
require_once '../config.php';

// Verificar nuevos pedidos no notificados
$sql = "SELECT COUNT(*) as count FROM pedidos WHERE origen = 'chatbot' AND notificado = 0";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$new_orders = $row['count'];

if ($new_orders > 0) {
    // Obtener detalles de los últimos pedidos
    $sql_details = "SELECT * FROM pedidos WHERE origen = 'chatbot' AND notificado = 0 ORDER BY fecha_pedido DESC LIMIT 5";
    $res_details = $conn->query($sql_details);
    $orders = [];
    while($o = $res_details->fetch_assoc()) {
        $orders[] = $o;
        // Marcar como notificado (opcional: hacerlo después de que el usuario lo vea)
        // Por ahora NO marcamos aquí para asegurar que el usuario lo vea, 
        // el frontend debería llamar a otro endpoint para marcar como 'visto'
    }
    
    echo json_encode(['new_orders' => $new_orders, 'orders' => $orders]);
} else {
    echo json_encode(['new_orders' => 0]);
}
?>
