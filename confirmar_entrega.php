<?php
session_start();

// Verificar sesión y rol de domiciliario o admin
require_once 'auth_helper.php';
verificarSesion();
verificarRolORedirect(['domiciliario', 'admin'], 'login.php');

require_once 'config.php';

// Validar parámetro
if (!isset($_GET['id'])) {
    $redirect = ($_SESSION['rol'] === 'admin') ? 'admin_pedidos.php' : 'domiciliario.php';
    header("Location: $redirect?error=" . urlencode("ID de pedido no especificado"));
    exit;
}

$pedido_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];
$user_rol = $_SESSION['rol'];

$conn = getDatabaseConnection();

// Verificar que el pedido existe y está en camino
// Si es domiciliario, verificar que está asignado a él
if ($user_rol === 'domiciliario') {
    $stmt = $conn->prepare("SELECT * FROM pedidos WHERE id = ? AND domiciliario_id = ? AND estado = 'en_camino'");
    $stmt->bind_param("ii", $pedido_id, $user_id);
} else {
    // Si es admin, solo verificar que el pedido está en camino
    $stmt = $conn->prepare("SELECT * FROM pedidos WHERE id = ? AND estado = 'en_camino'");
    $stmt->bind_param("i", $pedido_id);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    
    // Log para diagnóstico
    error_log("CONFIRMAR_ENTREGA: Pedido no encontrado. ID: $pedido_id, User: $user_id, Rol: $user_rol");
    
    $redirect = ($_SESSION['rol'] === 'admin') ? 'admin_pedidos.php' : 'domiciliario.php';
    header("Location: $redirect?error=" . urlencode("Pedido no encontrado o no está en camino"));
    exit;
}

$stmt->close();

// Actualizar estado a entregado y registrar hora de entrega
$stmt = $conn->prepare("UPDATE pedidos SET estado = 'entregado', hora_entrega = NOW() WHERE id = ?");
$stmt->bind_param("i", $pedido_id);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    $redirect = ($_SESSION['rol'] === 'admin') ? 'admin_pedidos.php' : 'domiciliario.php';
    header("Location: $redirect?success=" . urlencode("Entrega confirmada exitosamente"));
    exit;
} else {
    $error = $stmt->error;
    $stmt->close();
    $conn->close();
    $redirect = ($_SESSION['rol'] === 'admin') ? 'admin_pedidos.php' : 'domiciliario.php';
    header("Location: $redirect?error=" . urlencode("Error al confirmar entrega: " . $error));
    exit;
}
?>
