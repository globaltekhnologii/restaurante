# 🍽️ Restaurante El Sabor - Sistema de Gestión de Menú

Sistema completo de administración de menú para restaurante con panel de administración.

## 🚀 Características

- ✅ Menú público con categorías y filtros
- ✅ Sistema de búsqueda en tiempo real
- ✅ Panel de administración completo
- ✅ CRUD de platos (Crear, Leer, Actualizar, Eliminar)
- ✅ Sistema de autenticación
- ✅ Categorización de platos
- ✅ Badges especiales (Popular, Nuevo, Vegano)
- ✅ Diseño responsive y moderno

## 📋 Requisitos

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web (Apache/XAMPP)

## 🔧 Instalación

1. Clona el repositorio:
```bash
git clone https://github.com/tu-usuario/restaurante-el-sabor.git
```

2. Importa la base de datos:
   - Abre phpMyAdmin
   - Crea una base de datos llamada `menu_restaurante`
   - Importa el archivo `database.sql`

3. Configura la conexión a la base de datos en cada archivo PHP:
```php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "menu_restaurante";
```

4. Accede al sistema:
   - Menú público: `http://localhost/Restaurante/`
   - Panel admin: `http://localhost/Restaurante/login.php`

## 🔐 Credenciales por Defecto
```
Usuario: admin
Contraseña: admin123
```

**⚠️ IMPORTANTE:** Cambia estas credenciales en producción.

## 📁 Estructura del Proyecto
```
Restaurante/
├── index.php              # Menú público
├── login.php              # Página de inicio de sesión
├── logout.php             # Cerrar sesión
├── verificar_login.php    # Validación de login
├── admin.php              # Panel de administración
├── editar_plato.php       # Editar platos
├── actualizar_plato.php   # Procesar edición
├── borrar_plato.php       # Eliminar platos
├── insertar_plato_con_imagen.php  # Añadir platos
├── style.css              # Estilos
├── imagenes_platos/       # Imágenes de platos
└── README.md
```

## 🛠️ Tecnologías

- PHP
- MySQL
- HTML5
- CSS3
- JavaScript

## 📸 Screenshots

(Agrega capturas de pantalla de tu sistema)

## 👤 Autor

Tu Nombre

## 📄 Licencia

MIT License
