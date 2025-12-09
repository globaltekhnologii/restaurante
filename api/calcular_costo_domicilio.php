<?php
/**
 * API: Calcular Costo de Domicilio
 * Endpoint AJAX para calcular el costo de domicilio en tiempo real
 */

session_start();
header('Content-Type: application/json');

require_once '../config.php';
require_once '../includes/geocoding_service.php';
require_once '../includes/distance_calculator.php';
require_once '../includes/delivery_fee_calculator.php';

$conn = getDatabaseConnection();

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Método no permitido'
    ]);
    exit;
}

// Obtener datos
$direccion = trim($_POST['direccion'] ?? '');
$ciudad = trim($_POST['ciudad'] ?? 'Bogotá');
$pais = trim($_POST['pais'] ?? 'Colombia');

// Validar dirección
if (empty($direccion)) {
    echo json_encode([
        'success' => false,
        'error' => 'La dirección es requerida'
    ]);
    exit;
}

try {
    // 1. Verificar que el restaurante tenga coordenadas configuradas
    $coords_restaurante = obtenerCoordenadasRestaurante($conn);
    
    if ($coords_restaurante === false) {
        echo json_encode([
            'success' => false,
            'error' => 'El restaurante no tiene coordenadas GPS configuradas. Contacte al administrador.',
            'usar_tarifa_fija' => true,
            'tarifa_fija' => 5000.00
        ]);
        exit;
    }
    
    // 2. Geocodificar dirección del cliente
    $coords_cliente = geocodificarDireccion($direccion, $ciudad, $pais);
    
    if ($coords_cliente === false) {
        echo json_encode([
            'success' => false,
            'error' => 'No se pudo encontrar la dirección. Por favor verifica que esté correcta.',
            'usar_tarifa_fija' => true,
            'tarifa_fija' => 5000.00
        ]);
        exit;
    }
    
    // 3. Calcular distancia
    $distancia_km = calcularDistancia(
        $coords_restaurante['lat'],
        $coords_restaurante['lon'],
        $coords_cliente['lat'],
        $coords_cliente['lon']
    );
    
    // 4. Verificar si está dentro del rango permitido
    if (!verificarDistanciaPermitida($conn, $distancia_km)) {
        $sql = "SELECT distancia_maxima FROM configuracion_domicilios WHERE id = 1";
        $result = $conn->query($sql);
        $dist_max = 10.0;
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $dist_max = $row['distancia_maxima'];
        }
        
        echo json_encode([
            'success' => false,
            'error' => "Lo sentimos, la distancia ({$distancia_km} km) excede nuestro rango máximo de entrega ({$dist_max} km).",
            'distancia_km' => $distancia_km,
            'distancia_maxima' => $dist_max,
            'fuera_de_rango' => true
        ]);
        exit;
    }
    
    // 5. Calcular costo de domicilio
    $info_costo = obtenerInfoCostoDomicilio($conn, $distancia_km);
    
    // 6. Retornar resultado exitoso
    echo json_encode([
        'success' => true,
        'distancia_km' => $distancia_km,
        'costo_domicilio' => $info_costo['costo'],
        'costo_formateado' => $info_costo['costo_formateado'],
        'metodo_calculo' => $info_costo['metodo'],
        'detalle' => $info_costo['detalle'],
        'latitud_cliente' => $coords_cliente['lat'],
        'longitud_cliente' => $coords_cliente['lon'],
        'direccion_encontrada' => $coords_cliente['display_name']
    ]);
    
} catch (Exception $e) {
    error_log("API calcular_costo_domicilio: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => 'Error al calcular el costo de domicilio. Por favor intenta nuevamente.',
        'usar_tarifa_fija' => true,
        'tarifa_fija' => 5000.00
    ]);
}

$conn->close();
?>
