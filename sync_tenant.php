<?php
/**
 * Endpoint de Sincronización de Tenant
 * Sincroniza métricas con el servidor SaaS
 */

session_start();

// Verificar autenticación
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== TRUE) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

// Verificar que existe configuración de tenant
if (!file_exists(__DIR__ . '/tenant_config.php')) {
    echo json_encode([
        'success' => false,
        'message' => 'Tenant no configurado'
    ]);
    exit;
}

require_once __DIR__ . '/tenant_config.php';
require_once __DIR__ . '/includes/tenant_limits.php';

try {
    // Ejecutar sincronización
    $result = syncTenantMetrics();
    
    // Devolver resultado
    header('Content-Type: application/json');
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
