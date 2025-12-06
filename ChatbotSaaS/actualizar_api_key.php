<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualizar API Key</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1f2937;
            margin-bottom: 10px;
        }
        .info {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 12px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .success {
            background: #f0fdf4;
            border-left: 4px solid #22c55e;
            padding: 12px;
            margin: 20px 0;
            border-radius: 4px;
            color: #166534;
        }
        .error {
            background: #fef2f2;
            border-left: 4px solid #ef4444;
            padding: 12px;
            margin: 20px 0;
            border-radius: 4px;
            color: #991b1b;
        }
        label {
            display: block;
            font-weight: 600;
            margin: 15px 0 5px;
            color: #374151;
        }
        input, select {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }
        button {
            background: linear-gradient(135deg, #f97316, #ea580c);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
        }
        button:hover {
            background: linear-gradient(135deg, #ea580c, #c2410c);
        }
        code {
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔑 Actualizar API Key</h1>
        
        <div class="info">
            <strong>ℹ️ Instrucciones:</strong><br>
            1. Ve a <a href="https://console.anthropic.com/settings/keys" target="_blank">console.anthropic.com/settings/keys</a><br>
            2. Crea una nueva API Key o copia la existente<br>
            3. Pégala aquí abajo (debe empezar con <code>sk-ant-</code>)
        </div>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $api_key = trim($_POST['api_key']);
            $provider = $_POST['provider'];
            
            // Validar
            if (empty($api_key)) {
                echo '<div class="error">❌ La API Key no puede estar vacía</div>';
            } elseif ($provider === 'anthropic' && !str_starts_with($api_key, 'sk-ant-')) {
                echo '<div class="error">❌ La API Key de Anthropic debe empezar con <code>sk-ant-</code></div>';
            } elseif ($provider === 'openai' && !str_starts_with($api_key, 'sk-')) {
                echo '<div class="error">❌ La API Key de OpenAI debe empezar con <code>sk-</code></div>';
            } else {
                // Actualizar en BD
                $conn = new mysqli("localhost", "root", "", "menu_restaurante");
                
                if ($conn->connect_error) {
                    echo '<div class="error">❌ Error de conexión: ' . $conn->connect_error . '</div>';
                } else {
                    $stmt = $conn->prepare("UPDATE saas_chatbot_config SET api_key = ?, ai_provider = ? WHERE tenant_id = 1");
                    $stmt->bind_param("ss", $api_key, $provider);
                    
                    if ($stmt->execute()) {
                        echo '<div class="success">
                            <strong>✅ ¡API Key actualizada exitosamente!</strong><br><br>
                            Proveedor: <code>' . htmlspecialchars($provider) . '</code><br>
                            Key: <code>' . substr($api_key, 0, 15) . '...</code><br><br>
                            <a href="test_diagnostico.php">🧪 Probar API ahora</a> | 
                            <a href="demo/test_landing.html">🚀 Ir al Chatbot</a>
                        </div>';
                    } else {
                        echo '<div class="error">❌ Error al actualizar: ' . $stmt->error . '</div>';
                    }
                    
                    $stmt->close();
                    $conn->close();
                }
            }
        }
        ?>

        <form method="POST">
            <label>Proveedor de IA:</label>
            <select name="provider" required>
                <option value="anthropic">Anthropic Claude (Recomendado)</option>
                <option value="openai">OpenAI ChatGPT</option>
            </select>

            <label>API Key:</label>
            <input 
                type="text" 
                name="api_key" 
                placeholder="sk-ant-api03-..." 
                required
                autocomplete="off"
            >

            <button type="submit">💾 Guardar API Key</button>
        </form>
    </div>
</body>
</html>
