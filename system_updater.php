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

if ($action === 'check') {
    // Verificar estado remoto vs local
    // Nota: Esto requiere 'git fetch' que podría pedir clave. 
    // Por simplicidad, intentamos pull directo y capturamos resultado.
    $output = "Listo para buscar actualizaciones.";
} elseif ($action === 'update') {
    // Definir el comando. 2>&1 redirige errores al output estandar
    // Añadimos HOME env variable porque git a veces la necesita
    $cmd = "export HOME=/home/user/web/srv1208645.hstgr.cloud && cd " . __DIR__ . " && git pull origin main 2>&1";
    
    // Ejecutar
    $output = shell_exec($cmd);
    
    if (empty($output)) {
        $output = "Error: No se recibió respuesta del comando git. Verifica permisos o credenciales.";
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
        body { font-family: monospace; background: #1e1e1e; color: #0f0; padding: 20px; }
        .terminal { background: #000; padding: 20px; border-radius: 5px; border: 1px solid #333; white-space: pre-wrap; }
        .btn { padding: 10px 20px; font-size: 1.2em; cursor: pointer; background: #007bff; color: white; border: none; border-radius: 5px; text-decoration: none; display: inline-block; margin-bottom: 20px;}
        .btn:hover { background: #0056b3; }
        .back { color: #888; text-decoration: none; display: block; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>🛰️ Centro de Actualizaciones</h1>
    
    <?php if ($action !== 'update'): ?>
        <p>Este proceso descargará la última versión del software desde la nube (GitHub).</p>
        <a href="?action=update" class="btn">🚀 Buscar e Instalar Actualizaciones</a>
    <?php else: ?>
        <p>Ejecutando proceso de actualización...</p>
        <div class="terminal">> git pull origin main<br><br><?php echo htmlspecialchars($output); ?></div>
        
        <?php if (strpos($output, 'up to date') !== false || strpos($output, 'Fast-forward') !== false): ?>
            <h2 style="color: #48bb78">✅ Sistema Actualizado</h2>
        <?php else: ?>
            <h2 style="color: #ed8936">⚠️ Resultado de la operación</h2>
        <?php endif; ?>
        
        <a href="?action=update" class="btn">🔄 Reintentar</a>
    <?php endif; ?>
    
    <a href="admin.php" class="back">⬅ Volver al Panel</a>
</body>
</html>
