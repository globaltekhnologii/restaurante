<?php
require_once 'config.php';
$conn = getDatabaseConnection();

// 1. Obtener información real del restaurante
$result = $conn->query("SELECT * FROM configuracion_sistema WHERE id = 1 LIMIT 1");
if ($result && $result->num_rows > 0) {
    $info = $result->fetch_assoc();
    
    $nombre = $info['nombre_restaurante'];
    $telefono = $info['telefono'];
    $direccion = $info['direccion'];
    $horario = $info['horario_atencion'];
    
    echo "Información del restaurante:\n";
    echo "Nombre: $nombre\n";
    echo "Teléfono: $telefono\n";
    echo "Dirección: $direccion\n";
    echo "Horario: $horario\n\n";
    
    // 2. Actualizar configuración del chatbot (tenant_id = 2)
    $stmt = $conn->prepare("UPDATE saas_chatbot_config SET 
        phone = ?,
        address = ?,
        business_hours = ?,
        restaurant_name = ?
        WHERE tenant_id = 2");
    
    $stmt->bind_param("ssss", $telefono, $direccion, $horario, $nombre);
    
    if ($stmt->execute()) {
        echo "✅ Configuración del chatbot actualizada correctamente.\n";
    } else {
        echo "❌ Error actualizando: " . $stmt->error . "\n";
    }
    
    // 3. Verificar actualización
    echo "\nConfiguración actualizada:\n";
    $result = $conn->query("SELECT phone, address, business_hours, restaurant_name FROM saas_chatbot_config WHERE tenant_id = 2");
    if ($result && $result->num_rows > 0) {
        $config = $result->fetch_assoc();
        print_r($config);
    }
    
} else {
    echo "❌ No se encontró información del restaurante en configuracion_sistema.\n";
}

$conn->close();
?>
