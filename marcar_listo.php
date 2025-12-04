<?php
session_start();

// Verificar sesión y rol de chef
require_once 'auth_helper.php';
verificarSesion();
verificarRolORedirect(['chef'], 'login.php');

require_once 'config.php';

// Validar parámetro
if (!isset($_GET['id'])) {
    header("Location: chef.php?error=" . urlencode("ID de pedido no especificado"));
    exit;
}

$pedido_id = intval($_GET['id']);

$conn = getDatabaseConnection();

// Verificar que el pedido existe y está en preparación
$stmt = $conn->prepare("SELECT * FROM pedidos WHERE id = ? AND estado = 'preparando'");
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    header("Location: chef.php?error=" . urlencode("Pedido no encontrado o no está en preparación"));
    exit;
}

$stmt->close();

// Actualizar estado a en_camino (listo para servir/entregar)
$stmt = $conn->prepare("UPDATE pedidos SET estado = 'en_camino' WHERE id = ?");
$stmt->bind_param("i", $pedido_id);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header("Location: chef.php?success=" . urlencode("Pedido marcado como listo"));
    exit;
} else {
    $error = $stmt->error;
    $stmt->close();
    $conn->close();
    header("Location: chef.php?error=" . urlencode("Error al actualizar pedido: " . $error));
    exit;
}
?>
