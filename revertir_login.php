<?php
/**
 * Script de reversión para archivos críticos
 * Revierte los cambios en verificar_login.php y config.php
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔄 Reversión de Archivos Críticos</h1>";

// Archivos que NO deben modificarse
$archivos_criticos = [
    'verificar_login.php',
    'config.php'
];

echo "<p>Revirtiendo cambios en archivos críticos del sistema de login...</p>";

$dir = __DIR__;

foreach ($archivos_criticos as $archivo) {
    $ruta = $dir . '/' . $archivo;
    
    if (file_exists($ruta)) {
        $contenido = file_get_contents($ruta);
        
        // Revertir los cambios: descomentar verificarTokenOError
        $contenido = str_replace(
            '// verificarTokenOError(); // DESACTIVADO TEMPORALMENTE',
            'verificarTokenOError();',
            $contenido
        );
        
        // Revertir closeDatabaseConnection a su forma original
        $contenido = str_replace(
            '$conn->close()',
            'closeDatabaseConnection($conn)',
            $contenido
        );
        
        file_put_contents($ruta, $contenido);
        echo "<p style='color: green;'>✅ $archivo revertido</p>";
    } else {
        echo "<p style='color: red;'>❌ $archivo no encontrado</p>";
    }
}

echo "<hr>";
echo "<h2>✅ Reversión Completada</h2>";
echo "<p>Intenta iniciar sesión de nuevo.</p>";
echo "<p><a href='login.php'>Ir al Login</a></p>";
?>
