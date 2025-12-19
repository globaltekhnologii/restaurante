<?php
/**
 * Helper de Validación de Límites del Tenant
 * Funciones para verificar límites del plan antes de crear recursos
 */

// Cargar configuración del tenant si existe
if (file_exists(__DIR__ . '/../tenant_config.php')) {
    require_once __DIR__ . '/../tenant_config.php';
}

// Cargar sistema de sincronización
if (file_exists(__DIR__ . '/../core/tenant_sync.php')) {
    require_once __DIR__ . '/../core/tenant_sync.php';
}

/**
 * Verifica si se puede añadir un nuevo usuario
 * @return array ['allowed' => bool, 'message' => string, 'current' => int, 'max' => int]
 */
function checkCanAddUser() {
    // Si no está configurado el tenant, permitir (modo legacy)
    if (!defined('TENANT_ID') || TENANT_ID == 0) {
        return [
            'allowed' => true,
            'message' => 'Sistema sin límites (modo legacy)',
            'current' => 0,
            'max' => 999
        ];
    }
    
    try {
        $sync = new TenantSync();
        $check = $sync->checkPlanLimits('user');
        
        return $check;
        
    } catch (Exception $e) {
        error_log("Error verificando límite de usuarios: " . $e->getMessage());
        
        // Si hay error y no está en modo estricto, permitir
        if (!defined('STRICT_LIMITS') || !STRICT_LIMITS) {
            return [
                'allowed' => true,
                'message' => 'Error al verificar límites (permitido por configuración)'
            ];
        }
        
        return [
            'allowed' => false,
            'message' => 'Error al verificar límites: ' . $e->getMessage()
        ];
    }
}

/**
 * Verifica si se puede añadir un nuevo plato al menú
 * @return array ['allowed' => bool, 'message' => string, 'current' => int, 'max' => int]
 */
function checkCanAddMenuItem() {
    // Si no está configurado el tenant, permitir (modo legacy)
    if (!defined('TENANT_ID') || TENANT_ID == 0) {
        return [
            'allowed' => true,
            'message' => 'Sistema sin límites (modo legacy)',
            'current' => 0,
            'max' => 999
        ];
    }
    
    try {
        $sync = new TenantSync();
        $check = $sync->checkPlanLimits('menu_item');
        
        return $check;
        
    } catch (Exception $e) {
        error_log("Error verificando límite de platos: " . $e->getMessage());
        
        if (!defined('STRICT_LIMITS') || !STRICT_LIMITS) {
            return [
                'allowed' => true,
                'message' => 'Error al verificar límites (permitido por configuración)'
            ];
        }
        
        return [
            'allowed' => false,
            'message' => 'Error al verificar límites: ' . $e->getMessage()
        ];
    }
}

/**
 * Verifica si hay espacio suficiente para subir un archivo
 * @param int $fileSizeBytes Tamaño del archivo en bytes
 * @return array ['allowed' => bool, 'message' => string]
 */
function checkStorageLimit($fileSizeBytes) {
    // Si no está configurado el tenant, permitir (modo legacy)
    if (!defined('TENANT_ID') || TENANT_ID == 0) {
        return [
            'allowed' => true,
            'message' => 'Sistema sin límites (modo legacy)'
        ];
    }
    
    try {
        $sync = new TenantSync();
        $check = $sync->checkPlanLimits('storage');
        
        // Convertir bytes a MB
        $fileSizeMB = $fileSizeBytes / (1024 * 1024);
        
        // Verificar si hay espacio para el nuevo archivo
        $spaceAvailable = $check['max'] - $check['current'];
        
        if ($fileSizeMB > $spaceAvailable) {
            return [
                'allowed' => false,
                'message' => sprintf(
                    'Espacio insuficiente. Disponible: %.2f MB, Requerido: %.2f MB',
                    $spaceAvailable,
                    $fileSizeMB
                ),
                'current' => $check['current'],
                'max' => $check['max']
            ];
        }
        
        return [
            'allowed' => true,
            'message' => sprintf('Espacio disponible: %.2f MB', $spaceAvailable),
            'current' => $check['current'],
            'max' => $check['max']
        ];
        
    } catch (Exception $e) {
        error_log("Error verificando límite de almacenamiento: " . $e->getMessage());
        
        if (!defined('STRICT_LIMITS') || !STRICT_LIMITS) {
            return [
                'allowed' => true,
                'message' => 'Error al verificar límites (permitido por configuración)'
            ];
        }
        
        return [
            'allowed' => false,
            'message' => 'Error al verificar límites: ' . $e->getMessage()
        ];
    }
}

/**
 * Obtiene el estado actual de uso del tenant
 * @return array Estado completo del tenant
 */
function getTenantStatus() {
    if (!defined('TENANT_ID') || TENANT_ID == 0) {
        return [
            'configured' => false,
            'message' => 'Tenant no configurado'
        ];
    }
    
    try {
        $sync = new TenantSync();
        $status = $sync->getStatus();
        $status['configured'] = true;
        
        return $status;
        
    } catch (Exception $e) {
        return [
            'configured' => true,
            'error' => true,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Muestra un mensaje de alerta sobre límites
 * @param array $check Resultado de checkCanAdd*
 * @return string HTML del mensaje
 */
function showLimitAlert($check) {
    if (!isset($check['allowed'])) {
        return '';
    }
    
    if ($check['allowed']) {
        // Si está permitido pero cerca del límite (>80%), mostrar advertencia
        if (isset($check['percentage']) && $check['percentage'] >= 80) {
            $color = $check['percentage'] >= 90 ? '#ef4444' : '#f59e0b';
            return sprintf(
                '<div style="background: #fef3c7; border-left: 4px solid %s; padding: 1rem; margin: 1rem 0; border-radius: 4px;">
                    <strong>⚠️ Advertencia:</strong> %s
                </div>',
                $color,
                htmlspecialchars($check['message'])
            );
        }
        return '';
    }
    
    // No permitido - mostrar error
    return sprintf(
        '<div style="background: #fee2e2; border-left: 4px solid #ef4444; padding: 1rem; margin: 1rem 0; border-radius: 4px;">
            <strong>❌ Límite alcanzado:</strong> %s
        </div>',
        htmlspecialchars($check['message'])
    );
}

/**
 * Sincroniza métricas con el servidor SaaS
 * @return array Resultado de la sincronización
 */
function syncTenantMetrics() {
    if (!defined('TENANT_ID') || TENANT_ID == 0) {
        return [
            'success' => false,
            'message' => 'Tenant no configurado'
        ];
    }
    
    try {
        $sync = new TenantSync();
        return $sync->syncUsageMetrics();
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}
?>
