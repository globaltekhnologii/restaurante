<?php
/**
 * Calculadora de Tarifas de Domicilio
 * Calcula el costo de domicilio basado en la distancia
 */

/**
 * Calcula el costo de domicilio basado en la distancia
 * 
 * @param mysqli $conn Conexión a BD
 * @param float $distancia_km Distancia en kilómetros
 * @return array Array con ['costo', 'metodo'] donde metodo puede ser 'base_km' o 'rangos'
 */
function calcularCostoDomicilio($conn, $distancia_km) {
    // Obtener configuración
    $sql = "SELECT tarifa_base, costo_por_km, usar_rangos, activo 
            FROM configuracion_domicilios 
            WHERE id = 1 LIMIT 1";
    
    $result = $conn->query($sql);
    
    if (!$result || $result->num_rows == 0) {
        // Configuración por defecto si no existe
        return [
            'costo' => 5000.00,
            'metodo' => 'default',
            'detalle' => 'Tarifa fija por defecto'
        ];
    }
    
    $config = $result->fetch_assoc();
    
    // Si no está activo, retornar tarifa base
    if ($config['activo'] != 1) {
        return [
            'costo' => floatval($config['tarifa_base']),
            'metodo' => 'base',
            'detalle' => 'Sistema de tarifas desactivado'
        ];
    }
    
    // Decidir método de cálculo
    if ($config['usar_rangos'] == 1) {
        return calcularPorRangos($conn, $distancia_km);
    } else {
        return calcularPorBaseYKm($config, $distancia_km);
    }
}

/**
 * Calcula costo usando tarifa base + costo por km
 * 
 * @param array $config Configuración
 * @param float $distancia_km Distancia
 * @return array
 */
function calcularPorBaseYKm($config, $distancia_km) {
    $tarifa_base = floatval($config['tarifa_base']);
    $costo_por_km = floatval($config['costo_por_km']);
    
    $costo_total = $tarifa_base + ($distancia_km * $costo_por_km);
    
    return [
        'costo' => round($costo_total, 2),
        'metodo' => 'base_km',
        'detalle' => "Base: $" . number_format($tarifa_base, 0) . 
                     " + " . number_format($distancia_km, 2) . "km × $" . 
                     number_format($costo_por_km, 0) . "/km"
    ];
}

/**
 * Calcula costo usando rangos de distancia
 * 
 * @param mysqli $conn Conexión a BD
 * @param float $distancia_km Distancia
 * @return array
 */
function calcularPorRangos($conn, $distancia_km) {
    $sql = "SELECT distancia_min, distancia_max, tarifa 
            FROM rangos_tarifas_domicilio 
            WHERE activo = 1 
            AND ? >= distancia_min 
            AND ? <= distancia_max 
            ORDER BY distancia_min ASC 
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("dd", $distancia_km, $distancia_km);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $rango = $result->fetch_assoc();
        
        return [
            'costo' => floatval($rango['tarifa']),
            'metodo' => 'rangos',
            'detalle' => "Rango: " . $rango['distancia_min'] . "-" . 
                        $rango['distancia_max'] . "km"
        ];
    }
    
    // Si no hay rango que coincida, usar tarifa base
    $sql_base = "SELECT tarifa_base FROM configuracion_domicilios WHERE id = 1";
    $result_base = $conn->query($sql_base);
    $tarifa_base = 5000.00;
    
    if ($result_base && $result_base->num_rows > 0) {
        $row = $result_base->fetch_assoc();
        $tarifa_base = floatval($row['tarifa_base']);
    }
    
    return [
        'costo' => $tarifa_base,
        'metodo' => 'base_fallback',
        'detalle' => 'Fuera de rangos configurados, usando tarifa base'
    ];
}

/**
 * Obtiene información completa de costo para mostrar al cliente
 * 
 * @param mysqli $conn Conexión a BD
 * @param float $distancia_km Distancia
 * @return array Array con toda la información de costo
 */
function obtenerInfoCostoDomicilio($conn, $distancia_km) {
    $calculo = calcularCostoDomicilio($conn, $distancia_km);
    
    return [
        'distancia_km' => $distancia_km,
        'costo' => $calculo['costo'],
        'costo_formateado' => '$' . number_format($calculo['costo'], 0, ',', '.'),
        'metodo' => $calculo['metodo'],
        'detalle' => $calculo['detalle']
    ];
}
?>
