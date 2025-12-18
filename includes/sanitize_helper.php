<?php
// includes/sanitize_helper.php - Helper para sanitización de datos

/**
 * Limpia una cadena de texto eliminando etiquetas HTML y espacios extra.
 * @param string $input Cadena a limpiar
 * @return string Cadena limpia
 */
function cleanString($input) {
    if ($input === null) return '';
    return trim(strip_tags($input));
}

/**
 * Limpia un email eliminando caracteres ilegales.
 * @param string $email Email a limpiar
 * @return string Email limpio o vacío si no es válido
 */
function cleanEmail($email) {
    $email = trim($email);
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }
    return '';
}

/**
 * Asegura que el valor sea un entero.
 * @param mixed $input Valor a convertir
 * @return int Entero
 */
function cleanInt($input) {
    return intval($input);
}

/**
 * Asegura que el valor sea un número flotante.
 * @param mixed $input Valor a convertir
 * @return float Número flotante
 */
function cleanFloat($input) {
    return floatval($input);
}

/**
 * Limpia texto permitiendo solo ciertas etiquetas HTML seguras (útil para descripciones).
 * @param string $input Texto HTML
 * @return string HTML seguro
 */
function cleanHtml($input) {
    // Lista blanca básica de tags permitidos
    return strip_tags($input, '<b><strong><i><em><br><p><ul><li>');
}
?>
