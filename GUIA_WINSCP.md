# 📤 Guía: Transferir Archivos con WinSCP

## Paso 1: Descargar e Instalar WinSCP

1. **Descargar WinSCP**:
   - Ve a: https://winscp.net/eng/download.php
   - Descarga "Installation package"
   - Ejecuta el instalador
   - Acepta los valores por defecto

---

## Paso 2: Configurar Conexión

1. **Abrir WinSCP**
2. En la ventana de "Login", configura:
   - **File protocol**: `SFTP`
   - **Host name**: `72.62.82.98`
   - **Port number**: `22`
   - **User name**: `root`
   - **Password**: Tu contraseña SSH del VPS

3. Click en **"Save"** para guardar la sesión
4. Click en **"Login"**

---

## Paso 3: Preparar Archivos en tu PC

Antes de subir, necesitamos editar el archivo de configuración:

1. **Abrir archivo `.env.cloud`**:
   - Ubicación: `c:\xampp\htdocs\globaltekhnologii\Restaurante\.env.cloud`
   - Abrirlo con Notepad o VS Code

2. **Editar estas líneas**:
   ```env
   DB_PASS=RestauranteDB2025!
   MASTER_SERVER_URL=http://72.62.82.98
   ```
   (Usa la contraseña que elegiste para MySQL)

3. **Guardar el archivo**

---

## Paso 4: Transferir Archivos

En WinSCP verás dos paneles:
- **Izquierda**: Tu PC
- **Derecha**: El VPS

1. **En el panel izquierdo (tu PC)**:
   - Navega a: `c:\xampp\htdocs\globaltekhnologii\Restaurante`

2. **En el panel derecho (VPS)**:
   - Navega a: `/var/www/html/restaurante`

3. **Seleccionar archivos a subir**:
   - En el panel izquierdo, selecciona TODOS los archivos (Ctrl+A)
   - **EXCEPTO** estas carpetas (no las selecciones):
     - `backups/`
     - `node_modules/` (si existe)
     - `vendor/` (si existe)
     - `.git/` (si existe)

4. **Arrastrar archivos**:
   - Arrastra los archivos seleccionados del panel izquierdo al derecho
   - O usa el botón "Upload" (F5)

5. **Esperar a que termine** (puede tardar 5-10 minutos)

---

## Paso 5: Verificar Transferencia

En WinSCP, en el panel derecho (VPS), deberías ver:
- ✅ `index.php`
- ✅ `config.php`
- ✅ `login.php`
- ✅ `.env.cloud`
- ✅ Carpetas: `css/`, `js/`, `includes/`, etc.

---

## Paso 6: Renombrar archivo de configuración

En WinSCP (panel derecho - VPS):

1. Busca el archivo `.env.cloud`
2. Click derecho → **Rename**
3. Renombrar a: `.env`
4. Click OK

---

¡Listo! Archivos transferidos. Siguiente paso: configurar permisos y Apache.
