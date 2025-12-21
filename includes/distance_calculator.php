<?php
/**
 * Calculadora de Distancias
 * Calcula la distancia entre dos puntos GPS usando la fórmula de Haversine
 */

/**
 * Calcula la distancia entre dos coordenadas GPS usando la fórmula de Haversine
 * 
 * @param float $lat1 Latitud del punto 1
 * @param float $lon1 Longitud del punto 1
 * @param float $lat2 Latitud del punto 2
 * @param float $lon2 Longitud del punto 2
 * @return float Distancia en kilómetros
 */
function calcularDistancia($lat1, $lon1, $lat2, $lon2) {
    // Radio de la Tierra en kilómetros
    $radio_tierra = 6371;
    
    // Convertir grados a radianes
    $lat1_rad = deg2rad($lat1);
    $lon1_rad = deg2rad($lon1);
    $lat2_rad = deg2rad($lat2);
    $lon2_rad = deg2rad($lon2);
    
    // Diferencias
    $dlat = $lat2_rad - $lat1_rad;
    $dlon = $lon2_rad - $lon1_rad;
    
    // Fórmula de Haversine
    $a = sin($dlat / 2) * sin($dlat / 2) +
         cos($lat1_rad) * cos($lat2_rad) *
         sin($dlon / 2) * sin($dlon / 2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    $distancia = $radio_tierra * $c;
    
    // Redondear a 2 decimales
    return round($distancia, 2);
}

/**
 * Calcula la distancia entre el restaurante y una dirección del cliente
 * 
 * @param mysqli $conn Conexión a BD
 * @param float $lat_cliente Latitud del cliente
 * @param float $lon_cliente Longitud del cliente
 * @return float|false Distancia en km o false si no hay coordenadas del restaurante
 */
function calcularDistanciaDesdeRestaurante($conn, $lat_cliente, $lon_cliente) {
    require_once __DIR__ . '/geocoding_service.php';
    
    $coords_restaurante = obtenerCoordenadasRestaurante($conn);
    
    if ($coords_restaurante === false) {
        error_log("Distance: No hay coordenadas del restaurante configuradas");
        return false;
    }
    
    return calcularDistancia(
        $coords_restaurante['lat'],
        $coords_restaurante['lon'],
        $lat_cliente,
        $lon_cliente
    );
}

/**
 * Verifica si una distancia está dentro del rango de entrega permitido
 * 
 * @param mysqli $conn Conexión a BD
 * @param float $distancia_km Distancia en kilómetros
 * @return bool True si está dentro del rango
 */
function verificarDistanciaPermitida($conn, $distancia_km) {
    require_once __DIR__ . '/tenant_context.php';
    $tenant_id = getCurrentTenantId();
    $sql = "SELECT distancia_maxima FROM configuracion_domicilios WHERE tenant_id = $tenant_id AND activo = 1";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $distancia_maxima = floatval($row['distancia_maxima']);
        
        return $distancia_km <= $distancia_maxima;
    }
    
    // Si no hay configuración, permitir hasta 10km por defecto
    return $distancia_km <= 10.0;
}
?>
