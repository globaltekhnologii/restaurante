<?php
session_start();

// Verificar autenticación
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== TRUE) {
    header("Location: login.php");
    exit;
}

require_once 'config.php';
require_once 'password_helper.php';

$mensaje = '';
$tipo_mensaje = '';

// Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password_actual = trim($_POST['password_actual']);
    $password_nueva = trim($_POST['password_nueva']);
    $password_confirmar = trim($_POST['password_confirmar']);
    
    $errores = [];
    
    // Validar que se llenaron todos los campos
    if (empty($password_actual) || empty($password_nueva) || empty($password_confirmar)) {
        $errores[] = "Todos los campos son obligatorios";
    }
    
    // Validar que las contraseñas nuevas coincidan
    if ($password_nueva !== $password_confirmar) {
        $errores[] = "Las contraseñas nuevas no coinciden";
    }
    
    // Validar complejidad de la nueva contraseña
    if (empty($errores)) {
        $validacion = validarPassword($password_nueva);
        if (!$validacion['valida']) {
            $errores = array_merge($errores, $validacion['errores']);
        }
    }
    
    // Si no hay errores, proceder con el cambio
    if (empty($errores)) {
        $conn = getDatabaseConnection();
        
        // Obtener contraseña actual del usuario
        $stmt = $conn->prepare("SELECT clave FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
            // Verificar contraseña actual
            if (password_verify($password_actual, $row['clave']) || $password_actual === $row['clave']) {
                // Hashear nueva contraseña
                $password_hash = password_hash($password_nueva, PASSWORD_DEFAULT);
                
                // Actualizar en la base de datos
                $stmt_update = $conn->prepare("UPDATE usuarios SET clave = ?, ultimo_acceso = NOW() WHERE id = ?");
                $stmt_update->bind_param("si", $password_hash, $_SESSION['user_id']);
                
                if ($stmt_update->execute()) {
                    $mensaje = "Contraseña actualizada exitosamente";
                    $tipo_mensaje = "success";
                } else {
                    $errores[] = "Error al actualizar la contraseña";
                }
                
                $stmt_update->close();
            } else {
                $errores[] = "La contraseña actual es incorrecta";
            }
        } else {
            $errores[] = "Usuario no encontrado";
        }
        
        $stmt->close();
        closeDatabaseConnection($conn);
    }
    
    if (!empty($errores)) {
        $mensaje = implode("<br>", $errores);
        $tipo_mensaje = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña - Restaurante El Sabor</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2em;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
        }
        
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
            transition: border-color 0.3s;
        }
        
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .password-strength {
            margin-top: 8px;
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            overflow: hidden;
            display: none;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s;
        }
        
        .password-strength-text {
            margin-top: 5px;
            font-size: 0.9em;
            display: none;
        }
        
        .requirements {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            font-size: 0.9em;
        }
        
        .requirements ul {
            margin-left: 20px;
            color: #666;
        }
        
        .requirements li {
            margin: 5px 0;
        }
        
        .btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102,126,234,0.4);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .mensaje {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .mensaje.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .mensaje.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Cambiar Contraseña</h1>
        <p class="subtitle">Actualiza tu contraseña para mantener tu cuenta segura</p>
        
        <?php if (!empty($mensaje)): ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="passwordForm">
            <div class="form-group">
                <label for="password_actual">Contraseña Actual</label>
                <input type="password" id="password_actual" name="password_actual" required>
            </div>
            
            <div class="form-group">
                <label for="password_nueva">Nueva Contraseña</label>
                <input type="password" id="password_nueva" name="password_nueva" required>
                <div class="password-strength" id="strengthBar">
                    <div class="password-strength-bar" id="strengthBarFill"></div>
                </div>
                <div class="password-strength-text" id="strengthText"></div>
                
                <div class="requirements">
                    <strong>Requisitos de seguridad:</strong>
                    <ul>
                        <li>Mínimo 8 caracteres</li>
                        <li>Al menos 1 letra mayúscula</li>
                        <li>Al menos 1 letra minúscula</li>
                        <li>Al menos 1 número</li>
                        <li>Al menos 1 carácter especial (!@#$%^&*...)</li>
                    </ul>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password_confirmar">Confirmar Nueva Contraseña</label>
                <input type="password" id="password_confirmar" name="password_confirmar" required>
            </div>
            
            <button type="submit" class="btn btn-primary">💾 Cambiar Contraseña</button>
            <a href="admin.php" class="btn btn-secondary" style="display: inline-block; text-align: center; text-decoration: none;">← Volver al Panel</a>
        </form>
    </div>
    
    <script>
        // Validación de fuerza de contraseña en tiempo real
        document.getElementById('password_nueva').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('strengthBar');
            const strengthBarFill = document.getElementById('strengthBarFill');
            const strengthText = document.getElementById('strengthText');
            
            if (password.length === 0) {
                strengthBar.style.display = 'none';
                strengthText.style.display = 'none';
                return;
            }
            
            strengthBar.style.display = 'block';
            strengthText.style.display = 'block';
            
            let strength = 0;
            
            // Calcular fuerza
            if (password.length >= 8) strength += 20;
            if (password.length >= 12) strength += 10;
            if (password.length >= 16) strength += 10;
            if (/[a-z]/.test(password)) strength += 15;
            if (/[A-Z]/.test(password)) strength += 15;
            if (/[0-9]/.test(password)) strength += 15;
            if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength += 15;
            
            strength = Math.min(100, strength);
            
            // Actualizar barra
            strengthBarFill.style.width = strength + '%';
            
            // Determinar nivel y color
            let nivel, color;
            if (strength < 40) {
                nivel = 'Muy débil';
                color = '#dc3545';
            } else if (strength < 60) {
                nivel = 'Débil';
                color = '#fd7e14';
            } else if (strength < 80) {
                nivel = 'Media';
                color = '#ffc107';
            } else if (strength < 95) {
                nivel = 'Fuerte';
                color = '#28a745';
            } else {
                nivel = 'Muy fuerte';
                color = '#20c997';
            }
            
            strengthBarFill.style.backgroundColor = color;
            strengthText.textContent = 'Fuerza: ' + nivel;
            strengthText.style.color = color;
        });
        
        // Validar que las contraseñas coincidan
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            const nueva = document.getElementById('password_nueva').value;
            const confirmar = document.getElementById('password_confirmar').value;
            
            if (nueva !== confirmar) {
                e.preventDefault();
                alert('Las contraseñas nuevas no coinciden');
                return false;
            }
        });
    </script>
</body>
</html>
