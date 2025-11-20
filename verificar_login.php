<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "menu_restaurante";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Obtener datos del formulario
$usuario_input = trim($_POST['usuario']);
$clave_input = trim($_POST['clave']);

// Prevenir SQL injection usando prepared statements
$stmt = $conn->prepare("SELECT id, usuario, clave FROM usuarios WHERE usuario = ?");
$stmt->bind_param("s", $usuario_input);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    
    // Verificar contraseña
    // Si usas password_hash(), usa password_verify($clave_input, $row['clave'])
    // Por ahora verificación simple:
    if ($clave_input === $row['clave']) {
        // Login exitoso
        $_SESSION['loggedin'] = TRUE;
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['usuario'] = $row['usuario'];
        $_SESSION['login_time'] = time();
        
        // Registrar último acceso (opcional)
        $update = $conn->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?");
        $update->bind_param("i", $row['id']);
        $update->execute();
        
        // Redirigir al panel admin
        header("Location: admin.php");
        exit;
    } else {
        // Contraseña incorrecta
        header("Location: login.php?error=1");
        exit;
    }
} else {
    // Usuario no existe
    header("Location: login.php?error=1");
    exit;
}

$stmt->close();
$conn->close();
?>