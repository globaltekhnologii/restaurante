<?php
// Script de diagnóstico para inserción de platos
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Diagnóstico de Inserción de Platos</h1>";

// 1. Verificar que los archivos helper existen
echo "<h2>1. Verificando archivos requeridos:</h2>";
$required_files = [
    'config.php',
    'file_upload_helper.php',
    'includes/csrf_helper.php',
    'includes/sanitize_helper.php'
];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "✅ $file existe<br>";
    } else {
        echo "❌ $file NO EXISTE<br>";
    }
}

// 2. Intentar cargar los archivos
echo "<h2>2. Intentando cargar archivos:</h2>";
try {
    require_once 'config.php';
    echo "✅ config.php cargado<br>";
} catch (Exception $e) {
    echo "❌ Error en config.php: " . $e->getMessage() . "<br>";
}

try {
    require_once 'file_upload_helper.php';
    echo "✅ file_upload_helper.php cargado<br>";
} catch (Exception $e) {
    echo "❌ Error en file_upload_helper.php: " . $e->getMessage() . "<br>";
}

try {
    require_once 'includes/csrf_helper.php';
    echo "✅ csrf_helper.php cargado<br>";
} catch (Exception $e) {
    echo "❌ Error en csrf_helper.php: " . $e->getMessage() . "<br>";
}

try {
    require_once 'includes/sanitize_helper.php';
    echo "✅ sanitize_helper.php cargado<br>";
} catch (Exception $e) {
    echo "❌ Error en sanitize_helper.php: " . $e->getMessage() . "<br>";
}

// 3. Verificar funciones
echo "<h2>3. Verificando funciones disponibles:</h2>";
$required_functions = [
    'getDatabaseConnection',
    'validarImagenSubida',
    'moverArchivoSubido',
    'csrf_field',
    'cleanString',
    'cleanHtml',
    'cleanFloat'
];

foreach ($required_functions as $func) {
    if (function_exists($func)) {
        echo "✅ $func() existe<br>";
    } else {
        echo "❌ $func() NO EXISTE<br>";
    }
}

// 4. Verificar permisos de carpeta
echo "<h2>4. Verificando permisos de imagenes_platos/:</h2>";
if (is_dir('imagenes_platos')) {
    echo "✅ Carpeta existe<br>";
    if (is_writable('imagenes_platos')) {
        echo "✅ Carpeta tiene permisos de escritura<br>";
    } else {
        echo "❌ Carpeta NO tiene permisos de escritura<br>";
    }
} else {
    echo "⚠️ Carpeta no existe (se creará automáticamente)<br>";
}

echo "<h2>5. Conclusión:</h2>";
echo "Si todos los checks anteriores están en ✅, el problema puede ser:<br>";
echo "- Error en la validación de datos<br>";
echo "- Problema con el archivo de imagen<br>";
echo "- Error en la consulta SQL<br>";
echo "<br>";
echo "<a href='admin.php'>Volver al Admin</a>";
?>
