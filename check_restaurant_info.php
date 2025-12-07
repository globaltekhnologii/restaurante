<?php
require_once 'config.php';
$conn = getDatabaseConnection();

// Obtener información del negocio desde config.php
require_once 'config.php';

echo "Información del Restaurante:\n";
echo "Nombre: " . NOMBRE_RESTAURANTE . "\n";
echo "Teléfono: " . TELEFONO_RESTAURANTE . "\n";
echo "Dirección: " . DIRECCION_RESTAURANTE . "\n";

// Obtener horarios si existen en la BD
$result = $conn->query("SELECT * FROM horarios_atencion LIMIT 1");
if ($result && $result->num_rows > 0) {
    $horario = $result->fetch_assoc();
    echo "\nHorarios:\n";
    print_r($horario);
} else {
    echo "\nNo hay horarios configurados en la BD.\n";
}

// Verificar configuración actual del chatbot
$result = $conn->query("SELECT * FROM saas_chatbot_config WHERE tenant_id = 2");
if ($result && $result->num_rows > 0) {
    $config = $result->fetch_assoc();
    echo "\nConfiguración actual del chatbot:\n";
    echo "Teléfono: " . $config['phone'] . "\n";
    echo "Dirección: " . $config['address'] . "\n";
    echo "Horario: " . $config['business_hours'] . "\n";
}

$conn->close();
?>
