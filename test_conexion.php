<?php
// Script de prueba de conexión a BD
require_once 'config.php';

echo "=== PRUEBA DE CONEXIÓN A BASE DE DATOS ===\n\n";

try {
    $conn = getDatabaseConnection();
    echo "✅ Conexión exitosa a la base de datos\n\n";
    
    // Verificar tablas
    echo "--- Verificando tablas ---\n";
    $tables = ['platos', 'usuarios', 'pedidos', 'pedidos_items'];
    
    foreach ($tables as $table) {
        $result = $conn->query("SELECT COUNT(*) as total FROM $table");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "✅ Tabla '$table': {$row['total']} registros\n";
        } else {
            echo "❌ Error al consultar tabla '$table': " . $conn->error . "\n";
        }
    }
    
    echo "\n--- Verificando datos de platos ---\n";
    $result = $conn->query("SELECT id, nombre, precio, categoria FROM platos LIMIT 5");
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "  - {$row['nombre']} ({$row['categoria']}) - \${$row['precio']}\n";
        }
    } else {
        echo "⚠️ No hay platos en la base de datos\n";
    }
    
    echo "\n--- Verificando usuario admin ---\n";
    $result = $conn->query("SELECT usuario, nombre, rol FROM usuarios WHERE usuario = 'admin'");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo "✅ Usuario admin encontrado: {$row['nombre']} (rol: {$row['rol']})\n";
    } else {
        echo "⚠️ Usuario admin no encontrado\n";
    }
    
    closeDatabaseConnection($conn);
    
    echo "\n=== FIN DE LA PRUEBA ===\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
