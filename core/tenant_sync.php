<?php
/**
 * Sistema de Sincronización de Tenant
 * Maneja la comunicación entre el restaurante y el servidor SaaS
 */

class TenantSync {
    
    private $tenantId;
    private $tenantKey;
    private $apiToken;
    private $saasServerUrl;
    private $conn;
    
    public function __construct() {
        // Verificar si tenant_config existe
        if (!defined('TENANT_ID') || TENANT_ID == 0) {
            throw new Exception("Tenant no configurado. Instale tenant_config.php");
        }
        
        $this->tenantId = TENANT_ID;
        $this->tenantKey = TENANT_KEY;
        $this->apiToken = API_TOKEN;
        $this->saasServerUrl = SAAS_API_ENDPOINT ?? SAAS_SERVER_URL . '/api';
        
        // Conexión a BD local del restaurante
        $this->conn = getDatabaseConnection();
    }
    
    /**
     * Sincroniza métricas de uso con el servidor SaaS
     */
    public function syncUsageMetrics() {
        try {
            $metrics = $this->collectMetrics();
            
            // Enviar al servidor SaaS
            $response = $this->sendToServer('/tenant/sync-usage', $metrics);
            
            if ($response['success']) {
                // Actualizar límites locales si el servidor los envía
                if (isset($response['limits'])) {
                    $this->updateLocalLimits($response['limits']);
                }
                
                return [
                    'success' => true,
                    'message' => 'Sincronización exitosa',
                    'data' => $response
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Error en respuesta del servidor'
            ];
            
        } catch (Exception $e) {
            error_log("Error en sincronización: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Recopila métricas de uso del restaurante
     */
    private function collectMetrics() {
        $metrics = [];
        
        // Contar usuarios
        $result = $this->conn->query("SELECT COUNT(*) as total FROM usuarios");
        $metrics['current_users'] = $result->fetch_assoc()['total'];
        
        // Contar platos
        $result = $this->conn->query("SELECT COUNT(*) as total FROM platos");
        $metrics['current_menu_items'] = $result->fetch_assoc()['total'];
        
        // Calcular almacenamiento usado
        $metrics['current_storage_mb'] = $this->calculateStorageUsage();
        
        // Estadísticas adicionales
        $result = $this->conn->query("SELECT COUNT(*) as total FROM pedidos WHERE DATE(fecha_pedido) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
        $metrics['orders_last_30_days'] = $result->fetch_assoc()['total'];
        
        $result = $this->conn->query("SELECT COUNT(*) as total FROM clientes");
        $metrics['total_customers'] = $result->fetch_assoc()['total'];
        
        $metrics['last_sync'] = date('Y-m-d H:i:s');
        
        return $metrics;
    }
    
    /**
     * Calcula el almacenamiento usado en MB
     */
    private function calculateStorageUsage() {
        $totalSize = 0;
        
        // Calcular tamaño de imágenes de platos
        $imagePath = __DIR__ . '/../imagenes/platos';
        if (is_dir($imagePath)) {
            $totalSize += $this->getDirectorySize($imagePath);
        }
        
        // Calcular tamaño de publicidad
        $adsPath = __DIR__ . '/../imagenes/publicidad';
        if (is_dir($adsPath)) {
            $totalSize += $this->getDirectorySize($adsPath);
        }
        
        // Convertir a MB
        return round($totalSize / (1024 * 1024), 2);
    }
    
    /**
     * Obtiene el tamaño de un directorio recursivamente
     */
    private function getDirectorySize($path) {
        $size = 0;
        
        if (!is_dir($path)) {
            return 0;
        }
        
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }
        
        return $size;
    }
    
    /**
     * Verifica los límites del plan antes de realizar una acción
     */
    public function checkPlanLimits($resource) {
        try {
            // Si está en modo offline, permitir todo
            if (defined('OFFLINE_MODE') && OFFLINE_MODE) {
                return ['allowed' => true, 'message' => 'Modo offline'];
            }
            
            $metrics = $this->collectMetrics();
            
            switch ($resource) {
                case 'user':
                    $current = $metrics['current_users'];
                    $max = PLAN_MAX_USERS;
                    $resourceName = 'usuarios';
                    break;
                    
                case 'menu_item':
                    $current = $metrics['current_menu_items'];
                    $max = PLAN_MAX_MENU_ITEMS;
                    $resourceName = 'platos';
                    break;
                    
                case 'storage':
                    $current = $metrics['current_storage_mb'];
                    $max = PLAN_MAX_STORAGE_MB;
                    $resourceName = 'almacenamiento';
                    break;
                    
                default:
                    return ['allowed' => true, 'message' => 'Recurso no validado'];
            }
            
            $allowed = $current < $max;
            $percentage = round(($current / $max) * 100, 1);
            
            return [
                'allowed' => $allowed,
                'current' => $current,
                'max' => $max,
                'percentage' => $percentage,
                'resource' => $resourceName,
                'message' => $allowed 
                    ? "Uso actual: $current/$max ($percentage%)" 
                    : "Límite alcanzado: $current/$max. Actualiza tu plan."
            ];
            
        } catch (Exception $e) {
            error_log("Error verificando límites: " . $e->getMessage());
            // En caso de error, permitir la acción si no está en modo estricto
            return [
                'allowed' => !STRICT_LIMITS,
                'message' => 'Error al verificar límites: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Envía datos al servidor SaaS
     */
    private function sendToServer($endpoint, $data) {
        $url = $this->saasServerUrl . $endpoint;
        
        $payload = json_encode([
            'tenant_id' => $this->tenantId,
            'tenant_key' => $this->tenantKey,
            'api_token' => $this->apiToken,
            'data' => $data
        ]);
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->apiToken
                ],
                'content' => $payload,
                'timeout' => 10
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            throw new Exception("No se pudo conectar con el servidor SaaS");
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Actualiza los límites locales basados en la respuesta del servidor
     */
    private function updateLocalLimits($limits) {
        // TODO: Actualizar archivo tenant_config.php con nuevos límites
        // Por ahora solo registrar en log
        error_log("Límites actualizados: " . json_encode($limits));
    }
    
    /**
     * Obtiene el estado actual del tenant
     */
    public function getStatus() {
        $metrics = $this->collectMetrics();
        
        return [
            'tenant_id' => $this->tenantId,
            'tenant_key' => $this->tenantKey,
            'plan' => TENANT_PLAN,
            'metrics' => $metrics,
            'limits' => [
                'users' => PLAN_MAX_USERS,
                'menu_items' => PLAN_MAX_MENU_ITEMS,
                'storage_mb' => PLAN_MAX_STORAGE_MB
            ],
            'usage_percentage' => [
                'users' => round(($metrics['current_users'] / PLAN_MAX_USERS) * 100, 1),
                'menu_items' => round(($metrics['current_menu_items'] / PLAN_MAX_MENU_ITEMS) * 100, 1),
                'storage' => round(($metrics['current_storage_mb'] / PLAN_MAX_STORAGE_MB) * 100, 1)
            ]
        ];
    }
    
    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>
