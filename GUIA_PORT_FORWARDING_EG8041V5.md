# 🌐 Guía: Acceso Remoto con Módem EG8041V5 (Port Forwarding)

Esta guía te muestra cómo configurar tu módem **EG8041V5** para acceder a tu aplicación XAMPP desde internet sin usar servicios externos como Ngrok.

---

## 📋 Requisitos Previos

- ✅ Módem **EG8041V5** (Fibra óptica)
- ✅ XAMPP instalado y funcionando
- ✅ Acceso administrativo al módem
- ✅ IP pública de tu proveedor de internet (ISP)

---

## 🔍 Paso 1: Verificar tu IP Pública

### 1.1 Obtener tu IP Pública Actual

Abre tu navegador y ve a: **https://www.cual-es-mi-ip.net/**

Anota tu IP pública (ejemplo: `181.143.25.100`)

> [!WARNING]
> **Importante**: Muchos ISPs asignan IPs dinámicas que cambian periódicamente. Verifica con tu proveedor si tienes IP pública estática o dinámica.

### 1.2 Verificar si tu IP es Pública o Privada

Si tu IP comienza con:
- ❌ `10.x.x.x` - IP privada (CGNAT)
- ❌ `172.16.x.x` a `172.31.x.x` - IP privada (CGNAT)
- ❌ `192.168.x.x` - IP privada (CGNAT)
- ✅ Cualquier otra - IP pública (puedes continuar)

> [!CAUTION]
> Si tienes **CGNAT** (IP privada), el port forwarding NO funcionará. Necesitarás solicitar IP pública a tu ISP o usar Ngrok.

---

## 🖥️ Paso 2: Configurar IP Estática en tu PC

Para que el módem siempre sepa dónde enviar las peticiones, tu PC debe tener una IP local fija.

### 2.1 Obtener tu IP Local Actual

Abre **PowerShell** o **CMD** y ejecuta:

```powershell
ipconfig
```

Busca tu adaptador de red (WiFi o Ethernet) y anota:
- **Dirección IPv4**: (ejemplo: `192.168.1.105`)
- **Puerta de enlace predeterminada**: (ejemplo: `192.168.1.1`)
- **Máscara de subred**: (ejemplo: `255.255.255.0`)

### 2.2 Configurar IP Estática

1. Presiona **Windows + R**
2. Escribe: `ncpa.cpl` y presiona Enter
3. Haz clic derecho en tu adaptador de red → **Propiedades**
4. Selecciona **Protocolo de Internet versión 4 (TCP/IPv4)** → **Propiedades**
5. Selecciona **Usar la siguiente dirección IP**:
   - **Dirección IP**: `192.168.1.105` (la que anotaste)
   - **Máscara de subred**: `255.255.255.0`
   - **Puerta de enlace predeterminada**: `192.168.1.1`
   - **Servidor DNS preferido**: `8.8.8.8`
   - **Servidor DNS alternativo**: `8.8.4.4`
6. Clic en **Aceptar**

---

## 🔧 Paso 3: Acceder al Módem EG8041V5

### 3.1 Ingresar a la Interfaz Web

1. Abre tu navegador
2. Ingresa la IP del módem: **http://192.168.1.1** (o la puerta de enlace que anotaste)
3. Ingresa las credenciales de administrador

**Credenciales comunes del EG8041V5**:
- Usuario: `admin` / Contraseña: `admin`
- Usuario: `admin` / Contraseña: (la que está en la etiqueta del módem)
- Usuario: `telecomadmin` / Contraseña: `admintelecom`

> [!TIP]
> Si no conoces la contraseña, revisa la etiqueta en la parte trasera del módem o contacta a tu ISP.

---

## ⚙️ Paso 4: Configurar Port Forwarding (NAT)

### 4.1 Navegar a la Configuración de NAT

En la interfaz del módem EG8041V5:

1. Ve a **Application** → **Port Forwarding** (o **NAT** → **Port Mapping**)
2. Busca la opción **Add** o **Agregar nueva regla**

### 4.2 Crear Regla para HTTP (Puerto 80)

