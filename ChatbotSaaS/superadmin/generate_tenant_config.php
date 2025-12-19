<?php
/**
 * Generador de Archivo de Configuración de Tenant
 * Crea un archivo tenant_config.php personalizado para un restaurante específico
 */

require_once 'config.php';
checkSuperAdminAuth();

$conn = getDBConnection();

// Obtener ID del tenant
$tenant_id = isset($_GET['tenant_id']) ? intval($_GET['tenant_id']) : 0;

if ($tenant_id == 0) {
    die("Error: ID de tenant no especificado");
}

// Obtener información del tenant
$stmt = $conn->prepare("SELECT * FROM saas_tenants WHERE id = ?");
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$result = $stmt->get_result();
$tenant = $result->fetch_assoc();
$stmt->close();

if (!$tenant) {
    die("Error: Tenant no encontrado");
}

// Verificar que tenga tenant_key y api_token
if (empty($tenant['tenant_key']) || empty($tenant['api_token'])) {
    die("Error: El tenant no tiene claves generadas. Ejecute la migración primero.");
}

// Determinar límites según el plan
$limits = [
    'basic' => [
        'users' => 5,
        'menu_items' => 50,
        'storage_mb' => 500
    ],
    'pro' => [
        'users' => 15,
        'menu_items' => 200,
        'storage_mb' => 2048
    ],
    'enterprise' => [
        'users' => 999,
        'menu_items' => 999,
        'storage_mb' => 10240
    ]
];

$plan = $tenant['plan'];
$planLimits = $limits[$plan] ?? $limits['basic'];

// Generar contenido del archivo
$configContent = <<<PHP
<?php
/**
 * Configuración del Tenant
 * Este archivo identifica a qué tenant SaaS pertenece este restaurante
 * 
 * IMPORTANTE: Este archivo es generado automáticamente por el Super Admin
 * No modificar manualmente a menos que sepas lo que estás haciendo
 * 
 * Generado: {$tenant['created_at']}
 * Restaurante: {$tenant['restaurant_name']}
 * Plan: {$plan}
 */

// ============================================
// IDENTIFICACIÓN DEL TENANT
// ============================================

// ID único del tenant en el sistema SaaS
define('TENANT_ID', {$tenant['id']});

// Clave única del tenant (más amigable que el ID numérico)
define('TENANT_KEY', '{$tenant['tenant_key']}');

// Token de autenticación para API
define('API_TOKEN', '{$tenant['api_token']}');

// ============================================
// INFORMACIÓN DEL TENANT (Referencia)
// ============================================

// Nombre del restaurante
define('TENANT_NAME', '{$tenant['restaurant_name']}');

// Plan contratado
define('TENANT_PLAN', '{$plan}');

// ============================================
// CONFIGURACIÓN DEL SERVIDOR SAAS
// ============================================

// URL del servidor SaaS (para reportar métricas y sincronización)
define('SAAS_SERVER_URL', 'http://localhost/globaltekhnologii/Restaurante/ChatbotSaaS');

// Endpoint de la API
define('SAAS_API_ENDPOINT', SAAS_SERVER_URL . '/api');

// ============================================
// LÍMITES DEL PLAN (Sincronizados del servidor)
// ============================================

// Estos valores se sincronizan automáticamente con el servidor
// No modificar manualmente

define('PLAN_MAX_USERS', {$planLimits['users']});
define('PLAN_MAX_MENU_ITEMS', {$planLimits['menu_items']});
define('PLAN_MAX_STORAGE_MB', {$planLimits['storage_mb']});

// ============================================
// CONFIGURACIÓN DE SINCRONIZACIÓN
// ============================================

// Habilitar sincronización automática
define('SYNC_ENABLED', true);

// Intervalo de sincronización en segundos (por defecto: 1 hora)
define('SYNC_INTERVAL', 3600);

// Última sincronización (timestamp)
// Este valor se actualiza automáticamente
define('LAST_SYNC_TIME', 0);

// ============================================
// MODO DE OPERACIÓN
// ============================================

// Modo offline: Si está en true, el sistema funciona sin validar límites
// Útil para desarrollo o cuando el servidor SaaS no está disponible
define('OFFLINE_MODE', false);

// Modo estricto: Si está en true, bloquea acciones que excedan límites
// Si está en false, solo muestra advertencias
define('STRICT_LIMITS', true);

?>
PHP;

$conn->close();

// Configurar headers para descarga
$filename = "tenant_config_{$tenant['tenant_key']}.php";
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($configContent));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// Enviar contenido
echo $configContent;
exit;
?>
