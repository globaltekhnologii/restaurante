# GUÍA DE SOPORTE REMOTO Y ACTUALIZACIONES
## Para Instalaciones Locales (Windows)

Esta guía explica cómo mantener, soportar y actualizar el sistema "Restaurante El Sabor" cuando está instalado en una PC física del cliente, sin usar un VPS.

---

### 1. Estrategia de Conexión (El "Túnel")

Aunque el sistema es local, necesitas entrar desde tu oficina para arreglar problemas.

#### ❌ Lo que NO debes hacer:
*   Abrir puertos en el router del cliente (inseguro y difícil de configurar).
*   Dejar el XAMPP expuesto a todo internet.

#### ✅ La Solución Profesional: **ZeroTier** o **Tailscale**
Estas herramientas crean una "Red Privada Virtual" (VPN).
1.  Instalas **Tailscale** (gratuito) en la PC del restaurante.
2.  Instalas **Tailscale** en tu PC.
3.  Ambas PCs creerán que están en la misma habitación. Podrás entrar al sistema poniendo la IP de Tailscale (ej: `100.x.x.x/Restaurante`).
4.  Es 100% seguro y encriptado.

#### 🚑 La Solución de Emergencia: **AnyDesk** o **TeamViewer**
Para cuando necesitas "tomar el control" del mouse y ver la pantalla del cajero.
*   Ideal para: Enseñarles a usar el sistema, arreglar impresoras, configuración de Windows.

---

### 2. Estrategia de Actualizaciones (Código)

¿Cómo le envías las mejoras (como el módulo de publicidad) sin ir hasta allá?

#### Opción A: Sincronización con Git (Recomendada)
Si subes tu código a GitHub/GitLab (privado):
1.  Instalas **Git** en la PC del cliente.
2.  Clonas el repositorio en `C:\xampp\htdocs\Restaurante`.
3.  Creas un acceso directo o un script `.bat` que haga `git pull`.
4.  **Ventaja:** Actualizas en 1 segundo sin borrar datos ni fotos.

#### Opción B: Copia Manual (Zip)
Si no usas Git:
1.  Te conectas por AnyDesk.
2.  Envías el archivo `.zip` con la nueva versión.
3.  Descomprimes y reemplazas los archivos.
4.  **Desventaja:** Riesgo de borrar el `config.php` o las fotos de los platos si no tienes cuidado.

---

### 3. Checklist de Mantenimiento

Al instalar en el cliente, deja configurado:
- [ ] **IP Estática** en la PC del servidor (para que no cambie y los meseros no pierdan conexión).
- [ ] **Firewall de Windows**: Permitir puerto 80 (Apache) para la red privada.
- [ ] **Backup Automático**: Un script que guarde la base de datos en Google Drive o Dropbox todos los días.

---

### 4. Script de Actualización Automática (Ejemplo)

Si usas Git, puedes dejar este archivo en el escritorio del cliente llamado `ACTUALIZAR_SISTEMA.bat`:

```batch
@echo off
echo ==========================================
echo      ACTUALIZANDO RESTAURANTE EL SABOR
echo ==========================================
cd /d C:\xampp\htdocs\Restaurante
echo.
echo Descargando ultimas mejoras...
git pull origin main
echo.
echo Actualizacion completada.
echo.
pause
```
