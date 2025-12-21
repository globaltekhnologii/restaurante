<?php
/**
 * Servicio de Geocodificación
 * Convierte direcciones en coordenadas GPS usando OpenStreetMap Nominatim
 */

/**
 * Geocodifica una dirección usando OpenStreetMap Nominatim
 * 
 * @param string $direccion Dirección completa
 * @param string $ciudad Ciudad (opcional, mejora precisión)
 * @param string $pais País (opcional, mejora precisión)
 * @return array|false Array con ['lat', 'lon', 'display_name'] o false si falla
 */
function geocodificarDireccion($direccion, $ciudad = 'Bogotá', $pais = 'Colombia') {
    // Construir query completa
    $query = trim($direccion);
    if (!empty($ciudad)) {
        $query .= ", " . $ciudad;
    }
    if (!empty($pais)) {
        $query .= ", " . $pais;
    }
    
    // URL encode
    $query_encoded = urlencode($query);
    
    // Endpoint de Nominatim
    $url = "https://nominatim.openstreetmap.org/search?q={$query_encoded}&format=json&limit=1&addressdetails=1";
    
    // Configurar contexto HTTP con User-Agent (requerido por Nominatim)
    $options = [
        'http' => [
            'header' => "User-Agent: RestauranteApp/1.0\r\n",
            'timeout' => 5
        ]
    ];
    $context = stream_context_create($options);
    
    try {
        // Hacer petición
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            error_log("Geocoding: Error al conectar con Nominatim");
            return false;
        }
        
        $data = json_decode($response, true);
        
        if (empty($data)) {
            error_log("Geocoding: No se encontraron resultados para: $query");
            return false;
        }
        
        // Retornar primer resultado
        return [
            'lat' => floatval($data[0]['lat']),
            'lon' => floatval($data[0]['lon']),
            'display_name' => $data[0]['display_name'] ?? $query
        ];
        
    } catch (Exception $e) {
        error_log("Geocoding: Excepción - " . $e->getMessage());
        return false;
    }
}

/**
 * Geocodifica con cache en base de datos (opcional)
 * Evita llamadas repetidas a la API para direcciones conocidas
 * 
 * @param mysqli $conn Conexión a BD
 * @param string $direccion Dirección completa
 * @param string $ciudad Ciudad
 * @param string $pais País
 * @return array|false
 */
function geocodificarConCache($conn, $direccion, $ciudad = 'Bogotá', $pais = 'Colombia') {
    // Normalizar dirección para cache
    $direccion_normalizada = strtolower(trim($direccion . ', ' . $ciudad . ', ' . $pais));
    
    // Buscar en cache (tabla opcional - crear si se desea)
    // Por ahora, llamar directamente a geocodificar
    return geocodificarDireccion($direccion, $ciudad, $pais);
}

/**
 * Obtener coordenadas del restaurante desde configuración
 * 
 * @param mysqli $conn Conexión a BD
 * @return array|false Array con ['lat', 'lon'] o false si no están configuradas
 */
function obtenerCoordenadasRestaurante($conn) {
    // CORREGIDO: Obtener coordenadas del tenant actual
    require_once __DIR__ . '/tenant_context.php';
    $tenant_id = getCurrentTenantId();
    $sql = "SELECT latitud_restaurante, longitud_restaurante FROM configuracion_sistema WHERE tenant_id = $tenant_id LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        if (!empty($row['latitud_restaurante']) && !empty($row['longitud_restaurante'])) {
            return [
                'lat' => floatval($row['latitud_restaurante']),
                'lon' => floatval($row['longitud_restaurante'])
            ];
        }
    }
    
    return false;
}

/**
 * Geocodificar y guardar coordenadas del restaurante
 * 
 * @param mysqli $conn Conexión a BD
 * @param string $direccion Dirección del restaurante
 * @param string $ciudad Ciudad
 * @param string $pais País
 * @return bool True si se guardó exitosamente
 */
function geocodificarYGuardarRestaurante($conn, $direccion, $ciudad, $pais) {
    $coords = geocodificarDireccion($direccion, $ciudad, $pais);
    
    if ($coords === false) {
        return false;
    }
    
    $lat = $coords['lat'];
    $lon = $coords['lon'];
    
    $sql = "UPDATE configuracion_sistema 
            SET latitud_restaurante = ?, longitud_restaurante = ? 
            WHERE tenant_id = $tenant_id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("dd", $lat, $lon);
    
    return $stmt->execute();
}
?>
