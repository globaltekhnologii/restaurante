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

if ($rol === 'admin' || $rol === 'cajero') {
    // Buscar NUEVOS pedidos pendientes (para aceptar)
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM pedidos WHERE estado = 'pendiente' AND fecha_pedido > ?");
    $stmt->bind_param("s", $fecha_corte);
    $stmt->execute();
    $result = $stmt->get_result();
    $response['nuevos_pedidos'] += $result->fetch_assoc()['count'];
    $stmt->close();
}

if ($rol === 'chef' || $rol === 'admin') {
    // Buscar pedidos CONFIRMADOS (que entran a cocina)
    // Para el chef, un pedido "nuevo" es uno que acaba de ser confirmado
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM pedidos WHERE estado = 'confirmado' AND fecha_actualizacion > ?");
    $stmt->bind_param("s", $fecha_corte);
    $stmt->execute();
    $result = $stmt->get_result();
    // Sumamos a nuevos_pedidos para activar la misma alerta sonora/visual
    $response['nuevos_pedidos'] += $result->fetch_assoc()['count'];
    $stmt->close();
}

if ($rol === 'mesero' || $rol === 'domiciliario' || $rol === 'admin') {
    // Buscar pedidos LISTOS (para entegar) actualizados RECIENTEMENTE
    // Usamos fecha_actualizacion > fecha_corte Y estado = listo
    // Filtro adicional: Si es domiciliario, SOLO domicilio. Si es mesero, solo Mesa (opcional, pero por ahora general)
    
    $query = "SELECT COUNT(*) as count FROM pedidos WHERE estado = 'listo' AND fecha_actualizacion > ?";
    
    // Opcional: Refinar por tipo.
    if ($rol === 'domiciliario') {
        $query .= " AND tipo_pedido = 'domicilio'";
    }
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $fecha_corte);
    $stmt->execute();
    $result = $stmt->get_result();
    $response['pedidos_listos'] = $result->fetch_assoc()['count'];
    $stmt->close();
}

echo json_encode($response);
$conn->close();
?>
