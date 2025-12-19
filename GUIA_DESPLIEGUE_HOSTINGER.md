# 🚀 Guía de Despliegue en VPS Hostinger

**Sistema de Restaurante - Arquitectura Híbrida Cloud + Local**

---

## 📋 Información del Proyecto

- **Email Admin**: globaltekhnologii@gmail.com
- **Plan**: Hostinger VPS 2
- **Acceso**: Por IP (sin dominio)
- **Arquitectura**: Cloud (VPS) + Local (Restaurantes)

---

## 🏗️ Arquitectura del Sistema

```
┌─────────────────────────────────────────────────────────────┐
│                    TU PC (Desarrollo)                       │
│              XAMPP - Desarrollo y Testing                   │
└────────────────────┬────────────────────────────────────────┘
                     │ Deploy Script
                     ↓
┌─────────────────────────────────────────────────────────────┐
│              VPS HOSTINGER (Maestro Cloud)                  │
│   - Sistema completo en producción                          │
│   - API de sincronización                                   │
│   - Backups centralizados                                   │
│   - Control de versiones                                    │
└────────────────────┬────────────────────────────────────────┘
                     │ Sincronización
        ┌────────────┼────────────┐
        ↓            ↓            ↓
┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│ Restaurante 1│ │ Restaurante 2│ │ Restaurante N│
│ (XAMPP Local)│ │ (XAMPP Local)│ │ (XAMPP Local)│
│              │ │              │ │              │
│ ✅ Offline OK│ │ ✅ Offline OK│ │ ✅ Offline OK│
└──────────────┘ └──────────────┘ └──────────────┘
```

---

## 📝 PASO 1: Obtener Credenciales del VPS

### 1.1 Acceder al Panel de Hostinger

