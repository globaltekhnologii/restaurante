#!/bin/bash
# Script de configuración automática para servidor EC2
# Ejecutar como: sudo bash aws_setup.sh

echo "🚀 Iniciando configuración del servidor AWS EC2..."

# Actualizar sistema
echo "📦 Actualizando paquetes del sistema..."
yum update -y

# Instalar Apache
echo "🌐 Instalando Apache..."
yum install -y httpd

# Instalar PHP 8.x y extensiones necesarias
echo "🐘 Instalando PHP y extensiones..."
yum install -y php php-mysqlnd php-gd php-mbstring php-xml php-json php-zip php-curl

# Instalar Composer
echo "🎼 Instalando Composer..."
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# Instalar AWS SDK para PHP (necesario para S3)
echo "☁️ Instalando AWS SDK para PHP..."
cd /var/www/html
composer require aws/aws-sdk-php

# Configurar Apache
echo "⚙️ Configurando Apache..."

# Habilitar mod_rewrite
cat > /etc/httpd/conf.d/restaurante.conf << 'EOF'
<Directory "/var/www/html">
    AllowOverride All
    Require all granted
    
    # Habilitar compresión
    <IfModule mod_deflate.c>
        AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json
    </IfModule>
    
    # Configurar caché para archivos estáticos
    <IfModule mod_expires.c>
        ExpiresActive On
        ExpiresByType image/jpg "access plus 1 year"
        ExpiresByType image/jpeg "access plus 1 year"
        ExpiresByType image/gif "access plus 1 year"
        ExpiresByType image/png "access plus 1 year"
        ExpiresByType text/css "access plus 1 month"
        ExpiresByType application/javascript "access plus 1 month"
    </IfModule>
</Directory>

# Aumentar límites de PHP
php_value upload_max_filesize 50M
php_value post_max_size 50M
php_value max_execution_time 300
php_value memory_limit 256M
EOF

# Configurar PHP
echo "🔧 Configurando PHP..."
sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 50M/' /etc/php.ini
sed -i 's/post_max_size = 8M/post_max_size = 50M/' /etc/php.ini
sed -i 's/max_execution_time = 30/max_execution_time = 300/' /etc/php.ini
sed -i 's/memory_limit = 128M/memory_limit = 256M/' /etc/php.ini
sed -i 's/;date.timezone =/date.timezone = America\/Bogota/' /etc/php.ini

# Configurar permisos
echo "🔐 Configurando permisos..."
usermod -a -G apache ec2-user
chown -R ec2-user:apache /var/www
chmod 2775 /var/www
find /var/www -type d -exec chmod 2775 {} \;
find /var/www -type f -exec chmod 0664 {} \;

# Crear directorios necesarios
echo "📁 Creando directorios..."
mkdir -p /var/www/html/backups
mkdir -p /var/www/html/imagenes_platos
mkdir -p /var/www/html/imagenes_qr
mkdir -p /var/www/html/publicidad
mkdir -p /home/ec2-user/backups

chown -R apache:apache /var/www/html/backups
chown -R apache:apache /var/www/html/imagenes_platos
chown -R apache:apache /var/www/html/imagenes_qr
chown -R apache:apache /var/www/html/publicidad

# Iniciar y habilitar Apache
echo "▶️ Iniciando Apache..."
systemctl start httpd
systemctl enable httpd

# Configurar firewall
echo "🔥 Configurando firewall..."
systemctl start firewalld
systemctl enable firewalld
firewall-cmd --permanent --add-service=http
firewall-cmd --permanent --add-service=https
firewall-cmd --reload

# Crear script de backup automático
echo "💾 Configurando backups automáticos..."
cat > /home/ec2-user/backup_db.sh << 'EOF'
#!/bin/bash
# Script de backup automático de base de datos

# Cargar variables de entorno
source /etc/environment

# Crear backup
BACKUP_FILE="/home/ec2-user/backups/db-backup-$(date +%Y%m%d-%H%M%S).sql"
mysqldump -h $DB_HOST -u $DB_USER -p$DB_PASSWORD $DB_NAME > $BACKUP_FILE

# Comprimir
gzip $BACKUP_FILE

# Subir a S3 (opcional)
if [ ! -z "$S3_BUCKET" ]; then
    aws s3 cp ${BACKUP_FILE}.gz s3://$S3_BUCKET/backups/
fi

# Eliminar backups locales antiguos (más de 7 días)
find /home/ec2-user/backups -name "db-backup-*.sql.gz" -mtime +7 -delete

echo "✅ Backup completado: ${BACKUP_FILE}.gz"
EOF

chmod +x /home/ec2-user/backup_db.sh

# Configurar cron para backups diarios a las 2 AM
(crontab -l 2>/dev/null; echo "0 2 * * * /home/ec2-user/backup_db.sh >> /var/log/backup.log 2>&1") | crontab -

# Crear página de verificación
echo "✅ Creando página de verificación..."
cat > /var/www/html/health-check.php << 'EOF'
<?php
header('Content-Type: application/json');

$status = [
    'status' => 'OK',
    'timestamp' => date('Y-m-d H:i:s'),
    'server' => $_SERVER['SERVER_SOFTWARE'],
    'php_version' => phpversion()
];

// Verificar conexión a base de datos
try {
    $db_host = getenv('DB_HOST');
    $db_user = getenv('DB_USER');
    $db_pass = getenv('DB_PASSWORD');
    $db_name = getenv('DB_NAME');
    
    if ($db_host && $db_user && $db_name) {
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        if ($conn->connect_error) {
            $status['database'] = 'ERROR: ' . $conn->connect_error;
        } else {
            $status['database'] = 'Connected';
            $conn->close();
        }
    } else {
        $status['database'] = 'Not configured';
    }
} catch (Exception $e) {
    $status['database'] = 'ERROR: ' . $e->getMessage();
}

echo json_encode($status, JSON_PRETTY_PRINT);
?>
EOF

chown apache:apache /var/www/html/health-check.php

# Mostrar información del sistema
echo ""
echo "=========================================="
echo "✅ Configuración completada exitosamente"
echo "=========================================="
echo ""
echo "📊 Información del sistema:"
echo "  - Apache: $(httpd -v | head -n1)"
echo "  - PHP: $(php -v | head -n1)"
echo "  - Composer: $(composer --version)"
echo ""
echo "🌐 Servicios:"
systemctl status httpd --no-pager | grep Active
echo ""
echo "📁 Directorios creados:"
echo "  - /var/www/html (aplicación)"
echo "  - /var/www/html/backups (backups de aplicación)"
echo "  - /home/ec2-user/backups (backups de BD)"
echo ""
echo "⚙️ Próximos pasos:"
echo "  1. Configurar variables de entorno en /etc/environment"
echo "  2. Desplegar código de la aplicación"
echo "  3. Verificar: http://[IP-SERVIDOR]/health-check.php"
echo ""
echo "=========================================="
