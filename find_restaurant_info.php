<?php
require_once 'config.php';
$conn = getDatabaseConnection();

// Buscar tabla con información del negocio
$tables = ['info_negocio', 'configuracion', 'restaurante_info'];
$info_found = false;

foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        echo "Tabla encontrada: $table\n";
        $data = $conn->query("SELECT * FROM $table LIMIT 1");
        if ($data && $data->num_rows > 0) {
            $row = $data->fetch_assoc();
            echo "Datos:\n";
            print_r($row);
            $info_found = true;
            break;
        }
    }
}

if (!$info_found) {
    echo "No se encontró tabla de información del negocio.\n";
    echo "Buscando en includes/info_negocio.php...\n";
}

// Verificar configuración actual del chatbot
echo "\n\nConfiguración actual del chatbot (tenant_id=2):\n";
$result = $conn->query("SELECT * FROM saas_chatbot_config WHERE tenant_id = 2");
if ($result && $result->num_rows > 0) {
    $config = $result->fetch_assoc();
    print_r($config);
} else {
    echo "No hay configuración para tenant_id=2\n";
}

$conn->close();
?>
