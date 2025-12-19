@echo off
REM ========================================
REM Script de Despliegue Automático
REM Sistema de Restaurante - Hostinger VPS
REM ========================================

echo.
echo ========================================
echo  DESPLIEGUE AUTOMATICO AL SERVIDOR
echo ========================================
echo.

REM Paso 1: Agregar cambios a Git
echo [1/4] Agregando cambios locales...
git add .
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: No se pudieron agregar los archivos
    pause
    exit /b 1
)

REM Paso 2: Commit (si hay cambios)
echo [2/4] Creando commit...
git commit -m "Deploy: Actualizacion automatica desde PC local"
REM Ignorar error si no hay cambios (exit code 1)

REM Paso 3: Push a GitHub
echo [3/4] Subiendo cambios a GitHub...
git push
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: No se pudo hacer push a GitHub
    pause
    exit /b 1
)

REM Paso 4: Desplegar en servidor via SSH
echo [4/4] Desplegando en servidor VPS...
ssh root@srv1208645.hstgr.cloud "cd /home/user/web/srv1208645.hstgr.cloud/public_html && git pull"
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: No se pudo conectar al servidor o ejecutar git pull
    pause
    exit /b 1
)

echo.
echo ========================================
echo  DESPLIEGUE COMPLETADO EXITOSAMENTE
echo ========================================
echo.
echo Los cambios ya estan en produccion.
echo.
pause
