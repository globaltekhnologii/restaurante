<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== TRUE) {
    header("Location: login.php");
    exit;
}

// Verificar parámetros
if (!isset($_GET['id']) || !isset($_GET['estado'])) {
    header("Location: admin_pedidos.php?error=Parámetros inválidos");
    exit;
}

$pedido_id = intval($_GET['id']);
$nuevo_estado = trim($_GET['estado']);

// Validar estado
$estados_validos = ['pendiente', 'confirmado', 'preparando', 'en_camino', 'listo_recoger', 'entregado', 'cancelado'];

if (!in_array($nuevo_estado, $estados_validos)) {
    header("Location: admin_pedidos.php?error=Estado no válido");
    exit;
}

// Usar configuración centralizada
require_once 'config.php';
$conn = getDatabaseConnection();

// Actualizar estado
if ($nuevo_estado === 'entregado') {
    // Si se marca como entregado, también actualizar hora_entrega
    $stmt = $conn->prepare("UPDATE pedidos SET estado = ?, hora_entrega = NOW(), fecha_actualizacion = NOW() WHERE id = ?");
    $stmt->bind_param("si", $nuevo_estado, $pedido_id);
} else {
    // Para otros estados, solo actualizar el estado
    $stmt = $conn->prepare("UPDATE pedidos SET estado = ?, fecha_actualizacion = NOW() WHERE id = ?");
    $stmt->bind_param("si", $nuevo_estado, $pedido_id);
}

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    
    $redirect_url = isset($_GET['redirect']) ? $_GET['redirect'] : 'admin_pedidos.php';
    header("Location: " . $redirect_url . (strpos($redirect_url, '?') !== false ? '&' : '?') . "success=Estado actualizado correctamente");
    exit;
} else {
    $stmt->close();
    $conn->close();
    
    $redirect_url = isset($_GET['redirect']) ? $_GET['redirect'] : 'admin_pedidos.php';
    header("Location: " . $redirect_url . (strpos($redirect_url, '?') !== false ? '&' : '?') . "error=Error al actualizar el estado");
    exit;
}
?>