<?php
session_start();
require_once '../auth_helper.php';
verificarSesion();
verificarRolORedirect(['admin', 'cajero'], '../login.php');

require_once '../config.php';
$conn = getDatabaseConnection();

header('Content-Type: application/json');

try {
    $hoy = date('Y-m-d');
    $inicio_mes = date('Y-m-01');
    $inicio_semana = date('Y-m-d', strtotime('monday this week'));
    
    // Ventas de hoy
    $sql_hoy = "SELECT 
                    COUNT(*) as pedidos,
                    COALESCE(SUM(total), 0) as total
                FROM pedidos 
                WHERE DATE(fecha_pedido) = ? AND pagado = 1";
    
    $stmt = $conn->prepare($sql_hoy);
    $stmt->bind_param("s", $hoy);
    $stmt->execute();
    $ventas_hoy = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // Ventas del mes
    $sql_mes = "SELECT 
                    COUNT(*) as pedidos,
                    COALESCE(SUM(total), 0) as total
                FROM pedidos 
                WHERE DATE(fecha_pedido) >= ? AND pagado = 1";
    
    $stmt = $conn->prepare($sql_mes);
    $stmt->bind_param("s", $inicio_mes);
    $stmt->execute();
    $ventas_mes = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // Ventas de la semana
    $sql_semana = "SELECT 
                    COUNT(*) as pedidos,
                    COALESCE(SUM(total), 0) as total
                FROM pedidos 
                WHERE DATE(fecha_pedido) >= ? AND pagado = 1";
    
    $stmt = $conn->prepare($sql_semana);
    $stmt->bind_param("s", $inicio_semana);
    $stmt->execute();
    $ventas_semana = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // Últimos 7 días (para gráfico de tendencia)
    $sql_tendencia = "SELECT 
                        DATE(fecha_pedido) as fecha,
                        COUNT(*) as pedidos,
                        COALESCE(SUM(total), 0) as total
                    FROM pedidos 
                    WHERE DATE(fecha_pedido) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                    AND pagado = 1
                    GROUP BY DATE(fecha_pedido)
                    ORDER BY fecha ASC";
    
    $result = $conn->query($sql_tendencia);
    $tendencia = [];
    while ($row = $result->fetch_assoc()) {
        $tendencia[] = [
            'fecha' => $row['fecha'],
            'pedidos' => (int)$row['pedidos'],
            'total' => (float)$row['total']
        ];
    }
    
    // Pedidos pendientes
    $sql_pendientes = "SELECT COUNT(*) as total FROM pedidos WHERE pagado = 0";
    $pendientes = $conn->query($sql_pendientes)->fetch_assoc()['total'];
    
    // Método de pago más usado (hoy)
    $sql_metodos = "SELECT 
                        metodo_pago,
                        COUNT(*) as cantidad,
                        SUM(monto) as total
                    FROM pagos 
                    WHERE DATE(fecha_pago) = ?
                    GROUP BY metodo_pago
                    ORDER BY cantidad DESC";
    
    $stmt = $conn->prepare($sql_metodos);
    $stmt->bind_param("s", $hoy);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $metodos_pago = [];
    while ($row = $result->fetch_assoc()) {
        $metodos_pago[] = [
            'metodo' => $row['metodo_pago'],
            'cantidad' => (int)$row['cantidad'],
            'total' => (float)$row['total']
        ];
    }
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'hoy' => [
            'pedidos' => (int)$ventas_hoy['pedidos'],
            'total' => (float)$ventas_hoy['total']
        ],
        'semana' => [
            'pedidos' => (int)$ventas_semana['pedidos'],
            'total' => (float)$ventas_semana['total']
        ],
        'mes' => [
            'pedidos' => (int)$ventas_mes['pedidos'],
            'total' => (float)$ventas_mes['total']
        ],
        'tendencia_7_dias' => $tendencia,
        'pedidos_pendientes' => (int)$pendientes,
        'metodos_pago_hoy' => $metodos_pago
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>
