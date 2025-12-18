<?php
session_start();

// Verificar sesión y rol de administrador
// Verificar sesión y rol de administrador
require_once 'auth_helper.php';
require_once 'includes/csrf_helper.php';
require_once 'includes/sanitize_helper.php'; // Sanitización

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

// Obtener y validar datos con sanitización
$usuario = cleanString($_POST['usuario']);
$nombre = cleanString($_POST['nombre']);
$email = cleanEmail($_POST['email']); // Sanitización específica para email
$telefono = cleanString($_POST['telefono'] ?? '');
$rol = cleanString($_POST['rol']);
$clave = $_POST['clave']; // No sanitizar contraseñas (pueden tener caracteres especiales)
$clave_confirm = $_POST['clave_confirm'];

// Validaciones
if (empty($usuario) || empty($nombre) || empty($rol) || empty($clave)) {
    header("Location: admin_usuarios.php?error=" . urlencode("Todos los campos obligatorios deben ser completados"));
    exit;
}

if ($clave !== $clave_confirm) {
    header("Location: admin_usuarios.php?error=" . urlencode("Las contraseñas no coinciden"));
    exit;
}

if (strlen($clave) < 6) {
    header("Location: admin_usuarios.php?error=" . urlencode("La contraseña debe tener al menos 6 caracteres"));
    exit;
}

// Validar rol
$roles_validos = ['admin', 'mesero', 'chef', 'cajero', 'domiciliario'];
if (!in_array($rol, $roles_validos)) {
    header("Location: admin_usuarios.php?error=" . urlencode("Rol no válido"));
    exit;
}

$conn = getDatabaseConnection();

// Verificar si el usuario ya existe
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ?");
$stmt->bind_param("s", $usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $stmt->close();
    $conn->close();
    header("Location: admin_usuarios.php?error=" . urlencode("El nombre de usuario ya existe"));
    exit;
}
$stmt->close();

// Hashear contraseña
$clave_hash = password_hash($clave, PASSWORD_DEFAULT);

// Insertar usuario
$stmt = $conn->prepare("INSERT INTO usuarios (usuario, clave, nombre, email, telefono, rol, activo) VALUES (?, ?, ?, ?, ?, ?, 1)");
$stmt->bind_param("ssssss", $usuario, $clave_hash, $nombre, $email, $telefono, $rol);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header("Location: admin_usuarios.php?success=" . urlencode("Usuario creado exitosamente"));
    exit;
} else {
    $error = $stmt->error;
    $stmt->close();
    $conn->close();
    header("Location: admin_usuarios.php?error=" . urlencode("Error al crear usuario: " . $error));
    exit;
}
?>
