<?php
require_once 'config.php';

// Si ya está autenticado, redirigir al dashboard
if (isset($_SESSION['superadmin_logged_in']) && $_SESSION['superadmin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    $conn = getDBConnection();
    
    // Buscar super admin
    $stmt = $conn->prepare("SELECT id, password, name FROM saas_super_admins WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        
        // Verificar password
        if (password_verify($password, $admin['password'])) {
            // Login exitoso
            $_SESSION['superadmin_logged_in'] = true;
            $_SESSION['superadmin_id'] = $admin['id'];
            $_SESSION['superadmin_email'] = $email;
            $_SESSION['superadmin_name'] = $admin['name'];
            
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Contraseña incorrecta';
        }
    } else {
        $error = 'Super Administrador no encontrado';
    }
    
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Super Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
            padding: 40px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #3b82f6, #1e40af);
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        h1 {
            font-size: 24px;
            color: #1f2937;
            margin-bottom: 8px;
            text-align: center;
        }
        
        p {
            color: #6b7280;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .error {
            background: #fef2f2;
            border-left: 4px solid #ef4444;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 4px;
            color: #991b1b;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        
        input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        button {
            width: 100%;
            background: linear-gradient(135deg, #3b82f6, #1e40af);
            color: white;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(59, 130, 246, 0.4);
        }
        
        .demo-credentials {
            margin-top: 20px;
            padding: 12px;
            background: #eff6ff;
            border-radius: 8px;
            font-size: 13px;
            color: #1e40af;
        }
        
        .demo-credentials strong {
            display: block;
            margin-bottom: 4px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <div class="logo-icon">👑</div>
            <h1>Super Administrador</h1>
            <p>Panel de Gestión SaaS</p>
        </div>

        <?php if ($error): ?>
            <div class="error">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Email</label>
                <input 
                    type="email" 
                    name="email" 
                    placeholder="admin@saas.com" 
                    required
                    autocomplete="email"
                >
            </div>

            <div class="form-group">
                <label>Contraseña</label>
                <input 
                    type="password" 
                    name="password" 
                    placeholder="••••••••" 
                    required
                    autocomplete="current-password"
                >
            </div>

            <button type="submit">Iniciar Sesión</button>
        </form>

        <div class="demo-credentials">
            <strong>🔑 Credenciales por defecto:</strong>
            Email: admin@saas.com<br>
            Password: admin123
        </div>
    </div>
</body>
</html>
