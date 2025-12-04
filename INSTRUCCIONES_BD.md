# 🗄️ Instrucciones para Crear la Base de Datos

## Método 1: Usando phpMyAdmin (Recomendado)

### Pasos:

1. **Asegúrate que XAMPP esté corriendo**
   - Abre el Panel de Control de XAMPP
   - Inicia **Apache** ✅
   - Inicia **MySQL** ✅

2. **Abre phpMyAdmin**
   - Abre tu navegador
   - Ve a: `http://localhost/phpmyadmin`

3. **Importa el archivo SQL**
   - Haz clic en la pestaña **"Importar"**
   - Haz clic en **"Seleccionar archivo"**
   - Busca y selecciona: `C:\xampp\htdocs\Restaurante\database.sql`
   - Haz clic en **"Continuar"** o **"Go"**

4. **Verifica que se creó correctamente**
   - En el panel izquierdo deberías ver la base de datos **`menu_restaurante`**
   - Haz clic en ella
   - Deberías ver 4 tablas:
     - `platos` (15 registros de ejemplo)
     - `usuarios` (1 usuario admin)
     - `pedidos` (vacía)
     - `pedidos_items` (vacía)

---

## Método 2: Usando MySQL desde línea de comandos

### Pasos:

1. **Abre el terminal de XAMPP**
   - Abre el Panel de Control de XAMPP
   - Haz clic en **"Shell"**

2. **Ejecuta el script SQL**
   ```bash
   mysql -u root -p < C:\xampp\htdocs\Restaurante\database.sql
   ```
   - Cuando te pida la contraseña, presiona **Enter** (por defecto está vacía)

3. **Verifica la creación**
   ```bash
   mysql -u root -e "USE menu_restaurante; SHOW TABLES;"
   ```

---

## ✅ Verificación

Después de crear la base de datos, verifica:

### En phpMyAdmin:
- ✅ Base de datos `menu_restaurante` existe
- ✅ Tabla `platos` tiene 15 registros
- ✅ Tabla `usuarios` tiene 1 registro (admin)
- ✅ Tablas `pedidos` y `pedidos_items` están vacías

### En el navegador:
1. Ve a: `http://localhost/Restaurante/`
2. Deberías ver **15 platos** en el menú
3. Deberías poder filtrar por categorías
4. Deberías poder buscar platos

---

## 🔐 Credenciales de Acceso

### Usuario Administrador:
- **Usuario:** `admin`
- **Contraseña:** `admin123`

### Acceso al Panel Admin:
- URL: `http://localhost/Restaurante/login.php`

---

## 📊 Estructura de la Base de Datos

### Tabla: platos
- `id` - ID único del plato
- `nombre` - Nombre del plato
- `descripcion` - Descripción del plato
- `precio` - Precio en formato decimal
- `imagen_ruta` - Ruta de la imagen
- `categoria` - Categoría (Entradas, Platos Principales, Postres, Bebidas)
- `popular` - Si es popular (0 o 1)
- `nuevo` - Si es nuevo (0 o 1)
- `vegano` - Si es vegano (0 o 1)
- `fecha_creacion` - Fecha de creación automática

### Tabla: usuarios
- `id` - ID único del usuario
- `usuario` - Nombre de usuario (único)
- `clave` - Contraseña (hasheada)
- `nombre` - Nombre completo
- `email` - Email
- `rol` - Rol (admin, mesero, chef)
- `activo` - Si está activo (0 o 1)
- `fecha_creacion` - Fecha de creación
- `ultimo_acceso` - Último acceso

### Tabla: pedidos
- `id` - ID único del pedido
- `numero_pedido` - Número de pedido único
- `nombre_cliente` - Nombre del cliente
- `telefono` - Teléfono del cliente
- `direccion` - Dirección de entrega
- `email` - Email (opcional)
- `total` - Total del pedido
- `estado` - Estado del pedido
- `notas` - Notas adicionales
- `fecha_pedido` - Fecha del pedido
- `fecha_actualizacion` - Última actualización

### Tabla: pedidos_items
- `id` - ID único del item
- `pedido_id` - ID del pedido (FK)
- `plato_id` - ID del plato (FK)
- `nombre_plato` - Nombre del plato
- `precio` - Precio al momento del pedido
- `cantidad` - Cantidad
- `subtotal` - Subtotal (precio × cantidad)

---

## 🚨 Problemas Comunes

### Error: "Access denied for user"
**Solución:** Verifica que MySQL esté corriendo en XAMPP

### Error: "Unknown database"
**Solución:** El script crea la BD automáticamente, asegúrate de importar el archivo completo

### No se muestran platos en el menú
**Solución:** Verifica que se importaron los datos de ejemplo correctamente

---

## 📝 Próximos Pasos

Una vez creada la base de datos:

1. ✅ Probar el menú público: `http://localhost/Restaurante/`
2. ✅ Probar el login: `http://localhost/Restaurante/login.php`
3. ✅ Probar el panel admin: Crear, editar, eliminar platos
4. ✅ Probar el sistema de pedidos

---

*Creado el: 2025-12-03*
