<?php
// Configurar entorno para CLI
if (php_sapi_name() !== 'cli') {
    die("Este script solo debe ejecutarse desde la línea de comandos.");
}

// Definir constante para indicar entorno de pruebas
define('TESTING_ENV', true);

// Incluir runner base
require_once __DIR__ . '/TestRunner.php';

echo "\n🚀 Iniciando Suite de Pruebas Restaurante...\n";
echo "=============================================\n\n";

$totalPassed = 0;
$totalFailed = 0;

// Función para buscar archivos de test recursivamente
function getTestFiles($dir) {
    $results = [];
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            $results = array_merge($results, getTestFiles($path));
        } else {
            if (strpos($file, 'Test') === 0 && strpos($file, '.php') !== false && $file !== 'TestRunner.php') {
                $results[] = $path;
            }
        }
    }
    return $results;
}

$testFiles = getTestFiles(__DIR__);

foreach ($testFiles as $file) {
    echo "Running " . basename($file) . "...\n";
    require_once $file;
    
    $className = basename($file, '.php');
    if (class_exists($className)) {
        $test = new $className();
        $methods = get_class_methods($test);
        
        foreach ($methods as $method) {
            if (strpos($method, 'test') === 0) {
                // echo "   -> $method\n";
                try {
                    $test->$method();
                } catch (Exception $e) {
                    echo "   ⚠️ EXCEPTION in $method: " . $e->getMessage() . "\n";
                    $totalFailed++; // Contar excepción como fallo
                }
            }
        }
        
        $results = $test->getResults();
        $totalPassed += $results['passed'];
        $totalFailed += $results['failed'];
    }
    echo "\n";
}

echo "=============================================\n";
if ($totalFailed === 0) {
    echo "✅ SUCCESS: Todos los tests pasaron ($totalPassed aserciones).\n";
    exit(0);
} else {
    echo "❌ FAILURE: $totalFailed aserciones fallaron. ($totalPassed pasaron).\n";
    exit(1);
}
?>
