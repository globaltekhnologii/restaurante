<?php
header('Content-Type: application/json');

require_once '../config.php';
require_once '../includes/tenant_context.php'; // NUEVO: Soporte multi-tenencia

// Validar pedido_id
if (!isset($_GET['pedido_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de pedido requerido']);
    exit;
}

$pedido_id = intval($_GET['pedido_id']);
$conn = getDatabaseConnection();
$tenant_id = getCurrentTenantId(); // Obtener tenant actual

// Obtener ubicación del domiciliario asignado al pedido
// UNIR con tabla de usuarios para obtener nombre y teléfono
// UNIR con tabla de ubicación para obtener coordenadas
$sql = "SELECT 
            p.estado, 
            u.nombre as nombre_domiciliario, 
            u.telefono as telefono_domiciliario,
            ud.latitud, 
            ud.longitud, 
            ud.ultima_actualizacion 
        FROM pedidos p 
        JOIN usuarios u ON p.domiciliario_id = u.id 
        LEFT JOIN ubicacion_domiciliarios ud ON u.id = ud.usuario_id AND ud.tenant_id = ?
        WHERE p.id = ? AND p.tenant_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $tenant_id, $pedido_id, $tenant_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Pedido no encontrado o sin domiciliario']);
    exit;
}

$data = $result->fetch_assoc();

// Verificar estado
if ($data['estado'] !== 'en_camino') {
    // Si no está en camino, igual devolvemos info básica pero sin coordenadas tracking activo
    echo json_encode([
        'success' => true,
        'estado' => $data['estado'],
        'domiciliario' => [
            'nombre' => $data['nombre_domiciliario'],
            'telefono' => $data['telefono_domiciliario']
        ],
        'tracking_activo' => false
    ]);
    exit;
}

// Si está en camino
$response = [
    'success' => true,
    'estado' => $data['estado'],
    'domiciliario' => [
        'nombre' => $data['nombre_domiciliario'],
        'telefono' => $data['telefono_domiciliario']
    ],
    'tracking_activo' => false
];

// Verificar si hay coordenadas recientes (últimos 15 minutos)
if ($data['latitud'] && $data['longitud']) {
    $ultima_act = strtotime($data['ultima_actualizacion']);
    $ahora = time();
    $diferencia = $ahora - $ultima_act;
    
    // Si la ubicación es muy vieja, tal vez no está traqueando
    $es_reciente = $diferencia < 900; // 15 min
    
    $response['ubicacion'] = [
        'lat' => floatval($data['latitud']),
        'lng' => floatval($data['longitud']),
        'ultima_actualizacion' => $data['ultima_actualizacion'],
        'es_reciente' => $es_reciente
    ];
    $response['tracking_activo'] = true;
}

echo json_encode($response);

$stmt->close();
$conn->close();
