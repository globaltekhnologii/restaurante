<?php
session_start();

// Verificar sesión y rol de chef
require_once 'auth_helper.php';
verificarSesion();
verificarRolORedirect(['chef', 'admin', 'domiciliario'], 'login.php');

require_once 'config.php';

// Determinar página de retorno según el rol
$rol = $_SESSION['rol'];
$pagina_retorno = ($rol === 'domiciliario') ? 'domiciliario.php' : 'chef.php';

// Validar parámetros
if (!isset($_GET['pedido_id']) || !isset($_GET['nuevo_estado'])) {
    header("Location: $pagina_retorno?error=" . urlencode("Parámetros inválidos"));
    exit;
}

$pedido_id = intval($_GET['pedido_id']);
$nuevo_estado = $_GET['nuevo_estado'];

// Validar estado
$estados_validos = ['pendiente', 'confirmado', 'preparando', 'listo', 'en_camino', 'entregado', 'cancelado'];
if (!in_array($nuevo_estado, $estados_validos)) {
    header("Location: $pagina_retorno?error=" . urlencode("Estado no válido"));
    exit;
}

$conn = getDatabaseConnection();

// Actualizar estado
$stmt = $conn->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
$stmt->bind_param("si", $nuevo_estado, $pedido_id);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header("Location: $pagina_retorno?success=" . urlencode("Estado actualizado a: " . ucfirst($nuevo_estado)));
    exit;
} else {
    $error = $stmt->error;
    $stmt->close();
    $conn->close();
    header("Location: $pagina_retorno?error=" . urlencode("Error al actualizar: " . $error));
    exit;
}
?>
