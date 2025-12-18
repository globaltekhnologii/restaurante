# Guía de Despliegue en Amazon Web Services (AWS)

Esta guía detalla los pasos para desplegar tu aplicación "Restaurante" en AWS utilizando EC2, RDS, S3 y Route 53.

---

## 📋 Arquitectura AWS

- **EC2**: Servidor web (Apache + PHP)
- **RDS**: Base de datos MySQL
- **S3**: Almacenamiento de imágenes y archivos estáticos
- **Route 53**: Gestión de DNS y dominio

---

## 1. Requisitos Previos

1. Tener una cuenta de **AWS** activa
2. Tener instalado **AWS CLI** en tu computadora
   - Descarga: [https://aws.amazon.com/cli/](https://aws.amazon.com/cli/)
3. Tener un dominio (opcional, pero recomendado)
4. Tener acceso a tu repositorio de GitHub

---

## 2. Configurar Base de Datos (RDS)

### Paso 2.1: Crear Instancia RDS MySQL

1. Ve a la consola de AWS RDS: [https://console.aws.amazon.com/rds](https://console.aws.amazon.com/rds)
2. Haz clic en **"Crear base de datos"**
3. Configura:
   - **Método de creación**: Creación estándar
   - **Tipo de motor**: MySQL
   - **Versión**: MySQL 8.0.x (última estable)
   - **Plantillas**: Nivel gratuito (para pruebas) o Producción
   - **Identificador de instancia**: `restaurante-db`
   - **Nombre de usuario maestro**: `admin`
   - **Contraseña maestra**: Crea una contraseña segura y guárdala
   - **Clase de instancia**: db.t3.micro (nivel gratuito) o superior
   - **Almacenamiento**: 20 GB (SSD de uso general)
   - **Conectividad**:
     - VPC: Default VPC
     - **Acceso público**: Sí (temporalmente para configuración)
     - **Grupo de seguridad**: Crear nuevo `restaurante-db-sg`
   - **Nombre de base de datos inicial**: `menu_restaurante`

4. Haz clic en **"Crear base de datos"** (toma 5-10 minutos)

### Paso 2.2: Configurar Grupo de Seguridad

1. Ve a **EC2 > Grupos de seguridad**
2. Busca el grupo `restaurante-db-sg`
3. Edita las reglas de entrada:
   - **Tipo**: MySQL/Aurora
   - **Puerto**: 3306
   - **Origen**: Tu IP actual (para configuración inicial)
   - **Descripción**: "Acceso temporal para configuración"

### Paso 2.3: Importar Base de Datos

1. Obtén el endpoint de tu RDS:
   - En la consola RDS, selecciona tu instancia
   - Copia el **Endpoint** (ej: `restaurante-db.xxxxx.us-east-1.rds.amazonaws.com`)

2. Desde tu PC local, exporta tu base de datos:
   ```bash
   # Opción 1: Usar phpMyAdmin
   # Ve a http://localhost/phpmyadmin
   # Selecciona menu_restaurante > Exportar > SQL > Continuar
   # Guarda como backup_aws.sql
   
   # Opción 2: Usar mysqldump
   mysqldump -u root menu_restaurante > backup_aws.sql
   ```

3. Importa a RDS:
   ```bash
   mysql -h restaurante-db.xxxxx.us-east-1.rds.amazonaws.com -u admin -p menu_restaurante < backup_aws.sql
   ```

---

## 3. Configurar Almacenamiento (S3)

### Paso 3.1: Crear Bucket S3

1. Ve a S3: [https://console.aws.amazon.com/s3](https://console.aws.amazon.com/s3)
2. Haz clic en **"Crear bucket"**
3. Configura:
   - **Nombre del bucket**: `restaurante-assets-[tu-nombre-unico]`
   - **Región**: us-east-1 (o la misma que RDS)
   - **Bloquear todo el acceso público**: Desactivar
   - **Reconocimiento**: Marcar la casilla de advertencia
4. Crear bucket

### Paso 3.2: Configurar Política del Bucket

1. Selecciona tu bucket > Pestaña **"Permisos"**
2. En **"Política del bucket"**, pega:

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Sid": "PublicReadGetObject",
            "Effect": "Allow",
            "Principal": "*",
            "Action": "s3:GetObject",
            "Resource": "arn:aws:s3:::restaurante-assets-[tu-nombre-unico]/*"
        }
    ]
}
```

3. Reemplaza `[tu-nombre-unico]` con el nombre real de tu bucket

### Paso 3.3: Habilitar CORS

En **"Configuración de CORS"**, pega:

```json
[
    {
        "AllowedHeaders": ["*"],
        "AllowedMethods": ["GET", "PUT", "POST", "DELETE"],
        "AllowedOrigins": ["*"],
        "ExposeHeaders": []
    }
]
```

---

## 4. Configurar Servidor Web (EC2)

### Paso 4.1: Crear Instancia EC2

1. Ve a EC2: [https://console.aws.amazon.com/ec2](https://console.aws.amazon.com/ec2)
2. Haz clic en **"Lanzar instancia"**
3. Configura:
   - **Nombre**: `Restaurante-Web-Server`
   - **AMI**: Amazon Linux 2023 AMI
   - **Tipo de instancia**: t2.micro (nivel gratuito) o t3.small
   - **Par de claves**: Crear nuevo par de claves
     - Nombre: `restaurante-key`
     - Tipo: RSA
     - Formato: .pem
     - **¡Descarga y guarda el archivo .pem!**
   - **Configuración de red**:
     - VPC: Default
     - **Asignar IP pública automáticamente**: Habilitar
     - **Grupo de seguridad**: Crear nuevo `restaurante-web-sg`
       - Permitir SSH (22) desde tu IP
       - Permitir HTTP (80) desde cualquier lugar
       - Permitir HTTPS (443) desde cualquier lugar
   - **Almacenamiento**: 20 GB gp3

4. Haz clic en **"Lanzar instancia"**

### Paso 4.2: Conectar a EC2 y Configurar Servidor

1. Obtén la IP pública de tu instancia EC2

2. Conecta vía SSH:
   ```bash
   # En Windows (PowerShell)
   ssh -i "restaurante-key.pem" ec2-user@[IP-PUBLICA-EC2]
   
   # Si da error de permisos en Windows:
   icacls "restaurante-key.pem" /inheritance:r
   icacls "restaurante-key.pem" /grant:r "%username%:R"
   ```

3. Una vez conectado, ejecuta el script de instalación:
   ```bash
   # Actualizar sistema
   sudo yum update -y
   
   # Instalar Apache
   sudo yum install -y httpd
   
   # Instalar PHP 8.x y extensiones
   sudo yum install -y php php-mysqlnd php-gd php-mbstring php-xml php-json php-zip
   
   # Instalar AWS SDK para PHP (para S3)
   sudo yum install -y php-pear
   curl -sS https://getcomposer.org/installer | php
   sudo mv composer.phar /usr/local/bin/composer
   
   # Iniciar Apache
   sudo systemctl start httpd
   sudo systemctl enable httpd
   
   # Configurar permisos
   sudo usermod -a -G apache ec2-user
   sudo chown -R ec2-user:apache /var/www
   sudo chmod 2775 /var/www
   find /var/www -type d -exec sudo chmod 2775 {} \;
   find /var/www -type f -exec sudo chmod 0664 {} \;
   
   # Crear directorio para backups
   mkdir -p /home/ec2-user/backups
   
   echo "✅ Servidor configurado correctamente"
   ```

### Paso 4.3: Configurar Variables de Entorno

1. Crea el archivo de variables de entorno:
   ```bash
   sudo nano /etc/environment
   ```

2. Agrega las siguientes variables (reemplaza con tus valores):
   ```bash
   # Base de datos RDS
   DB_HOST="restaurante-db.xxxxx.us-east-1.rds.amazonaws.com"
   DB_USER="admin"
   DB_PASSWORD="tu-contraseña-rds"
   DB_NAME="menu_restaurante"
   
   # S3
   S3_BUCKET="restaurante-assets-[tu-nombre-unico]"
   S3_REGION="us-east-1"
   AWS_USE_S3="true"
   ```

3. Guarda (Ctrl+O, Enter, Ctrl+X)

4. Reinicia Apache:
   ```bash
   sudo systemctl restart httpd
   ```

---

## 5. Configurar GitHub Actions (Despliegue Automático)

### Paso 5.1: Configurar Secrets en GitHub

1. Ve a tu repositorio en GitHub
2. Ve a **Settings > Secrets and variables > Actions**
3. Haz clic en **"New repository secret"** y agrega:

| Nombre | Valor | Descripción |
|--------|-------|-------------|
| `AWS_ACCESS_KEY_ID` | Tu Access Key ID | Credenciales IAM de AWS |
| `AWS_SECRET_ACCESS_KEY` | Tu Secret Access Key | Credenciales IAM de AWS |
| `EC2_INSTANCE_ID` | i-xxxxxxxxx | ID de tu instancia EC2 |
| `EC2_HOST` | IP pública de EC2 | Dirección IP pública |
| `EC2_SSH_PRIVATE_KEY` | Contenido de .pem | Clave privada SSH completa |
| `S3_BUCKET_NAME` | restaurante-assets-xxx | Nombre de tu bucket S3 |

### Paso 5.2: Crear Usuario IAM para GitHub Actions

1. Ve a IAM: [https://console.aws.amazon.com/iam](https://console.aws.amazon.com/iam)
2. **Usuarios > Crear usuario**
3. Nombre: `github-actions-deploy`
4. **Tipo de acceso**: Clave de acceso programático
5. **Permisos**: Adjuntar políticas existentes:
   - `AmazonS3FullAccess`
   - `AmazonEC2ReadOnlyAccess`
6. Crear usuario y **guardar las credenciales**

### Paso 5.3: Probar Despliegue

1. Haz un commit y push a la rama `main`:
   ```bash
   git add .
   git commit -m "Configurar despliegue AWS"
   git push origin main
   ```

2. Ve a **Actions** en GitHub para ver el progreso del despliegue

---

## 6. Configurar Dominio (Route 53) - Opcional

### Paso 6.1: Registrar o Transferir Dominio

1. Ve a Route 53: [https://console.aws.amazon.com/route53](https://console.aws.amazon.com/route53)
2. **Dominios registrados > Registrar dominio** (o transferir si ya tienes uno)

### Paso 6.2: Crear Zona Hospedada

1. **Zonas hospedadas > Crear zona hospedada**
2. Nombre de dominio: `turestaurante.com`
3. Tipo: Zona hospedada pública

### Paso 6.3: Crear Registros DNS

1. Dentro de tu zona hospedada, crea un registro:
   - **Tipo**: A
   - **Nombre**: (vacío para dominio raíz) o `www`
   - **Valor**: IP pública de tu EC2
   - **TTL**: 300

2. Crea otro registro para www:
   - **Tipo**: CNAME
   - **Nombre**: `www`
   - **Valor**: `turestaurante.com`

### Paso 6.4: Configurar SSL (HTTPS) - Opcional

```bash
# Conecta a EC2
ssh -i "restaurante-key.pem" ec2-user@[IP-EC2]

