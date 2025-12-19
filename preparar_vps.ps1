# Script de Preparación para Despliegue VPS
# Este script copia los archivos necesarios y configura el proyecto para VPS

Write-Host "=== Preparando Paquete para VPS Hostinger ===" -ForegroundColor Green
Write-Host ""

# Rutas
$sourceDir = "c:\xampp\htdocs\globaltekhnologii\Restaurante"
$destDir = "c:\xampp\htdocs\globaltekhnologii_vps\Restaurante"

# Crear directorio destino
Write-Host "1. Creando directorio destino..." -ForegroundColor Yellow
New-Item -ItemType Directory -Path $destDir -Force | Out-Null

# Archivos y carpetas a EXCLUIR
$excludeItems = @(
    ".git",
    ".github",
    ".vscode",
    "node_modules",
    "vendor",
    "backups",
    "tests",
    "android_app",
    "ChatbotSaaS",
    "*.log",
    "*.tmp",
    "composer-setup.php",
    "test_*.php",
    "debug_*.php",
    "diagnostico_*.php",
    "verificar_*.php",
    "simular_*.php",
    "ver_estructura_*.php",
    "find_*.php",
    "prepare_*.php",
    "actualizar_passwords.php"
)

Write-Host "2. Copiando archivos del proyecto..." -ForegroundColor Yellow
Write-Host "   Excluyendo: tests, backups, archivos de debug..." -ForegroundColor Gray

# Copiar todos los archivos primero
Copy-Item -Path "$sourceDir\*" -Destination $destDir -Recurse -Force -Exclude $excludeItems

Write-Host "3. Limpiando archivos innecesarios..." -ForegroundColor Yellow

# Eliminar carpetas específicas si se copiaron
$foldersToRemove = @("tests", "backups", ".git", ".github", ".vscode", "android_app", "ChatbotSaaS")
foreach ($folder in $foldersToRemove) {
    $folderPath = Join-Path $destDir $folder
    if (Test-Path $folderPath) {
        Remove-Item -Path $folderPath -Recurse -Force
        Write-Host "   Eliminado: $folder" -ForegroundColor Gray
    }
}

# Eliminar archivos de debug y prueba
$debugFiles = Get-ChildItem -Path $destDir -Filter "test_*.php" -File
$debugFiles += Get-ChildItem -Path $destDir -Filter "debug_*.php" -File
$debugFiles += Get-ChildItem -Path $destDir -Filter "diagnostico_*.php" -File
$debugFiles += Get-ChildItem -Path $destDir -Filter "verificar_*.php" -File

foreach ($file in $debugFiles) {
    Remove-Item -Path $file.FullName -Force
    Write-Host "   Eliminado: $($file.Name)" -ForegroundColor Gray
}

Write-Host ""
Write-Host "4. Configurando para entorno VPS..." -ForegroundColor Yellow

# Copiar .env.cloud como .env
Copy-Item -Path "$destDir\.env.cloud" -Destination "$destDir\.env" -Force
Write-Host "   Creado archivo .env desde .env.cloud" -ForegroundColor Gray

# Crear archivo de configuración VPS
$vpsConfig = @"
<?php
/**
 * Configuración para VPS Hostinger
 * Este archivo sobrescribe config.php en producción
 */

// Entorno
define('ENVIRONMENT', 'PRODUCTION_CLOUD');

// Base de Datos VPS
define('DB_HOST', 'localhost');
define('DB_NAME', 'restaurante_db');
define('DB_USER', 'restaurante_user');
define('DB_PASS', 'CAMBIAR_EN_VPS'); // Cambiar después de crear BD en VPS

// Rutas VPS
define('BASE_PATH', '/web/srv1208645.hstgr.cloud/public_html');
define('BASE_URL', 'http://srv1208645.hstgr.cloud'); // Acceso IP: 72.62.82.98

// Seguridad
define('SESSION_SECURE', true); // HTTPS habilitado
define('CSRF_ENABLED', true);

// Logs y Backups
define('LOG_PATH', '/web/srv1208645.hstgr.cloud/logs');
define('BACKUP_PATH', '/web/srv1208645.hstgr.cloud/backups');

// Sincronización
define('SYNC_ENABLED', true);
define('OFFLINE_MODE', false);
define('MASTER_SERVER', true);

// Email
define('ADMIN_EMAIL', 'globaltekhnologii@gmail.com');

// Zona horaria
date_default_timezone_set('America/Bogota');

// Errores (NO mostrar en producción)
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', LOG_PATH . '/php_errors.log');
"@

$vpsConfig | Out-File -FilePath "$destDir\config_vps.php" -Encoding UTF8
Write-Host "   Creado config_vps.php con configuración de producción" -ForegroundColor Gray

# Crear .htaccess optimizado para VPS
$htaccess = @"
# Configuración Apache para VPS Hostinger
# Sistema de Restaurante - Producción

# Habilitar Rewrite Engine
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    
    # Forzar HTTPS (descomentar cuando SSL esté configurado)
    # RewriteCond %{HTTPS} off
    # RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</IfModule>

# Seguridad
<IfModule mod_headers.c>
    # Prevenir clickjacking
    Header set X-Frame-Options "DENY"
    
    # Prevenir MIME sniffing
    Header set X-Content-Type-Options "nosniff"
    
    # XSS Protection
    Header set X-XSS-Protection "1; mode=block"
    
    # Referrer Policy
    Header set Referrer-Policy "strict-origin-when-cross-origin"
    
    # Content Security Policy (ajustar según necesidad)
    Header set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';"
