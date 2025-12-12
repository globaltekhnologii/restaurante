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

// Verificar que el pedido está asignado al domiciliario y listo para salir
$stmt = $conn->prepare("SELECT * FROM pedidos WHERE id = ? AND domiciliario_id = ? AND estado = 'listo'");
$stmt->bind_param("ii", $pedido_id, $domiciliario_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    header("Location: domiciliario.php?error=" . urlencode("Pedido no encontrado o no está listo"));
    exit;
}

$stmt->close();

// Actualizar estado a en_camino y registrar hora de salida
$stmt = $conn->prepare("UPDATE pedidos SET estado = 'en_camino', hora_salida = NOW() WHERE id = ?");
$stmt->bind_param("i", $pedido_id);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header("Location: domiciliario.php?success=" . urlencode("Salida registrada - En camino"));
    exit;
} else {
    $error = $stmt->error;
    $stmt->close();
    $conn->close();
    header("Location: domiciliario.php?error=" . urlencode("Error al registrar salida: " . $error));
    exit;
}
?>
