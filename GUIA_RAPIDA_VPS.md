# 🚀 GUÍA RÁPIDA: Despliegue en VPS Hostinger

**Tiempo estimado: 2-3 horas**

---

## ✅ CHECKLIST PRE-DESPLIEGUE

Antes de comenzar, asegúrate de tener:

- [ ] Acceso al panel de Hostinger (hpanel.hostinger.com)
- [ ] IP del VPS anotada
- [ ] Usuario y contraseña SSH
- [ ] WinSCP o FileZilla instalado (para transferir archivos)
- [ ] PuTTY instalado (para SSH en Windows)

---

## 📋 PASOS RESUMIDOS

### PASO 1: Conectar al VPS (5 min)

```bash
# Desde PowerShell o PuTTY
ssh root@TU_IP_VPS
```

### PASO 2: Ejecutar Script de Configuración (15 min)

```bash
# Descargar script de setup
wget https://raw.githubusercontent.com/tu-repo/vps_setup.sh

# O subir manualmente el archivo vps_setup.sh

# Dar permisos y ejecutar
chmod +x vps_setup.sh
bash vps_setup.sh
```

El script instalará automáticamente:
- Apache 2.4
- MySQL 8.0
- PHP 8.1 + extensiones
- Firewall UFW
- Utilidades básicas

### PASO 3: Asegurar MySQL (5 min)

```bash
mysql_secure_installation
```

Responde:
- Set root password? → **Yes** (elige password fuerte)
- Remove anonymous users? → **Yes**
- Disallow root login remotely? → **Yes**
- Remove test database? → **Yes**
- Reload privilege tables? → **Yes**

### PASO 4: Crear Base de Datos (5 min)

```bash
mysql -u root -p
```

Dentro de MySQL:

```sql
CREATE DATABASE restaurante_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'restaurante_user'@'localhost' IDENTIFIED BY 'TU_PASSWORD_SEGURO';
GRANT ALL PRIVILEGES ON restaurante_db.* TO 'restaurante_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

**📝 ANOTA:** Usuario: `restaurante_user` | Password: `___________`

### PASO 5: Preparar Archivos en tu PC (10 min)

1. **Editar `.env.cloud`**:
   - Abre `c:\xampp\htdocs\globaltekhnologii\Restaurante\.env.cloud`
   - Cambia `DB_PASS` por el password que creaste
   - Cambia `TU_IP_VPS` por la IP real

2. **Crear archivo ZIP**:
   ```powershell
   cd c:\xampp\htdocs\globaltekhnologii\Restaurante
   Compress-Archive -Path * -DestinationPath ..\restaurante.zip -Force
   ```

### PASO 6: Subir Archivos al VPS (15 min)

**Opción A: WinSCP (Recomendado)**

1. Abrir WinSCP
2. Conectar a tu VPS:
   - Host: TU_IP_VPS
   - Usuario: root
   - Password: tu password SSH
3. Navegar a `/var/www/html/`
4. Subir `restaurante.zip`
5. En el VPS, descomprimir:

```bash
cd /var/www/html
unzip restaurante.zip -d restaurante
rm restaurante.zip
```

### PASO 7: Importar Base de Datos (5 min)

```bash
cd /var/www/html/restaurante
mysql -u restaurante_user -p restaurante_db < database_inventario.sql
```

### PASO 8: Configurar Apache (10 min)

```bash
nano /etc/apache2/sites-available/restaurante.conf
```

Pegar:

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

    ErrorLog ${APACHE_LOG_DIR}/restaurante_error.log
    CustomLog ${APACHE_LOG_DIR}/restaurante_access.log combined
</VirtualHost>
```

Activar:

```bash
a2dissite 000-default.conf
a2ensite restaurante.conf
systemctl restart apache2
```

### PASO 9: Configurar Permisos (5 min)

```bash
chown -R www-data:www-data /var/www/html/restaurante
chmod -R 755 /var/www/html/restaurante
chmod -R 775 /var/www/html/restaurante/backups
chmod -R 775 /var/www/html/restaurante/imagenes_platos
chmod -R 775 /var/www/html/restaurante/imagenes_qr
chmod -R 775 /var/www/html/restaurante/publicidad
```

### PASO 10: Actualizar config.php (5 min)

```bash
nano /var/www/html/restaurante/config.php
```

Asegúrate de que use las variables del `.env.cloud`:

```php
<?php
// Cargar variables de entorno
if (file_exists(__DIR__ . '/.env.cloud')) {
    $env = parse_ini_file(__DIR__ . '/.env.cloud');
    
    define('DB_HOST', $env['DB_HOST']);
    define('DB_NAME', $env['DB_NAME']);
    define('DB_USER', $env['DB_USER']);
    define('DB_PASS', $env['DB_PASS']);
}
```

### PASO 11: Probar el Sistema (10 min)

1. **Abrir navegador**: `http://TU_IP_VPS`
2. **Ir a login**: `http://TU_IP_VPS/login.php`
3. **Credenciales por defecto**:
   - Usuario: `admin`
   - Password: `admin123`

4. **⚠️ CAMBIAR PASSWORD INMEDIATAMENTE**

5. **Verificar Health Check**: `http://TU_IP_VPS/health_check.php`

### PASO 12: Configurar Backups (10 min)

```bash
nano /root/backup_restaurante.sh
```

Pegar el script de backup (ver GUIA_DESPLIEGUE_HOSTINGER.md sección 11.1)

```bash
chmod +x /root/backup_restaurante.sh
crontab -e
```

Agregar:
```
0 3 * * * /root/backup_restaurante.sh >> /var/log/backup_restaurante.log 2>&1
```

---

## ✅ VERIFICACIÓN FINAL

- [ ] El sitio carga en `http://TU_IP_VPS`
- [ ] Puedes hacer login
- [ ] Health check muestra `"database": true`
- [ ] Puedes crear un plato de prueba
- [ ] Puedes procesar un pedido de prueba
- [ ] Los backups están programados

---

## 🔒 SEGURIDAD POST-DESPLIEGUE

1. **Cambiar password de admin**
2. **Cambiar password de MySQL root**
3. **Verificar firewall**: `ufw status`
4. **Revisar logs**: `tail -f /var/log/apache2/restaurante_error.log`

---

## 🆘 PROBLEMAS COMUNES

### Error: "No se puede conectar a la base de datos"

```bash
# Verificar MySQL
systemctl status mysql

# Probar conexión
mysql -u restaurante_user -p restaurante_db
```

### Error: "500 Internal Server Error"

```bash
# Ver logs
tail -50 /var/log/apache2/restaurante_error.log

# Verificar permisos
ls -la /var/www/html/restaurante
```

### Página en blanco

```bash
# Habilitar errores temporalmente
nano /etc/php/8.1/apache2/php.ini
# Cambiar: display_errors = On

systemctl restart apache2
```

---

## 📞 SIGUIENTE PASO

Una vez que el VPS esté funcionando, el siguiente paso será:

1. **Configurar sistema de sincronización** (Fase 2)
2. **Crear instalador para restaurantes**
3. **Probar con restaurante piloto**

---

## 📝 NOTAS IMPORTANTES

- **Sin dominio**: Accederás siempre por IP
- **SSL autofirmado**: El navegador mostrará advertencia (es normal)
- **Para dominio futuro**: Configurar DNS y usar Let's Encrypt
- **Backups**: Se guardan en `/root/backups`
- **Logs**: En `/var/log/apache2/`

---

**¡Éxito en tu despliegue! 🚀**
