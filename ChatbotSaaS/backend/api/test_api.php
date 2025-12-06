<?php
/**
 * Test API - Verificar que el endpoint funciona
 */

// Headers CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Conexión a BD
$conn = new mysqli("localhost", "root", "", "menu_restaurante");

if ($conn->connect_error) {
    echo json_encode(['error' => 'Error de conexión: ' . $conn->connect_error]);
    exit();
}

// Test básico
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    echo json_encode([
        'success' => true,
        'message' => '✅ API funcionando correctamente',
        'received' => $input,
        'db_connected' => true
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Solo se aceptan peticiones POST'
    ]);
}
?>
