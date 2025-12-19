<?php
/**
 * Sistema de Versionado
 * Define la versión actual del sistema
 */

define('SYSTEM_VERSION', '2.5.1');
define('SYSTEM_NAME', 'Sistema Multi-Tenant SaaS - Restaurante');
define('VERSION_DATE', '2025-12-18');

/**
 * Obtiene la versión actual del sistema
 */
function getCurrentVersion() {
    return SYSTEM_VERSION;
}

/**
 * Compara dos versiones
 * @return int -1 si v1 < v2, 0 si iguales, 1 si v1 > v2
 */
function compareVersions($v1, $v2) {
    return version_compare($v1, $v2);
}

/**
 * Verifica si hay una nueva versión disponible
 */
function isNewerVersion($newVersion) {
    return compareVersions($newVersion, SYSTEM_VERSION) > 0;
}
