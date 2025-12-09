<?php
session_start();
require_once '../auth_helper.php';
verificarSesion();
verificarRolORedirect(['admin', 'cajero'], '../login.php');

require_once '../config.php';
$conn = getDatabaseConnection();

header('Content-Type: application/json');

// Obtener parámetros
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-d');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');

try {
    // Validar fechas
    $inicio = new DateTime($fecha_inicio);
    $fin = new DateTime($fecha_fin);
    
    if ($inicio > $fin) {
        throw new Exception('La fecha de inicio no puede ser mayor que la fecha fin');
    }
    
    // Consulta principal: Ventas por día
    $sql = "SELECT 
                DATE(p.fecha_pedido) as fecha,
                COUNT(DISTINCT p.id) as total_pedidos,
                SUM(p.total) as total_ventas,
                AVG(p.total) as ticket_promedio,
                SUM(CASE WHEN pag.metodo_pago = 'efectivo' THEN pag.monto ELSE 0 END) as efectivo,
                SUM(CASE WHEN pag.metodo_pago = 'tarjeta' THEN pag.monto ELSE 0 END) as tarjeta,
                SUM(CASE WHEN pag.metodo_pago = 'transferencia' THEN pag.monto ELSE 0 END) as transferencia
            FROM pedidos p
            LEFT JOIN pagos pag ON p.id = pag.pedido_id
            WHERE DATE(p.fecha_pedido) BETWEEN ? AND ?
            AND p.pagado = 1
            GROUP BY DATE(p.fecha_pedido)
            ORDER BY fecha DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $ventas_por_dia = [];
    while ($row = $result->fetch_assoc()) {
        $ventas_por_dia[] = [
            'fecha' => $row['fecha'],
            'total_pedidos' => (int)$row['total_pedidos'],
            'total_ventas' => (float)$row['total_ventas'],
            'ticket_promedio' => (float)$row['ticket_promedio'],
            'efectivo' => (float)$row['efectivo'],
            'tarjeta' => (float)$row['tarjeta'],
            'transferencia' => (float)$row['transferencia']
        ];
    }
    $stmt->close();
    
    // Resumen del período
    $sql_resumen = "SELECT 
                        COUNT(DISTINCT p.id) as total_pedidos,
                        COALESCE(SUM(p.total), 0) as total_ventas,
                        COALESCE(AVG(p.total), 0) as ticket_promedio,
                        COALESCE(MIN(p.total), 0) as venta_minima,
                        COALESCE(MAX(p.total), 0) as venta_maxima
                    FROM pedidos p
                    WHERE DATE(p.fecha_pedido) BETWEEN ? AND ?
                    AND p.pagado = 1";
    
    $stmt = $conn->prepare($sql_resumen);
    $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $resumen = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // Desglose por tipo de pedido
    $sql_tipos = "SELECT 
                    tipo_pedido,
                    COUNT(*) as cantidad,
                    SUM(total) as total
                  FROM pedidos
                  WHERE DATE(fecha_pedido) BETWEEN ? AND ?
                  AND pagado = 1
                  GROUP BY tipo_pedido";
    
    $stmt = $conn->prepare($sql_tipos);
    $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $por_tipo = [];
    while ($row = $result->fetch_assoc()) {
        $por_tipo[] = [
            'tipo' => $row['tipo_pedido'] ?: 'mesa',
            'cantidad' => (int)$row['cantidad'],
            'total' => (float)$row['total']
        ];
    }
    $stmt->close();
    
    // Asegurar valores por defecto si no hay datos
    $resumen_final = [
        'total_pedidos' => (int)($resumen['total_pedidos'] ?? 0),
        'total_ventas' => (float)($resumen['total_ventas'] ?? 0),
        'ticket_promedio' => (float)($resumen['ticket_promedio'] ?? 0),
        'venta_minima' => (float)($resumen['venta_minima'] ?? 0),
        'venta_maxima' => (float)($resumen['venta_maxima'] ?? 0)
    ];

    echo json_encode([
        'success' => true,
        'periodo' => [
            'inicio' => $fecha_inicio,
            'fin' => $fecha_fin
        ],
        'resumen' => $resumen_final,
        'ventas_por_dia' => $ventas_por_dia,
        'por_tipo_pedido' => $por_tipo
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>