# Instalar Certbot
sudo yum install -y certbot python3-certbot-apache

# Obtener certificado SSL
sudo certbot --apache -d turestaurante.com -d www.turestaurante.com

# Renovación automática
sudo systemctl enable certbot-renew.timer
```

---

## 7. Actualizar Código para AWS

### Paso 7.1: Actualizar config.php

El archivo `config.php` ya está preparado para detectar variables de entorno. Asegúrate de que esté actualizado.

### Paso 7.2: Actualizar file_upload_helper.php para S3

Este archivo se actualizará para subir imágenes a S3 cuando esté en AWS.

---

## 8. Troubleshooting

### Error de conexión a RDS

- Verifica que el grupo de seguridad de RDS permita conexiones desde el grupo de seguridad de EC2
- Verifica el endpoint y credenciales en `/etc/environment`

### Imágenes no cargan desde S3

- Verifica la política del bucket (debe ser pública)
- Verifica que CORS esté configurado
- Verifica las credenciales IAM

### Apache no inicia

```bash
# Ver logs de error
sudo tail -f /var/log/httpd/error_log

# Verificar configuración
sudo apachectl configtest

# Reiniciar servicio
sudo systemctl restart httpd
```

### GitHub Actions falla

- Verifica que todos los secrets estén configurados correctamente
- Verifica que la clave SSH no tenga espacios extra al copiarla
- Revisa los logs en la pestaña Actions de GitHub

### Permisos de archivos

```bash
# Restablecer permisos correctos
sudo chown -R apache:apache /var/www/html
sudo chmod -R 755 /var/www/html
sudo chmod -R 775 /var/www/html/backups
```

---

## 9. Comandos Útiles

### Conectar a EC2
```bash
ssh -i "restaurante-key.pem" ec2-user@[IP-EC2]
```

### Ver logs de Apache
```bash
sudo tail -f /var/log/httpd/error_log
sudo tail -f /var/log/httpd/access_log
```

### Reiniciar servicios
```bash
sudo systemctl restart httpd
```

### Backup manual de base de datos
```bash
mysqldump -h [RDS-ENDPOINT] -u admin -p menu_restaurante > backup-$(date +%Y%m%d).sql
```

### Sincronizar archivos locales a S3
```bash
aws s3 sync ./imagenes_platos s3://restaurante-assets-xxx/imagenes_platos
```

---

## 10. Costos Estimados (USD/mes)

- **EC2 t2.micro**: $0 (nivel gratuito) o ~$8.50/mes
- **RDS db.t3.micro**: $0 (nivel gratuito) o ~$15/mes
- **S3**: ~$0.50/mes (primeros 5GB gratis)
- **Route 53**: $0.50/mes por zona hospedada + $12/año por dominio
- **Transferencia de datos**: Variable según tráfico

**Total estimado**: $0-5/mes (nivel gratuito) o $25-35/mes (producción pequeña)

---

## 11. Próximos Pasos

1. ✅ Configurar monitoreo con CloudWatch
2. ✅ Configurar backups automáticos de RDS
3. ✅ Implementar CDN con CloudFront
4. ✅ Configurar Auto Scaling para alta demanda
5. ✅ Implementar balanceador de carga (ELB)

---

**¿Dudas?** Revisa la documentación oficial de AWS o los logs del servidor.
