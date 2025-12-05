<?php
session_start();

// Usar configuración centralizada
require_once 'config.php';
$conn = getDatabaseConnection();

// Obtener datos del formulario
$usuario_input = trim($_POST['usuario']);
$clave_input = trim($_POST['clave']);

// Prevenir SQL injection usando prepared statements
// Ahora también obtenemos el rol y nombre del usuario
$stmt = $conn->prepare("SELECT id, usuario, clave, rol, nombre FROM usuarios WHERE usuario = ? AND activo = 1");
$stmt->bind_param("s", $usuario_input);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    
    // Verificar contraseña usando password_verify() para contraseñas hasheadas
    // Esto soporta tanto contraseñas hasheadas como texto plano (para migración)
    $password_valida = false;
    
    // Primero intentar con password_verify (para contraseñas hasheadas)
    if (password_verify($clave_input, $row['clave'])) {
        $password_valida = true;
    } 
    // Si falla, verificar si es texto plano (para compatibilidad durante migración)
    elseif ($clave_input === $row['clave']) {
        $password_valida = true;
        
        // IMPORTANTE: Actualizar automáticamente a hash si aún está en texto plano
        $clave_hash = password_hash($clave_input, PASSWORD_DEFAULT);
        $update_pwd = $conn->prepare("UPDATE usuarios SET clave = ? WHERE id = ?");
        $update_pwd->bind_param("si", $clave_hash, $row['id']);
        $update_pwd->execute();
        $update_pwd->close();
    }
    
    if ($password_valida) {
        // Login exitoso - Guardar datos en sesión
        $_SESSION['loggedin'] = TRUE;
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['usuario'] = $row['usuario'];
        $_SESSION['rol'] = $row['rol'];
        $_SESSION['nombre'] = $row['nombre'];
        $_SESSION['login_time'] = time();
        
        // Registrar último acceso
        $update = $conn->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?");
        $update->bind_param("i", $row['id']);
        $update->execute();
        $update->close();
        
        // Redirigir según el rol del usuario
        switch ($row['rol']) {
            case 'admin':
                header("Location: admin.php");
                break;
            case 'mesero':
                header("Location: mesero.php");
                break;
            case 'chef':
                header("Location: chef.php");
                break;
            case 'domiciliario':
                header("Location: domiciliario.php");
                break;
            case 'cajero':
                header("Location: cajero.php");
                break;
            default:
                // Si el rol no es reconocido, redirigir a admin por defecto
                header("Location: admin.php");
                break;
        }
        exit;
    } else {
        // Contraseña incorrecta
        header("Location: login.php?error=1");
        exit;
    }
} else {
    // Usuario no existe o está inactivo
    header("Location: login.php?error=1");
    exit;
}

$stmt->close();
$conn->close();
?>