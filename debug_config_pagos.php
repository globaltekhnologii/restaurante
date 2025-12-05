<?php
// Script de diagnóstico para config_pagos.php
require_once 'config.php';

echo "<h1>Diagnóstico de Configuración de Pagos</h1>";

$conn = getDatabaseConnection();

// Verificar conexión
if ($conn->connect_error) {
    die("<p style='color:red'>Error de conexión: " . $conn->connect_error . "</p>");
}
echo "<p style='color:green'>✅ Conexión a base de datos exitosa</p>";

// Verificar si existe la tabla
$result = $conn->query("SHOW TABLES LIKE 'metodos_pago_config'");
if ($result->num_rows > 0) {
    echo "<p style='color:green'>✅ Tabla 'metodos_pago_config' existe</p>";
} else {
    echo "<p style='color:red'>❌ Tabla 'metodos_pago_config' NO existe</p>";
    echo "<p><a href='setup_pagos.php'>Ejecutar setup_pagos.php</a></p>";
    exit;
}

// Verificar datos en la tabla
$result = $conn->query("SELECT * FROM metodos_pago_config");
echo "<h2>Datos en metodos_pago_config:</h2>";
echo "<p>Registros encontrados: " . $result->num_rows . "</p>";

if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Método</th><th>Nombre</th><th>Activo</th><th>Número Cuenta</th><th>QR Imagen</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['metodo'] . "</td>";
        echo "<td>" . $row['nombre_display'] . "</td>";
        echo "<td>" . ($row['activo'] ? 'Sí' : 'No') . "</td>";
        echo "<td>" . ($row['numero_cuenta'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['qr_imagen'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:orange'>⚠️ No hay métodos de pago configurados</p>";
    echo "<p>Ejecutando INSERT de métodos por defecto...</p>";
    
    $sql = "INSERT INTO metodos_pago_config (metodo, nombre_display, activo, orden) VALUES
    ('efectivo', 'Efectivo', 1, 1),
    ('nequi', 'Nequi', 1, 2),
    ('daviplata', 'Daviplata', 1, 3),
    ('dale', 'Dale', 1, 4),
    ('bancolombia', 'Bancolombia Ahorros', 1, 5)
    ON DUPLICATE KEY UPDATE nombre_display = VALUES(nombre_display)";
    
    if ($conn->query($sql)) {
        echo "<p style='color:green'>✅ Métodos de pago insertados correctamente</p>";
    } else {
        echo "<p style='color:red'>❌ Error al insertar: " . $conn->error . "</p>";
    }
}

$conn->close();

echo "<hr>";
echo "<p><a href='config_pagos.php'>Ir a Configuración de Pagos</a></p>";
?>
