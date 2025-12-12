<?php
/**
 * Script de Verificación de Integridad de Código
 * Verifica que los archivos modificados tengan la sintaxis correcta
 */

echo "<h1>🔍 Verificación de Integridad del Código</h1>";
echo "<style>
    .success { color: green; padding: 10px; background: #e8f5e9; margin: 5px 0; }
    .error { color: red; padding: 10px; background: #ffebee; margin: 5px 0; }
    .warning { color: orange; padding: 10px; background: #fff3e0; margin: 5px 0; }
</style>";

$archivos_criticos = [
    'procesar_pedido.php',
    'procesar_pedido_mesero.php',
    'api/get_pedidos_chef.php',
    'api/get_pedidos_domiciliario.php',
    'confirmacion_pedido.php',
    'ver_ticket.php',
    'ver_factura.php',
    'chef.php'
];

$errores = 0;
$advertencias = 0;

foreach ($archivos_criticos as $archivo) {
    $ruta = __DIR__ . '/' . $archivo;
    
    if (!file_exists($ruta)) {
        echo "<div class='error'>❌ Archivo no encontrado: $archivo</div>";
        $errores++;
        continue;
    }
    
    // Verificar sintaxis PHP
    $output = [];
    $return_var = 0;
    exec("php -l \"$ruta\" 2>&1", $output, $return_var);
    
    if ($return_var !== 0) {
        echo "<div class='error'>❌ Error de sintaxis en $archivo:<br>" . implode('<br>', $output) . "</div>";
        $errores++;
    } else {
        echo "<div class='success'>✅ $archivo - Sintaxis correcta</div>";
    }
    
    // Verificaciones específicas
    $contenido = file_get_contents($ruta);
    
    // Verificar uso de plato_nombre vs nombre_plato
    if (strpos($contenido, "['nombre_plato']") !== false && 
        strpos($contenido, "['plato_nombre']") === false &&
        strpos($contenido, "??") === false) {
        echo "<div class='warning'>⚠️ $archivo usa 'nombre_plato' sin fallback a 'plato_nombre'</div>";
        $advertencias++;
    }
    
    // Verificar bind_param
    if (preg_match_all('/bind_param\s*\(\s*["\']([sids]+)["\']\s*,/', $contenido, $matches)) {
        foreach ($matches[1] as $tipos) {
            $num_tipos = strlen($tipos);
            // Contar las comas después del string de tipos
            $patron = '/bind_param\s*\(\s*["\']' . preg_quote($tipos, '/') . '["\']\s*,([^)]+)\)/';
            if (preg_match($patron, $contenido, $params)) {
                $num_params = substr_count($params[1], ',') + 1;
                if ($num_tipos !== $num_params) {
                    echo "<div class='error'>❌ $archivo: bind_param tiene $num_tipos tipos pero $num_params parámetros</div>";
                    $errores++;
                }
            }
        }
    }
}

echo "<hr>";
echo "<h2>Resumen:</h2>";
if ($errores === 0 && $advertencias === 0) {
    echo "<div class='success'><h3>🎉 ¡TODO PERFECTO! No se encontraron errores ni advertencias.</h3></div>";
} else {
    if ($errores > 0) {
        echo "<div class='error'><strong>Errores críticos: $errores</strong></div>";
    }
    if ($advertencias > 0) {
        echo "<div class='warning'><strong>Advertencias: $advertencias</strong></div>";
    }
}
?>
