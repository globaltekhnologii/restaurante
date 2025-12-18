# 🎯 Configuración Específica para tu Red

## 📊 Tu Configuración de Red

```
Internet
    ↓
Router Movistar (Askey RTF8225VW)
IP: 192.168.1.1
    ↓
Router EG8041V5
IP WAN: 192.168.1.X (asignada por Movistar)
IP LAN: 192.168.18.1
    ↓
Tu PC con XAMPP
IP: 192.168.18.X (a configurar)
```

---

## ⚡ OPCIÓN RÁPIDA: Usar Ngrok (5 minutos)

**Si quieres acceso inmediato sin complicaciones:**

1. Descarga Ngrok: https://ngrok.com/download
2. Ejecuta: `ngrok http 80`
3. Copia la URL pública
4. ¡Listo! Accede desde cualquier lugar

**Ventaja**: No necesitas configurar ningún router.

---

## 🔧 OPCIÓN PERMANENTE: Port Forwarding en Ambos Routers

### PASO 1: Configurar IP Estática en tu PC

1. Presiona **Windows + R** → `ncpa.cpl`
2. Clic derecho en tu adaptador → **Propiedades**
3. **TCP/IPv4** → **Propiedades**
4. Configura:
   - **IP**: `192.168.18.100`
   - **Máscara**: `255.255.255.0`
   - **Puerta de enlace**: `192.168.18.1`
   - **DNS preferido**: `8.8.8.8`
   - **DNS alternativo**: `8.8.4.4`

---

### PASO 2: Configurar Router EG8041V5 (192.168.18.1)

#### 2.1 Acceder al Router
- URL: **http://192.168.18.1**
- Usuario: `admin` / Contraseña: `admin`

#### 2.2 Configurar Port Forwarding
1. Ve a **Application** → **Port Forwarding**
2. Agregar nueva regla:

| Campo | Valor |
|-------|-------|
| **Service Name** | XAMPP-HTTP |
| **Protocol** | TCP |
| **External Port** | 80 |
| **Internal Host** | 192.168.18.100 |
| **Internal Port** | 80 |
| **Enable** | ✅ |

3. **Guardar**

#### 2.3 Obtener IP WAN del EG8041V5
- En el mismo router, ve a **Status** → **WAN**
- Anota la **IP WAN** (ejemplo: `192.168.1.50`)
- Esta es la IP que el router Movistar le asignó al EG8041V5

---

### PASO 3: Configurar Router Movistar (192.168.1.1)

#### 3.1 Acceder al Router Movistar
- URL: **http://192.168.1.1**
- Credenciales comunes:
  - `1234` / `1234`
  - `admin` / `admin`
  - O la que está en la etiqueta del router

#### 3.2 Configurar Port Forwarding
1. Busca **NAT** o **Port Forwarding** o **Aplicaciones**
2. Agregar nueva regla:

| Campo | Valor |
|-------|-------|
| **Nombre** | XAMPP-Cascada |
| **Protocolo** | TCP |
| **Puerto Externo** | 80 |
| **IP Destino** | 192.168.1.50 (IP WAN del EG8041V5) |
| **Puerto Interno** | 80 |
| **Habilitar** | ✅ |

3. **Guardar**

---

### PASO 4: Configurar Firewall de Windows

Ejecuta **PowerShell como Administrador**:

```powershell
netsh advfirewall firewall add rule name="Apache HTTP" dir=in action=allow protocol=TCP localport=80
```

---

### PASO 5: Verificar

#### Localmente:
```
http://localhost/Restaurante
http://192.168.18.100/Restaurante
```

#### Desde Internet:
1. Obtén tu IP pública: https://www.cual-es-mi-ip.net/
2. Desde tu teléfono (datos móviles):
```
http://[TU-IP-PUBLICA]/Restaurante
```

---

## 🎯 Flujo de Datos Completo

```
Internet (Puerto 80)
    ↓
Router Movistar (192.168.1.1)
    ↓ Reenvía a 192.168.1.50:80
Router EG8041V5 (192.168.1.50 / 192.168.18.1)
    ↓ Reenvía a 192.168.18.100:80
Tu PC (192.168.18.100)
    ↓
Apache (XAMPP)
```

---

## 💡 Alternativa MÁS FÁCIL: Modo DMZ

Si el port forwarding es complicado:

### En Router Movistar:
1. Ve a **Seguridad** → **DMZ**
2. Habilita DMZ
3. IP del Host DMZ: `192.168.1.50` (IP del EG8041V5)
4. Guardar

Luego solo configuras port forwarding en el EG8041V5.

---

## ✅ Checklist

- [ ] IP estática en PC: `192.168.18.100`
- [ ] Port forwarding en EG8041V5: `80 → 192.168.18.100:80`
- [ ] Anotar IP WAN del EG8041V5 (en Status → WAN)
- [ ] Port forwarding en Movistar: `80 → [IP WAN EG8041V5]:80`
- [ ] Firewall Windows configurado
- [ ] XAMPP corriendo
- [ ] Probar desde internet

---

## 🆘 Mi Recomendación

**Para empezar YA**: Usa **Ngrok** (5 minutos)

**Para uso permanente**: 
1. Simplifica tu red (modo Bridge en EG8041V5)
2. O despliega en AWS/GCP

**El doble NAT es complicado y puede dar problemas.**

---

¿Con cuál opción quieres que te ayude?
