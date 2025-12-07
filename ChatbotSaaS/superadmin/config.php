<?php
/**
 * Configuración y Helpers para Super Admin
 */

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuración de base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'menu_restaurante');

/**
 * Obtener conexión a la base de datos
 */
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}

/**
 * Verificar si el usuario está autenticado como Super Admin
 */
function checkSuperAdminAuth() {
    if (!isset($_SESSION['superadmin_logged_in']) || $_SESSION['superadmin_logged_in'] !== true) {
        header('Location: login.php');
        exit();
    }
}

/**
 * Obtener información del Super Admin actual
 */
function getCurrentSuperAdmin() {
    if (!isset($_SESSION['superadmin_id'])) {
        return null;
    }
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id, email, name FROM saas_super_admins WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['superadmin_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    
    $stmt->close();
    $conn->close();
    
    return $admin;
}

/**
 * Formatear fecha en español
 */
function formatDateES($date) {
    if (!$date) return 'N/A';
    
    $timestamp = strtotime($date);
    $months = [
        1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
        5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
        9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'
    ];
    
    $day = date('d', $timestamp);
    $month = $months[(int)date('m', $timestamp)];
    $year = date('Y', $timestamp);
    
    return "$day $month $year";
}

/**
 * Formatear moneda colombiana
 */
function formatCurrency($amount) {
    return '$' . number_format($amount, 0, ',', '.');
}

/**
 * Calcular días restantes de suscripción
 */
function getDaysRemaining($subscription_end) {
    if (!$subscription_end) return null;
    
    $today = new DateTime();
    $end_date = new DateTime($subscription_end);
    $diff = $today->diff($end_date);
    
    if ($diff->invert) {
        return -$diff->days; // Negativo si ya expiró
    }
    
    return $diff->days;
}

/**
 * Obtener badge de estado
 */
function getStatusBadge($status) {
    $badges = [
        'active' => '<span class="badge badge-success">Activo</span>',
        'suspended' => '<span class="badge badge-warning">Suspendido</span>',
        'cancelled' => '<span class="badge badge-danger">Cancelado</span>'
    ];
    
    return $badges[$status] ?? '<span class="badge badge-secondary">Desconocido</span>';
}

/**
 * Obtener badge de plan
 */
function getPlanBadge($plan) {
    $badges = [
        'basic' => '<span class="badge badge-info">Básico</span>',
        'pro' => '<span class="badge badge-primary">Pro</span>',
        'enterprise' => '<span class="badge badge-dark">Enterprise</span>'
    ];
    
    return $badges[$plan] ?? '<span class="badge badge-secondary">N/A</span>';
}
?>
