<?php
session_start();
require_once 'auth_helper.php';
verificarSesion();
verificarRolORedirect(['admin'], 'login.php');

require_once 'config.php';
require_once 'includes/tenant_context.php'; // NUEVO: Soporte multi-tenencia
$conn = getDatabaseConnection();

// Obtener tenant_id del usuario actual
$tenant_id = getCurrentTenantId();

// Procesar actualización de configuración
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $metodo = $_POST['metodo'] ?? '';
    $numero_cuenta = $_POST['numero_cuenta'] ?? '';
    $nombre_titular = $_POST['nombre_titular'] ?? '';
    
    // Procesar imagen QR si se subió
    $qr_imagen = null;
    if (isset($_FILES['qr_imagen']) && $_FILES['qr_imagen']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'imagenes_qr/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $extension = pathinfo($_FILES['qr_imagen']['name'], PATHINFO_EXTENSION);
        $filename = $metodo . '_qr_' . time() . '.' . $extension;
        $filepath = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['qr_imagen']['tmp_name'], $filepath)) {
            $qr_imagen = $filepath;
        }
    }
    
    // Actualizar configuración filtrada por tenant
    $stmt = $conn->prepare("UPDATE metodos_pago_config SET numero_cuenta = ?, nombre_titular = ?" . 
                           ($qr_imagen ? ", qr_imagen = ?" : "") . 
                           " WHERE metodo = ? AND tenant_id = ?");
    
    if ($qr_imagen) {
        $stmt->bind_param("ssssi", $numero_cuenta, $nombre_titular, $qr_imagen, $metodo, $tenant_id);
    } else {
        $stmt->bind_param("sssi", $numero_cuenta, $nombre_titular, $metodo, $tenant_id);
    }
    
    if ($stmt->execute()) {
        header("Location: config_pagos.php?success=1");
        exit;
    }
}

// Obtener métodos de pago filtrados por tenant
$metodos = [];
$result = $conn->query("SELECT * FROM metodos_pago_config WHERE tenant_id = $tenant_id AND activo = 1 ORDER BY orden");
while ($row = $result->fetch_assoc()) {
    $metodos[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de Pagos - Restaurante</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f5f7fa;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .navbar a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            background: rgba(255,255,255,0.2);
            border-radius: 5px;
        }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .card h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #667eea;
        }
        
        .metodo-item {
            padding: 20px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .metodo-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .metodo-nombre {
            font-size: 1.3em;
            font-weight: bold;
            color: #667eea;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 1em;
        }
        
        .qr-preview {
            max-width: 200px;
            margin-top: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        
        .success-message {
            background: #d4edda;
            border-left: 4px solid #28a745;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>💳 Configuración de Métodos de Pago</h1>
        <a href="admin.php">← Volver al Admin</a>
    </div>

    <div class="container">
        <?php if (isset($_GET['success'])): ?>
        <div class="success-message">
            ✅ Configuración actualizada correctamente
        </div>
        <?php endif; ?>

        <div class="card">
            <h2>📱 Métodos de Pago Disponibles</h2>
            <p style="color: #666; margin-bottom: 20px;">
                Configura los números de cuenta y códigos QR para cada método de pago.
            </p>

            <?php foreach ($metodos as $metodo): ?>
            <div class="metodo-item">
                <div class="metodo-header">
                    <div class="metodo-nombre">
                        <?php 
                        $iconos = [
                            'efectivo' => '💵',
                            'nequi' => '📱',
                            'daviplata' => '📱',
                            'dale' => '📱',
                            'bancolombia' => '🏦'
                        ];
                        echo $iconos[$metodo['metodo']] ?? '💳';
                        ?>
                        <?php echo htmlspecialchars($metodo['nombre_display']); ?>
                    </div>
                </div>

                <?php if ($metodo['metodo'] !== 'efectivo'): ?>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="metodo" value="<?php echo $metodo['metodo']; ?>">
                    
                    <div class="form-group">
                        <label>Número de Cuenta / Celular:</label>
                        <input type="text" name="numero_cuenta" 
                               value="<?php echo htmlspecialchars($metodo['numero_cuenta'] ?? ''); ?>"
                               placeholder="Ej: 3001234567">
                    </div>

                    <div class="form-group">
                        <label>Nombre del Titular:</label>
                        <input type="text" name="nombre_titular" 
                               value="<?php echo htmlspecialchars($metodo['nombre_titular'] ?? ''); ?>"
                               placeholder="Ej: Restaurante El Sabor">
                    </div>

                    <div class="form-group">
                        <label>Código QR (Imagen):</label>
                        <input type="file" name="qr_imagen" accept="image/*">
                        <?php if (!empty($metodo['qr_imagen']) && file_exists($metodo['qr_imagen'])): ?>
                            <div>
                                <p style="margin-top: 10px; color: #666;">QR Actual:</p>
                                <img src="<?php echo htmlspecialchars($metodo['qr_imagen']); ?>" 
                                     class="qr-preview" alt="QR Code">
                            </div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        💾 Guardar Configuración
                    </button>
                </form>
                <?php else: ?>
                <p style="color: #999; font-style: italic;">
                    El pago en efectivo no requiere configuración adicional.
                </p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
