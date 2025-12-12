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

// Verificar que el pedido existe y está disponible (listo para recoger)
$stmt = $conn->prepare("SELECT * FROM pedidos WHERE id = ? AND domiciliario_id IS NULL AND estado = 'listo'");
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    header("Location: domiciliario.php?error=" . urlencode("Pedido no disponible"));
    exit;
}

$stmt->close();

// Asignar el pedido al domiciliario (mantener en listo hasta que salga)
$stmt = $conn->prepare("UPDATE pedidos SET domiciliario_id = ? WHERE id = ?");
$stmt->bind_param("ii", $domiciliario_id, $pedido_id);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header("Location: domiciliario.php?success=" . urlencode("Entrega asignada exitosamente"));
    exit;
} else {
    $error = $stmt->error;
    $stmt->close();
    $conn->close();
    header("Location: domiciliario.php?error=" . urlencode("Error al asignar entrega: " . $error));
    exit;
}
?>
