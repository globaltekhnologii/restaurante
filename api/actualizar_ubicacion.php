<?php
header('Content-Type: application/json');
session_start();

require_once '../config.php';
require_once '../auth_helper.php';

// Verificar sesión y rol
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'domiciliario') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Obtener datos
$data = json_decode(file_get_contents('php://input'), true);
if (!$data && !empty($_POST)) {
    $data = $_POST;
}

$latitud = isset($data['latitud']) ? floatval($data['latitud']) : null;
$longitud = isset($data['longitud']) ? floatval($data['longitud']) : null;

if (!$latitud || !$longitud) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Coordenadas inválidas']);
    exit;
}

$usuario_id = $_SESSION['user_id'];
$conn = getDatabaseConnection();

// Verificar si ya existe registro de ubicación para este usuario
$check = $conn->prepare("SELECT id FROM ubicacion_domiciliarios WHERE usuario_id = ?");
$check->bind_param("i", $usuario_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    // Actualizar
    $stmt = $conn->prepare("UPDATE ubicacion_domiciliarios SET latitud = ?, longitud = ?, ultima_actualizacion = NOW() WHERE usuario_id = ?");
    $stmt->bind_param("ddi", $latitud, $longitud, $usuario_id);
} else {
    // Insertar
    $stmt = $conn->prepare("INSERT INTO ubicacion_domiciliarios (usuario_id, latitud, longitud) VALUES (?, ?, ?)");
    $stmt->bind_param("idd", $usuario_id, $latitud, $longitud);
}

$check->close();

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al guardar ubicación: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
