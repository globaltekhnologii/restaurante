<?php
// Script de diagnóstico para reportes
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config.php';

try {
    $conn = getDatabaseConnection();
    
    $fecha_inicio = date('Y-m-d');
    $fecha_fin = date('Y-m-d');
    
    // Test 1: Verificar conexión
    $test1 = ['test' => 'conexion', 'status' => 'OK'];
    
    // Test 2: Verificar tabla pedidos
    $sql = "SELECT COUNT(*) as total FROM pedidos";
    $result = $conn->query($sql);
    $test2 = ['test' => 'tabla_pedidos', 'total' => $result->fetch_assoc()['total']];
    
    // Test 3: Verificar tabla pagos
    $sql = "SELECT COUNT(*) as total FROM pagos";
    $result = $conn->query($sql);
    $test3 = ['test' => 'tabla_pagos', 'total' => $result->fetch_assoc()['total']];
    
    // Test 4: Probar consulta de ventas
    $sql = "SELECT 
                DATE(p.fecha_pedido) as fecha,
                COUNT(DISTINCT p.id) as total_pedidos,
                SUM(p.total) as total_ventas
            FROM pedidos p
            WHERE DATE(p.fecha_pedido) BETWEEN ? AND ?
            AND p.pagado = 1
            GROUP BY DATE(p.fecha_pedido)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $result = $stmt->get_result();
    $ventas = [];
    while ($row = $result->fetch_assoc()) {
        $ventas[] = $row;
    }
    $test4 = ['test' => 'consulta_ventas', 'resultados' => count($ventas), 'datos' => $ventas];
    
    // Test 5: Verificar métodos de pago
    $sql = "SELECT DISTINCT metodo_pago FROM pagos";
    $result = $conn->query($sql);
    $metodos = [];
    while ($row = $result->fetch_assoc()) {
        $metodos[] = $row['metodo_pago'];
    }
    $test5 = ['test' => 'metodos_pago', 'metodos' => $metodos];
    
    echo json_encode([
        'success' => true,
        'tests' => [$test1, $test2, $test3, $test4, $test5]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
?>
