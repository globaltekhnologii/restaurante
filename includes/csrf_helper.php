<?php
// includes/csrf_helper.php - Helper para protección contra Cross-Site Request Forgery

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

/**
 * Genera un token CSRF y lo guarda en la sesión si no existe.
 * @return string El token CSRF
 */
function generarCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        try {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } catch (Exception $e) {
            // Fallback para versiones antiguas de PHP o problemas con random_bytes
            $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
        }
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida que el token CSRF recibido coincida con el de la sesión.
 * @param string $token El token recibido del formulario
 * @return bool True si es válido, False si no
 */
function validarCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Genera el campo input HTML oculto con el token CSRF.
 * @return string HTML del input
 */
function csrf_field() {
    $token = generarCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Verifica el token CSRF de una petición POST y termina la ejecución si es inválido.
 * Útil para poner al principio de archivos de procesamiento.
 */
function verificarTokenOError() {
    $token = $_POST['csrf_token'] ?? '';
    if (!validarCsrfToken($token)) {
        // Log del intento fallido para auditoría
        error_log("Fallo de validación CSRF desde IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'desconocida'));
        
        // Respuesta de error
        header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
        die('<h1>Error de Seguridad (403)</h1><p>La sesión ha expirado o la petición es inválida. Por favor recarga la página e intenta nuevamente.</p>');
    }
}