</IfModule>

# Proteger archivos sensibles
<FilesMatch "^(config\.php|config_vps\.php|\.env|\.env\.cloud|\.htaccess)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Prevenir ejecución de PHP en directorios de uploads
<Directory "imagenes_platos">
    php_flag engine off
</Directory>
<Directory "imagenes_qr">
    php_flag engine off
</Directory>
<Directory "publicidad">
    php_flag engine off
</Directory>

# Cache de archivos estáticos
<IfModule mod_expires.c>
    ExpiresActive On
    
    # Imágenes
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/webp "access plus 1 year"
    
    # CSS y JavaScript
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    
    # Fuentes
    ExpiresByType font/woff2 "access plus 1 year"
</IfModule>

# Compresión GZIP
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
</IfModule>

# Deshabilitar listado de directorios
Options -Indexes

# Página de error personalizada
ErrorDocument 404 /index.php
ErrorDocument 403 /index.php
"@

$htaccess | Out-File -FilePath "$destDir\.htaccess" -Encoding UTF8 -NoNewline
Write-Host "   Actualizado .htaccess con configuración de seguridad" -ForegroundColor Gray

# Crear README para VPS
$readmeVPS = @"
# Paquete de Despliegue VPS - Sistema de Restaurante

**Fecha de Preparación:** $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")
**Destino:** VPS Hostinger
**Versión:** 2.5 (Producción)

## Archivos Incluidos

Este paquete contiene:
- ✅ Código fuente completo (sin archivos de debug)
- ✅ Configuración para VPS (.env, config_vps.php)
- ✅ .htaccess optimizado con seguridad
- ✅ Scripts de API y procesamiento
- ✅ Archivos estáticos (CSS, JS, imágenes)

## Archivos Excluidos

Para reducir tamaño y seguridad:
- ❌ Tests automatizados
- ❌ Backups locales
- ❌ Archivos de debug/diagnóstico
- ❌ Aplicación Android
- ❌ ChatbotSaaS (separado)
- ❌ Repositorio Git

## Pasos de Instalación en VPS

1. **Subir archivos al VPS**
   - Comprimir esta carpeta en ZIP
   - Subir vía WinSCP o SCP a /var/www/html/
   - Descomprimir en el servidor

2. **Configurar Base de Datos**
   - Importar database_inventario.sql
   - Actualizar credenciales en config_vps.php

3. **Configurar Permisos**
   ```bash
   chown -R www-data:www-data /var/www/html/restaurante
   chmod -R 755 /var/www/html/restaurante
   chmod -R 775 /var/www/html/restaurante/imagenes_platos
   chmod -R 775 /var/www/html/restaurante/imagenes_qr
   chmod -R 775 /var/www/html/restaurante/publicidad
   ```

4. **Configurar Apache**
   - Seguir GUIA_DESPLIEGUE_HOSTINGER.md

5. **Configurar SSL**
   - Instalar certificado SSL
   - Descomentar redirección HTTPS en .htaccess

## Archivos de Configuración Importantes

- **config_vps.php** - Configuración principal para VPS
- **.env** - Variables de entorno (CAMBIAR CREDENCIALES)
- **.htaccess** - Configuración de seguridad Apache

## Seguridad

⚠️ **IMPORTANTE:** Antes de poner en producción:
1. Cambiar password de admin (actualmente: admin123)
2. Actualizar credenciales de BD en config_vps.php
3. Cambiar DB_PASS en .env
4. Configurar SSL/HTTPS
5. Revisar permisos de archivos

## Soporte

- Email: globaltekhnologii@gmail.com
- Documentación: Ver archivos GUIA_*.md

---
© 2025 Global Tekhno Logii
"@

$readmeVPS | Out-File -FilePath "$destDir\README_VPS.md" -Encoding UTF8
Write-Host "   Creado README_VPS.md con instrucciones" -ForegroundColor Gray

Write-Host ""
Write-Host "5. Creando directorios necesarios..." -ForegroundColor Yellow

# Crear directorios que deben existir vacíos
$dirsToCreate = @("backups", "logs")
foreach ($dir in $dirsToCreate) {
    $dirPath = Join-Path $destDir $dir
    New-Item -ItemType Directory -Path $dirPath -Force | Out-Null
    
    # Crear .gitkeep para mantener el directorio
    New-Item -ItemType File -Path "$dirPath\.gitkeep" -Force | Out-Null
    Write-Host "   Creado directorio: $dir" -ForegroundColor Gray
}

Write-Host ""
Write-Host "=== Preparación Completada ===" -ForegroundColor Green
Write-Host ""
Write-Host "Ubicación del paquete: $destDir" -ForegroundColor Cyan
Write-Host ""
Write-Host "Próximos pasos:" -ForegroundColor Yellow
Write-Host "1. Exportar base de datos (ejecutar script de exportación)" -ForegroundColor White
Write-Host "2. Comprimir carpeta en ZIP" -ForegroundColor White
Write-Host "3. Subir al VPS vía WinSCP" -ForegroundColor White
Write-Host "4. Seguir GUIA_DESPLIEGUE_HOSTINGER.md" -ForegroundColor White
Write-Host ""
