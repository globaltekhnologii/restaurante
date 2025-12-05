<?php
session_start();
require_once 'auth_helper.php';
verificarSesion();
verificarRolORedirect(['admin'], 'login.php');

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: validar_pagos_anticipados.php");
    exit;
}

$pedido_id = intval($_POST['pedido_id']);
$accion = $_POST['accion']; // 'aprobar' o 'rechazar'

$conn = getDatabaseConnection();

// Obtener información del pedido
$stmt = $conn->prepare("SELECT * FROM pedidos WHERE id = ? AND pago_anticipado = 1 AND pago_validado = 0");
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();

if (!$pedido) {
    header("Location: validar_pagos_anticipados.php?error=" . urlencode("Pedido no encontrado o ya validado"));
    exit;
}

$conn->begin_transaction();

try {
    if ($accion === 'aprobar') {
        // Aprobar el pago
        // 1. Marcar como validado y confirmado
        $stmt = $conn->prepare("UPDATE pedidos SET pago_validado = 1, estado = 'confirmado', pagado = 1 WHERE id = ?");
        $stmt->bind_param("i", $pedido_id);
        $stmt->execute();
        
        // 2. Registrar el pago en la tabla de pagos
        $numero_transaccion = 'TXN-' . date('Ymd') . '-' . str_pad($pedido_id, 4, '0', STR_PAD_LEFT) . '-' . rand(1000, 9999);
        
        $stmt = $conn->prepare("INSERT INTO pagos (pedido_id, numero_transaccion, metodo_pago, referencia_pago, monto, usuario_id, fecha_pago) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("isssdi", 
            $pedido_id,
            $numero_transaccion,
            $pedido['metodo_pago_seleccionado'],
            $pedido['referencia_pago_anticipado'],
            $pedido['total'],
            $_SESSION['user_id']
        );
        $stmt->execute();
        
        $conn->commit();
        header("Location: validar_pagos_anticipados.php?success=" . urlencode("Pago aprobado exitosamente"));
        
    } else if ($accion === 'rechazar') {
        // Rechazar el pago
        // 1. Marcar pedido como cancelado
        $stmt = $conn->prepare("UPDATE pedidos SET estado = 'cancelado', pago_validado = 0 WHERE id = ?");
        $stmt->bind_param("i", $pedido_id);
        $stmt->execute();
        
        // 2. Si es pedido de mesa, liberar la mesa
        if ($pedido['mesa_id']) {
            $stmt = $conn->prepare("UPDATE mesas SET ocupada = 0 WHERE id = ?");
            $stmt->bind_param("i", $pedido['mesa_id']);
            $stmt->execute();
        }
        
        $conn->commit();
        header("Location: validar_pagos_anticipados.php?success=" . urlencode("Pago rechazado y pedido cancelado"));
    }
    
} catch (Exception $e) {
    $conn->rollback();
    header("Location: validar_pagos_anticipados.php?error=" . urlencode("Error al procesar: " . $e->getMessage()));
}

$conn->close();
?>
