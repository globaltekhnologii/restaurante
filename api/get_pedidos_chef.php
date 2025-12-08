<?php
// api/get_pedidos_chef.php - Endpoint para obtener pedidos del chef
session_start();
header('Content-Type: application/json');

require_once '../auth_helper.php';
require_once '../config.php';

// Verificar sesión
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== TRUE) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$conn = getDatabaseConnection();

try {
    // Obtener pedidos pendientes y en preparación
    $sql = "SELECT p.*, m.numero_mesa, u.nombre as mesero_nombre
            FROM pedidos p 
            LEFT JOIN mesas m ON p.mesa_id = m.id 
            LEFT JOIN usuarios u ON p.usuario_id = u.id
            WHERE p.estado IN ('confirmado', 'preparando')
            ORDER BY p.fecha_pedido ASC";
    
    $result = $conn->query($sql);
    
    $pedidos = [];
    while ($row = $result->fetch_assoc()) {
        // Obtener items del pedido
        $stmt = $conn->prepare("SELECT * FROM pedidos_items WHERE pedido_id = ?");
        $pedido_id = $row['id'];
        $stmt->bind_param("i", $pedido_id);
        $stmt->execute();
        $items_result = $stmt->get_result();
        
        $items = [];
        while ($item = $items_result->fetch_assoc()) {
            $items[] = [
                'nombre' => $item['plato_nombre'],
                'cantidad' => (int)$item['cantidad'],
                'notas' => $item['notas_item'] ?? ''
            ];
        }
        $stmt->close();
        
        $pedidos[] = [
            'id' => (int)$row['id'],
            'numero_pedido' => $row['numero_pedido'],
            'mesa' => $row['numero_mesa'] ?: 'Domicilio',
            'mesero' => $row['mesero_nombre'],
            'estado' => $row['estado'],
            'items' => $items,
            'notas' => $row['notas'],
            'hora' => date('H:i', strtotime($row['fecha_pedido'])),
            'tiempo_espera' => calcularTiempoEspera($row['fecha_pedido'])
        ];
    }
    
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

function calcularTiempoEspera($fecha_pedido) {
    $inicio = new DateTime($fecha_pedido);
    $ahora = new DateTime();
    $diff = $ahora->diff($inicio);
    
    $minutos = $diff->i + ($diff->h * 60);
    return $minutos;
}
?>
