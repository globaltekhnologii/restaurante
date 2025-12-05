<?php
// api/get_pedidos_mesero.php - Endpoint para obtener pedidos del mesero
session_start();
header('Content-Type: application/json');

require_once '../auth_helper.php';
require_once '../config.php';

// Verificar sesión
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== TRUE) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$mesero_id = $_SESSION['user_id'];
$conn = getDatabaseConnection();

try {
    // Obtener pedidos del mesero
    $sql = "SELECT p.*, m.numero_mesa 
            FROM pedidos p 
            LEFT JOIN mesas m ON p.mesa_id = m.id 
            WHERE p.usuario_id = ? 
            AND (p.estado IN ('pendiente', 'confirmado', 'preparando', 'listo', 'entregado') 
                 OR (p.estado = 'entregado' AND p.pagado = 0))
            ORDER BY p.fecha_pedido DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $mesero_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $pedidos = [];
    while ($row = $result->fetch_assoc()) {
        $pedidos[] = [
            'id' => (int)$row['id'],
            'numero_pedido' => $row['numero_pedido'],
            'nombre_cliente' => $row['nombre_cliente'],
            'telefono' => $row['telefono'],
            'total' => (float)$row['total'],
            'estado' => $row['estado'],
            'pagado' => (bool)$row['pagado'],
            'mesa' => $row['numero_mesa'] ?: 'Domicilio',
            'mesa_id' => $row['mesa_id'],
            'hora' => date('H:i', strtotime($row['fecha_pedido'])),
            'fecha_pedido' => $row['fecha_pedido']
        ];
    }
    
    $stmt->close();
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'pedidos' => $pedidos,
        'total' => count($pedidos),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
