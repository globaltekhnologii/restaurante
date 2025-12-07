<?php
require_once 'config.php';
$conn = getDatabaseConnection();

// 1. Agregar columnas faltantes a saas_chatbot_config
echo "Agregando columnas a saas_chatbot_config...\n";

$columns_to_add = [
    "ALTER TABLE saas_chatbot_config ADD COLUMN IF NOT EXISTS phone VARCHAR(20) DEFAULT NULL",
    "ALTER TABLE saas_chatbot_config ADD COLUMN IF NOT EXISTS address VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE saas_chatbot_config ADD COLUMN IF NOT EXISTS business_hours VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE saas_chatbot_config ADD COLUMN IF NOT EXISTS restaurant_name VARCHAR(100) DEFAULT NULL"
];

foreach ($columns_to_add as $sql) {
    if ($conn->query($sql)) {
        echo "✅ Columna agregada.\n";
    } else {
        // Ignorar error si la columna ya existe
        if (strpos($conn->error, 'Duplicate column') === false) {
            echo "⚠️ " . $conn->error . "\n";
        }
    }
}

// 2. Obtener información del restaurante
$result = $conn->query("SELECT * FROM configuracion_sistema WHERE id = 1 LIMIT 1");
if ($result && $result->num_rows > 0) {
    $info = $result->fetch_assoc();
    
    $nombre = $info['nombre_restaurante'];
    $telefono = $info['telefono'];
    $direccion = $info['direccion'];
    $horario = $info['horario_atencion'];
    
    echo "\nActualizando configuración del chatbot...\n";
    
    // 3. Actualizar configuración del chatbot
    $stmt = $conn->prepare("UPDATE saas_chatbot_config SET 
        phone = ?,
        address = ?,
        business_hours = ?,
        restaurant_name = ?
        WHERE tenant_id = 2");
    
    $stmt->bind_param("ssss", $telefono, $direccion, $horario, $nombre);
    
    if ($stmt->execute()) {
        echo "✅ Configuración actualizada:\n";
        echo "   Nombre: $nombre\n";
        echo "   Teléfono: $telefono\n";
        echo "   Dirección: $direccion\n";
        echo "   Horario: $horario\n";
    } else {
        echo "❌ Error: " . $stmt->error . "\n";
    }
}

$conn->close();
?>
