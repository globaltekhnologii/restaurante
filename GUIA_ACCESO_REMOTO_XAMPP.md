# 🌐 Guía Rápida: Acceso Remoto a XAMPP con Ngrok

Esta guía te muestra cómo acceder a tu aplicación de restaurante en XAMPP desde **cualquier lugar por internet** en menos de 5 minutos.

---

## ⚡ Opción 1: Ngrok (Recomendada - Más Rápida)

### Paso 1: Descargar Ngrok

1. Ve a: **https://ngrok.com/download**
2. Descarga la versión para **Windows**
3. Extrae el archivo `ngrok.exe` en una carpeta fácil de encontrar (ejemplo: `C:\ngrok\`)

### Paso 2: Crear Cuenta Gratis

1. Regístrate en: **https://dashboard.ngrok.com/signup**
2. Inicia sesión
3. Copia tu **Authtoken** (aparece en el dashboard)

### Paso 3: Configurar Ngrok

Abre **PowerShell** o **CMD** y ejecuta:

```powershell
# Navega a la carpeta donde está ngrok.exe
cd C:\ngrok

# Configura tu token (solo una vez)
.\ngrok.exe config add-authtoken TU_TOKEN_AQUI
```

> Reemplaza `TU_TOKEN_AQUI` con el token que copiaste del dashboard.

### Paso 4: Iniciar XAMPP

1. Abre **XAMPP Control Panel**
2. Inicia **Apache** (debe estar en verde)
3. Inicia **MySQL** (debe estar en verde)
4. Verifica que tu aplicación funcione localmente: `http://localhost/Restaurante`

### Paso 5: Crear el Túnel

En PowerShell/CMD, ejecuta:

```powershell
cd C:\ngrok
.\ngrok.exe http 80
```

### Paso 6: Obtener tu URL Pública

Ngrok mostrará algo como esto:

```
Session Status                online
Account                       tu-email@gmail.com
Version                       3.x.x
Region                        United States (us)
Latency                       45ms
Web Interface                 http://127.0.0.1:4040
Forwarding                    https://abc123xyz.ngrok-free.app -> http://localhost:80
```

**¡Tu URL pública es!**: `https://abc123xyz.ngrok-free.app/Restaurante`

### Paso 7: Compartir el Acceso

Ahora puedes compartir esta URL con:
- ✅ Meseros: `https://abc123xyz.ngrok-free.app/Restaurante/mesero.php`
- ✅ Chef: `https://abc123xyz.ngrok-free.app/Restaurante/chef.php`
- ✅ Cajero: `https://abc123xyz.ngrok-free.app/Restaurante/cajero.php`
- ✅ Domiciliarios: `https://abc123xyz.ngrok-free.app/Restaurante/domiciliario.php`
- ✅ Admin: `https://abc123xyz.ngrok-free.app/Restaurante/admin.php`
- ✅ Clientes (menú): `https://abc123xyz.ngrok-free.app/Restaurante/index.php`

**Cualquier persona con esta URL puede acceder desde cualquier lugar del mundo** 🌍

---

## 📱 Probar desde tu Teléfono

1. Abre el navegador de tu teléfono
2. **Desconecta el WiFi** (usa datos móviles)
3. Ingresa la URL de Ngrok
4. ¡Deberías ver tu aplicación! ✅

---

## ⚠️ Limitaciones de Ngrok Gratis

| Característica | Gratis | Pagado |
|----------------|--------|--------|
| **URL cambia** | ✅ Sí (cada vez que reinicias) | ❌ No (URL fija) |
| **Límite de conexiones** | 40/minuto | Ilimitado |
| **Túnel activo** | Solo mientras tu PC esté encendida | Igual |
| **Costo** | $0 | ~$8/mes |

> [!WARNING]
> **La URL cambia cada vez que cierras y abres Ngrok**. Si necesitas una URL permanente, considera la versión pagada o usar AWS/Google Cloud.

---

## 🔄 Mantener Ngrok Corriendo

Para que el túnel no se cierre, **NO cierres la ventana de PowerShell/CMD** donde está corriendo Ngrok.

Si necesitas que esté siempre activo:

### Opción A: Dejar la PC Encendida
- Mantén XAMPP y Ngrok corriendo 24/7
- Configura Windows para que no se suspenda

### Opción B: Crear un Acceso Directo

1. Crea un archivo `iniciar_ngrok.bat` en `C:\ngrok\`:

```batch
@echo off
echo ==========================================
echo   INICIANDO TUNEL NGROK PARA RESTAURANTE
echo ==========================================
cd /d C:\ngrok
ngrok.exe http 80
pause
```

2. Haz doble clic en este archivo para iniciar Ngrok rápidamente

---

## 🎯 Opción 2: Ngrok con URL Personalizada (Pagado)

Si pagas la versión Pro de Ngrok ($8/mes), puedes tener:

```powershell
.\ngrok.exe http 80 --domain=mirestaurante.ngrok.app
```

Tu URL será siempre: `https://mirestaurante.ngrok.app/Restaurante`

---

## 🆓 Opción 3: LocalTunnel (Alternativa Gratis)

Si prefieres otra herramienta gratuita:

### Instalar LocalTunnel

```powershell
# Necesitas Node.js instalado
npm install -g localtunnel
```

### Iniciar Túnel

```powershell
lt --port 80 --subdomain mirestaurante
```

Tu URL será: `https://mirestaurante.loca.lt`

---

## 🔐 Configurar la App Android

Si tienes la aplicación Android para domiciliarios, actualiza la URL:

1. Abre el proyecto Android
2. Busca el archivo de configuración (probablemente en `Constants.java` o similar)
3. Actualiza:

```java
// Antes
public static final String BASE_URL = "http://192.168.1.100/Restaurante/";

// Después (con Ngrok)
public static final String BASE_URL = "https://abc123xyz.ngrok-free.app/Restaurante/";
```

4. Recompila la app
5. Instala en los dispositivos

> [!IMPORTANT]
> Recuerda actualizar la URL cada vez que reinicies Ngrok (a menos que uses la versión pagada con dominio fijo).

---

## 🚨 Troubleshooting

### ❌ "Tunnel not found"

**Solución**: Verifica que copiaste bien el authtoken:
```powershell
.\ngrok.exe config check
```

### ❌ "Port 80 already in use"

**Causa**: Otro programa está usando el puerto 80.

**Solución**: Cambia el puerto de Apache en XAMPP:
1. XAMPP Control Panel → Apache → Config → httpd.conf
2. Busca `Listen 80` y cámbialo a `Listen 8080`
3. Reinicia Apache
4. Usa: `.\ngrok.exe http 8080`

### ❌ "ERR_NGROK_108"

**Causa**: Límite de conexiones excedido (40/minuto en plan gratis).

**Solución**: Espera 1 minuto o actualiza a plan pagado.

### ❌ La aplicación carga pero sin estilos

**Causa**: Las rutas CSS/JS son relativas.

**Solución**: Verifica que en tu HTML uses rutas correctas:
```html
<!-- Correcto -->
<link rel="stylesheet" href="css/style.css">

<!-- Incorrecto -->
<link rel="stylesheet" href="/css/style.css">
```

---

## 📊 Comparación de Opciones

| Opción | Costo | URL Fija | Configuración | Mejor Para |
|--------|-------|----------|---------------|------------|
| **Ngrok Gratis** | $0 | ❌ No | 5 min | Pruebas/Demos |
| **Ngrok Pro** | $8/mes | ✅ Sí | 5 min | Desarrollo |
| **LocalTunnel** | $0 | ⚠️ A veces | 5 min | Pruebas |
| **AWS EC2** | $0-25/mes | ✅ Sí | 2 horas | Producción |

---

## ✅ Checklist Rápido

- [ ] Descargar Ngrok
- [ ] Crear cuenta y obtener authtoken
- [ ] Configurar authtoken en Ngrok
- [ ] Iniciar Apache y MySQL en XAMPP
- [ ] Ejecutar `ngrok http 80`
- [ ] Copiar la URL pública
- [ ] Probar desde el teléfono (datos móviles)
- [ ] Compartir URL con el equipo

---

## 🎓 Próximos Pasos

### Para Pruebas Cortas
✅ **Usa Ngrok gratis** - Es perfecto para lo que necesitas ahora

### Para Uso Permanente
Considera migrar a:
- **AWS EC2** (guía completa en `GUIA_DESPLIEGUE_AWS.md`)
- **Google Cloud** (guía completa en `GUIA_DESPLIEGUE_GCP.md`)
- **Ngrok Pro** (más fácil pero pagado)

---

## 🆘 ¿Necesitas Ayuda?

Si tienes algún problema:
1. Verifica que XAMPP esté corriendo
2. Verifica que `http://localhost/Restaurante` funcione
3. Revisa los logs de Ngrok en la ventana de PowerShell
4. Visita el panel web de Ngrok: `http://127.0.0.1:4040`

**¡Listo!** Ahora puedes acceder a tu sistema desde cualquier lugar 🚀
