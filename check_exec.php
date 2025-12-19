<?php
// Script de diagnóstico para verificar funciones de ejecución
session_start();
require_once 'auth_helper.php';

// Solo admin
verificarSesion();
if ($_SESSION['rol'] !== 'admin') {
    die("Acceso denegado");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Diagnóstico de Funciones de Ejecución</title>
    <style>
        body { font-family: 'Courier New', monospace; background: #1e1e1e; color: #0f0; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: #2d2d2d; padding: 30px; border-radius: 10px; }
        h1 { color: #48bb78; }
        .test { background: #000; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #666; }
        .success { border-left-color: #48bb78; }
        .error { border-left-color: #ed8936; }
        .label { color: #888; font-weight: bold; }
        .result { color: #0f0; margin-top: 5px; }
        pre { background: #000; padding: 10px; border-radius: 3px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Diagnóstico de Funciones de Ejecución PHP</h1>
        
        <?php
        // Test 1: Verificar si las funciones existen
        echo '<div class="test">';
        echo '<div class="label">1. Verificando si las funciones están definidas:</div>';
        echo '<div class="result">';
        echo 'shell_exec: ' . (function_exists('shell_exec') ? '✅ Existe' : '❌ No existe') . '<br>';
        echo 'exec: ' . (function_exists('exec') ? '✅ Existe' : '❌ No existe') . '<br>';
        echo 'system: ' . (function_exists('system') ? '✅ Existe' : '❌ No existe') . '<br>';
        echo 'passthru: ' . (function_exists('passthru') ? '✅ Existe' : '❌ No existe') . '<br>';
        echo '</div>';
        echo '</div>';
        
        // Test 2: Verificar funciones deshabilitadas
        echo '<div class="test">';
        echo '<div class="label">2. Funciones deshabilitadas en php.ini:</div>';
        $disabled = ini_get('disable_functions');
        echo '<div class="result">';
        if (empty($disabled)) {
            echo '✅ Ninguna función deshabilitada<br>';
        } else {
            echo '⚠️ Funciones deshabilitadas:<br>';
            echo '<pre>' . htmlspecialchars($disabled) . '</pre>';
        }
        echo '</div>';
        echo '</div>';
        
        // Test 3: Intentar ejecutar comando simple con shell_exec
        echo '<div class="test ';
        $shell_result = @shell_exec('echo "test"');
        echo ($shell_result !== null) ? 'success' : 'error';
        echo '">';
        echo '<div class="label">3. Test de shell_exec:</div>';
        echo '<div class="result">';
        if ($shell_result !== null) {
            echo '✅ shell_exec funciona<br>';
            echo 'Resultado: <pre>' . htmlspecialchars($shell_result) . '</pre>';
        } else {
            echo '❌ shell_exec está bloqueado o falló<br>';
        }
        echo '</div>';
        echo '</div>';
        
        // Test 4: Intentar ejecutar comando simple con exec
        echo '<div class="test ';
        $exec_output = [];
        $exec_return = -1;
        @exec('echo "test"', $exec_output, $exec_return);
        echo (!empty($exec_output)) ? 'success' : 'error';
        echo '">';
        echo '<div class="label">4. Test de exec:</div>';
        echo '<div class="result">';
        if (!empty($exec_output)) {
            echo '✅ exec funciona<br>';
            echo 'Resultado: <pre>' . htmlspecialchars(implode("\n", $exec_output)) . '</pre>';
            echo 'Return code: ' . $exec_return . '<br>';
        } else {
            echo '❌ exec está bloqueado o falló<br>';
        }
        echo '</div>';
        echo '</div>';
        
        // Test 5: Verificar si Git está disponible
        echo '<div class="test ';
        $git_output = [];
        @exec('git --version 2>&1', $git_output);
        echo (!empty($git_output)) ? 'success' : 'error';
        echo '">';
        echo '<div class="label">5. Test de Git:</div>';
        echo '<div class="result">';
        if (!empty($git_output)) {
            echo '✅ Git está instalado y accesible<br>';
            echo 'Versión: <pre>' . htmlspecialchars(implode("\n", $git_output)) . '</pre>';
        } else {
            echo '❌ Git no está disponible o exec está bloqueado<br>';
        }
        echo '</div>';
        echo '</div>';
        
        // Test 6: Información del servidor
        echo '<div class="test">';
        echo '<div class="label">6. Información del servidor:</div>';
        echo '<div class="result">';
        echo 'PHP Version: ' . phpversion() . '<br>';
        echo 'Server Software: ' . ($_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido') . '<br>';
        echo 'OS: ' . PHP_OS . '<br>';
        echo 'Safe Mode: ' . (ini_get('safe_mode') ? 'Activado ❌' : 'Desactivado ✅') . '<br>';
        echo '</div>';
        echo '</div>';
        
        // Conclusión
        echo '<div class="test">';
        echo '<div class="label">📋 CONCLUSIÓN:</div>';
        echo '<div class="result">';
        if ($shell_result !== null || !empty($exec_output)) {
            echo '✅ <strong>Las funciones de ejecución están HABILITADAS</strong><br>';
            echo 'El módulo de actualización debería funcionar. Si falla, el error es otro.<br>';
        } else {
            echo '❌ <strong>Las funciones de ejecución están BLOQUEADAS</strong><br>';
            echo 'Necesitas contactar a Hostinger para habilitar exec/shell_exec<br>';
            echo 'O usar un método alternativo (script local SSH, GitHub Actions, etc.)<br>';
        }
        echo '</div>';
        echo '</div>';
        ?>
        
        <br>
        <a href="admin.php" style="color: #888; text-decoration: none;">⬅ Volver al Panel</a>
    </div>
</body>
</html>
