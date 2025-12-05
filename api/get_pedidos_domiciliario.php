<?php
// api/get_pedidos_domiciliario.php - Endpoint para obtener pedidos del domiciliario
session_start();
header('Content-Type: application/json');

require_once '../auth_helper.php';
require_once '../config.php';

// Verificar sesión
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== TRUE) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$domiciliario_id = $_SESSION['user_id'];
$conn = getDatabaseConnection();

try {
    // Obtener pedidos del domiciliario (listos y en camino)
    $sql = "SELECT p.*, u.nombre as mesero_nombre
            FROM pedidos p 
            LEFT JOIN usuarios u ON p.usuario_id = u.id
            WHERE p.tipo_pedido = 'domicilio' 
            AND (p.estado = 'listo' OR (p.estado = 'en_camino' AND p.domiciliario_id = ?))
            ORDER BY p.fecha_pedido ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $domiciliario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $pedidos = [];
    while ($row = $result->fetch_assoc()) {
        $pedidos[] = [
            'id' => (int)$row['id'],
            'numero_pedido' => $row['numero_pedido'],
            'nombre_cliente' => $row['nombre_cliente'],
            'telefono' => $row['telefono'],
            'direccion' => $row['direccion'],
            'total' => (float)$row['total'],
            'estado' => $row['estado'],
            'mesero' => $row['mesero_nombre'],
            'hora' => date('H:i', strtotime($row['fecha_pedido'])),
            'es_mio' => ($row['domiciliario_id'] == $domiciliario_id),
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
