# 🔐 Guía de Prueba - Sistema de Cambio de Contraseña

## 📋 Información Importante

### Credenciales Actuales
- **Usuario:** `admin`
- **Contraseña:** `Admin@2024!` (nueva contraseña hasheada)

> ⚠️ **NOTA:** Si ya habías creado la base de datos antes, la contraseña sigue siendo `admin123`. 
> Para usar la nueva contraseña hasheada, necesitas reimportar `database.sql`.

---

## 🧪 Pasos para Probar

### 1. Iniciar Sesión

1. Abre tu navegador en: `http://localhost/Restaurante/login.php`
2. Ingresa las credenciales:
   - Usuario: `admin`
   - Contraseña: `Admin@2024!` (o `admin123` si no reimportaste la BD)
3. Haz clic en "Iniciar Sesión"

**Resultado esperado:** ✅ Deberías ver el panel de administración

---

### 2. Acceder al Cambio de Contraseña

**Opción A - Desde la barra superior:**
1. En el panel admin, busca el botón amarillo "🔐 Cambiar Contraseña"
2. Haz clic en él

**Opción B - URL directa:**
1. Ve a: `http://localhost/Restaurante/cambiar_password.php`

**Resultado esperado:** ✅ Deberías ver el formulario de cambio de contraseña

---

### 3. Probar Validación de Contraseña Débil

**Prueba 1 - Contraseña muy corta:**
1. Contraseña actual: `Admin@2024!` (o `admin123`)
2. Nueva contraseña: `abc123`
3. Confirmar: `abc123`
4. Haz clic en "Cambiar Contraseña"

**Resultado esperado:** ❌ Debe rechazar con mensaje de error

---

**Prueba 2 - Sin mayúsculas:**
1. Contraseña actual: `Admin@2024!`
2. Nueva contraseña: `password123!`
3. Confirmar: `password123!`
4. Haz clic en "Cambiar Contraseña"

**Resultado esperado:** ❌ Debe rechazar (falta mayúscula)

---

**Prueba 3 - Sin caracteres especiales:**
1. Contraseña actual: `Admin@2024!`
2. Nueva contraseña: `Password123`
3. Confirmar: `Password123`
4. Haz clic en "Cambiar Contraseña"

**Resultado esperado:** ❌ Debe rechazar (falta carácter especial)

---

### 4. Probar Indicador de Fuerza en Tiempo Real

1. En el campo "Nueva Contraseña", escribe lentamente:
   - `abc` → Debería mostrar barra roja "Muy débil"
   - `Abc123` → Debería mostrar barra naranja/amarilla "Débil/Media"
   - `Abc123!` → Debería mostrar barra verde "Fuerte"
   - `Admin@2024!` → Debería mostrar barra verde brillante "Muy fuerte"

**Resultado esperado:** ✅ La barra y el texto cambian en tiempo real

---

### 5. Cambiar Contraseña Exitosamente

1. Contraseña actual: `Admin@2024!` (o `admin123`)
2. Nueva contraseña: `MiNueva@Pass2024!`
3. Confirmar: `MiNueva@Pass2024!`
4. Haz clic en "Cambiar Contraseña"

**Resultado esperado:** ✅ Mensaje de éxito "Contraseña actualizada exitosamente"

---

### 6. Verificar que Funciona la Nueva Contraseña

1. Haz clic en "Cerrar Sesión"
2. Vuelve a `login.php`
3. Intenta login con la contraseña ANTIGUA: `Admin@2024!`
   - **Resultado esperado:** ❌ Debe rechazar
4. Intenta login con la contraseña NUEVA: `MiNueva@Pass2024!`
   - **Resultado esperado:** ✅ Debe permitir acceso

---

## 🎯 Checklist de Pruebas

- [ ] Login con contraseña actual funciona
- [ ] Formulario de cambio de contraseña carga correctamente
- [ ] Indicador de fuerza funciona en tiempo real
- [ ] Rechaza contraseña muy corta (< 8 caracteres)
- [ ] Rechaza contraseña sin mayúsculas
- [ ] Rechaza contraseña sin minúsculas
- [ ] Rechaza contraseña sin números
- [ ] Rechaza contraseña sin caracteres especiales
- [ ] Rechaza si las contraseñas no coinciden
- [ ] Rechaza si la contraseña actual es incorrecta
- [ ] Acepta contraseña fuerte válida
- [ ] Nueva contraseña funciona para login
- [ ] Contraseña antigua ya no funciona

---

## 🐛 Solución de Problemas

### Error: "Usuario o contraseña incorrectos"

**Causa:** La contraseña en la BD sigue siendo la antigua.

**Solución:**
1. Abre phpMyAdmin: `http://localhost/phpmyadmin`
2. Ve a la base de datos `menu_restaurante`
3. Tabla `usuarios`
4. Verifica la columna `clave` del usuario `admin`
5. Si es `admin123`, usa esa contraseña para login
6. O reimporta `database.sql` para usar `Admin@2024!`

---

### Error: "Contraseña actual es incorrecta"

**Causa:** Estás usando la contraseña equivocada.

**Solución:**
- Si NO reimportaste la BD: usa `admin123`
- Si SÍ reimportaste la BD: usa `Admin@2024!`

---

### El indicador de fuerza no aparece

**Causa:** JavaScript no está cargando.

**Solución:**
1. Abre la consola del navegador (F12)
2. Busca errores en JavaScript
3. Recarga la página (Ctrl+R)

---

## 📊 Características a Observar

### Diseño Visual
- ✨ Formulario moderno con gradiente morado
- 🎨 Campos con bordes redondeados
- 📊 Barra de progreso de fuerza
- 🎯 Lista de requisitos siempre visible
- 💫 Animaciones suaves

### Validación
- ⚡ Validación en tiempo real (JavaScript)
- 🛡️ Validación en servidor (PHP)
- 📝 Mensajes de error claros
- ✅ Feedback visual inmediato

### Seguridad
- 🔐 Contraseña actual requerida
- 🔒 Hash bcrypt automático
- 🛡️ Requisitos de complejidad estrictos
- ✅ Confirmación de contraseña

---

## 💡 Consejos

1. **Anota tu nueva contraseña** - No la olvides
2. **Usa el indicador de fuerza** - Apunta a "Muy fuerte"
3. **Prueba todas las validaciones** - Asegúrate que funcionan
4. **Verifica el login** - Confirma que la nueva contraseña funciona

---

*Guía creada el: 2025-12-04*
