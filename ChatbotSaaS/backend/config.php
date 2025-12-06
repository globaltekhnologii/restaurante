
<?php
/**
 * Configuración del Backend SaaS Chatbot
 */

// Conexión a la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "menu_restaurante";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(['error' => 'Error de conexión a la base de datos']));
}

$conn->set_charset("utf8mb4");

// Configuración de sesiones
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Función helper para respuestas JSON
 */
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Función helper para errores
 */
function jsonError($message, $status = 400) {
    jsonResponse(['error' => $message], $status);
}

/**
 * Verificar autenticación de tenant
 */
function requireAuth() {
    if (!isset($_SESSION['saas_tenant_id'])) {
        jsonError('No autorizado', 401);
    }
    return $_SESSION['saas_tenant_id'];
}

/**
 * Obtener configuración del chatbot por tenant
 */
function getChatbotConfig($conn, $tenant_id) {
    $stmt = $conn->prepare("
        SELECT c.*, t.restaurant_name 
        FROM saas_chatbot_config c
        JOIN saas_tenants t ON t.id = c.tenant_id
        WHERE c.tenant_id = ?
    ");
    $stmt->bind_param("i", $tenant_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Obtener menú del tenant
 */
function getMenuItems($conn, $tenant_id, $available_only = true) {
    $sql = "SELECT * FROM saas_menu_items WHERE tenant_id = ?";
    if ($available_only) {
        $sql .= " AND available = TRUE";
    }
    $sql .= " ORDER BY category, name";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $tenant_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    return $items;
}
?>
