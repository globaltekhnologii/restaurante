<?php
session_start();

// Verificar sesión y rol de admin
require_once 'auth_helper.php';
verificarSesion();
verificarRolORedirect(['admin'], 'login.php');

require_once 'config.php';

// Validar que se recibieron los datos
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin_pedidos.php?error=" . urlencode("Método no permitido"));
    exit;
}

$pedido_id = intval($_POST['pedido_id']);
$domiciliario_id = intval($_POST['domiciliario_id']);

// Validaciones
if ($pedido_id <= 0 || $domiciliario_id <= 0) {
    header("Location: admin_pedidos.php?error=" . urlencode("Datos inválidos"));
    exit;
}

$conn = getDatabaseConnection();

// Verificar que el pedido existe
$stmt = $conn->prepare("SELECT numero_pedido FROM pedidos WHERE id = ?");
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    header("Location: admin_pedidos.php?error=" . urlencode("Pedido no encontrado"));
    exit;
}

$pedido = $result->fetch_assoc();
$stmt->close();

// Verificar que el domiciliario existe y está activo
$stmt = $conn->prepare("SELECT nombre FROM usuarios WHERE id = ? AND rol = 'domiciliario' AND activo = 1");
$stmt->bind_param("i", $domiciliario_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    header("Location: admin_pedidos.php?error=" . urlencode("Domiciliario no encontrado o inactivo"));
    exit;
}

$domiciliario = $result->fetch_assoc();
$stmt->close();

// Asignar domiciliario al pedido
$stmt = $conn->prepare("UPDATE pedidos SET domiciliario_id = ? WHERE id = ?");
$stmt->bind_param("ii", $domiciliario_id, $pedido_id);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header("Location: admin_pedidos.php?success=" . urlencode("Domiciliario " . $domiciliario['nombre'] . " asignado al pedido " . $pedido['numero_pedido']));
    exit;
} else {
    $error = $stmt->error;
    $stmt->close();
    $conn->close();
    header("Location: admin_pedidos.php?error=" . urlencode("Error al asignar domiciliario: " . $error));
    exit;
}
?>
