<?php
// verificar_pagos.php - Diagnóstico del sistema de pagos
require_once 'config.php';

echo "<h1>Diagnóstico del Sistema de Pagos</h1>";
echo "<style>body { font-family: Arial; padding: 20px; } .ok { color: green; } .error { color: red; } pre { background: #f5f5f5; padding: 10px; }</style>";

$conn = getDatabaseConnection();

// 1. Verificar si existe la tabla pagos
echo "<h2>1. Verificando tabla 'pagos'</h2>";
$result = $conn->query("SHOW TABLES LIKE 'pagos'");
if ($result->num_rows > 0) {
    echo "<p class='ok'>✓ Tabla 'pagos' existe</p>";
    
    // Mostrar estructura
    echo "<h3>Estructura de la tabla:</h3>";
    $structure = $conn->query("DESCRIBE pagos");
    echo "<table border='1' cellpadding='5'><tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $structure->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Contar registros
    $count = $conn->query("SELECT COUNT(*) as total FROM pagos")->fetch_assoc();
    echo "<p>Total de pagos registrados: <strong>{$count['total']}</strong></p>";
    
    // Mostrar últimos 5 pagos
    if ($count['total'] > 0) {
        echo "<h3>Últimos 5 pagos:</h3>";
        $pagos = $conn->query("SELECT p.*, ped.numero_pedido 
                               FROM pagos p 
                               LEFT JOIN pedidos ped ON p.pedido_id = ped.id 
                               ORDER BY p.fecha_pago DESC LIMIT 5");
        echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Pedido</th><th>Método</th><th>Monto</th><th>Fecha</th></tr>";
        while ($row = $pagos->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['numero_pedido']}</td>";
            echo "<td>{$row['metodo_pago']}</td>";
            echo "<td>\${$row['monto']}</td>";
            echo "<td>{$row['fecha_pago']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} else {
    echo "<p class='error'>✗ Tabla 'pagos' NO existe</p>";
    echo "<p><strong>Solución:</strong> Ejecuta el archivo <code>sql/pagos.sql</code> en phpMyAdmin</p>";
}

// 2. Verificar columna 'pagado' en tabla pedidos
echo "<h2>2. Verificando columna 'pagado' en tabla pedidos</h2>";
$result = $conn->query("SHOW COLUMNS FROM pedidos LIKE 'pagado'");
if ($result->num_rows > 0) {
    echo "<p class='ok'>✓ Columna 'pagado' existe en tabla pedidos</p>";
    
    // Contar pedidos pagados
    $stats = $conn->query("SELECT 
                            COUNT(*) as total,
                            SUM(pagado = 1) as pagados,
                            SUM(pagado = 0) as pendientes
                          FROM pedidos")->fetch_assoc();
    echo "<p>Total pedidos: <strong>{$stats['total']}</strong></p>";
    echo "<p>Pagados: <strong class='ok'>{$stats['pagados']}</strong></p>";
    echo "<p>Pendientes: <strong class='error'>{$stats['pendientes']}</strong></p>";
} else {
    echo "<p class='error'>✗ Columna 'pagado' NO existe en tabla pedidos</p>";
    echo "<p><strong>Solución:</strong> Ejecuta: <code>ALTER TABLE pedidos ADD COLUMN pagado TINYINT(1) DEFAULT 0 AFTER estado;</code></p>";
}

// 3. Verificar tabla metodos_pago_config
echo "<h2>3. Verificando tabla 'metodos_pago_config'</h2>";
$result = $conn->query("SHOW TABLES LIKE 'metodos_pago_config'");
if ($result->num_rows > 0) {
    echo "<p class='ok'>✓ Tabla 'metodos_pago_config' existe</p>";
    
    $metodos = $conn->query("SELECT * FROM metodos_pago_config ORDER BY orden");
    echo "<h3>Métodos de pago configurados:</h3>";
    echo "<table border='1' cellpadding='5'><tr><th>Método</th><th>Nombre</th><th>Activo</th><th>Número Cuenta</th></tr>";
    while ($row = $metodos->fetch_assoc()) {
        $activo = $row['activo'] ? "<span class='ok'>Sí</span>" : "<span class='error'>No</span>";
        echo "<tr>";
        echo "<td>{$row['metodo']}</td>";
        echo "<td>{$row['nombre_display']}</td>";
        echo "<td>{$activo}</td>";
        echo "<td>{$row['numero_cuenta']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='error'>✗ Tabla 'metodos_pago_config' NO existe</p>";
    echo "<p><strong>Solución:</strong> Ejecuta el archivo <code>sql/pagos.sql</code> en phpMyAdmin</p>";
}

// 4. Verificar archivos necesarios
echo "<h2>4. Verificando archivos del sistema</h2>";
$archivos = [
    'registrar_pago.php',
    'procesar_pago.php',
    'ver_comprobante_pago.php',
    'ver_factura.php',
    'config_pagos.php'
];

foreach ($archivos as $archivo) {
    if (file_exists($archivo)) {
        echo "<p class='ok'>✓ {$archivo} existe</p>";
    } else {
        echo "<p class='error'>✗ {$archivo} NO existe</p>";
    }
}

echo "<hr>";
echo "<h2>Resumen</h2>";
echo "<p>Si todos los checks están en verde (✓), el sistema de pagos está correctamente configurado.</p>";
echo "<p>Si hay errores (✗), sigue las soluciones indicadas.</p>";

$conn->close();
?>
