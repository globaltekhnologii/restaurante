<?php
// system_updater.php - Módulo de Actualización Automática
session_start();
require_once 'auth_helper.php';

// Solo admin puede actualizar
verificarSesion();
if ($_SESSION['rol'] !== 'admin') {
    die("Acceso denegado");
}

$action = $_GET['action'] ?? '';
$output = '';
$success = false;

if ($action === 'update') {
    // Cambiar al directorio del proyecto
    $dir = __DIR__;
    chdir($dir);
    
    // Ejecutar git pull y capturar salida
    $command = "git pull 2>&1";
    $output_lines = [];
    $return_code = 0;
    
    exec($command, $output_lines, $return_code);
    
    $output = implode("\n", $output_lines);
    
    if (empty($output)) {
        $output = "Error: No se pudo ejecutar el comando. Verifica que Git esté instalado y que shell_exec/exec estén habilitados.";
    } else {
        // Verificar si fue exitoso
        if (stripos($output, 'up to date') !== false || stripos($output, 'Fast-forward') !== false || $return_code === 0) {
            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Actualización de Sistema</title>
    <link rel="stylesheet" href="css/admin-modern.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #1e1e1e; color: #0f0; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: #2d2d2d; padding: 30px; border-radius: 10px; }
        .terminal { background: #000; padding: 20px; border-radius: 5px; border: 1px solid #333; white-space: pre-wrap; font-family: 'Courier New', monospace; color: #0f0; margin: 20px 0; }
        .btn { padding: 12px 24px; font-size: 1.1em; cursor: pointer; background: #007bff; color: white; border: none; border-radius: 5px; text-decoration: none; display: inline-block; margin: 10px 5px;}
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        .back { color: #888; text-decoration: none; display: block; margin-top: 20px; }
        h1 { color: #48bb78; }
        h2 { color: #ed8936; }
        .success { color: #48bb78; }
        .error { color: #ed8936; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛰️ Centro de Actualizaciones</h1>
        
        <?php if ($action !== 'update'): ?>
            <p style="color: #ccc;">Este proceso descargará la última versión del software desde GitHub.</p>
            <p style="color: #999; font-size: 0.9em;">Asegúrate de haber guardado cualquier cambio antes de continuar.</p>
            <a href="?action=update" class="btn btn-success">🚀 Buscar e Instalar Actualizaciones</a>
        <?php else: ?>
            <p style="color: #ccc;">Ejecutando proceso de actualización...</p>
            <div class="terminal">> git pull<br><br><?php echo htmlspecialchars($output); ?></div>
            
            <?php if ($success): ?>
                <h2 class="success">✅ Sistema Actualizado Correctamente</h2>
                <p style="color: #ccc;">Los cambios se han aplicado. Recarga la página para ver las mejoras.</p>
            <?php else: ?>
                <h2 class="error">⚠️ Revisa el Resultado</h2>
                <p style="color: #ccc;">Si ves un error, contacta al soporte técnico.</p>
            <?php endif; ?>
            
            <a href="?action=update" class="btn">🔄 Reintentar</a>
        <?php endif; ?>
        
        <a href="admin.php" class="back">⬅ Volver al Panel</a>
    </div>
</body>
</html>
