<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Admin Chatbot</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            background: linear-gradient(135deg, #f97316, #ea580c);
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
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }
        
        button {
            width: 100%;
            background: linear-gradient(135deg, #f97316, #ea580c);
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
            box-shadow: 0 8px 24px rgba(249, 115, 22, 0.4);
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
            <div class="logo-icon">🤖</div>
            <h1>Panel Administrativo</h1>
            <p>Gestiona tu chatbot SaaS</p>
        </div>

        <?php
        session_start();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            
            // Conexión a BD
            $conn = new mysqli("localhost", "root", "", "menu_restaurante");
            
            if ($conn->connect_error) {
                echo '<div class="error">❌ Error de conexión a la base de datos</div>';
            } else {
                // Buscar usuario
                $stmt = $conn->prepare("SELECT id, owner_password, restaurant_name FROM saas_tenants WHERE owner_email = ? AND status = 'active'");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    
                    // Verificar password
                    if (password_verify($password, $user['owner_password'])) {
                        // Login exitoso
                        $_SESSION['admin_logged_in'] = true;
                        $_SESSION['tenant_id'] = $user['id'];
                        $_SESSION['restaurant_name'] = $user['restaurant_name'];
                        $_SESSION['owner_email'] = $email;
                        
                        header('Location: dashboard.php');
                        exit();
                    } else {
                        echo '<div class="error">❌ Contraseña incorrecta</div>';
                    }
                } else {
                    echo '<div class="error">❌ Usuario no encontrado</div>';
                }
                
                $stmt->close();
                $conn->close();
            }
        }
        ?>

        <form method="POST">
            <div class="form-group">
                <label>Email</label>
                <input 
                    type="email" 
                    name="email" 
                    placeholder="tu@email.com" 
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
            <strong>🔑 Credenciales de prueba:</strong>
            Email: demo@restaurante.com<br>
            Password: demo123
        </div>
    </div>
</body>
</html>
