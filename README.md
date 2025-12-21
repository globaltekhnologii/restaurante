# 🍽️ Sistema de Gestión de Restaurantes Multi-Tenant

Sistema completo de gestión para restaurantes con soporte multi-tenencia, pedidos en línea, tracking GPS en tiempo real, y paneles especializados para diferentes roles.

[![Estado](https://img.shields.io/badge/Estado-Producción-success)](https://github.com)
[![Versión](https://img.shields.io/badge/Versión-2.0-blue)](https://github.com)
[![Multi-Tenencia](https://img.shields.io/badge/Multi--Tenencia-100%25-green)](https://github.com)
[![GPS](https://img.shields.io/badge/GPS-Tiempo%20Real-orange)](https://github.com)

---

## 🎯 Características Principales

### ✅ Multi-Tenencia Completa
- **Aislamiento 100%** de datos entre restaurantes
- Cada tenant tiene su propia configuración, menú, clientes y pedidos
- Soporte para múltiples restaurantes en una sola instalación
- Sistema de respaldos independiente por tenant

### 📍 GPS en Tiempo Real
- **Tracking continuo** de domiciliarios durante entregas
- Visualización en mapa para clientes
- Actualización automática cada 10-30 segundos
- Soporte HTTPS para funcionamiento en dispositivos móviles
- Precisión en metros mostrada en tiempo real

### 👥 Paneles Especializados por Rol
- **Admin:** Gestión completa del restaurante
- **Mesero:** Toma de pedidos en mesa
- **Chef/Cocina:** Visualización de pedidos en preparación
- **Domiciliario:** Gestión de entregas con GPS
- **Cajero:** Procesamiento de pagos

### 🛒 Sistema de Pedidos
- Menú público con carrito de compras
- Pedidos para mesa y domicilio
- Cálculo automático de tarifas de entrega
- Integración con pasarelas de pago (Bold, Mercado Pago)
- Notificaciones en tiempo real

### 📊 Gestión Completa
- Inventario de platos con imágenes
- Gestión de clientes
- Configuración de métodos de pago
- Sistema de publicidad por tenant
- Respaldos automáticos en JSON
- Geocodificación de direcciones

---

## 🚀 Instalación

### Requisitos Previos
- PHP 7.4 o superior
- MySQL 5.7 o superior
- Apache con mod_rewrite
- Certificado SSL (para GPS en móviles)

### Instalación Local (XAMPP)

```bash
# 1. Clonar repositorio
git clone https://github.com/tu-usuario/restaurante.git
cd restaurante

# 2. Importar base de datos
mysql -u root -p < database/restaurante.sql

# 3. Configurar config.php
cp config.example.php config.php
# Editar config.php con tus credenciales de BD

# 4. Configurar permisos
chmod -R 755 imagenes_platos/
chmod -R 755 respaldos/
chmod -R 755 uploads/

# 5. Acceder
http://localhost/restaurante/
```

### Instalación en VPS (Producción)

```bash
# 1. Actualizar sistema
sudo apt update && sudo apt upgrade -y

# 2. Instalar dependencias
sudo apt install apache2 mysql-server php libapache2-mod-php \
  php-mysql php-curl php-json php-mbstring certbot python3-certbot-apache

# 3. Clonar proyecto
cd /var/www/html
sudo git clone https://github.com/tu-usuario/restaurante.git

# 4. Configurar base de datos
sudo mysql -u root -p
CREATE DATABASE restaurante_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'restaurante_user'@'localhost' IDENTIFIED BY 'password_seguro';
GRANT ALL PRIVILEGES ON restaurante_db.* TO 'restaurante_user'@'localhost';
FLUSH PRIVILEGES;
exit;

# 5. Importar estructura
sudo mysql -u restaurante_user -p restaurante_db < database/restaurante.sql

# 6. Ejecutar scripts de migración (en orden)
# Acceder vía navegador:
https://tudominio.com/restaurante/agregar_tenant_metodos_pago.php
https://tudominio.com/restaurante/eliminar_indice_metodo.php
https://tudominio.com/restaurante/agregar_tenant_publicidad.php
https://tudominio.com/restaurante/crear_tabla_respaldos.php
https://tudominio.com/restaurante/corregir_indice_telefono.php
https://tudominio.com/restaurante/agregar_tenant_ubicaciones.php
https://tudominio.com/restaurante/verificar_config_domicilios.php

# 7. Configurar SSL
sudo certbot --apache -d tudominio.com

# 8. Configurar permisos
sudo chown -R www-data:www-data /var/www/html/restaurante
sudo chmod -R 755 /var/www/html/restaurante
sudo chmod -R 775 /var/www/html/restaurante/imagenes_platos
sudo chmod -R 775 /var/www/html/restaurante/respaldos
```

---

## 📖 Documentación

### Documentos Principales
- **[SESION_FINAL_MULTITENENCIA_GPS.md](docs/SESION_FINAL_MULTITENENCIA_GPS.md)** - Guía completa de implementación
- **[SOLUCIONES_COMPLETAS_ERRORES.md](docs/SOLUCIONES_COMPLETAS_ERRORES.md)** - Catálogo de errores resueltos
- **[GUIA_CREAR_NUEVO_RESTAURANTE.md](docs/GUIA_CREAR_NUEVO_RESTAURANTE.md)** - Crear nuevos tenants

### Estructura del Proyecto

```
restaurante/
├── api/                          # APIs REST
│   ├── actualizar_ubicacion.php  # GPS tracking
│   ├── obtener_ubicacion_pedido.php
│   ├── gestionar_publicidad.php
│   └── ...
├── includes/                     # Funciones compartidas
│   ├── tenant_context.php        # Multi-tenencia
│   ├── clientes_helper.php
│   ├── geocoding_service.php
│   └── ...
├── admin.php                     # Panel administrador
├── mesero.php                    # Panel mesero
├── cocina.php                    # Panel cocina
├── domiciliario.php              # Panel domiciliario
├── index.php                     # Menú público
├── config.php                    # Configuración BD
└── docs/                         # Documentación
```

---

## 🔧 Configuración

### config.php
```php
<?php
// Configuración de Base de Datos
define('DB_HOST', 'localhost');
define('DB_USER', 'restaurante_user');
define('DB_PASS', 'tu_password');
define('DB_NAME', 'restaurante_db');

// URL Base
define('BASE_URL', 'https://tudominio.com/restaurante/');

// Modo Debug (desactivar en producción)
define('DEBUG_MODE', false);
?>
```

### HTTPS para GPS (Requerido)

El GPS en tiempo real requiere HTTPS. Configuración en XAMPP local:

```powershell
# PowerShell como Administrador
cd C:\xampp\apache
$env:OPENSSL_CONF="C:\xampp\apache\conf\openssl.cnf"

.\bin\openssl req -x509 -nodes -days 365 -newkey rsa:2048 `
  -keyout conf\ssl.key\server.key `
  -out conf\ssl.crt\server.crt

# Common Name: TU_IP_LOCAL (ej: 192.168.1.9)
```

Acceso desde celular: `https://192.168.1.9/restaurante/domiciliario.php`

---

## 🎨 Características Técnicas

### Multi-Tenencia
- **Filtrado automático** por `tenant_id` en todas las consultas
- **Índices únicos compuestos** para evitar conflictos entre tenants
- **Aislamiento de datos** en 24 archivos PHP
- **7 tablas** con soporte multi-tenant

### GPS en Tiempo Real
- **API de Geolocalización** del navegador
- **watchPosition** para tracking continuo
- **Actualización automática** cada 10-30 segundos
- **Precisión en metros** mostrada en tiempo real
- **Marcador animado** en mapa Leaflet

### Seguridad
- **Prepared Statements** en todas las consultas SQL
- **CSRF Protection** en formularios
- **Sanitización** de inputs
- **Validación** de sesiones y roles
- **HTTPS** requerido para GPS

### Performance
- **Caché de sesión** para datos frecuentes
- **Índices optimizados** en tablas
- **Consultas eficientes** con JOINs
- **Auto-refresh** configurable

---

## 📊 Tablas de Base de Datos

### Principales
- `saas_tenants` - Restaurantes (tenants)
- `usuarios` - Usuarios del sistema
- `platos` - Menú de platos
- `pedidos` - Pedidos realizados
- `clientes` - Base de clientes
- `ubicacion_domiciliarios` - GPS tracking
- `configuracion_sistema` - Config por tenant
- `configuracion_domicilios` - Tarifas de entrega

### Índices Únicos Compuestos
```sql
-- Permite mismo valor en diferentes tenants
UNIQUE KEY (tenant_id, columna)

Ejemplos:
- clientes: (tenant_id, telefono)
- metodos_pago_config: (tenant_id, metodo)
- config_pagos: (tenant_id, pasarela)
```

---

## 🐛 Solución de Problemas

### GPS no funciona en celular
**Error:** "only secure origins are allowed"  
**Solución:** Configurar HTTPS (ver sección HTTPS)

### Datos de otro tenant visibles
**Causa:** Falta filtro por `tenant_id`  
**Solución:** Ejecutar scripts de migración

### Error bind_param
**Error:** "number of elements must match"  
**Solución:** Verificar tipos coincidan con variables

### Session start duplicado
**Error:** "session already active"  
**Solución:** Usar `session_status()` antes de `session_start()`

Ver documentación completa en [SOLUCIONES_COMPLETAS_ERRORES.md](docs/SOLUCIONES_COMPLETAS_ERRORES.md)

---

## 🔄 Actualización desde Versión Anterior

Si tienes una instalación previa sin multi-tenencia:

```bash
# 1. Hacer backup completo
mysqldump -u root -p restaurante_db > backup_$(date +%Y%m%d).sql

# 2. Ejecutar scripts de migración en orden
# (Ver lista en sección Instalación en VPS)

# 3. Verificar aislamiento
# Crear usuarios de prueba en diferentes tenants
# Verificar que solo ven sus datos
```

---

## 📱 Uso del Sistema

### Crear Nuevo Restaurante (Tenant)
1. Acceder como super admin
2. Ir a gestión de tenants
3. Crear nuevo tenant con datos del restaurante
4. Asignar usuario administrador
5. Configurar menú, tarifas y métodos de pago

### Activar GPS para Entregas
1. Domiciliario accede desde celular con HTTPS
2. Hacer clic en "📍 Activar GPS"
3. Dar permisos de ubicación al navegador
4. Tomar pedido "en_camino"
5. GPS se activa automáticamente
6. Cliente ve ubicación en tiempo real

### Gestionar Pedidos
1. Cliente hace pedido desde menú público
2. Pedido aparece en panel de admin/cocina
3. Chef marca como "listo"
4. Domiciliario toma entrega
5. Marca "en_camino" (activa GPS)
6. Marca "entregado" al finalizar

---

## 🤝 Contribuir

Las contribuciones son bienvenidas. Por favor:

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

---

## 📝 Changelog

### Versión 2.0 (20 Diciembre 2025)
- ✅ **Multi-tenencia 100% implementada**
- ✅ **GPS en tiempo real funcionando continuamente**
- ✅ **24 archivos corregidos** para aislamiento de datos
- ✅ **7 tablas actualizadas** con tenant_id
- ✅ **HTTPS configurado** para GPS en móviles
- ✅ **15+ bugs críticos resueltos**
- ✅ **Sistema listo para producción en VPS**

### Versión 1.0
- Sistema básico de gestión de restaurante
- Pedidos en línea
- Paneles por rol

---

## 📄 Licencia

Este proyecto es privado y propietario.

---

## 👨‍💻 Autor

**Sistema de Gestión de Restaurantes**  
Desarrollado con ❤️ para la industria de alimentos

---

## 📞 Soporte

Para soporte técnico, consultar:
- **Documentación:** [docs/](docs/)
- **Errores comunes:** [SOLUCIONES_COMPLETAS_ERRORES.md](docs/SOLUCIONES_COMPLETAS_ERRORES.md)
- **Guía de implementación:** [SESION_FINAL_MULTITENENCIA_GPS.md](docs/SESION_FINAL_MULTITENENCIA_GPS.md)

---

## ⚡ Quick Start

```bash
# Clonar
git clone https://github.com/tu-usuario/restaurante.git

# Configurar
cp config.example.php config.php
# Editar config.php

# Importar BD
mysql -u root -p < database/restaurante.sql

# Acceder
http://localhost/restaurante/

# Usuario por defecto
Usuario: admin
Password: admin123
```

---

**✅ Sistema 100% Funcional | 🚀 Listo para Producción | 📍 GPS en Tiempo Real**
