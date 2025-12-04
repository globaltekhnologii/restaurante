<?php
session_start();

// Verificar sesión y rol de domiciliario
require_once 'auth_helper.php';
verificarSesion();
verificarRolORedirect(['domiciliario'], 'login.php');

require_once 'config.php';

// Validar parámetro
if (!isset($_GET['id'])) {
    header("Location: domiciliario.php?error=" . urlencode("ID de pedido no especificado"));
    exit;
}

$pedido_id = intval($_GET['id']);
$domiciliario_id = $_SESSION['user_id'];

$conn = getDatabaseConnection();

// Verificar que el pedido está asignado al domiciliario y en camino
$stmt = $conn->prepare("SELECT * FROM pedidos WHERE id = ? AND domiciliario_id = ? AND estado = 'en_camino'");
$stmt->bind_param("ii", $pedido_id, $domiciliario_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    header("Location: domiciliario.php?error=" . urlencode("Pedido no encontrado o no está en camino"));
    exit;
}

$stmt->close();

// Actualizar estado a entregado y registrar hora de entrega
$stmt = $conn->prepare("UPDATE pedidos SET estado = 'entregado', hora_entrega = NOW() WHERE id = ?");
$stmt->bind_param("i", $pedido_id);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header("Location: domiciliario.php?success=" . urlencode("Entrega confirmada exitosamente"));
    exit;
} else {
    $error = $stmt->error;
    $stmt->close();
    $conn->close();
    header("Location: domiciliario.php?error=" . urlencode("Error al confirmar entrega: " . $error));
    exit;
}
?>
