<?php
session_start();

// Verificar sesión y rol de mesero
require_once 'auth_helper.php';
verificarSesion();
verificarRolORedirect(['mesero'], 'login.php');

require_once 'config.php';

// Validar parámetro
if (!isset($_GET['mesa_id'])) {
    header("Location: mesero.php?error=" . urlencode("ID de mesa no especificado"));
    exit;
}

$mesa_id = intval($_GET['mesa_id']);
$mesero_id = $_SESSION['user_id'];

$conn = getDatabaseConnection();

// Verificar que la mesa existe y está ocupada
$stmt = $conn->prepare("SELECT * FROM mesas WHERE id = ? AND estado = 'ocupada' AND mesero_asignado = ?");
$stmt->bind_param("ii", $mesa_id, $mesero_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    header("Location: mesero.php?error=" . urlencode("Mesa no encontrada o no está asignada a ti"));
    exit;
}

$mesa = $result->fetch_assoc();
$stmt->close();

// Liberar la mesa
$stmt = $conn->prepare("UPDATE mesas SET estado = 'disponible', pedido_actual = NULL, mesero_asignado = NULL, fecha_ocupacion = NULL WHERE id = ?");
$stmt->bind_param("i", $mesa_id);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header("Location: mesero.php?success=" . urlencode("Mesa liberada exitosamente"));
    exit;
} else {
    $error = $stmt->error;
    $stmt->close();
    $conn->close();
    header("Location: mesero.php?error=" . urlencode("Error al liberar mesa: " . $error));
    exit;
}
?>
