# 🎛️ Guía Completa: Despliegue con HestiaCP

## ¿Qué es HestiaCP?

HestiaCP es un panel de control web que te permite administrar tu VPS desde el navegador, sin necesidad de usar comandos SSH. Es mucho más fácil y visual.

---

## 🔐 Paso 1: Acceder a HestiaCP

### Abrir el Panel

En tu navegador, ve a una de estas URLs:

```
https://72.62.82.98:8083
```

O también puede ser:
```
https://srv1208645.hstgr.cloud:8083
```

**Nota**: El navegador mostrará una advertencia de seguridad (certificado no confiable). Es normal, haz click en "Avanzado" → "Continuar de todos modos".

### Iniciar Sesión

- **Usuario**: `admin` o `root`
- **Contraseña**: La misma que usas para SSH

---

## 📋 Paso 2: Crear Base de Datos

1. **En el menú principal, click en "DB"** (Database)

2. **Click en el botón "+ Add Database"**

3. **Completa el formulario**:
   - **Database**: `restaurante_db`
   - **User**: `restaurante_user`
   - **Password**: `RestauranteDB2025!` (o la que prefieras)
   - **Charset**: `UTF8`

4. **Click en "Save"**

5. **Anota las credenciales**:
   ```
   Database: restaurante_db
   User: restaurante_user
   Password: RestauranteDB2025!
   Host: localhost
   ```

---

## 📊 Paso 3: Importar Estructura de Base de Datos

1. **En la lista de bases de datos, busca `restaurante_db`**

2. **Click en el icono de "phpMyAdmin"** (aparece al pasar el mouse)

3. **En phpMyAdmin**:
   - En el panel izquierdo, click en `restaurante_db`
   - Ve a la pestaña **"Import"** (Importar)
   - Click en **"Choose File"** (Elegir archivo)
   - Selecciona: `c:\xampp\htdocs\globaltekhnologii\Restaurante\database_inventario.sql`
   - Scroll hasta abajo y click en **"Go"** (Ejecutar)

4. **Verificar**: Deberías ver un mensaje de éxito y las tablas creadas en el panel izquierdo

---

## 📁 Paso 4: Subir Archivos del Proyecto

### Opción A: Usando File Manager (Recomendado)

1. **En HestiaCP, click en "FILE"** (File Manager)

2. **Navegar al directorio web**:
   - Si tienes un dominio configurado: `/home/admin/web/tudominio/public_html/`
   - Si no: `/var/www/html/restaurante/`

3. **Crear carpeta (si no existe)**:
   - Click en "New Folder"
   - Nombre: `restaurante`
   - Enter

4. **Entrar a la carpeta `restaurante`**

