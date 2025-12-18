<?php
require_once 'config.php';

$conn = getDatabaseConnection();

// Verificar si existe la tabla
$result = $conn->query("SHOW TABLES LIKE 'config_pagos'");

if ($result->num_rows > 0) {
    echo "<h2 style='color: green;'>✅ Tabla config_pagos ya existe</h2>";
    echo "<p><a href='admin_config_pagos.php' style='padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;'>Ir a Configuración</a></p>";
} else {
    echo "<h2 style='color: orange;'>⚠️ Tabla no existe, ejecutando migración...</h2>";
    
    // Crear tabla
    $sql = "CREATE TABLE config_pagos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        pasarela VARCHAR(50) NOT NULL UNIQUE,
        activa BOOLEAN DEFAULT 0,
        modo ENUM('sandbox', 'production') DEFAULT 'sandbox',
        public_key TEXT,
        secret_key TEXT,
        configuracion JSON,
        fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($sql)) {
        echo "<p style='color: green;'>✅ Tabla creada</p>";
        
        // Insertar pasarelas
        $conn->query("INSERT INTO config_pagos (pasarela, activa, modo) VALUES ('bold', 0, 'sandbox')");
        $conn->query("INSERT INTO config_pagos (pasarela, activa, modo) VALUES ('mercadopago', 0, 'sandbox')");
        
        echo "<p style='color: green;'>✅ Pasarelas configuradas</p>";
        echo "<br><a href='admin_config_pagos.php' style='padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;'>Ir a Configuración</a>";
    } else {
        echo "<p style='color: red;'>❌ Error: " . $conn->error . "</p>";
    }
}

$conn->close();
?>
