<?php
/**
 * Gestión del Contexto de Tenant
 * 
 * Este archivo proporciona funciones centralizadas para identificar
 * y trabajar con el tenant actual en todo el sistema.
 * 
 * IMPORTANTE: Incluir este archivo en cualquier script que necesite
 * filtrar datos por tenant.
 */

/**
 * Obtener el ID del tenant actual basado en el usuario logueado
 * 
 * @return int ID del tenant actual
 */
function getCurrentTenantId() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Si ya está en sesión, retornar directamente
    if (isset($_SESSION['tenant_id']) && $_SESSION['tenant_id'] > 0) {
        return (int)$_SESSION['tenant_id'];
    }
    
    // Si el usuario está logueado, obtener su tenant_id de la BD
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
        require_once __DIR__ . '/../config.php';
        $conn = getDatabaseConnection();
        
        $stmt = $conn->prepare("SELECT tenant_id FROM usuarios WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $_SESSION['tenant_id'] = (int)$row['tenant_id'];
            $stmt->close();
            return (int)$row['tenant_id'];
        }
        $stmt->close();
    }
    
    // Por defecto, tenant 1 (para compatibilidad con datos existentes)
    return 1;
}

/**
 * Obtener información completa del tenant actual
 * 
 * @return array|null Datos del tenant o null si no existe
 */
function getCurrentTenant() {
    $tenant_id = getCurrentTenantId();
    
    require_once __DIR__ . '/../config.php';
    $conn = getDatabaseConnection();
    
    $stmt = $conn->prepare("SELECT * FROM saas_tenants WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $tenant_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $tenant = $result->fetch_assoc();
    $stmt->close();
    
    return $tenant;
}

/**
 * Agregar filtro de tenant a una consulta WHERE
 * 
 * Uso en consultas SQL:
 * - WHERE " . tenantFilter() . " AND activo = 1
 * - WHERE " . tenantFilter("p") . " AND p.categoria = 'Platos'
 * 
 * @param string|null $table_alias Alias de la tabla (opcional)
 * @return string Condición SQL para filtrar por tenant
 */
function tenantFilter($table_alias = null) {
    $tenant_id = getCurrentTenantId();
    $prefix = $table_alias ? "$table_alias." : "";
    return "{$prefix}tenant_id = $tenant_id";
}

/**
 * Obtener tenant_id como parámetro para prepared statements
 * 
 * Uso:
 * $stmt = $conn->prepare("SELECT * FROM platos WHERE tenant_id = ? AND activo = 1");
 * $tenant_id = getTenantIdParam();
 * $stmt->bind_param("i", $tenant_id);
 * 
 * @return int ID del tenant para usar en bind_param
 */
function getTenantIdParam() {
    return getCurrentTenantId();
}

/**
 * Verificar si el usuario actual tiene acceso a un recurso específico
 * 
 * Uso:
 * if (!verifyTenantAccess('platos', $plato_id)) {
 *     die('Acceso denegado');
 * }
 * 
 * @param string $table Nombre de la tabla
 * @param int $record_id ID del registro
 * @return bool True si tiene acceso, False si no
 */
function verifyTenantAccess($table, $record_id) {
    $tenant_id = getCurrentTenantId();
    
    require_once __DIR__ . '/../config.php';
    $conn = getDatabaseConnection();
    
    $stmt = $conn->prepare("SELECT tenant_id FROM $table WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $record_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $stmt->close();
        return (int)$row['tenant_id'] === $tenant_id;
    }
    
    $stmt->close();
    return false;
}

/**
 * Establecer el tenant_id en la sesión (usado en login)
 * 
 * @param int $tenant_id ID del tenant
 */
function setCurrentTenant($tenant_id) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['tenant_id'] = (int)$tenant_id;
}

/**
 * Limpiar el tenant de la sesión (usado en logout)
 */
function clearCurrentTenant() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    unset($_SESSION['tenant_id']);
}

/**
 * Verificar si un tenant está activo
 * 
 * @param int|null $tenant_id ID del tenant (null = tenant actual)
 * @return bool True si está activo, False si no
 */
function isTenantActive($tenant_id = null) {
    if ($tenant_id === null) {
        $tenant_id = getCurrentTenantId();
    }
    
    require_once __DIR__ . '/../config.php';
    $conn = getDatabaseConnection();
    
    $stmt = $conn->prepare("SELECT status FROM saas_tenants WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $tenant_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $stmt->close();
        return $row['status'] === 'active';
    }
    
    $stmt->close();
    return false;
}

/**
 * Obtener el nombre del restaurante del tenant actual
 * 
 * @return string Nombre del restaurante
 */
function getCurrentRestaurantName() {
    $tenant = getCurrentTenant();
    return $tenant ? $tenant['restaurant_name'] : 'Restaurante';
}

/**
 * Validar que el tenant actual tiene acceso activo
 * Redirige al login si el tenant está suspendido o cancelado
 */
function validateTenantAccess() {
    if (!isTenantActive()) {
        session_destroy();
        header("Location: login.php?error=tenant_suspended");
        exit;
    }
}