Configura los siguientes valores:

| Campo | Valor | Descripción |
|-------|-------|-------------|
| **Service Name** | `XAMPP-HTTP` | Nombre descriptivo |
| **Protocol** | `TCP` | Tipo de protocolo |
| **WAN Interface** | `Default` o `Internet` | Interfaz de salida |
| **External Port Start** | `80` | Puerto externo inicial |
| **External Port End** | `80` | Puerto externo final |
| **Internal Host** | `192.168.1.105` | IP de tu PC |
| **Internal Port Start** | `80` | Puerto interno inicial |
| **Internal Port End** | `80` | Puerto interno final |
| **Enable** | ✅ Activado | Habilitar regla |

### 4.3 Crear Regla para HTTPS (Puerto 443) - Opcional

Si planeas usar HTTPS:

| Campo | Valor |
|-------|-------|
| **Service Name** | `XAMPP-HTTPS` |
| **Protocol** | `TCP` |
| **External Port** | `443` |
| **Internal Host** | `192.168.1.105` |
| **Internal Port** | `443` |
| **Enable** | ✅ Activado |

### 4.4 Guardar Configuración

1. Haz clic en **Apply** o **Guardar**
2. El módem puede reiniciarse (espera 1-2 minutos)

---

## 🔥 Paso 5: Configurar Firewall de Windows

Windows puede bloquear las conexiones entrantes. Debes permitir Apache:

### 5.1 Abrir Firewall de Windows

```powershell
# Ejecuta PowerShell como Administrador
# Clic derecho en el menú Inicio → Windows PowerShell (Administrador)

# Permitir Apache en el Firewall
netsh advfirewall firewall add rule name="Apache HTTP" dir=in action=allow protocol=TCP localport=80

netsh advfirewall firewall add rule name="Apache HTTPS" dir=in action=allow protocol=TCP localport=443
```

### 5.2 Verificar Reglas

1. Presiona **Windows + R**
2. Escribe: `wf.msc` y presiona Enter
3. Ve a **Reglas de entrada**
4. Verifica que existan las reglas **Apache HTTP** y **Apache HTTPS**

---

## ✅ Paso 6: Probar la Configuración

### 6.1 Verificar XAMPP

1. Abre **XAMPP Control Panel**
2. Asegúrate de que **Apache** esté corriendo (verde)
3. Verifica localmente: `http://localhost/Restaurante`

### 6.2 Probar desde Internet

**Desde tu teléfono móvil** (desconecta WiFi, usa datos móviles):

```
http://[TU-IP-PUBLICA]/Restaurante
```

Ejemplo: `http://181.143.25.100/Restaurante`

### 6.3 Usar Herramienta de Verificación

Ve a: **https://www.yougetsignal.com/tools/open-ports/**
- Ingresa tu IP pública
- Puerto: `80`
- Clic en **Check**
- Debe decir: **Port 80 is open** ✅

---

## 🌍 Paso 7: Configurar Dominio Dinámico (Opcional)

Si tu IP pública cambia frecuentemente, usa un servicio de DNS dinámico:

### Opción A: No-IP (Gratis)

1. Regístrate en: **https://www.noip.com**
2. Crea un hostname: `mirestaurante.ddns.net`
3. Descarga el cliente **DUC** (Dynamic Update Client)
4. Instala y configura con tus credenciales
5. El cliente actualizará automáticamente tu IP

**Acceso**: `http://mirestaurante.ddns.net/Restaurante`

### Opción B: DuckDNS (Gratis)

1. Regístrate en: **https://www.duckdns.org**
2. Crea un subdominio: `mirestaurante.duckdns.org`
3. Descarga el script de actualización
4. Configura una tarea programada en Windows

---

## 🔒 Paso 8: Seguridad (MUY IMPORTANTE)

> [!CAUTION]
> Exponer tu servidor a internet tiene riesgos de seguridad. Sigue estas recomendaciones:

### 8.1 Cambiar Puerto por Defecto

En lugar de usar el puerto 80 (muy atacado), usa otro puerto:

