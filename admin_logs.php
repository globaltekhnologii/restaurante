<?php
// admin_logs.php - Visor de Logs de Seguridad
require_once 'includes/auth_helper.php';
require_once 'config.php';

// Verificar permisos de admin
verificarSesion();
// Asumimos que el rol admin está en la sesión
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    // Si no hay rol en sesión, verificar si es el admin logueado (login.php pone 'loggedin' => true)
    // En este sistema, parece que login.php maneja roles de forma simple.
    // Ajustar según lógica de admin.php
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        header("Location: index.php");
        exit;
    }
}

$logFile = ini_get('error_log');
$logs = [];

if ($logFile && file_exists($logFile) && is_readable($logFile)) {
    // Leer últimas 50 líneas
    $lines = file($logFile);
    if ($lines) {
        $logs = array_slice($lines, -50);
        $logs = array_reverse($logs); // Más reciente primero
    }
} else {
    $logs[] = "No se pudo leer el archivo de logs: " . ($logFile ? $logFile : 'No definido en php.ini');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Monitor de Logs - Admin</title>
    <style>
        body { font-family: sans-serif; background: #f4f4f4; padding: 20px; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn { text-decoration: none; padding: 10px 20px; background: #007bff; color: white; border-radius: 5px; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .log-container {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 0.9em;
            overflow-x: auto;
            max-height: 600px;
            overflow-y: scroll;
        }
        .log-entry {
            padding: 5px 0;
            border-bottom: 1px solid #333;
            white-space: pre-wrap;
        }
        .log-entry:hover { background: #2a2a2a; }
        .error { color: #f48771; }
        .warning { color: #cca700; }
        .notice { color: #4ec9b0; }
    </style>
</head>
<body>
    <div class="top-bar">
        <h1>🔍 Monitor de Logs del Servidor</h1>
        <a href="admin.php" class="btn">Volver al Panel</a>
    </div>

    <div style="max-width: 1200px; margin: 0 auto;">
        <div class="card">
            <h3>Últimas 50 entradas de error</h3>
            <p><strong>Archivo:</strong> <?php echo htmlspecialchars($logFile); ?></p>
            
            <div class="log-container">
                <?php if (empty($logs)): ?>
                    <div class="log-entry">No hay logs registrados o el archivo está vacío.</div>
                <?php else: ?>
                    <?php foreach ($logs as $line): ?>
                        <?php 
                            $class = '';
                            if (stripos($line, 'Error') !== false || stripos($line, 'Fatal') !== false) $class = 'error';
                            elseif (stripos($line, 'Warning') !== false) $class = 'warning';
                        ?>
                        <div class="log-entry <?php echo $class; ?>"><?php echo htmlspecialchars($line); ?></div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
