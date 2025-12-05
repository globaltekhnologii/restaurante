<?php
// Obtener la IP local del servidor
$ip_server = getHostByName(getHostName());
// Si getHostByName devuelve localhost, intentar obtener la IP real
if ($ip_server == '127.0.0.1' || $ip_server == '::1') {
    // Ejecutar ipconfig para buscar la IP local (Windows)
    $output = shell_exec('ipconfig');
    preg_match('/IPv4 Address.*: (192\.168\.\d+\.\d+)/', $output, $matches);
    if (isset($matches[1])) {
        $ip_server = $matches[1];
    }
}

$base_url = "http://" . $ip_server . "/Restaurante";
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($base_url);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Móvil - Restaurante El Sabor</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f5f7fa;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            text-align: center;
        }
        .card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            max-width: 400px;
            width: 90%;
        }
        h1 { color: #333; margin-bottom: 10px; }
        p { color: #666; margin-bottom: 30px; }
        img { 
            border: 10px solid white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .url {
            background: #e2e8f0;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 1.2em;
            color: #2d3748;
            word-break: break-all;
        }
        .note {
            font-size: 0.9em;
            color: #718096;
            margin-top: 20px;
            background: #fff5f5;
            padding: 10px;
            border-radius: 5px;
            border-left: 4px solid #fc8181;
        }
        .btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>📱 Acceso Móvil</h1>
        <p>Escanea este código con tu celular para acceder al sistema:</p>
        
        <img src="<?php echo $qr_url; ?>" alt="QR Code">
        
        <p>O escribe esta dirección en tu navegador:</p>
        <div class="url"><?php echo $base_url; ?></div>
        
        <div class="note">
            ⚠️ Asegúrate de que tu celular esté conectado a la misma red Wi-Fi que esta computadora.
        </div>

        <a href="index.php" class="btn">Volver al Inicio</a>
    </div>
</body>
</html>
