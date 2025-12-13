<?php
session_start();
require_once '../auth_helper.php';
require_once '../config.php';

// Verificar sesión
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$conn = getDatabaseConnection();
$response = [
    'nuevos_pedidos' => 0,
    'pedidos_listos' => 0,
    'timestamp' => time()
];

// Obtener último chequeo del cliente (o hace 10 segundos por defecto)
$last_check = isset($_GET['last_check']) ? intval($_GET['last_check']) : (time() - 10);
// Convertir a formato MySQL
$fecha_corte = date('Y-m-d H:i:s', $last_check);

// Lógica según ROL
$rol = $_SESSION['rol'];

if ($rol === 'admin' || $rol === 'cajero' || $rol === 'chef') {
    // Buscar NUEVOS pedidos (para cocina/admin) creado RECIENTEMENTE
    // Usamos fecha_pedido > fecha_corte
    $sql = "SELECT COUNT(*) as count FROM pedidos WHERE estado = 'pendiente' AND fecha_pedido > ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $fecha_corte);
    $stmt->execute();
    $result = $stmt->get_result();
    $response['nuevos_pedidos'] = $result->fetch_assoc()['count'];
    $stmt->close();
}

if ($rol === 'mesero' || $rol === 'admin') {
    // Buscar pedidos LISTOS (para meseros) actualizados RECIENTEMENTE
    // Usamos fecha_actualizacion > fecha_corte Y estado = listo
    $sql = "SELECT COUNT(*) as count FROM pedidos WHERE estado = 'listo' AND fecha_actualizacion > ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $fecha_corte);
    $stmt->execute();
    $result = $stmt->get_result();
    $response['pedidos_listos'] = $result->fetch_assoc()['count'];
    $stmt->close();
}

echo json_encode($response);
$conn->close();
?>