1. Ve a [https://hpanel.hostinger.com](https://hpanel.hostinger.com)
2. Inicia sesión con tu cuenta
3. Busca tu VPS en el panel

### 1.2 Obtener Información de Acceso SSH

En el panel de tu VPS encontrarás:

- **IP del VPS**: `XXX.XXX.XXX.XXX`
- **Usuario SSH**: generalmente `root` o usuario personalizado
- **Contraseña SSH**: la que configuraste o enviada por email
- **Puerto SSH**: generalmente `22`

> **📝 ANOTA ESTA INFORMACIÓN AQUÍ:**
> ```
> IP VPS: _______________________
> Usuario: _______________________
> Puerto: _______________________
> ```

---

## 🔧 PASO 2: Conectar al VPS por Primera Vez

### 2.1 Desde Windows (PowerShell)

```powershell
# Abrir PowerShell como Administrador
ssh root@TU_IP_VPS
```

### 2.2 Desde Windows (PuTTY)

1. Descargar PuTTY: https://www.putty.org/
2. Abrir PuTTY
3. En "Host Name": poner tu IP
4. Puerto: 22
5. Click en "Open"
6. Ingresar usuario y contraseña

### 2.3 Primera Conexión

Al conectar por primera vez verás:
```
The authenticity of host 'XXX.XXX.XXX.XXX' can't be established.
Are you sure you want to continue connecting (yes/no)?
```

Escribe: **yes** y presiona Enter

---

## ⚙️ PASO 3: Configuración Inicial del VPS

### 3.1 Actualizar el Sistema

```bash
# Actualizar lista de paquetes
apt update

# Actualizar paquetes instalados
apt upgrade -y
```

### 3.2 Instalar Stack LAMP

```bash
# Instalar Apache
apt install apache2 -y

# Instalar MySQL
apt install mysql-server -y

# Instalar PHP 8.1 y extensiones
apt install php8.1 php8.1-cli php8.1-fpm php8.1-mysql php8.1-mbstring php8.1-xml php8.1-curl php8.1-zip php8.1-gd -y

# Habilitar módulos de Apache
a2enmod rewrite
a2enmod ssl
a2enmod headers

# Reiniciar Apache
systemctl restart apache2
```

### 3.3 Verificar Instalación

```bash
# Verificar Apache
systemctl status apache2

# Verificar MySQL
systemctl status mysql

# Verificar PHP
php -v
```

Deberías ver PHP 8.1.x

---

## 🔒 PASO 4: Configurar Seguridad Básica

### 4.1 Configurar Firewall

```bash
# Instalar UFW (si no está instalado)
apt install ufw -y

# Permitir SSH
ufw allow 22/tcp

# Permitir HTTP
ufw allow 80/tcp

# Permitir HTTPS
ufw allow 443/tcp

# Habilitar firewall
ufw enable

# Verificar estado
ufw status
```

### 4.2 Asegurar MySQL

```bash
# Ejecutar script de seguridad
mysql_secure_installation
```

Responde:
- **Set root password?** → Yes (elige una contraseña FUERTE)
- **Remove anonymous users?** → Yes
- **Disallow root login remotely?** → Yes
- **Remove test database?** → Yes
- **Reload privilege tables?** → Yes

> **📝 ANOTA LA CONTRASEÑA DE MYSQL:**
> ```
> MySQL Root Password: _______________________
> ```

---

## 📦 PASO 5: Preparar Archivos en tu PC

### 5.1 Limpiar Proyecto

En tu PC, abre PowerShell en la carpeta del proyecto:

```powershell
cd c:\xampp\htdocs\globaltekhnologii\Restaurante
```

### 5.2 Crear Archivo de Configuración para VPS

Crea un archivo `.env.cloud` con este contenido:

```env
# Configuración VPS Hostinger
ENVIRONMENT=PRODUCTION_CLOUD

# Base de Datos
DB_HOST=localhost
DB_NAME=restaurante_db
DB_USER=restaurante_user
DB_PASS=TU_PASSWORD_SEGURO_AQUI

# Email
ADMIN_EMAIL=globaltekhnologii@gmail.com

# Seguridad
SESSION_SECURE=true
CSRF_ENABLED=true

# Modo
SYNC_ENABLED=true
OFFLINE_MODE=false
```

### 5.3 Comprimir Proyecto

```powershell
# Crear archivo ZIP (excluyendo archivos innecesarios)
Compress-Archive -Path * -DestinationPath ..\restaurante_deploy.zip -Force
```

---

## 📤 PASO 6: Transferir Archivos al VPS

### 6.1 Opción A: Usar WinSCP (Recomendado para Windows)

1. Descargar WinSCP: https://winscp.net/
2. Abrir WinSCP
3. Configurar conexión:
   - **Protocolo**: SFTP
   - **Host**: Tu IP VPS
   - **Puerto**: 22
   - **Usuario**: root
   - **Contraseña**: tu contraseña SSH
4. Click en "Login"
5. Navegar a `/var/www/html/`
6. Subir el archivo `restaurante_deploy.zip`

### 6.2 Opción B: Usar SCP desde PowerShell

```powershell
scp ..\restaurante_deploy.zip root@TU_IP_VPS:/var/www/html/
```

---

## 🗄️ PASO 7: Configurar Base de Datos

### 7.1 Conectar a MySQL en el VPS

```bash
mysql -u root -p
```

Ingresa la contraseña de MySQL que configuraste.

### 7.2 Crear Base de Datos y Usuario

```sql
-- Crear base de datos
CREATE DATABASE restaurante_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Crear usuario
CREATE USER 'restaurante_user'@'localhost' IDENTIFIED BY 'TU_PASSWORD_SEGURO';

-- Dar permisos
GRANT ALL PRIVILEGES ON restaurante_db.* TO 'restaurante_user'@'localhost';

-- Aplicar cambios
FLUSH PRIVILEGES;

-- Salir
EXIT;
```

> **📝 ANOTA ESTAS CREDENCIALES:**
> ```
> DB Name: restaurante_db
> DB User: restaurante_user
> DB Pass: _______________________
> ```

### 7.3 Importar Estructura de Base de Datos

```bash
# Descomprimir proyecto
cd /var/www/html
unzip restaurante_deploy.zip -d restaurante

# Importar base de datos
mysql -u restaurante_user -p restaurante_db < /var/www/html/restaurante/database_inventario.sql
```

---

## 🌐 PASO 8: Configurar Apache

### 8.1 Crear Virtual Host

```bash
nano /etc/apache2/sites-available/restaurante.conf
```

Pega este contenido (reemplaza TU_IP_VPS):

```apache
<VirtualHost *:80>
    ServerAdmin globaltekhnologii@gmail.com
    ServerName TU_IP_VPS
    DocumentRoot /var/www/html/restaurante

    <Directory /var/www/html/restaurante>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Logs
    ErrorLog ${APACHE_LOG_DIR}/restaurante_error.log
    CustomLog ${APACHE_LOG_DIR}/restaurante_access.log combined

    # Seguridad
    <FilesMatch "\.php$">
        SetHandler "proxy:unix:/run/php/php8.1-fpm.sock|fcgi://localhost"
    </FilesMatch>
</VirtualHost>
```

Guarda con: `Ctrl + X`, luego `Y`, luego `Enter`

### 8.2 Habilitar Sitio

```bash
# Deshabilitar sitio por defecto
a2dissite 000-default.conf

# Habilitar nuestro sitio
a2ensite restaurante.conf

# Reiniciar Apache
systemctl restart apache2
```

### 8.3 Configurar Permisos

```bash
# Cambiar propietario
chown -R www-data:www-data /var/www/html/restaurante

# Configurar permisos
chmod -R 755 /var/www/html/restaurante

# Permisos especiales para carpetas de escritura
chmod -R 775 /var/www/html/restaurante/backups
chmod -R 775 /var/www/html/restaurante/imagenes_platos
chmod -R 775 /var/www/html/restaurante/imagenes_qr
chmod -R 775 /var/www/html/restaurante/publicidad
```

---

## 🔐 PASO 9: Configurar SSL (HTTPS)

### 9.1 Instalar Certbot

```bash
apt install certbot python3-certbot-apache -y
```

### 9.2 Generar Certificado Autofirmado (Temporal)

Como no tienes dominio, usaremos un certificado autofirmado:

```bash
# Crear certificado
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout /etc/ssl/private/restaurante-selfsigned.key \
  -out /etc/ssl/certs/restaurante-selfsigned.crt
```

Completa la información solicitada:
- **Country**: CO
- **State**: Tu departamento
- **City**: Tu ciudad
- **Organization**: Global Tekhno Logii
- **Common Name**: Tu IP VPS
- **Email**: globaltekhnologii@gmail.com

### 9.3 Configurar Apache para HTTPS

```bash
nano /etc/apache2/sites-available/restaurante-ssl.conf
```

Contenido:

```apache
<VirtualHost *:443>
    ServerAdmin globaltekhnologii@gmail.com
    ServerName TU_IP_VPS
    DocumentRoot /var/www/html/restaurante

    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/restaurante-selfsigned.crt
    SSLCertificateKeyFile /etc/ssl/private/restaurante-selfsigned.key

    <Directory /var/www/html/restaurante>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/restaurante_ssl_error.log
    CustomLog ${APACHE_LOG_DIR}/restaurante_ssl_access.log combined
</VirtualHost>
```

```bash
# Habilitar sitio SSL
a2ensite restaurante-ssl.conf

# Reiniciar Apache
systemctl restart apache2
```

---

## ✅ PASO 10: Verificar Funcionamiento

### 10.1 Probar Acceso Web

Abre tu navegador y ve a:

```
http://TU_IP_VPS
```

Deberías ver la página principal del sistema.

### 10.2 Probar Login

```
http://TU_IP_VPS/login.php
```

Credenciales por defecto:
- **Usuario**: admin
- **Contraseña**: admin123

> ⚠️ **IMPORTANTE**: Cambia esta contraseña inmediatamente después del primer login.

### 10.3 Ejecutar Health Check

```
http://TU_IP_VPS/health_check.php
```

Deberías ver:
```json
{
  "status": "ok",
  "database": true,
  "timestamp": "..."
}
```

---

## 🔄 PASO 11: Configurar Backups Automáticos

### 11.1 Crear Script de Backup

```bash
nano /root/backup_restaurante.sh
```

Contenido:

```bash
#!/bin/bash

# Configuración
BACKUP_DIR="/root/backups"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="restaurante_db"
DB_USER="restaurante_user"
DB_PASS="TU_PASSWORD_MYSQL"

# Crear directorio si no existe
mkdir -p $BACKUP_DIR

# Backup de base de datos
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/db_$DATE.sql

# Backup de archivos
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/html/restaurante

# Eliminar backups antiguos (más de 7 días)
find $BACKUP_DIR -type f -mtime +7 -delete

echo "Backup completado: $DATE"
```

```bash
# Dar permisos de ejecución
chmod +x /root/backup_restaurante.sh
```

### 11.2 Programar Backup Diario

```bash
# Editar crontab
crontab -e
```

Agregar esta línea (backup diario a las 3 AM):

```
0 3 * * * /root/backup_restaurante.sh >> /var/log/backup_restaurante.log 2>&1
```

---

## 📊 PASO 12: Monitoreo y Mantenimiento

### 12.1 Ver Logs de Apache

```bash
# Errores
tail -f /var/log/apache2/restaurante_error.log

# Accesos
tail -f /var/log/apache2/restaurante_access.log
```

### 12.2 Ver Logs de PHP

```bash
tail -f /var/log/php8.1-fpm.log
```

### 12.3 Monitorear Recursos

```bash
# CPU y Memoria
htop

# Espacio en disco
df -h

# Procesos de Apache
ps aux | grep apache
```

---

## 🚨 Troubleshooting Común

### Problema: "500 Internal Server Error"

**Solución**:
```bash
# Ver logs
tail -50 /var/log/apache2/restaurante_error.log

# Verificar permisos
ls -la /var/www/html/restaurante

# Verificar sintaxis PHP
php -l /var/www/html/restaurante/index.php
```

### Problema: "No se puede conectar a la base de datos"

**Solución**:
```bash
# Verificar que MySQL esté corriendo
systemctl status mysql

# Probar conexión
mysql -u restaurante_user -p restaurante_db

# Verificar config.php
nano /var/www/html/restaurante/config.php
```

### Problema: "Página en blanco"

**Solución**:
```bash
# Habilitar errores de PHP temporalmente
nano /etc/php/8.1/apache2/php.ini

# Buscar y cambiar:
display_errors = On
error_reporting = E_ALL

# Reiniciar Apache
systemctl restart apache2
```

---

## 📚 Próximos Pasos

Una vez que el VPS esté funcionando:

1. ✅ **Cambiar contraseñas por defecto**
2. ✅ **Configurar sistema de sincronización** (Fase 2)
3. ✅ **Crear instalador para restaurantes** (Fase 2)
4. ✅ **Documentar procedimientos**
5. ✅ **Probar con restaurante piloto**

---

## 📞 Soporte

Si encuentras problemas, documenta:
- Mensaje de error exacto
- Logs relevantes
- Pasos que realizaste antes del error

---

**¡Felicidades! Tu sistema está en la nube 🎉**