5. **Subir archivos**:
   - **Opción 1 - Archivo por archivo**:
     - Click en "Upload"
     - Selecciona archivos de `c:\xampp\htdocs\globaltekhnologii\Restaurante\`
     - Espera a que termine
   
   - **Opción 2 - Subir ZIP (Más rápido)**:
     - Primero, en tu PC, comprime la carpeta Restaurante en un ZIP
     - En HestiaCP File Manager, click en "Upload"
     - Sube el archivo ZIP
     - Click derecho en el ZIP → "Extract"
     - Elimina el ZIP después de extraer

### Opción B: Usando FTP (Alternativa)

1. **En HestiaCP, ve a "WEB"**

2. **Busca las credenciales FTP**:
   - Usuario FTP
   - Contraseña FTP
   - Puerto: 21

3. **Usa FileZilla o WinSCP** con estas credenciales

---

## ⚙️ Paso 5: Configurar el Archivo .env

1. **En File Manager, navega a `/var/www/html/restaurante/`**

2. **Busca el archivo `.env.cloud`**

3. **Click derecho → "Edit"**

4. **Modificar estas líneas**:
   ```env
   DB_HOST=localhost
   DB_NAME=restaurante_db
   DB_USER=restaurante_user
   DB_PASS=RestauranteDB2025!
   MASTER_SERVER_URL=http://72.62.82.98
   ```

5. **Guardar** (botón "Save")

6. **Renombrar el archivo**:
   - Click derecho en `.env.cloud`
   - "Rename"
   - Cambiar a: `.env`

---

## 🌐 Paso 6: Configurar Dominio Web (Opcional pero Recomendado)

1. **En HestiaCP, ve a "WEB"**

2. **Click en "+ Add Web Domain"**

3. **Configurar**:
   - **Domain**: `restaurante.72.62.82.98` o solo `72.62.82.98`
   - **IP Address**: Selecciona la IP disponible
   - **Aliases**: (dejar vacío)
   - **Proxy Support**: ✅ Enabled
   - **SSL Support**: ✅ Enabled (Let's Encrypt)
   - **PHP**: ✅ Enabled
   - **PHP Version**: Selecciona `8.1` o superior

4. **Click en "Save"**

5. **Mover archivos al directorio del dominio**:
   - Los archivos deben estar en: `/home/admin/web/tudominio/public_html/`

---

## 🔒 Paso 7: Configurar Permisos

1. **En File Manager, navega a `/var/www/html/restaurante/`**

2. **Selecciona las siguientes carpetas**:
   - `backups/`
   - `imagenes_platos/`
   - `imagenes_qr/`
   - `publicidad/`

3. **Para cada una**:
   - Click derecho → "Permissions"
   - Cambiar a: `775`
   - ✅ "Apply to subdirectories"
   - Click "OK"

---

## ✅ Paso 8: Probar el Sistema

1. **Abrir navegador**:
   ```
   http://72.62.82.98
   ```
   O si configuraste dominio:
   ```
   http://tudominio
   ```

2. **Deberías ver la página principal del restaurante**

3. **Ir a login**:
   ```
   http://72.62.82.98/login.php
   ```

4. **Credenciales por defecto**:
   - Usuario: `admin`
   - Password: `admin123`

5. **⚠️ CAMBIAR PASSWORD INMEDIATAMENTE**

---

## 🔍 Paso 9: Verificar Funcionamiento

### Health Check

```
http://72.62.82.98/health_check.php
```

Deberías ver:
```json
{
  "status": "ok",
  "database": true,
  "timestamp": "..."
}
```

### Verificar Logs

En HestiaCP:
1. Ve a "WEB"
2. Click en tu dominio
3. Ve a "Logs"
4. Revisa "Error Log" para ver si hay errores

---

## 🛠️ Funciones Útiles de HestiaCP

### 📊 Monitoreo
- **Dashboard**: Ver uso de CPU, RAM, disco
- **Statistics**: Tráfico web, visitas

### 🔧 Configuración PHP
1. Ve a "WEB"
2. Click en tu dominio
3. "Edit"
4. Ajustar:
   - `upload_max_filesize`: 20M
   - `post_max_size`: 25M
   - `memory_limit`: 256M

### 📧 Crear Email (Opcional)
1. Ve a "MAIL"
2. "Add Mail Domain"
3. Crear cuentas: `admin@tudominio.com`

### 💾 Backups
1. Ve a "BACKUP"
2. "Create Backup"
3. Descargar backups automáticos

---

## 🆘 Troubleshooting

### No puedo acceder a HestiaCP

- Verifica el puerto: `:8083`
- Prueba con HTTPS: `https://72.62.82.98:8083`
- Acepta el certificado autofirmado

### Error de base de datos

1. Ve a "DB" en HestiaCP
2. Verifica que la base de datos existe
3. Verifica credenciales en `.env`
4. Usa phpMyAdmin para probar conexión

### Página en blanco

1. Ve a "WEB" → Tu dominio → "Logs"
2. Revisa "Error Log"
3. Verifica que PHP 8.1 esté habilitado

### Permisos denegados

1. En File Manager
2. Click derecho en carpeta → "Permissions"
3. Cambiar a `775` para carpetas de escritura

---

## ✅ Checklist Final

- [ ] Base de datos creada en HestiaCP
- [ ] SQL importado vía phpMyAdmin
- [ ] Archivos subidos a `/var/www/html/restaurante/`
- [ ] Archivo `.env` configurado correctamente
- [ ] Permisos de carpetas configurados (775)
- [ ] Sitio accesible en `http://72.62.82.98`
- [ ] Login funciona correctamente
- [ ] Health check muestra "database": true
- [ ] Password de admin cambiado

---

**¡Con HestiaCP todo es más fácil! 🎉**
