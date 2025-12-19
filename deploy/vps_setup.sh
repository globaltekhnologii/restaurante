#!/bin/bash

# =====================================================
# SCRIPT DE CONFIGURACIÓN INICIAL PARA VPS HOSTINGER
# =====================================================
# Este script automatiza la instalación del stack LAMP
# y la configuración de seguridad básica
#
# Uso: bash vps_setup.sh
# =====================================================

set -e  # Salir si hay errores

echo "======================================================"
echo "  Configuración Inicial VPS - Sistema de Restaurante"
echo "======================================================"
echo ""

# Colores para output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# =====================================================
# 1. ACTUALIZAR SISTEMA
# =====================================================
echo -e "${YELLOW}[1/8] Actualizando sistema operativo...${NC}"
apt update
apt upgrade -y
echo -e "${GREEN}✓ Sistema actualizado${NC}"
echo ""

# =====================================================
# 2. INSTALAR APACHE
# =====================================================
echo -e "${YELLOW}[2/8] Instalando Apache Web Server...${NC}"
apt install apache2 -y
systemctl enable apache2
systemctl start apache2
echo -e "${GREEN}✓ Apache instalado y activo${NC}"
echo ""

# =====================================================
# 3. INSTALAR MYSQL
# =====================================================
echo -e "${YELLOW}[3/8] Instalando MySQL Server...${NC}"
apt install mysql-server -y
systemctl enable mysql
systemctl start mysql
echo -e "${GREEN}✓ MySQL instalado y activo${NC}"
echo ""

# =====================================================
# 4. INSTALAR PHP 8.1 Y EXTENSIONES
# =====================================================
echo -e "${YELLOW}[4/8] Instalando PHP 8.1 y extensiones...${NC}"
apt install software-properties-common -y
add-apt-repository ppa:ondrej/php -y
apt update

apt install php8.1 php8.1-cli php8.1-fpm php8.1-mysql php8.1-mbstring \
    php8.1-xml php8.1-curl php8.1-zip php8.1-gd php8.1-intl \
    php8.1-bcmath php8.1-soap -y

echo -e "${GREEN}✓ PHP 8.1 instalado${NC}"
php -v
echo ""

# =====================================================
# 5. CONFIGURAR MÓDULOS DE APACHE
# =====================================================
echo -e "${YELLOW}[5/8] Configurando módulos de Apache...${NC}"
a2enmod rewrite
a2enmod ssl
a2enmod headers
a2enmod proxy_fcgi setenvif
a2enconf php8.1-fpm
systemctl restart apache2
echo -e "${GREEN}✓ Módulos configurados${NC}"
echo ""

# =====================================================
# 6. CONFIGURAR FIREWALL UFW
# =====================================================
echo -e "${YELLOW}[6/8] Configurando firewall...${NC}"
apt install ufw -y

# Permitir SSH (IMPORTANTE: no bloquear SSH)
ufw allow 22/tcp

# Permitir HTTP y HTTPS
ufw allow 80/tcp
ufw allow 443/tcp

# Habilitar firewall
echo "y" | ufw enable

ufw status
echo -e "${GREEN}✓ Firewall configurado${NC}"
echo ""

# =====================================================
# 7. INSTALAR UTILIDADES
# =====================================================
echo -e "${YELLOW}[7/8] Instalando utilidades...${NC}"
apt install zip unzip curl wget git htop nano -y
echo -e "${GREEN}✓ Utilidades instaladas${NC}"
echo ""

# =====================================================
# 8. CONFIGURAR PHP
# =====================================================
echo -e "${YELLOW}[8/8] Configurando PHP...${NC}"

# Backup de php.ini
cp /etc/php/8.1/apache2/php.ini /etc/php/8.1/apache2/php.ini.backup

# Configuraciones recomendadas
sed -i 's/upload_max_filesize = .*/upload_max_filesize = 20M/' /etc/php/8.1/apache2/php.ini
sed -i 's/post_max_size = .*/post_max_size = 25M/' /etc/php/8.1/apache2/php.ini
sed -i 's/memory_limit = .*/memory_limit = 256M/' /etc/php/8.1/apache2/php.ini
sed -i 's/max_execution_time = .*/max_execution_time = 300/' /etc/php/8.1/apache2/php.ini
sed -i 's/;date.timezone =.*/date.timezone = America\/Bogota/' /etc/php/8.1/apache2/php.ini

# Reiniciar Apache
systemctl restart apache2
echo -e "${GREEN}✓ PHP configurado${NC}"
echo ""

# =====================================================
# RESUMEN
# =====================================================
echo ""
echo "======================================================"
echo -e "${GREEN}  ✓ INSTALACIÓN COMPLETADA EXITOSAMENTE${NC}"
echo "======================================================"
echo ""
echo "Stack instalado:"
echo "  - Apache: $(apache2 -v | head -n 1)"
echo "  - MySQL:  $(mysql --version)"
echo "  - PHP:    $(php -v | head -n 1)"
echo ""
echo "Próximos pasos:"
echo "  1. Ejecutar: mysql_secure_installation"
echo "  2. Crear base de datos y usuario"
echo "  3. Subir archivos del proyecto"
echo "  4. Configurar Virtual Host"
echo ""
echo "======================================================"
