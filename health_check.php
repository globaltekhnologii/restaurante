<?php
// health_check.php - Endpoint para verificadores de uptime
header('Content-Type: application/json');

// Permitir acceso solo si se conoce un token secreto (opcional) o dejar público para UptimeRobot
// Por simplicidad en este caso, lo dejamos público pero sin mostrar datos sensibles.

$response = [
    'status' => 'ok',
    'timestamp' => time(),
    'database' => false
];

// Verificar DB
require_once 'config.php';
try {
    $conn = getDatabaseConnection();
    if ($conn->ping()) {
        $response['database'] = true;
    }
    $conn->close();
} catch (Exception $e) {
    $response['status'] = 'error';
    $response['message'] = 'Database connection failed';
    http_response_code(500);
}

// Verificar espacio en disco (advertencia si queda menos de 100MB)
$freeSpace = disk_free_space(".");
$response['disk_free_mb'] = round($freeSpace / 1024 / 1024, 2);

if ($response['database']) {
    echo json_encode($response);
} else {
    http_response_code(500);
    echo json_encode($response);
}
?>
