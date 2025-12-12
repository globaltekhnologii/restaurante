<?php
require_once 'config.php';
checkSuperAdminAuth();

$conn = getDBConnection();
$message = '';
$message_type = '';
$current_admin = getCurrentSuperAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $current_password = $_POST['current_password'];
    
    // Verificar contraseña actual
    $admin_id = $current_admin['id'];
    $stmt = $conn->prepare("SELECT password FROM saas_super_admins WHERE id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $admin_data = $res->fetch_assoc();
    
    if (password_verify($current_password, $admin_data['password'])) {
        // Contraseña correcta, proceder a actualizar
        $update_password = false;
        
        if (!empty($new_password)) {
            if ($new_password === $confirm_password) {
                $update_password = true;
            } else {
                $message = "Las nuevas contraseñas no coinciden";
                $message_type = "error";
            }
        }
        
        if ($message_type !== 'error') {
            if ($update_password) {
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE saas_super_admins SET name = ?, email = ?, password = ? WHERE id = ?");
                $stmt->bind_param("sssi", $name, $email, $password_hash, $admin_id);
            } else {
                $stmt = $conn->prepare("UPDATE saas_super_admins SET name = ?, email = ? WHERE id = ?");
                $stmt->bind_param("ssi", $name, $email, $admin_id);
            }
            
            if ($stmt->execute()) {
                $message = "Perfil actualizado exitosamente";
                $message_type = "success";
                // Actualizar sesión si cambió el nombre
                $_SESSION['super_admin_name'] = $name;
                $current_admin = getCurrentSuperAdmin(); // Refrescar datos
            } else {
                $message = "Error al actualizar perfil: " . $stmt->error;
                $message_type = "error";
            }
        }
    } else {
        $message = "La contraseña actual es incorrecta";
        $message_type = "error";
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Super Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f3f4f6; color: #1f2937; }
        
        <?php include 'includes/navbar.php'; ?>
        
        .container { max-width: 800px; margin: 2rem auto; padding: 0 2rem; }
        .card { background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 2rem; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 0.5rem; color: #374151; }
        .form-group input { width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; }
        .btn { padding: 0.75rem 1.5rem; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; color: white; background: #3b82f6; }
        .btn:hover { background: #2563eb; }
        .message { padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .message.success { background: #d1fae5; color: #065f46; border-left: 4px solid #22c55e; }
        .message.error { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container">
        <h1 style="margin-bottom: 2rem;">👤 Mi Perfil</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <form method="POST">
                <div class="form-group">
                    <label>Nombre</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($current_admin['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($current_admin['email']); ?>" required>
                </div>
                
                <hr style="margin: 2rem 0; border: 0; border-top: 1px solid #e5e7eb;">
                <h3 style="margin-bottom: 1rem; color: #6b7280;">Cambiar Contraseña</h3>
                
                <div class="form-group">
                    <label>Contraseña Actual (Requerido para cambios)</label>
                    <input type="password" name="current_password" required>
                </div>
                
                <div class="form-group">
                    <label>Nueva Contraseña (Dejar en blanco para mantener)</label>
                    <input type="password" name="new_password">
                </div>
                
                <div class="form-group">
                    <label>Confirmar Nueva Contraseña</label>
                    <input type="password" name="confirm_password">
                </div>
                
                <button type="submit" class="btn">Guardar Cambios</button>
            </form>
        </div>
    </div>
</body>
</html>
