<?php
// Endpoint público para obtener anuncios activos (sin autenticación)
require_once '../config.php';
require_once '../includes/tenant_context.php';
header('Content-Type: application/json');

try {
    $conn = getDatabaseConnection();
    $tenant_id = getCurrentTenantId();
    
    // Obtener solo anuncios activos y vigentes del tenant actual
    $hoy = date('Y-m-d');
    $sql = "SELECT id, titulo, tipo, archivo_url, link_destino 
            FROM publicidad 
            WHERE tenant_id = ? 
            AND activo = 1 
            AND (fecha_inicio IS NULL OR fecha_inicio <= ?)
            AND (fecha_fin IS NULL OR fecha_fin >= ?)
            ORDER BY orden ASC, fecha_creacion DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $tenant_id, $hoy, $hoy);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $anuncios = [];
    while ($row = $result->fetch_assoc()) {
        // Ajustar URL para frontend (ruta relativa desde index.php)
        $row['archivo_url'] = 'publicidad/' . basename($row['archivo_url']);
        $anuncios[] = $row;
    }
    
    echo json_encode($anuncios);
    
    $conn->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al cargar publicidad']);
}
?>
