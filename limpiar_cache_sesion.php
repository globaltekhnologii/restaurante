<?php
session_start();

// Limpiar caché de info_negocio
unset($_SESSION['info_negocio']);

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Caché Limpiado</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 100px auto; padding: 20px; background: #f5f5f5; text-align: center; }
        .container { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #28a745; }
        .btn { display: inline-block; margin-top: 20px; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
<div class='container'>
    <h1>✅ Caché Limpiado</h1>
    <p>El caché de configuración ha sido eliminado.</p>
    <p>Ahora verás los datos correctos de tu tenant.</p>
    <a href='admin_configuracion.php' class='btn'>→ Ir a Configuración</a>
</div>
</body>
</html>";
?>
