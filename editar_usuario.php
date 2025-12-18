<?php
session_start();

// Verificar sesión y rol de administrador
require_once 'auth_helper.php';
verificarSesion();
verificarRolORedirect(['admin'], 'login.php');

require_once 'config.php';
require_once 'includes/csrf_helper.php';

// Validar parámetro
if (!isset($_GET['id'])) {
    header("Location: admin_usuarios.php?error=" . urlencode("ID de usuario no especificado"));
    exit;
}

$id = intval($_GET['id']);

$conn = getDatabaseConnection();

// Obtener datos del usuario
$stmt = $conn->prepare("SELECT id, usuario, nombre, email, telefono, rol, activo FROM usuarios WHERE id = ?");
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario - Restaurante El Sabor</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }
        
        .admin-navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .admin-navbar h1 { font-size: 1.5em; }
        
        .container {
            max-width: 800px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .section h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #667eea;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 1em;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-secondary {
            background: #868e96;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="admin-navbar">
        <h1>✏️ Editar Usuario</h1>
    </div>

    <div class="container">
        <div class="section">
            <h2>Editar Usuario: <?php echo htmlspecialchars($usuario['usuario']); ?></h2>
            
            <div class="info-box">
                <strong>ℹ️ Nota:</strong> Deja el campo de contraseña vacío si no deseas cambiarla.
            </div>

            <form action="actualizar_usuario.php" method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" value="<?php echo $usuario['id']; ?>">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Usuario *</label>
                        <input type="text" name="usuario" value="<?php echo htmlspecialchars($usuario['usuario']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Nombre Completo *</label>
                        <input type="text" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input type="tel" name="telefono" value="<?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?>" placeholder="Para domiciliarios">
                    </div>
                    
                    <div class="form-group">
                        <label>Rol *</label>
                        <select name="rol" required>
                            <option value="mesero" <?php echo $usuario['rol'] === 'mesero' ? 'selected' : ''; ?>>🍽️ Mesero</option>
                            <option value="chef" <?php echo $usuario['rol'] === 'chef' ? 'selected' : ''; ?>>👨‍🍳 Chef</option>
                            <option value="cajero" <?php echo $usuario['rol'] === 'cajero' ? 'selected' : ''; ?>>💰 Cajero</option>
                            <option value="domiciliario" <?php echo $usuario['rol'] === 'domiciliario' ? 'selected' : ''; ?>>🏍️ Domiciliario</option>
                            <option value="admin" <?php echo $usuario['rol'] === 'admin' ? 'selected' : ''; ?>>👨‍💼 Administrador</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Nueva Contraseña (opcional)</label>
                        <input type="password" name="clave" minlength="6" placeholder="Dejar vacío para no cambiar">
                    </div>
                    
                    <div class="form-group">
                        <label>Confirmar Contraseña</label>
                        <input type="password" name="clave_confirm" minlength="6" placeholder="Dejar vacío para no cambiar">
                    </div>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn btn-primary">💾 Guardar Cambios</button>
                    <a href="admin_usuarios.php" class="btn btn-secondary">❌ Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
