# Sistema de Despliegue Automático

## 🚀 Cómo Usar

Dado que Hostinger tiene **todas las funciones de ejecución PHP bloqueadas** (`exec`, `shell_exec`, `system`, etc.), el módulo de actualización web no puede funcionar.

### Solución: Script de Despliegue Local

Hemos creado `deploy.bat` que automatiza todo el proceso desde tu PC.

### Pasos para Desplegar Cambios

1. **Haz tus cambios** en los archivos localmente
2. **Ejecuta el script:**
   ```
   deploy.bat
   ```
3. **¡Listo!** El script automáticamente:
   - ✅ Hace `git add .`
   - ✅ Hace `git commit`
   - ✅ Hace `git push` a GitHub
   - ✅ Se conecta por SSH al servidor
   - ✅ Ejecuta `git pull` en el servidor

### Requisitos Previos

- ✅ Git instalado en tu PC
- ✅ SSH configurado (ya lo tienes)
- ✅ Credenciales de Git guardadas (ya lo hicimos)

### Ejemplo de Uso

```batch
C:\xampp\htdocs\globaltekhnologii\Restaurante> deploy.bat

========================================
 DESPLIEGUE AUTOMATICO AL SERVIDOR
========================================

[1/4] Agregando cambios locales...
[2/4] Creando commit...
[3/4] Subiendo cambios a GitHub...
[4/4] Desplegando en servidor VPS...

========================================
 DESPLIEGUE COMPLETADO EXITOSAMENTE
========================================
```

### Ventajas

- 🎯 **Un solo comando** para todo el proceso
- ⚡ **Rápido**: No necesitas terminal SSH aparte
- 🔒 **Seguro**: Usa tu autenticación SSH existente
- 📝 **Trazable**: Cada despliegue queda registrado en Git

### Notas

- Si no hay cambios, el commit fallará (es normal)
- El script continuará con el push de commits anteriores
- Si falla la conexión SSH, verás un mensaje de error claro

---

## 🔧 Problemas Comunes

**"ssh: command not found"**
- Instala OpenSSH en Windows: `Settings > Apps > Optional Features > OpenSSH Client`

**"Permission denied (publickey)"**
- Usa la contraseña cuando te la pida
- O configura SSH keys para no escribir contraseña

**"fatal: not a git repository"**
- Asegúrate de ejecutar el script desde la carpeta del proyecto
