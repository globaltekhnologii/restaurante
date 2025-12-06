<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

$conn = new mysqli("localhost", "root", "", "menu_restaurante");
$tenant_id = $_SESSION['tenant_id'];

// Manejar actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare("UPDATE saas_chatbot_config SET 
        chatbot_name = ?, 
        welcome_message = ?, 
        primary_color = ?, 
        enable_delivery = ?, 
        enable_reservations = ?,
        enable_whatsapp = ?,
        whatsapp_number = ?,
        ai_provider = ?,
        api_key = ?
        WHERE tenant_id = ?");
    
    $enable_delivery = isset($_POST['enable_delivery']) ? 1 : 0;
    $enable_reservations = isset($_POST['enable_reservations']) ? 1 : 0;
    $enable_whatsapp = isset($_POST['enable_whatsapp']) ? 1 : 0;
    
    $stmt->bind_param("sssiiisssi", 
        $_POST['chatbot_name'],
        $_POST['welcome_message'],
        $_POST['primary_color'],
        $enable_delivery,
        $enable_reservations,
        $enable_whatsapp,
        $_POST['whatsapp_number'],
        $_POST['ai_provider'],
        $_POST['api_key'],
        $tenant_id
    );
    
    if ($stmt->execute()) {
        $success = "Configuración actualizada exitosamente";
    }
}

// Obtener configuración actual
$result = $conn->query("SELECT * FROM saas_chatbot_config WHERE tenant_id = $tenant_id");
$config = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - <?php echo htmlspecialchars($_SESSION['restaurant_name']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f9fafb; }
        
        .header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-left { display: flex; align-items: center; gap: 12px; }
        .logo { width: 40px; height: 40px; background: linear-gradient(135deg, #f97316, #ea580c); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .header-title h1 { font-size: 18px; color: #1f2937; }
        .header-title p { font-size: 13px; color: #6b7280; }
        
        .nav-links { display: flex; gap: 12px; }
        .nav-links a { padding: 8px 16px; color: #6b7280; text-decoration: none; border-radius: 6px; transition: all 0.2s; }
        .nav-links a:hover, .nav-links a.active { background: #f3f4f6; color: #f97316; }
        
        .container { max-width: 800px; margin: 0 auto; padding: 24px; }
        .page-title { font-size: 28px; color: #1f2937; margin-bottom: 24px; }
        
        .success { background: #dcfce7; border-left: 4px solid #22c55e; padding: 12px; margin-bottom: 20px; border-radius: 4px; color: #166534; }
        
        .card { background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 24px; margin-bottom: 24px; }
        .card-title { font-size: 18px; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #e5e7eb; }
        
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: 600; color: #374151; margin-bottom: 6px; font-size: 14px; }
        input, select, textarea { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #f97316; box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1); }
        textarea { resize: vertical; min-height: 100px; }
        
        .checkbox-group { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; }
        .checkbox-group input[type="checkbox"] { width: auto; }
        .checkbox-group label { margin: 0; font-weight: normal; }
        
        .color-preview { width: 50px; height: 50px; border-radius: 8px; border: 2px solid #e5e7eb; margin-top: 8px; }
        
        .btn { padding: 12px 24px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
        .btn-primary { background: linear-gradient(135deg, #f97316, #ea580c); color: white; width: 100%; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(249, 115, 22, 0.4); }
        
        .help-text { font-size: 12px; color: #6b7280; margin-top: 4px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <div class="logo">🤖</div>
            <div class="header-title">
                <h1><?php echo htmlspecialchars($_SESSION['restaurant_name']); ?></h1>
                <p>Configuración del Chatbot</p>
            </div>
        </div>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="menu.php">Menú</a>
            <a href="configuracion.php" class="active">Configuración</a>
            <a href="conversaciones.php">Conversaciones</a>
            <a href="logout.php">Salir</a>
        </div>
    </div>

    <div class="container">
        <h1 class="page-title">Configuración del Chatbot</h1>

        <?php if (isset($success)): ?>
            <div class="success">✅ <?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST">
            <!-- Apariencia -->
            <div class="card">
                <h2 class="card-title">🎨 Apariencia</h2>
                
                <div class="form-group">
                    <label>Nombre del Chatbot</label>
                    <input type="text" name="chatbot_name" value="<?php echo htmlspecialchars($config['chatbot_name']); ?>" required>
                    <div class="help-text">Este nombre aparecerá en el header del chat</div>
                </div>

                <div class="form-group">
                    <label>Mensaje de Bienvenida</label>
                    <textarea name="welcome_message" required><?php echo htmlspecialchars($config['welcome_message']); ?></textarea>
                    <div class="help-text">Primer mensaje que verá el usuario al abrir el chat</div>
                </div>

                <div class="form-group">
                    <label>Color Principal</label>
                    <input type="color" name="primary_color" value="<?php echo htmlspecialchars($config['primary_color']); ?>" required>
                    <div class="color-preview" style="background: <?php echo htmlspecialchars($config['primary_color']); ?>;"></div>
                    <div class="help-text">Color del botón y elementos del chat</div>
                </div>
            </div>

            <!-- Funcionalidades -->
            <div class="card">
                <h2 class="card-title">⚙️ Funcionalidades</h2>
                
                <div class="checkbox-group">
                    <input type="checkbox" name="enable_delivery" id="enable_delivery" <?php echo $config['enable_delivery'] ? 'checked' : ''; ?>>
                    <label for="enable_delivery">Habilitar servicio a domicilio</label>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" name="enable_reservations" id="enable_reservations" <?php echo $config['enable_reservations'] ? 'checked' : ''; ?>>
                    <label for="enable_reservations">Habilitar reservaciones</label>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" name="enable_whatsapp" id="enable_whatsapp" <?php echo $config['enable_whatsapp'] ? 'checked' : ''; ?>>
                    <label for="enable_whatsapp">Habilitar integración con WhatsApp</label>
                </div>

                <div class="form-group">
                    <label>Número de WhatsApp (opcional)</label>
                    <input type="text" name="whatsapp_number" value="<?php echo htmlspecialchars($config['whatsapp_number'] ?? ''); ?>" placeholder="+57 300 123 4567">
                    <div class="help-text">Incluye código de país</div>
                </div>
            </div>

            <!-- IA -->
            <div class="card">
                <h2 class="card-title">🤖 Inteligencia Artificial</h2>
                
                <div class="form-group">
                    <label>Proveedor de IA</label>
                    <select name="ai_provider" required>
                        <option value="anthropic" <?php echo $config['ai_provider'] === 'anthropic' ? 'selected' : ''; ?>>Anthropic Claude</option>
                        <option value="openai" <?php echo $config['ai_provider'] === 'openai' ? 'selected' : ''; ?>>OpenAI ChatGPT</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>API Key</label>
                    <input type="password" name="api_key" value="<?php echo htmlspecialchars($config['api_key'] ?? ''); ?>" required placeholder="sk-...">
                    <div class="help-text">
                        Obtén tu API Key en: 
                        <a href="https://console.anthropic.com/settings/keys" target="_blank">Anthropic</a> o 
                        <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI</a>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">💾 Guardar Configuración</button>
        </form>
    </div>
</body>
</html>
