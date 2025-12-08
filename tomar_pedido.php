<?php
session_start();

// Verificar sesión y rol de domiciliario
require_once 'auth_helper.php';
verificarSesion();
verificarRolORedirect(['domiciliario'], 'login.php');

require_once 'config.php';

// Validar parámetros
if (!isset($_GET['pedido_id'])) {
    header("Location: domiciliario.php?error=" . urlencode("ID de pedido no especificado"));
    exit;
}

$pedido_id = intval($_GET['pedido_id']);
$domiciliario_id = $_SESSION['user_id'];

$conn = getDatabaseConnection();

// Verificar que el pedido existe y está disponible
$stmt = $conn->prepare("SELECT * FROM pedidos WHERE id = ? AND estado = 'listo' AND tipo_pedido = 'domicilio' AND domiciliario_id IS NULL");
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    header("Location: domiciliario.php?error=" . urlencode("Pedido no disponible o ya asignado"));
    exit;
}

$stmt->close();

// Asignar el pedido al domiciliario y cambiar estado a 'en_camino'
$stmt = $conn->prepare("UPDATE pedidos SET domiciliario_id = ?, estado = 'en_camino' WHERE id = ?");
$stmt->bind_param("ii", $domiciliario_id, $pedido_id);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header("Location: domiciliario.php?success=" . urlencode("Pedido tomado exitosamente"));
    exit;
} else {
    $error = $stmt->error;
    $stmt->close();
    $conn->close();
    header("Location: domiciliario.php?error=" . urlencode("Error al tomar el pedido: " . $error));
    exit;
}
?>
