# 🍽️ Restaurante El Sabor - Sistema de Gestión de Menú

[![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)](https://html.spec.whatwg.org/)
[![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)](https://www.w3.org/Style/CSS/)
[![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)](https://www.javascript.com/)

Sistema completo de administración de menú para restaurante con panel de administración moderno y responsive. Desarrollado con PHP puro y MySQL.

## 📋 Tabla de Contenidos

- [Características](#-características)
- [Demo](#-demo)
- [Requisitos](#-requisitos)
- [Instalación](#-instalación)
- [Uso](#-uso)
- [Estructura del Proyecto](#-estructura-del-proyecto)
- [Tecnologías](#️-tecnologías)
- [Capturas de Pantalla](#-capturas-de-pantalla)
- [Contribuir](#-contribuir)
- [Licencia](#-licencia)
- [Contacto](#-contacto)

## ✨ Características

### 🌐 Menú Público
- ✅ Visualización de platos con imágenes
- ✅ Categorización automática (Entradas, Platos Principales, Postres, Bebidas)
- ✅ Búsqueda en tiempo real por nombre o descripción
- ✅ Filtros por categoría
- ✅ Badges especiales (Popular ⭐, Nuevo ✨, Vegano 🌱)
- ✅ Diseño responsive para móviles y tablets
- ✅ Contador de resultados en tiempo real

### 🔐 Sistema de Autenticación
- ✅ Login seguro con validación
- ✅ Control de sesiones
- ✅ Protección contra SQL injection
- ✅ Cierre de sesión con animaciones

### 👨‍💼 Panel de Administración
- ✅ Dashboard con estadísticas en tiempo real
- ✅ CRUD completo de platos (Crear, Leer, Actualizar, Eliminar)
- ✅ Gestión de usuarios y roles
- ✅ Gestión de pedidos y entregas
- ✅ Asignación de domiciliarios
- ✅ Gestión de imágenes
- ✅ Asignación de categorías
- ✅ Búsqueda y filtros avanzados
- ✅ Interfaz moderna con animaciones

### 👥 Sistema Multi-Usuario
- ✅ 4 Roles definidos: Admin, Mesero, Chef, Domiciliario
- ✅ Paneles personalizados por rol
- ✅ Mesero: Toma de pedidos, gestión de mesas
- ✅ Chef: Visualización de comandas en cocina
- ✅ Domiciliario: Gestión de entregas y rutas
- ✅ Admin: Control total del sistema

### 📦 Gestión de Pedidos
- ✅ Carrito de compras dinámico
- ✅ Toma de pedidos en mesa y domicilio
- ✅ Tracking de estados (Pendiente -> Confirmado -> Preparando -> En Camino -> Entregado)
- ✅ Timeline de tiempos de entrega


### 🎨 Diseño
- ✅ UI/UX moderno y profesional
- ✅ Gradientes y animaciones suaves
- ✅ Efectos hover interactivos
- ✅ Navegación intuitiva
- ✅ Sticky navbar
- ✅ Loading states

## 🎬 Demo

> **Nota:** Puedes agregar aquí un link a una demo en vivo o un GIF animado mostrando el sistema.

```
🌐 Demo en vivo: [Próximamente]
📹 Video demo: [Próximamente]
```

## 📋 Requisitos

Antes de instalar, asegúrate de tener:

- **PHP** 7.4 o superior
- **MySQL** 5.7 o superior
- **Servidor web** (Apache recomendado - incluido en XAMPP)
- **XAMPP** 8.0+ (recomendado) o cualquier stack LAMP/WAMP

## 🚀 Instalación

### Paso 1: Clonar el repositorio

```bash
git clone https://github.com/globaltekhnologii/restaurante.git
cd restaurante
```

### Paso 2: Configurar el servidor

1. Mueve la carpeta del proyecto a tu directorio web:
   - **XAMPP:** `C:\xampp\htdocs\restaurante`
   - **WAMP:** `C:\wamp64\www\restaurante`
   - **LAMP:** `/var/www/html/restaurante`

2. Inicia Apache y MySQL desde el panel de control de XAMPP

### Paso 3: Crear la base de datos

1. Abre **phpMyAdmin**: `http://localhost/phpmyadmin`
2. Crea una nueva base de datos llamada `menu_restaurante`
3. Importa el archivo `database.sql` (incluido en el proyecto)

O ejecuta estos comandos SQL manualmente:

```sql
-- Crear base de datos
CREATE DATABASE menu_restaurante CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE menu_restaurante;

-- Tabla de platos
CREATE TABLE platos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    imagen_ruta VARCHAR(255),
    categoria VARCHAR(50) DEFAULT 'General',
    popular TINYINT(1) DEFAULT 0,
    nuevo TINYINT(1) DEFAULT 0,
    vegano TINYINT(1) DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de usuarios
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) UNIQUE NOT NULL,
    clave VARCHAR(255) NOT NULL,
    nombre VARCHAR(100),
    email VARCHAR(100),
    rol ENUM('admin', 'mesero', 'chef', 'domiciliario') DEFAULT 'admin',
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de mesas
CREATE TABLE mesas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_mesa VARCHAR(10) UNIQUE NOT NULL,
    capacidad INT DEFAULT 4,
    estado ENUM('disponible', 'ocupada', 'reservada') DEFAULT 'disponible',
    pedido_actual INT,
    mesero_asignado INT,
    fecha_ocupacion DATETIME
);

-- Tabla de pedidos
CREATE TABLE pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_pedido VARCHAR(20) UNIQUE,
    mesa_id INT,
    usuario_id INT, -- Mesero
    domiciliario_id INT,
    nombre_cliente VARCHAR(100),
    telefono VARCHAR(20),
    direccion TEXT,
    notas TEXT,
    total DECIMAL(10,2),
    estado ENUM('pendiente', 'confirmado', 'preparando', 'en_camino', 'entregado', 'cancelado') DEFAULT 'pendiente',
    fecha_pedido DATETIME DEFAULT CURRENT_TIMESTAMP,
    hora_salida DATETIME,
    hora_entrega DATETIME
);

-- Tabla de items del pedido
CREATE TABLE pedidos_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT,
    plato_id INT,
    nombre_plato VARCHAR(100),
    precio DECIMAL(10,2),
    cantidad INT,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE
);

-- Insertar usuario administrador por defecto
INSERT INTO usuarios (usuario, clave, nombre, rol) 
VALUES ('admin', 'admin123', 'Administrador Principal', 'admin');
```

### Paso 4: Configurar la conexión (si es necesario)

Los archivos PHP ya vienen configurados con estos valores por defecto:

```php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "menu_restaurante";
```

Si tu configuración es diferente, edita estos valores en cada archivo PHP.

### Paso 5: Crear carpeta de imágenes

```bash
mkdir imagenes_platos
chmod 777 imagenes_platos  # Solo en Linux/Mac
```

En Windows, asegúrate de que la carpeta tenga permisos de escritura.

## 🎯 Uso
#### Como Usuario (Público)
1. Navega por el menú completo
2. Usa el buscador para encontrar platos específicos
3. Filtra por categorías
4. Ve información detallada de cada plato

#### Como Administrador
1. Inicia sesión con tus credenciales
2. Desde el dashboard puedes:
   - ➕ Agregar nuevos platos
   - ✏️ Editar platos existentes
   - 🗑️ Eliminar platos
   - 📊 Ver estadísticas en tiempo real
   - 🔍 Buscar y filtrar platos

## 📁 Estructura del Proyecto

```
restaurante/
│
├── 📄 index.php                      # Página principal - Menú público
├── 🔐 login.php                      # Página de inicio de sesión
├── 🚪 logout.php                     # Cerrar sesión
├── ✅ verificar_login.php            # Validación de credenciales
│
├── 👨‍💼 admin.php                       # Panel de administración principal
├── 🍽️ mesero.php                      # Panel de mesero
├── 👨‍🍳 chef.php                        # Panel de chef
├── 🏍️ domiciliario.php                # Panel de domiciliario
├── 📝 tomar_pedido_mesero.php         # Interfaz de toma de pedidos
├── 👁️ ver_pedido.php                  # Vista detallada de pedidos
├── 📦 admin_pedidos.php               # Gestión de pedidos (Admin)
├── 👥 admin_usuarios.php              # Gestión de usuarios (Admin)
│
├── ➕ insertar_plato_con_imagen.php  # Agregar nuevo plato
├── ✏️ editar_plato.php                # Editar plato existente
├── 💾 actualizar_plato.php           # Procesar actualización
├── 🗑️ borrar_plato.php                # Eliminar plato
│
├── 🎨 style.css                      # Estilos principales
├── 📸 imagenes_platos/               # Carpeta de imágenes
├── 📋 database.sql                   # Script de base de datos completo
├── 📖 README.md                      # Este archivo
└── 🚫 .gitignore                     # Archivos ignorados por Git
```

## 🛠️ Tecnologías

### Backend
- **PHP 8.0** - Lenguaje de programación del lado del servidor
- **MySQL** - Sistema de gestión de base de datos
- **MySQLi** - Extensión PHP para conectar con MySQL

### Frontend
- **HTML5** - Estructura y contenido
- **CSS3** - Estilos y animaciones
- **JavaScript (Vanilla)** - Interactividad del lado del cliente

### Características Técnicas
- ✅ Prepared Statements (prevención de SQL Injection)
- ✅ Sessions management
- ✅ File upload handling
- ✅ Responsive design
- ✅ Real-time search
- ✅ AJAX-like interactions
- ✅ Form validation

## 📸 Capturas de Pantalla

> **Nota:** Agrega aquí capturas de pantalla de:
> 1. Menú público
> 2. Login
> 3. Dashboard admin
> 4. Formulario de edición
> 5. Vista móvil

```markdown
### Menú Público
![Menu](screenshots/menu.png)

### Panel de Administración
![Admin](screenshots/admin.png)

### Formulario de Edición
![Edit](screenshots/edit.png)
```

## 🤝 Contribuir

Las contribuciones son bienvenidas. Para contribuir:

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m '✨ Add: AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

### Convención de Commits

Usa emojis para identificar el tipo de commit:

- ✨ `:sparkles:` - Nueva característica
- 🐛 `:bug:` - Corrección de bug
- 📝 `:memo:` - Documentación
- 💄 `:lipstick:` - UI/Estilos
- ♻️ `:recycle:` - Refactorización
- 🔥 `:fire:` - Eliminar código
- ✅ `:white_check_mark:` - Tests

## 🔮 Roadmap

### Versión 1.0 (Actual)
- ✅ CRUD completo de platos
- ✅ Sistema de autenticación
- ✅ Búsqueda y filtros
- ✅ Categorización

### Versión 2.0 (Completado)
- ✅ Sistema de pedidos online
- ✅ Carrito de compras
- ✅ Gestión de múltiples usuarios (Roles)
- ✅ Sistema de mesas
- ✅ Dashboard con gráficos y estadísticas
- ✅ Tracking de entregas

### Versión 3.0 (Planeado)
- ⬜ Exportar/Importar menú
- ⬜ API REST
- ⬜ Modo oscuro
- ⬜ Impresión de tickets
- ⬜ Notificaciones en tiempo real (WebSockets)

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

```
MIT License

Copyright (c) 2024 Global Tekhnologii

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction...
```

## 👤 Contacto

**Global Tekhnologii**

- 🌐 GitHub: [@globaltekhnologii](https://github.com/globaltekhnologii)
- 📧 Email: [tu-email@ejemplo.com]
- 🔗 LinkedIn: [Tu perfil]

---

## ⭐ Dale una estrella

Si este proyecto te fue útil, no olvides darle una ⭐ en GitHub!

---

<div align="center">

**Hecho con ❤️ por Global Tekhnologii**

[⬆ Volver arriba](#-restaurante-el-sabor---sistema-de-gestión-de-menú)

</div>
