<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

$tenant_id = $_SESSION['tenant_id'];
$conn = new mysqli("localhost", "root", "", "menu_restaurante");
$result = $conn->query("SELECT * FROM saas_chatbot_config WHERE tenant_id = $tenant_id");
$config = $result->fetch_assoc();

// Generar código de integración
$widget_code = <<<HTML
<!-- Chatbot SaaS Widget -->
<script>
  window.chatbotConfig = {
    tenantId: {$tenant_id},
    primaryColor: '{$config['primary_color']}',
    chatbotName: '{$config['chatbot_name']}',
    welcomeMessage: '{$config['welcome_message']}'
  };
</script>
<script src="http://localhost/Restaurante/ChatbotSaaS/widget/chatbot-widget.js"></script>
HTML;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Integración - <?php echo htmlspecialchars($_SESSION['restaurant_name']); ?></title>
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
        .page-title { font-size: 28px; color: #1f2937; margin-bottom: 8px; }
        .page-subtitle { color: #6b7280; margin-bottom: 24px; }
        
        .card { background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 24px; margin-bottom: 24px; }
        .card-title { font-size: 18px; color: #1f2937; margin-bottom: 16px; }
        
        .code-block { background: #1f2937; color: #f3f4f6; padding: 20px; border-radius: 8px; font-family: 'Courier New', monospace; font-size: 13px; line-height: 1.6; overflow-x: auto; position: relative; }
        
        .btn { padding: 12px 24px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
        .btn-primary { background: linear-gradient(135deg, #f97316, #ea580c); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(249, 115, 22, 0.4); }
        
        .steps { counter-reset: step; }
        .step { margin-bottom: 24px; padding-left: 40px; position: relative; }
        .step::before { counter-increment: step; content: counter(step); position: absolute; left: 0; top: 0; width: 28px; height: 28px; background: linear-gradient(135deg, #f97316, #ea580c); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 14px; }
        .step h3 { font-size: 16px; color: #1f2937; margin-bottom: 8px; }
        .step p { color: #6b7280; font-size: 14px; line-height: 1.6; }
        
        .success-toast { display: none; position: fixed; bottom: 24px; right: 24px; background: #22c55e; color: white; padding: 16px 24px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); animation: slideIn 0.3s; }
        @keyframes slideIn { from { transform: translateX(400px); } to { transform: translateX(0); } }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <div class="logo">🤖</div>
            <div class="header-title">
                <h1><?php echo htmlspecialchars($_SESSION['restaurant_name']); ?></h1>
                <p>Código de Integración</p>
            </div>
        </div>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="menu.php">Menú</a>
            <a href="configuracion.php">Configuración</a>
            <a href="conversaciones.php">Conversaciones</a>
            <a href="logout.php">Salir</a>
        </div>
    </div>

    <div class="container">
        <h1 class="page-title">🔗 Integra tu Chatbot</h1>
        <p class="page-subtitle">Copia y pega este código en tu sitio web para activar el chatbot</p>

        <div class="card">
            <h2 class="card-title">Código de Integración</h2>
            <div class="code-block" id="widgetCode"><?php echo htmlspecialchars($widget_code); ?></div>
            <button class="btn btn-primary" onclick="copyCode()" style="margin-top: 16px; width: 100%;">
                📋 Copiar Código
            </button>
        </div>

        <div class="card">
            <h2 class="card-title">📝 Instrucciones</h2>
            <div class="steps">
                <div class="step">
                    <h3>Copia el código</h3>
                    <p>Haz clic en el botón "Copiar Código" de arriba</p>
                </div>
                <div class="step">
                    <h3>Pega en tu sitio web</h3>
                    <p>Abre el archivo HTML de tu sitio web y pega el código justo antes de la etiqueta <code>&lt;/body&gt;</code></p>
                </div>
                <div class="step">
                    <h3>Guarda y recarga</h3>
                    <p>Guarda los cambios y recarga tu página web. El chatbot aparecerá en la esquina inferior derecha</p>
                </div>
                <div class="step">
                    <h3>¡Listo!</h3>
                    <p>Tu chatbot ya está funcionando. Los usuarios podrán interactuar con él inmediatamente</p>
                </div>
            </div>
        </div>

        <div class="card">
            <h2 class="card-title">🎨 Personalización</h2>
            <p style="color: #6b7280; margin-bottom: 16px;">
                Puedes personalizar el chatbot desde la página de <a href="configuracion.php" style="color: #f97316; font-weight: 600;">Configuración</a>:
            </p>
            <ul style="color: #6b7280; margin-left: 20px; line-height: 1.8;">
                <li>Cambiar el nombre del bot</li>
                <li>Modificar el mensaje de bienvenida</li>
                <li>Ajustar el color principal</li>
                <li>Habilitar/deshabilitar funcionalidades</li>
            </ul>
        </div>
    </div>

    <div class="success-toast" id="toast">✅ Código copiado al portapapeles</div>

    <script>
        function copyCode() {
            const code = document.getElementById('widgetCode').textContent;
            navigator.clipboard.writeText(code).then(() => {
                const toast = document.getElementById('toast');
                toast.style.display = 'block';
                setTimeout(() => {
                    toast.style.display = 'none';
                }, 3000);
            });
        }
    </script>
</body>
</html>
