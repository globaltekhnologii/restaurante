# 🌐 Guía: Port Forwarding con Doble Router (Movistar + EG8041V5)

Esta guía te muestra cómo configurar el acceso remoto cuando tienes **dos routers en cascada**:
- **Router 1**: Askey RTF8225VW (Movistar) - Principal
- **Router 2**: EG8041V5 - Secundario
- **Tu PC**: XAMPP corriendo

---

## 📊 Diagrama de tu Red

```
Internet → [Movistar RTF8225VW] → [EG8041V5] → [Tu PC con XAMPP]
           192.168.1.1              192.168.0.1    192.168.0.105
```

---

## 🎯 Estrategia: Configurar AMBOS Routers

Necesitas hacer port forwarding en **los dos routers** para que las peticiones lleguen hasta tu PC.

---

## 📋 Paso 1: Verificar la Configuración de Red

### 1.1 Ejecutar Script de Diagnóstico

Haz doble clic en `verificar_red.bat` y anota:
- Tu IP local (probablemente `192.168.0.x`)
- Tu puerta de enlace (probablemente `192.168.0.1` - el EG8041V5)

### 1.2 Verificar IP Pública

Ve a: **https://www.cual-es-mi-ip.net/** y anota tu IP pública.

---

## 🔧 Paso 2: Configurar Router Secundario (EG8041V5)

### 2.1 Configurar IP Estática en tu PC

1. Presiona **Windows + R** → escribe `ncpa.cpl`
2. Clic derecho en tu adaptador → **Propiedades**
3. Selecciona **TCP/IPv4** → **Propiedades**
4. Configura:
   - **IP**: `192.168.0.105` (o cualquier IP entre .100 y .200)
   - **Máscara**: `255.255.255.0`
   - **Puerta de enlace**: `192.168.0.1`
   - **DNS preferido**: `8.8.8.8`
   - **DNS alternativo**: `8.8.4.4`

### 2.2 Acceder al EG8041V5

1. Abre tu navegador
2. Ve a: **http://192.168.0.1**
3. Usuario: `admin` / Contraseña: `admin` (o la del módem)

### 2.3 Configurar Port Forwarding en EG8041V5

1. Ve a **Application** → **Port Forwarding** (o **NAT** → **Port Mapping**)
2. Clic en **Add** o **Agregar**
3. Configura:

| Campo | Valor |
|-------|-------|
| **Service Name** | `XAMPP-HTTP` |
| **Protocol** | `TCP` |
| **External Port** | `80` |
| **Internal Host** | `192.168.0.105` (IP de tu PC) |
| **Internal Port** | `80` |
| **Enable** | ✅ Activado |

4. Guarda y aplica

---

## 🔧 Paso 3: Configurar Router Principal (Movistar RTF8225VW)

### 3.1 Obtener IP del EG8041V5 en la Red de Movistar

Necesitas saber qué IP le asignó el router Movistar al EG8041V5.

**Opción A**: Desde el EG8041V5
1. En la interfaz del EG8041V5 (`http://192.168.0.1`)
2. Ve a **Status** → **WAN** o **Internet**
3. Busca **WAN IP Address** (ejemplo: `192.168.1.50`)
4. Anota esta IP

**Opción B**: Desde el Router Movistar
1. Accede al router Movistar (siguiente paso)
2. Ve a **DHCP** → **Lista de clientes**
3. Busca el dispositivo EG8041V5
4. Anota su IP

### 3.2 Acceder al Router Movistar RTF8225VW

1. Abre tu navegador
2. Ve a: **http://192.168.1.1**
3. Credenciales comunes de Movistar:
   - Usuario: `1234` / Contraseña: `1234`
   - Usuario: `admin` / Contraseña: `admin`
   - Usuario: `admin` / Contraseña: (la que está en la etiqueta del router)

> [!TIP]
> Si no puedes acceder, la contraseña suele estar en una etiqueta en la parte trasera del router.

### 3.3 Configurar Port Forwarding en Router Movistar

1. Busca la sección **NAT**, **Port Forwarding** o **Aplicaciones**
2. Clic en **Agregar** o **Add**
3. Configura:

| Campo | Valor |
|-------|-------|
| **Nombre** | `XAMPP-Cascada` |
| **Protocolo** | `TCP` |
| **Puerto Externo** | `80` |
| **IP Destino** | `192.168.1.50` (IP del EG8041V5 en red Movistar) |
| **Puerto Interno** | `80` |
| **Habilitar** | ✅ Sí |

4. Guarda y aplica

---

## 🔥 Paso 4: Configurar Firewall de Windows

Ejecuta **PowerShell como Administrador**:

```powershell
# Permitir Apache en el Firewall
netsh advfirewall firewall add rule name="Apache HTTP" dir=in action=allow protocol=TCP localport=80

# Verificar
netsh advfirewall firewall show rule name="Apache HTTP"
```

---

## ✅ Paso 5: Verificar la Configuración

### 5.1 Flujo de Datos

```
Internet (Puerto 80)
    ↓
Router Movistar (192.168.1.1)
    ↓ Reenvía a 192.168.1.50:80
EG8041V5 (192.168.1.50 / 192.168.0.1)
    ↓ Reenvía a 192.168.0.105:80
Tu PC (192.168.0.105)
    ↓
XAMPP Apache
```

### 5.2 Probar Localmente

```
http://localhost/Restaurante
http://192.168.0.105/Restaurante
```

### 5.3 Probar desde Internet

