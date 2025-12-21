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

require_once 'includes/tenant_context.php'; // NUEVO: Soporte multi-tenencia
$conn = getDatabaseConnection();

// Obtener tenant_id del usuario actual
$tenant_id = getCurrentTenantId();

// Verificar que el pedido existe, pertenece al tenant y está confirmado
$stmt = $conn->prepare("SELECT * FROM pedidos WHERE id = ? AND tenant_id = ? AND estado = 'confirmado'");
$stmt->bind_param("ii", $pedido_id, $tenant_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    header("Location: chef.php?error=" . urlencode("Pedido no encontrado o ya está en preparación"));
    exit;
}

$stmt->close();

// Actualizar estado a preparando
$stmt = $conn->prepare("UPDATE pedidos SET estado = 'preparando' WHERE id = ?");
$stmt->bind_param("i", $pedido_id);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header("Location: chef.php?success=" . urlencode("Pedido en preparación"));
    exit;
} else {
    $error = $stmt->error;
    $stmt->close();
    $conn->close();
    header("Location: chef.php?error=" . urlencode("Error al actualizar pedido: " . $error));
    exit;
}
?>
