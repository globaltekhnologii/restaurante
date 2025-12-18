<?php
session_start();

// Verificar sesión y rol de administrador
// Verificar sesión y rol de administrador
require_once 'auth_helper.php';
require_once 'includes/csrf_helper.php';
require_once 'includes/sanitize_helper.php';

verificarSesion();
verificarRolORedirect(['admin'], 'login.php');

require_once 'config.php';

// Validar método POST y token CSRF
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin_usuarios.php?error=" . urlencode("Método no permitido. Use el botón de la interfaz."));
    exit;
}

verificarTokenOError();

// Validar parámetros (ahora vienen por POST)
if (!isset($_POST['id']) || !isset($_POST['accion'])) {
    header("Location: admin_usuarios.php?error=" . urlencode("Parámetros inválidos"));
    exit;
}

$id = cleanInt($_POST['id']);
$accion = cleanString($_POST['accion']);

if ($accion !== 'activar' && $accion !== 'desactivar') {
    header("Location: admin_usuarios.php?error=" . urlencode("Acción no válida"));
    exit;
}

$conn = getDatabaseConnection();

// Verificar que el usuario existe
$stmt = $conn->prepare("SELECT rol FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    header("Location: admin_usuarios.php?error=" . urlencode("Usuario no encontrado"));
    exit;
}

$usuario = $result->fetch_assoc();
$stmt->close();

// Si es admin y se intenta desactivar, verificar que no sea el último
if ($usuario['rol'] === 'admin' && $accion === 'desactivar') {
    $count_admins = $conn->query("SELECT COUNT(*) as count FROM usuarios WHERE rol = 'admin' AND activo = 1")->fetch_assoc()['count'];
    
    if ($count_admins <= 1) {
        $conn->close();
        header("Location: admin_usuarios.php?error=" . urlencode("No se puede desactivar el último administrador"));
        exit;
    }
}

// Actualizar estado
$nuevo_estado = ($accion === 'activar') ? 1 : 0;
$stmt = $conn->prepare("UPDATE usuarios SET activo = ? WHERE id = ?");
$stmt->bind_param("ii", $nuevo_estado, $id);

if ($stmt->execute()) {
    $mensaje = ($accion === 'activar') ? "Usuario activado exitosamente" : "Usuario desactivado exitosamente";
    $stmt->close();
    $conn->close();
    header("Location: admin_usuarios.php?success=" . urlencode($mensaje));
    exit;
} else {
    $error = $stmt->error;
    $stmt->close();
    $conn->close();
    header("Location: admin_usuarios.php?error=" . urlencode("Error al actualizar usuario: " . $error));
    exit;
}
?>
