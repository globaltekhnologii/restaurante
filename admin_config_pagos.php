<?php
session_start();
require_once 'config.php';

// Verificar admin - más flexible
if (!isset($_SESSION['usuario_id'])) {
    // Si no hay sesión, redirigir a login
    header('Location: login.php');
    exit;
}

$conn = getDatabaseConnection();

// Guardar configuración
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pasarela = $_POST['pasarela'];
    $activa = isset($_POST['activa']) ? 1 : 0;
    $modo = $_POST['modo'];
    $publicKey = $_POST['public_key'] ?? '';
    $secretKey = $_POST['secret_key'] ?? '';
    
    $stmt = $conn->prepare("UPDATE config_pagos SET activa = ?, modo = ?, public_key = ?, secret_key = ? WHERE pasarela = ?");
    $stmt->bind_param("issss", $activa, $modo, $publicKey, $secretKey, $pasarela);
    
    if ($stmt->execute()) {
        $mensaje = "✅ Configuración guardada";
    } else {
        $mensaje = "❌ Error al guardar";
    }
}

// Obtener configuraciones
$pasarelas = $conn->query("SELECT * FROM config_pagos ORDER BY pasarela")->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configuración de Pagos</title>
    <link rel="stylesheet" href="css/admin-modern.css">
    <style>
        .pasarela-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .pasarela-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .toggle-switch {
            position: relative;
            width: 60px;
            height: 30px;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 30px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: #28a745;
        }
        input:checked + .slider:before {
            transform: translateX(30px);
        }
    </style>
</head>
<body>
    <?php // include 'includes/navbar_admin.php'; ?>
    
    <div class="container" style="max-width: 1000px; margin: 40px auto; padding: 20px;">
        <h1>💳 Configuración de Pasarelas de Pago</h1>
        
        <?php if (isset($mensaje)): ?>
            <div style="padding: 15px; background: #d4edda; border-radius: 8px; margin-bottom: 20px;">
                <?php echo $mensaje; ?>
            </div>
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
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <div>
                            <label>Modo:</label>
                            <select name="modo" style="width: 100%; padding: 10px; border-radius: 4px;">
                                <option value="sandbox" <?php echo $p['modo'] === 'sandbox' ? 'selected' : ''; ?>>Sandbox (Pruebas)</option>
                                <option value="production" <?php echo $p['modo'] === 'production' ? 'selected' : ''; ?>>Producción</option>
                            </select>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <div>
                            <label>Public Key:</label>
                            <input type="text" name="public_key" value="<?php echo htmlspecialchars($p['public_key'] ?? ''); ?>" placeholder="Llave pública" style="width: 100%; padding: 10px; border-radius: 4px;">
                        </div>
                        <div>
                            <label>Secret Key:</label>
                            <input type="password" name="secret_key" value="<?php echo htmlspecialchars($p['secret_key'] ?? ''); ?>" placeholder="Llave secreta" style="width: 100%; padding: 10px; border-radius: 4px;">
                        </div>
                    </div>
                    
                    <button type="submit" style="padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        💾 Guardar Configuración
                    </button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
