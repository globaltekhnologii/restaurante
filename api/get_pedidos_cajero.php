<?php
// api/get_pedidos_cajero.php - Endpoint para obtener todos los pedidos pendientes de pago
session_start();
header('Content-Type: application/json');

require_once '../auth_helper.php';
require_once '../config.php';

// Verificar sesión y rol de cajero
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== TRUE) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

if ($_SESSION['rol'] !== 'cajero') {
    echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
    exit;
}

$conn = getDatabaseConnection();

try {
    // Obtener TODOS los pedidos no pagados (de todas las mesas y domicilios)
    $sql = "SELECT p.*, 
                   m.numero_mesa,
                   u.nombre as mesero_nombre,
                   d.nombre as domiciliario_nombre
            FROM pedidos p 
            LEFT JOIN mesas m ON p.mesa_id = m.id 
            LEFT JOIN usuarios u ON p.usuario_id = u.id
            LEFT JOIN usuarios d ON p.domiciliario_id = d.id
            WHERE p.pagado = 0 
            AND p.estado IN ('pendiente', 'confirmado', 'preparando', 'listo', 'entregado', 'en_camino')
            ORDER BY p.fecha_pedido DESC";
    
    $result = $conn->query($sql);
    
    $pedidos = [];
    $total_pendiente = 0;
    $total_efectivo_esperado = 0;
    $total_tarjeta_esperado = 0;
    
    while ($row = $result->fetch_assoc()) {
        $pedido = [
            'id' => (int)$row['id'],
            'numero_pedido' => $row['numero_pedido'],
            'nombre_cliente' => $row['nombre_cliente'],
            'telefono' => $row['telefono'],
            'direccion' => $row['direccion'],
            'total' => (float)$row['total'],
            'estado' => $row['estado'],
            'pagado' => (bool)$row['pagado'],
            'tipo_pedido' => $row['tipo_pedido'],
            'mesa' => $row['numero_mesa'] ?: 'Domicilio',
            'mesa_id' => $row['mesa_id'],
            'mesero' => $row['mesero_nombre'] ?: 'N/A',
            'domiciliario' => $row['domiciliario_nombre'] ?: 'N/A',
            'hora' => date('H:i', strtotime($row['fecha_pedido'])),
            'fecha_pedido' => $row['fecha_pedido'],
            'notas' => $row['notas'],
            'origen' => $row['origen'] // Nuevo campo included
        ];
        
        $pedidos[] = $pedido;
        $total_pendiente += $pedido['total'];
    }
    
    // Obtener estadísticas del día
    $hoy = date('Y-m-d');
    
    // Total cobrado hoy
    $stmt = $conn->prepare("SELECT 
                            COUNT(*) as total_pagos,
                            SUM(monto) as total_cobrado,
                            SUM(CASE WHEN metodo_pago = 'efectivo' THEN monto ELSE 0 END) as total_efectivo,
                            SUM(CASE WHEN metodo_pago = 'tarjeta' THEN monto ELSE 0 END) as total_tarjeta,
                            SUM(CASE WHEN metodo_pago = 'transferencia' THEN monto ELSE 0 END) as total_transferencia
                            FROM pagos 
                            WHERE DATE(fecha_pago) = ?");
    $stmt->bind_param("s", $hoy);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'pedidos' => $pedidos,
        'total_pedidos' => count($pedidos),
        'total_pendiente' => $total_pendiente,
        'estadisticas_hoy' => [
            'total_pagos' => (int)$stats['total_pagos'],
            'total_cobrado' => (float)$stats['total_cobrado'],
            'total_efectivo' => (float)$stats['total_efectivo'],
            'total_tarjeta' => (float)$stats['total_tarjeta'],
            'total_transferencia' => (float)$stats['total_transferencia']
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
