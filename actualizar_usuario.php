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

// Validar que se recibieron los datos
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin_usuarios.php?error=" . urlencode("Método no permitido"));
    exit;
}

// Validar Token CSRF
verificarTokenOError();

// Obtener y validar datos
$id = cleanInt($_POST['id']);
$usuario = cleanString($_POST['usuario']);
$nombre = cleanString($_POST['nombre']);
$email = cleanEmail($_POST['email']);
$telefono = cleanString($_POST['telefono'] ?? '');
$rol = cleanString($_POST['rol']);
$clave = isset($_POST['clave']) ? $_POST['clave'] : '';
$clave_confirm = isset($_POST['clave_confirm']) ? $_POST['clave_confirm'] : '';

// Validaciones básicas
if (empty($usuario) || empty($nombre) || empty($rol)) {
    header("Location: editar_usuario.php?id=$id&error=" . urlencode("Todos los campos obligatorios deben ser completados"));
    exit;
}

// Validar rol
$roles_validos = ['admin', 'mesero', 'chef', 'cajero', 'domiciliario'];
if (!in_array($rol, $roles_validos)) {
    header("Location: editar_usuario.php?id=$id&error=" . urlencode("Rol no válido"));
    exit;
}

// Si se proporciona contraseña, validar
if (!empty($clave)) {
    if ($clave !== $clave_confirm) {
        header("Location: editar_usuario.php?id=$id&error=" . urlencode("Las contraseñas no coinciden"));
        exit;
    }
    
    if (strlen($clave) < 6) {
        header("Location: editar_usuario.php?id=$id&error=" . urlencode("La contraseña debe tener al menos 6 caracteres"));
        exit;
    }
}

$conn = getDatabaseConnection();

// Verificar que el usuario existe
$stmt = $conn->prepare("SELECT usuario, rol FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    header("Location: admin_usuarios.php?error=" . urlencode("Usuario no encontrado"));
    exit;
}

$usuario_actual = $result->fetch_assoc();
$stmt->close();

// Verificar si el nombre de usuario ya existe (excepto para el mismo usuario)
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ? AND id != ?");
$stmt->bind_param("si", $usuario, $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $stmt->close();
    $conn->close();
    header("Location: editar_usuario.php?id=$id&error=" . urlencode("El nombre de usuario ya existe"));
    exit;
}
$stmt->close();

// Si se está cambiando el rol de admin, verificar que no sea el último
if ($usuario_actual['rol'] === 'admin' && $rol !== 'admin') {
    $count_admins = $conn->query("SELECT COUNT(*) as count FROM usuarios WHERE rol = 'admin'")->fetch_assoc()['count'];
    
    if ($count_admins <= 1) {
        $conn->close();
        header("Location: editar_usuario.php?id=$id&error=" . urlencode("No se puede cambiar el rol del último administrador"));
        exit;
    }
}

// Actualizar usuario
if (!empty($clave)) {
    // Actualizar con nueva contraseña
    $clave_hash = password_hash($clave, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE usuarios SET usuario = ?, nombre = ?, email = ?, telefono = ?, rol = ?, clave = ? WHERE id = ?");
    $stmt->bind_param("ssssssi", $usuario, $nombre, $email, $telefono, $rol, $clave_hash, $id);
} else {
    // Actualizar sin cambiar contraseña
    $stmt = $conn->prepare("UPDATE usuarios SET usuario = ?, nombre = ?, email = ?, telefono = ?, rol = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $usuario, $nombre, $email, $telefono, $rol, $id);
}

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header("Location: admin_usuarios.php?success=" . urlencode("Usuario actualizado exitosamente"));
    exit;
} else {
    $error = $stmt->error;
    $stmt->close();
    $conn->close();
    header("Location: editar_usuario.php?id=$id&error=" . urlencode("Error al actualizar usuario: " . $error));
    exit;
}
?>
