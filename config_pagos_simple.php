<?php
// Versión temporal sin autenticación - solo para configurar
require_once 'config.php';
require_once 'includes/tenant_context.php'; // NUEVO: Soporte multi-tenencia

// Iniciar sesión para obtener tenant_id
session_start();
$conn = getDatabaseConnection();
$tenant_id = getCurrentTenantId();

// Guardar configuración
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pasarela = $_POST['pasarela'];
    $activa = isset($_POST['activa']) ? 1 : 0;
    $modo = $_POST['modo'];
    $publicKey = $_POST['public_key'] ?? '';
    $secretKey = $_POST['secret_key'] ?? '';
    
    $stmt = $conn->prepare("UPDATE config_pagos SET activa = ?, modo = ?, public_key = ?, secret_key = ? WHERE pasarela = ? AND tenant_id = ?");
    $stmt->bind_param("issssi", $activa, $modo, $publicKey, $secretKey, $pasarela, $tenant_id);
    
    if ($stmt->execute()) {
        $mensaje = "✅ Configuración guardada exitosamente";
    } else {
        $mensaje = "❌ Error al guardar: " . $conn->error;
    }
}

// Obtener configuraciones filtradas por tenant
$pasarelas = $conn->query("SELECT * FROM config_pagos WHERE tenant_id = $tenant_id ORDER BY pasarela")->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configuración de Pagos</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        h1 { color: #333; margin-bottom: 30px; }
        .mensaje { padding: 15px; background: #d4edda; border-radius: 8px; margin-bottom: 20px; color: #155724; }
        .pasarela-card { background: white; padding: 25px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .pasarela-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #f0f0f0; }
        .pasarela-header h2 { color: #667eea; font-size: 24px; }
        .toggle-switch { position: relative; width: 60px; height: 30px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 30px; }
        .slider:before { position: absolute; content: ""; height: 22px; width: 22px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #28a745; }
        input:checked + .slider:before { transform: translateX(30px); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { margin-bottom: 5px; font-weight: bold; color: #555; }
        .form-group input, .form-group select { padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
        .btn-save { padding: 12px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: bold; transition: all 0.3s; }
        .btn-save:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4); }
        .back-link { display: inline-block; margin-bottom: 20px; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 6px; }
        .back-link:hover { background: #5a6268; }
    </style>
</head>
<body>
    <div class="container">
        <a href="admin.php" class="back-link">← Volver al Admin</a>
        
        <h1>💳 Configuración de Pasarelas de Pago</h1>
        
        <?php if (isset($mensaje)): ?>
            <div class="mensaje"><?php echo $mensaje; ?></div>
        <?php endif; ?>
        
        <?php foreach ($pasarelas as $p): ?>
            <div class="pasarela-card">
                <form method="POST">
                    <input type="hidden" name="pasarela" value="<?php echo $p['pasarela']; ?>">
                    
                    <div class="pasarela-header">
                        <h2><?php echo ucfirst($p['pasarela']); ?></h2>
                        <label class="toggle-switch">
                            <input type="checkbox" name="activa" <?php echo $p['activa'] ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Modo:</label>
                            <select name="modo">
                                <option value="sandbox" <?php echo $p['modo'] === 'sandbox' ? 'selected' : ''; ?>>Sandbox (Pruebas)</option>
                                <option value="production" <?php echo $p['modo'] === 'production' ? 'selected' : ''; ?>>Producción</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Public Key <?php echo $p['pasarela'] === 'mercadopago' ? '(Opcional)' : ''; ?>:</label>
                            <input type="text" name="public_key" value="<?php echo htmlspecialchars($p['public_key'] ?? ''); ?>" placeholder="Llave pública">
                        </div>
                        <div class="form-group">
                            <label>Secret Key / Access Token:</label>
                            <input type="password" name="secret_key" value="<?php echo htmlspecialchars($p['secret_key'] ?? ''); ?>" placeholder="Llave secreta">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-save">💾 Guardar Configuración</button>
                </form>
            </div>
        <?php endforeach; ?>
        
        <div style="background: #fff3cd; padding: 20px; border-radius: 8px; margin-top: 30px;">
            <h3 style="color: #856404; margin-bottom: 10px;">📝 Instrucciones:</h3>
            <ul style="color: #856404; margin-left: 20px;">
                <li><strong>Bold:</strong> Ingresa Public Key y Secret Key de tu cuenta Bold</li>
                <li><strong>Mercado Pago:</strong> Solo necesitas el Access Token (va en Secret Key)</li>
                <li>Activa el switch para habilitar la pasarela en el checkout</li>
                <li>Usa Sandbox para pruebas, Producción para pagos reales</li>
            </ul>
        </div>
    </div>
</body>
</html>
