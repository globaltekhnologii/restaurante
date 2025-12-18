
#### [NEW] [includes/csrf_helper.php](file:///c:/xampp/htdocs/Restaurante/includes/csrf_helper.php)
- Función `generarCsrfToken()`: Genera y almacena un token seguro en la sesión.
- Función `validarCsrfToken()`: Verifica el token recibido contra el de la sesión.
- Función `csrf_field()`: Helper HTML para insertar el campo input oculto.

### 2. Configuración Segura de Errores

Modificación de la configuración global para manejar errores de forma segura según el entorno.

#### [MODIFY] [config.php](file:///c:/xampp/htdocs/Restaurante/config.php)
- Implementar lógica para detectar entorno (LOCAL vs PROD).
- Configurar `ini_set('display_errors', 0)` por defecto.
- Configurar ruta de `error_log` personalizada.

### 3. Integración de CSRF en Formularios Clave

Se actualizarán los formularios críticos para incluir el token CSRF.

**Archivos a Modificar (Frontend - Formularios):**
- `login.php`: Formulario de inicio de sesión.
- `checkout.php`: Formulario de finalización de compra.
- `admin_usuarios.php`: Formularios de gestión de usuarios.
- `admin_platos.php`: (y similares) para gestión de inventario/menú.
- `cajero.php`: Procesamiento de pagos.
- `domiciliario.php`: Acciones de entrega.

### 4. Validación de CSRF en Procesamiento

Se añadirá la validación del token al inicio de los scripts que procesan datos.

**Archivos a Modificar (Backend - Procesamiento):**
- `verificar_login.php`
- `procesar_pedido.php`
- `procesar_pago.php`
- `actualizar_usuario.php`, `insertar_usuario.php`, `toggle_usuario.php`
- `actualizar_plato.php`, `insertar_plato.php`

## 🧪 Plan de Verificación

### Pruebas Manuales
- [x] Ejecutar script de prueba `test_csrf_check.php` para validar rechazo sin token.
- [x] Verificar que el login y checkout funcionen correctamente con el token integrado.
- [x] Provocar un error intencional (ej. error de sintaxis en un archivo de prueba) y verificar que NO se muestre en pantalla, pero SÍ se registre en el log.

### Pruebas Automatizadas
- Crear script `test_csrf_security.php` que intente realizar acciones protegidas sin token.

## 🗑️ Limpieza de Código y Archivos Legacy

### Objetivo
Eliminar deuda técnica, reducir la superficie de ataque borrando archivos de debug/test expuestos, y unificar la lógica de conexión a la base de datos.

### Acciones
1.  **Unificar Conexión de BD**:
    - Reemplazar todas las dependencias de `conexion.php` por `config.php`.
    - Eliminar `conexion.php`.

2.  **Eliminar Archivos de Debug/Test**:
    - Se eliminarán scripts `debug_*.php`, `test_*.php`, y otros archivos temporales detectados que no son parte del núcleo de la aplicación.
    - *Nota:* Archivos como `verificar_login.php` **NO** se tocarán.

3.  **Archivos a Eliminar (Lista Preliminar)**:
    - `conexion.php`
    - `debug_*.php`
    - `test_*.php`
    - `check_*.php`
    - `api_prueba.php`, `crear_pedido_prueba.php`
    - `verificar_red.bat` (si no es necesario)

### Beneficios
- código más limpio y mantenible.
- Menor riesgo de seguridad (menos archivos expuestos).
- Configuración centralizada.

## 🚀 Optimización de Rendimiento

### Estrategia
1.  **Cache de Servidor (Session)**:
    -   Modificar `includes/info_negocio.php` para guardar los datos en `$_SESSION['info_negocio']`.
    -   Modificar `guardar_configuracion.php` para limpiar esa variable de sesión al guardar cambios.
    -   *Impacto*: Reduce 1 consulta SQL por vista en TODO el sitio.

2.  **Cache de Navegador (.htaccess)**:
    -   Crear archivo `.htaccess` optimizado.
    -   Configurar expiración larga para imágenes (jpg, png), CSS y JS.
    -   *Impacto*: Carga instantánea para usuarios recurrentes.

3.  **Indices SQL**:
    -   Ejecutar `ADD INDEX idx_platos_cat (categoria, nombre)` en tabla `platos`.
    -   Ejecutar `ADD INDEX idx_pedidos_estado (estado, fecha_pedido)` en tabla `pedidos`.
    -   *Impacto*: Ordenamiento y filtrado más rápido.

## 🧪 Testing Automatizado

### Enfoque "Lightweight"
Para evitar instalar dependencias complejas en un entorno XAMPP existente sin Composer, crearemos un **Micro-Framework de Testing** en la carpeta `tests/`.

### Estructura
```
tests/
  ├── TestRunner.php      # Clase base para aserciones (assertEquals, assertTrue)
  ├── run_tests.php       # Script ejecutable que corre todos los tests
  ├── Unit/
  │   ├── TestSanitize.php
  │   └── TestCsrf.php
  └── Integration/
      └── TestDb.php
```

### Ventajas
- **Portabilidad**: Funciona en cualquier servidor PHP sin instalación.
- **Velocidad**: Ejecución inmediata.
- **Simplicidad**: Código fácil de entender y mantener para el usuario.

## 📦 Preparación para Producción

### 1. Monitoreo y Logs
- **Endpoint de Salud (`health_check.php`)**: Retorna JSON `{status: "ok", db: true}`. Usado para servicios como UptimeRobot.
- **Visor de Logs (`admin_logs.php`)**: Interfaz simple protegida para leer las últimas líneas de `error_log` sin entrar al servidor.

### 2. Sistema de Respaldos
- **Script PHP (`scripts/backup_system.php`)**:
    - Genera SQL dump de la base de datos.
    - Comprime archivos críticos (imágenes, código) en ZIP.
    - Guarda en carpeta `backups/` con fecha `Y-m-d`.
    - Rotación: Elimina backups de más de 30 días.

### 3. Configuración Final
- Asegurar que `DISPLAY_ERRORS` esté en `0`.
- Verificar permisos de escritura solo en `backups/`, `imagenes_platos/` y logs.
