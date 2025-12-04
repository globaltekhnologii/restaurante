<?php
/**
 * Password Helper - Funciones de validación de contraseñas
 * 
 * Este archivo contiene funciones reutilizables para validar
 * la complejidad y seguridad de contraseñas en todo el sistema.
 */

/**
 * Valida que una contraseña cumpla con los requisitos de seguridad
 * 
 * Requisitos:
 * - Mínimo 8 caracteres
 * - Al menos 1 letra mayúscula
 * - Al menos 1 letra minúscula
 * - Al menos 1 número
 * - Al menos 1 carácter especial
 * 
 * @param string $password Contraseña a validar
 * @return array ['valida' => bool, 'errores' => array]
 */
function validarPassword($password) {
    $errores = [];
    
    // Longitud mínima
    if (strlen($password) < 8) {
        $errores[] = "La contraseña debe tener al menos 8 caracteres";
    }
    
    // Al menos una mayúscula
    if (!preg_match('/[A-Z]/', $password)) {
        $errores[] = "Debe contener al menos una letra mayúscula";
    }
    
    // Al menos una minúscula
    if (!preg_match('/[a-z]/', $password)) {
        $errores[] = "Debe contener al menos una letra minúscula";
    }
    
    // Al menos un número
    if (!preg_match('/[0-9]/', $password)) {
        $errores[] = "Debe contener al menos un número";
    }
    
    // Al menos un carácter especial
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        $errores[] = "Debe contener al menos un carácter especial (!@#$%^&*...)";
    }
    
    return [
        'valida' => empty($errores),
        'errores' => $errores
    ];
}

/**
 * Calcula la fuerza de una contraseña (0-100)
 * 
 * @param string $password Contraseña a evaluar
 * @return int Puntuación de 0 a 100
 */
function calcularFuerzaPassword($password) {
    $fuerza = 0;
    $longitud = strlen($password);
    
    // Puntos por longitud
    if ($longitud >= 8) $fuerza += 20;
    if ($longitud >= 12) $fuerza += 10;
    if ($longitud >= 16) $fuerza += 10;
    
    // Puntos por variedad de caracteres
    if (preg_match('/[a-z]/', $password)) $fuerza += 15;
    if (preg_match('/[A-Z]/', $password)) $fuerza += 15;
    if (preg_match('/[0-9]/', $password)) $fuerza += 15;
    if (preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) $fuerza += 15;
    
    return min(100, $fuerza);
}

/**
 * Obtiene el nivel de fuerza como texto
 * 
 * @param int $fuerza Puntuación de 0 a 100
 * @return string 'Muy débil', 'Débil', 'Media', 'Fuerte', 'Muy fuerte'
 */
function obtenerNivelFuerza($fuerza) {
    if ($fuerza < 40) return 'Muy débil';
    if ($fuerza < 60) return 'Débil';
    if ($fuerza < 80) return 'Media';
    if ($fuerza < 95) return 'Fuerte';
    return 'Muy fuerte';
}

/**
 * Obtiene el color para el indicador de fuerza
 * 
 * @param int $fuerza Puntuación de 0 a 100
 * @return string Color hexadecimal
 */
function obtenerColorFuerza($fuerza) {
    if ($fuerza < 40) return '#dc3545'; // Rojo
    if ($fuerza < 60) return '#fd7e14'; // Naranja
    if ($fuerza < 80) return '#ffc107'; // Amarillo
    if ($fuerza < 95) return '#28a745'; // Verde
    return '#20c997'; // Verde brillante
}

/**
 * Genera una contraseña segura aleatoria
 * 
 * @param int $longitud Longitud de la contraseña (mínimo 8)
 * @return string Contraseña generada
 */
function generarPasswordSegura($longitud = 12) {
    $longitud = max(8, $longitud);
    
    $mayusculas = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $minusculas = 'abcdefghijklmnopqrstuvwxyz';
    $numeros = '0123456789';
    $especiales = '!@#$%^&*()';
    
    $todos = $mayusculas . $minusculas . $numeros . $especiales;
    
    // Asegurar al menos un carácter de cada tipo
    $password = '';
    $password .= $mayusculas[random_int(0, strlen($mayusculas) - 1)];
    $password .= $minusculas[random_int(0, strlen($minusculas) - 1)];
    $password .= $numeros[random_int(0, strlen($numeros) - 1)];
    $password .= $especiales[random_int(0, strlen($especiales) - 1)];
    
    // Completar con caracteres aleatorios
    for ($i = 4; $i < $longitud; $i++) {
        $password .= $todos[random_int(0, strlen($todos) - 1)];
    }
    
    // Mezclar la contraseña
    return str_shuffle($password);
}
?>
