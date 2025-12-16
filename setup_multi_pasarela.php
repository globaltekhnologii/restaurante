<?php
// Script para crear tabla de configuración de pagos
require_once 'config.php';

echo "<h2>🔧 Configuración Multi-Pasarela</h2>";

try {
    $conn = getDatabaseConnection();
    
    // Crear tabla de configuración de pagos
    $sql = "CREATE TABLE IF NOT EXISTS config_pagos (
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
        echo "<p style='color: green;'>✅ Tabla config_pagos creada</p>";
        
        // Insertar configuraciones por defecto
        $pasarelas = [
            ['bold', 0, 'sandbox'],
            ['mercadopago', 0, 'sandbox']
        ];
        
        foreach ($pasarelas as $p) {
            $stmt = $conn->prepare("INSERT IGNORE INTO config_pagos (pasarela, activa, modo) VALUES (?, ?, ?)");
            $stmt->bind_param("sis", $p[0], $p[1], $p[2]);
            $stmt->execute();
        }
        
        echo "<p style='color: green;'>✅ Pasarelas configuradas: Bold, Mercado Pago</p>";
        echo "<br><a href='admin_config_pagos.php' style='padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;'>Configurar Pasarelas</a>";
    } else {
        throw new Exception($conn->error);
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
