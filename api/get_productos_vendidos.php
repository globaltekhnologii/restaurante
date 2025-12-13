<?php
session_start();
require_once '../auth_helper.php';
verificarSesion();
verificarRolORedirect(['admin', 'cajero'], '../login.php');

require_once '../config.php';
$conn = getDatabaseConnection();

header('Content-Type: application/json');

// Obtener parámetros
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
$limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 10;

try {
    // Top productos por cantidad vendida
    $sql_cantidad = "SELECT 
                        pi.plato_nombre,
                        pi.plato_id,
                        p.categoria,
                        SUM(pi.cantidad) as cantidad_vendida,
                        COALESCE(SUM(pi.precio_unitario * pi.cantidad), 0) as ingresos_totales,
                        COALESCE(AVG(pi.precio_unitario), 0) as precio_promedio,
                        COUNT(DISTINCT pi.pedido_id) as pedidos_distintos
                    FROM pedidos_items pi
                    JOIN pedidos ped ON pi.pedido_id = ped.id
                    LEFT JOIN platos p ON pi.plato_id = p.id
                    WHERE DATE(ped.fecha_pedido) BETWEEN ? AND ?
                    AND ped.pagado = 1
                    GROUP BY pi.plato_nombre, pi.plato_id, p.categoria
                    ORDER BY cantidad_vendida DESC
                    LIMIT ?";
    
    $stmt = $conn->prepare($sql_cantidad);
    $stmt->bind_param("ssi", $fecha_inicio, $fecha_fin, $limite);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $top_cantidad = [];
    while ($row = $result->fetch_assoc()) {
        $top_cantidad[] = [
            'nombre' => $row['plato_nombre'],
            'categoria' => $row['categoria'] ?: 'Sin categoría',
            'cantidad_vendida' => (int)$row['cantidad_vendida'],
            'ingresos_totales' => (float)$row['ingresos_totales'],
            'precio_promedio' => (float)$row['precio_promedio'],
            'pedidos_distintos' => (int)$row['pedidos_distintos']
        ];
    }
    $stmt->close();
    
    // Top productos por ingresos
    $sql_ingresos = "SELECT 
                        pi.plato_nombre,
                        pi.plato_id,
                        p.categoria,
                        SUM(pi.cantidad) as cantidad_vendida,
                        COALESCE(SUM(pi.precio_unitario * pi.cantidad), 0) as ingresos_totales,
                        COALESCE(AVG(pi.precio_unitario), 0) as precio_promedio
                    FROM pedidos_items pi
                    JOIN pedidos ped ON pi.pedido_id = ped.id
                    LEFT JOIN platos p ON pi.plato_id = p.id
                    WHERE DATE(ped.fecha_pedido) BETWEEN ? AND ?
                    AND ped.pagado = 1
                    GROUP BY pi.plato_nombre, pi.plato_id, p.categoria
                    ORDER BY ingresos_totales DESC
                    LIMIT ?";
    
    $stmt = $conn->prepare($sql_ingresos);
    $stmt->bind_param("ssi", $fecha_inicio, $fecha_fin, $limite);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $top_ingresos = [];
    while ($row = $result->fetch_assoc()) {
        $top_ingresos[] = [
            'nombre' => $row['plato_nombre'],
            'categoria' => $row['categoria'] ?: 'Sin categoría',
            'cantidad_vendida' => (int)$row['cantidad_vendida'],
            'ingresos_totales' => (float)$row['ingresos_totales'],
            'precio_promedio' => (float)$row['precio_promedio']
        ];
    }
    $stmt->close();
    
    // Ventas por categoría
    $sql_categorias = "SELECT 
                        COALESCE(p.categoria, 'Sin categoría') as categoria,
                        COUNT(DISTINCT pi.pedido_id) as pedidos,
                        SUM(pi.cantidad) as cantidad_vendida,
                        COALESCE(SUM(pi.precio_unitario * pi.cantidad), 0) as ingresos_totales
                    FROM pedidos_items pi
                    JOIN pedidos ped ON pi.pedido_id = ped.id
                    LEFT JOIN platos p ON pi.plato_id = p.id
                    WHERE DATE(ped.fecha_pedido) BETWEEN ? AND ?
                    AND ped.pagado = 1
                    GROUP BY p.categoria
                    ORDER BY ingresos_totales DESC";
    
    $stmt = $conn->prepare($sql_categorias);
    $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $por_categoria = [];
    while ($row = $result->fetch_assoc()) {
        $por_categoria[] = [
            'categoria' => $row['categoria'],
            'pedidos' => (int)$row['pedidos'],
            'cantidad_vendida' => (int)$row['cantidad_vendida'],
            'ingresos_totales' => (float)$row['ingresos_totales']
        ];
    }
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'periodo' => [
            'inicio' => $fecha_inicio,
            'fin' => $fecha_fin
        ],
        'top_cantidad' => $top_cantidad,
        'top_ingresos' => $top_ingresos,
        'por_categoria' => $por_categoria
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
