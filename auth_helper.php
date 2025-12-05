<?php
// auth_helper.php - Helper de autenticación y permisos

/**
 * Verificar que el usuario esté logueado
 * Redirige a login.php si no hay sesión activa
 */
function verificarSesion() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== TRUE) {
        header("Location: login.php");
        exit;
    }
}

/**
 * Verificar que el usuario tenga uno de los roles permitidos
 * @param array $roles_permitidos Array de roles permitidos
 * @return bool True si el usuario tiene permiso, false si no
 */
function verificarRol($roles_permitidos) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['rol'])) {
        return false;
    }
    
    return in_array($_SESSION['rol'], $roles_permitidos);
}

/**
 * Verificar rol y redirigir si no tiene permiso
 * @param array $roles_permitidos Array de roles permitidos
 * @param string $redirect_url URL a la que redirigir si no tiene permiso
 */
function verificarRolORedirect($roles_permitidos, $redirect_url = 'login.php') {
    if (!verificarRol($roles_permitidos)) {
        header("Location: $redirect_url");
        exit;
    }
}

/**
 * Redirigir al usuario según su rol
 */
function redirigirSegunRol() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['rol'])) {
        header("Location: login.php");
        exit;
    }
    
    switch ($_SESSION['rol']) {
        case 'admin':
            header("Location: admin.php");
            break;
        case 'mesero':
            header("Location: mesero.php");
            break;
        case 'chef':
            header("Location: chef.php");
            break;
        case 'domiciliario':
            header("Location: domiciliario.php");
            break;
        case 'cajero':
            header("Location: cajero.php");
            break;
        default:
            header("Location: login.php");
            break;
    }
    exit;
}

/**
 * Verificar si el usuario es administrador
 * @return bool
 */
function esAdmin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
}

/**
 * Verificar si el usuario es mesero
 * @return bool
 */
function esMesero() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'mesero';
}

/**
 * Verificar si el usuario es chef
 * @return bool
 */
function esChef() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'chef';
}

/**
 * Verificar si el usuario es domiciliario
 * @return bool
 */
function esDomiciliario() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'domiciliario';
}

/**
 * Obtener el nombre del rol en español
 * @param string $rol Rol del usuario
 * @return string Nombre del rol en español
 */
function getNombreRol($rol) {
    $roles = [
        'admin' => 'Administrador',
        'mesero' => 'Mesero',
        'chef' => 'Chef',
        'domiciliario' => 'Domiciliario'
    ];
    
    return isset($roles[$rol]) ? $roles[$rol] : 'Usuario';
}

/**
 * Obtener el icono del rol
 * @param string $rol Rol del usuario
 * @return string Emoji del rol
 */
function getIconoRol($rol) {
    $iconos = [
        'admin' => '👨‍💼',
        'mesero' => '🍽️',
        'chef' => '👨‍🍳',
        'domiciliario' => '🏍️'
    ];
    
    return isset($iconos[$rol]) ? $iconos[$rol] : '👤';
}

/**
 * Obtener el color del rol
 * @param string $rol Rol del usuario
 * @return string Color hexadecimal
 */
function getColorRol($rol) {
    $colores = [
        'admin' => '#667eea',
        'mesero' => '#48bb78',
        'chef' => '#ed8936',
        'domiciliario' => '#4299e1'
    ];
    
    return isset($colores[$rol]) ? $colores[$rol] : '#718096';
}
?>