1. Edita `C:\xampp\apache\conf\httpd.conf`
2. Busca: `Listen 80`
3. Cambia a: `Listen 8080` (o cualquier puerto > 1024)
4. Reinicia Apache
5. En el módem, configura port forwarding: `80 → 8080`

**Acceso**: `http://[TU-IP]:8080/Restaurante`

### 8.2 Proteger con .htaccess

Crea un archivo `.htaccess` en `C:\xampp\htdocs\Restaurante\`:

```apache
# Bloquear acceso a archivos sensibles
<FilesMatch "^(config\.php|conexion\.php|\.env)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Proteger directorios
Options -Indexes
```

### 8.3 Actualizar Contraseñas

- Cambia las contraseñas de administrador del sistema
- Usa contraseñas fuertes (mínimo 12 caracteres)
- Cambia la contraseña de MySQL

### 8.4 Monitorear Accesos

Revisa regularmente los logs de Apache:
- `C:\xampp\apache\logs\access.log`
- `C:\xampp\apache\logs\error.log`

---

## 📱 Paso 9: Configurar App Android

Actualiza la URL en tu aplicación Android:

```java
// En Constants.java o similar
public static final String BASE_URL = "http://[TU-IP-PUBLICA]/Restaurante/";

// O con dominio dinámico
public static final String BASE_URL = "http://mirestaurante.ddns.net/Restaurante/";
```

---

## 🚨 Troubleshooting

### ❌ No puedo acceder desde internet

**Verificaciones**:
1. ✅ ¿XAMPP está corriendo?
2. ✅ ¿Funciona localmente? (`http://localhost/Restaurante`)
3. ✅ ¿Tu IP es pública? (no CGNAT)
4. ✅ ¿El puerto 80 está abierto? (usa yougetsignal.com)
5. ✅ ¿El firewall de Windows permite Apache?
6. ✅ ¿La regla de port forwarding está activa en el módem?

### ❌ Funciona desde mi red pero no desde internet

**Causa**: Probablemente estás detrás de CGNAT.

**Solución**:
- Contacta a tu ISP y solicita IP pública
- O usa Ngrok como alternativa

### ❌ La IP pública cambia constantemente

**Solución**: Usa un servicio de DNS dinámico (No-IP o DuckDNS)

### ❌ "ERR_CONNECTION_REFUSED"

**Causas posibles**:
- Apache no está corriendo
- Firewall bloqueando
- Puerto incorrecto en la configuración

### ❌ "ERR_CONNECTION_TIMED_OUT"

**Causas posibles**:
- ISP bloqueando el puerto 80 (algunos ISPs lo hacen)
- Regla de port forwarding mal configurada
- CGNAT activo

**Solución**: Usa un puerto alternativo (8080, 8888, etc.)

---

## 📊 Comparación de Métodos

| Método | Costo | Configuración | Seguridad | Mejor Para |
|--------|-------|---------------|-----------|------------|
| **Port Forwarding** | Gratis | 30 min | ⚠️ Media | Uso permanente |
| **Ngrok** | Gratis/$8 | 5 min | ✅ Alta | Pruebas rápidas |
| **AWS/GCP** | $0-25/mes | 2 horas | ✅ Alta | Producción |

---

## ✅ Checklist de Configuración

- [ ] Verificar IP pública (no CGNAT)
- [ ] Configurar IP estática en la PC
- [ ] Acceder al módem (192.168.1.1)
- [ ] Crear regla de port forwarding (puerto 80)
- [ ] Configurar firewall de Windows
- [ ] Probar acceso local (localhost)
- [ ] Probar acceso remoto (desde teléfono)
- [ ] Configurar DNS dinámico (opcional)
- [ ] Implementar medidas de seguridad
- [ ] Actualizar URL en app Android

---

## 🆘 ¿Necesitas Ayuda?

Si tienes problemas:
1. Verifica que tu ISP no use CGNAT
2. Contacta a tu ISP si necesitas IP pública
3. Considera usar Ngrok si el port forwarding no funciona
4. Revisa los logs de Apache para errores

**¡Listo!** Ahora tu aplicación será accesible desde internet 🌍