Desde tu teléfono (datos móviles, NO WiFi):

```
http://[TU-IP-PUBLICA]/Restaurante
```

### 5.4 Verificar Puerto Abierto

Ve a: **https://www.yougetsignal.com/tools/open-ports/**
- IP: Tu IP pública
- Puerto: `80`
- Debe decir: **Port 80 is open** ✅

---

## 🚨 Troubleshooting Específico para Doble NAT

### ❌ No funciona después de configurar ambos routers

**Verificaciones**:

1. **¿XAMPP está corriendo?**
   ```powershell
   netstat -ano | findstr :80
   ```

2. **¿Funciona localmente?**
   - Prueba: `http://192.168.0.105/Restaurante`

3. **¿El EG8041V5 está en modo Router o Bridge?**
   - Debe estar en modo **Router** (NAT habilitado)
   - Si está en modo Bridge, no necesitas configurarlo

4. **¿La IP del EG8041V5 en la red Movistar es correcta?**
   - Verifica en el router Movistar → DHCP → Clientes

5. **¿Ambas reglas de port forwarding están activas?**
   - Router Movistar: `80 → 192.168.1.50:80`
   - EG8041V5: `80 → 192.168.0.105:80`

### ❌ Funciona desde la red local pero no desde internet

**Causa**: Probablemente CGNAT o ISP bloqueando puerto 80.

**Soluciones**:
1. Contacta a Movistar y solicita IP pública
2. Usa un puerto alternativo (8080, 8888)
3. Usa Ngrok como alternativa

### ❌ El router Movistar no permite port forwarding

**Causa**: Algunos routers de ISP tienen limitaciones.

**Soluciones**:
1. Solicita a Movistar que habiliten el modo avanzado
2. Pide que configuren el puerto ellos
3. Considera poner el EG8041V5 en **DMZ** del router Movistar

---

## 💡 Opción Alternativa: Modo DMZ (Más Fácil)

Si el port forwarding es muy complicado, puedes usar **DMZ** (Zona Desmilitarizada):

### En el Router Movistar:

1. Ve a **Seguridad** → **DMZ**
2. Habilita DMZ
3. IP del Host DMZ: `192.168.1.50` (IP del EG8041V5)
4. Guarda

Esto enviará **TODO el tráfico** al EG8041V5, y solo necesitarás configurar port forwarding en el EG8041V5.

> [!WARNING]
> DMZ es menos seguro porque expone completamente el router secundario. Úsalo solo si entiendes los riesgos.

---

## 🎯 Opción Recomendada: Simplificar la Red

### Opción A: Modo Bridge en EG8041V5

Si no necesitas el EG8041V5 como router:

1. Configura el EG8041V5 en **modo Bridge** (o AP - Access Point)
2. Tu PC estará directamente en la red del router Movistar
3. Solo necesitarás configurar port forwarding en el router Movistar

### Opción B: Usar Solo el Router Movistar

1. Conecta tu PC directamente al router Movistar (WiFi o cable)
2. Configura port forwarding solo en el router Movistar
3. Elimina la complejidad del doble NAT

---

## 🌍 Configurar DNS Dinámico (Recomendado)

Con doble NAT, es aún más importante usar DNS dinámico:

### No-IP (Gratis):

1. Regístrate: **https://www.noip.com**
2. Crea hostname: `mirestaurante.ddns.net`
3. Descarga e instala **DUC** (Dynamic Update Client)
4. Configura con tus credenciales

**Acceso**: `http://mirestaurante.ddns.net/Restaurante`

---

## 📊 Comparación de Opciones

| Método | Complejidad | Seguridad | Recomendado |
|--------|-------------|-----------|-------------|
| **Port Forwarding Doble** | ⚠️⚠️⚠️ Alta | ⚠️ Media | Solo si es necesario |
| **DMZ + Port Forwarding** | ⚠️⚠️ Media | ⚠️ Baja | Más fácil |
| **Modo Bridge** | ⚠️ Baja | ✅ Media | **Recomendado** |
| **Ngrok** | ✅ Muy baja | ✅ Alta | **Más fácil** |
| **AWS/GCP** | ⚠️⚠️ Media | ✅ Alta | Producción |

---

## ✅ Checklist de Configuración

### Router Secundario (EG8041V5):
- [ ] IP estática en tu PC (192.168.0.105)
- [ ] Port forwarding: 80 → 192.168.0.105:80
- [ ] Anotar IP WAN del EG8041V5 (ej: 192.168.1.50)

### Router Principal (Movistar):
- [ ] Acceder a http://192.168.1.1
- [ ] Port forwarding: 80 → [IP del EG8041V5]:80
- [ ] Verificar que la regla esté activa

### Sistema:
- [ ] Firewall de Windows configurado
- [ ] XAMPP corriendo (Apache verde)
- [ ] Probar localmente
- [ ] Probar desde internet
- [ ] Configurar DNS dinámico (opcional)

---

## 🆘 Mi Recomendación Personal

Dado que tienes **doble NAT**, te recomiendo:

### Para Pruebas Inmediatas:
✅ **Usa Ngrok** - Es mucho más fácil y evita toda esta complejidad

### Para Uso Permanente:
✅ **Simplifica tu red**:
1. Pon el EG8041V5 en modo Bridge/AP
2. Conecta tu PC al router Movistar
3. Configura port forwarding solo en el Movistar

### Para Producción:
✅ **Despliega en AWS/GCP** - Más profesional y seguro

---

¿Necesitas ayuda con alguna de estas opciones?
