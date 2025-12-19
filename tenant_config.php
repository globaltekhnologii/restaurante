<?php
/**
 * Configuración del Tenant
 * Este archivo identifica a qué tenant SaaS pertenece este restaurante
 * 
 * IMPORTANTE: Este archivo es generado automáticamente por el Super Admin
 * No modificar manualmente a menos que sepas lo que estás haciendo
 * 
 * Generado: 2024-12-18
 * Restaurante: Restaurante Demo
 * Plan: pro
 */

// ============================================
// IDENTIFICACIÓN DEL TENANT
// ============================================

// ID único del tenant en el sistema SaaS
define('TENANT_ID', 1);

// Clave única del tenant (más amigable que el ID numérico)
define('TENANT_KEY', 'tenant_000001_0180237a');

// Token de autenticación para API
define('API_TOKEN', '8c5e1f4a9b2d3e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0b1c2d3e4f5a6b7c8d9e0f');

// ============================================
// INFORMACIÓN DEL TENANT (Referencia)
// ============================================

// Nombre del restaurante
define('TENANT_NAME', 'Restaurante Demo');

// Plan contratado
define('TENANT_PLAN', 'pro');

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

define('PLAN_MAX_USERS', 15);
define('PLAN_MAX_MENU_ITEMS', 200);
define('PLAN_MAX_STORAGE_MB', 2048);

// ============================================
// CONFIGURACIÓN DE SINCRONIZACIÓN
// ============================================

// Habilitar sincronización automática
if (!defined('SYNC_ENABLED')) define('SYNC_ENABLED', true);

// Intervalo de sincronización en segundos (por defecto: 1 hora)
if (!defined('SYNC_INTERVAL')) define('SYNC_INTERVAL', 3600);

// Última sincronización (timestamp)
// Este valor se actualiza automáticamente
if (!defined('LAST_SYNC_TIME')) define('LAST_SYNC_TIME', 0);

// ============================================
// MODO DE OPERACIÓN
// ============================================

// Modo offline: Si está en true, el sistema funciona sin validar límites
// Útil para desarrollo o cuando el servidor SaaS no está disponible
if (!defined('OFFLINE_MODE')) define('OFFLINE_MODE', false);

// Modo estricto: Si está en true, bloquea acciones que excedan límites
// Si está en false, solo muestra advertencias
if (!defined('STRICT_LIMITS')) define('STRICT_LIMITS', true);

?>
