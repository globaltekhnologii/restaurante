# Script de Exportación de Base de Datos para VPS
# Exporta la base de datos actual a SQL para importar en VPS

Write-Host "=== Exportando Base de Datos para VPS ===" -ForegroundColor Green
Write-Host ""

# Configuración
$mysqlPath = "c:\xampp\mysql\bin\mysqldump.exe"
$dbName = "restaurante_db"
$dbUser = "root"
$dbPass = ""  # Password de MySQL local (vacío por defecto en XAMPP)
$outputFile = "c:\xampp\htdocs\globaltekhnologii_vps\Restaurante\database_vps_export.sql"

# Verificar que mysqldump existe
if (-not (Test-Path $mysqlPath)) {
    Write-Host "ERROR: No se encuentra mysqldump en $mysqlPath" -ForegroundColor Red
    Write-Host "Verifica la ruta de instalación de XAMPP" -ForegroundColor Yellow
    exit 1
}

Write-Host "1. Exportando base de datos '$dbName'..." -ForegroundColor Yellow

# Construir comando mysqldump
$arguments = @(
    "-u$dbUser",
    "--databases", $dbName,
    "--add-drop-database",
    "--add-drop-table",
    "--routines",
    "--triggers",
    "--events",
    "--single-transaction",
    "--quick",
    "--lock-tables=false",
    "--result-file=`"$outputFile`""
)

# Agregar password si no está vacío
if ($dbPass -ne "") {
    $arguments = @("-p$dbPass") + $arguments
}

try {
    # Ejecutar mysqldump
    & $mysqlPath $arguments
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "   ✓ Base de datos exportada exitosamente" -ForegroundColor Green
        
        # Obtener tamaño del archivo
        $fileSize = (Get-Item $outputFile).Length
        $fileSizeMB = [math]::Round($fileSize / 1MB, 2)
        Write-Host "   Tamaño: $fileSizeMB MB" -ForegroundColor Gray
        
        # Agregar comentarios al inicio del archivo SQL
        $header = @"
-- =====================================================
-- EXPORTACIÓN DE BASE DE DATOS PARA VPS HOSTINGER
-- =====================================================
-- Fecha: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")
-- Base de Datos: $dbName
-- Origen: Desarrollo Local (XAMPP)
-- Destino: VPS Hostinger
-- =====================================================
-- 
-- INSTRUCCIONES DE IMPORTACIÓN:
-- 1. Conectar al VPS vía SSH
-- 2. Crear base de datos y usuario:
--    CREATE DATABASE restaurante_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
--    CREATE USER 'restaurante_user'@'localhost' IDENTIFIED BY 'PASSWORD_SEGURO';
--    GRANT ALL PRIVILEGES ON restaurante_db.* TO 'restaurante_user'@'localhost';
--    FLUSH PRIVILEGES;
-- 3. Importar este archivo:
--    mysql -u restaurante_user -p restaurante_db < database_vps_export.sql
-- =====================================================

"@
        
        $content = Get-Content $outputFile -Raw
        $header + $content | Set-Content $outputFile -Encoding UTF8
        
        Write-Host ""
        Write-Host "2. Creando información adicional..." -ForegroundColor Yellow
        
        # Crear archivo con información de la BD
        $dbInfo = @"
# Información de Base de Datos - VPS

**Fecha de Exportación:** $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")
**Base de Datos Origen:** $dbName
**Tamaño del Archivo:** $fileSizeMB MB

## Estructura de la Base de Datos

### Tablas Principales:
- **usuarios** - Usuarios del sistema (admin, chef, mesero, cajero, domiciliario)
- **platos** - Menú del restaurante
- **pedidos** - Órdenes de clientes
- **pedidos_items** - Detalle de items en cada pedido
- **clientes** - Información de clientes
- **configuracion** - Configuración del negocio
- **inventario** - Control de stock
- **ingredientes** - Ingredientes para recetas
- **recetas** - Recetas de platos
- **proveedores** - Proveedores de ingredientes
- **movimientos** - Movimientos de inventario

### Credenciales por Defecto:
⚠️ **CAMBIAR INMEDIATAMENTE EN PRODUCCIÓN**

**Usuario Administrador:**
- Usuario: admin
- Password: admin123

## Pasos de Importación en VPS

1. **Subir archivo SQL al VPS**
   ``````bash
   scp database_vps_export.sql root@TU_IP_VPS:/root/
   ``````

2. **Conectar al VPS vía SSH**
   ``````bash
   ssh root@TU_IP_VPS
   ``````

3. **Crear Base de Datos y Usuario**
   ``````bash
   mysql -u root -p
   ``````
   
   Luego ejecutar:
   ``````sql
   CREATE DATABASE restaurante_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'restaurante_user'@'localhost' IDENTIFIED BY 'TU_PASSWORD_SEGURO';
   GRANT ALL PRIVILEGES ON restaurante_db.* TO 'restaurante_user'@'localhost';
   FLUSH PRIVILEGES;
   EXIT;
   ``````

4. **Importar Base de Datos**
   ``````bash
   mysql -u restaurante_user -p restaurante_db < /root/database_vps_export.sql
   ``````

5. **Verificar Importación**
   ``````bash
   mysql -u restaurante_user -p restaurante_db -e "SHOW TABLES;"
   ``````

## Actualizar Credenciales

Después de importar, actualizar el password del admin:

``````sql
USE restaurante_db;
UPDATE usuarios SET password = PASSWORD('TU_NUEVO_PASSWORD_SEGURO') WHERE usuario = 'admin';
``````

O usar el script PHP:
``````bash
php /var/www/html/restaurante/actualizar_password_admin.sql
``````

## Notas Importantes

- ✅ El archivo incluye toda la estructura y datos
- ✅ Compatible con MySQL 5.7+ y MariaDB 10.3+
- ✅ Codificación UTF-8 para caracteres especiales
- ⚠️ Cambiar passwords por defecto antes de producción
- ⚠️ Configurar backups automáticos en VPS

---
© 2025 Global Tekhno Logii
"@
        
        $dbInfo | Out-File -FilePath "c:\xampp\htdocs\globaltekhnologii_vps\Restaurante\DATABASE_INFO.md" -Encoding UTF8
        Write-Host "   ✓ Creado DATABASE_INFO.md con instrucciones" -ForegroundColor Green
        
    } else {
        Write-Host "   ✗ Error al exportar base de datos" -ForegroundColor Red
        Write-Host "   Código de error: $LASTEXITCODE" -ForegroundColor Red
        exit 1
    }
    
} catch {
    Write-Host "ERROR: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "=== Exportación Completada ===" -ForegroundColor Green
Write-Host ""
Write-Host "Archivo SQL: $outputFile" -ForegroundColor Cyan
Write-Host "Tamaño: $fileSizeMB MB" -ForegroundColor Cyan
Write-Host ""
