@echo off
REM ========================================
REM Script de Despliegue Automático
REM Sistema de Restaurante - Hostinger VPS
REM ========================================

echo.
echo ========================================
echo  DESPLIEGUE AUTOMATICO
echo ========================================
echo.

REM Paso 1: Agregar cambios a Git
echo [1/3] Agregando cambios locales...
git add .
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: No se pudieron agregar los archivos
    pause
    exit /b 1
)

REM Paso 2: Commit (si hay cambios)
echo [2/3] Creando commit...
git commit -m "Deploy: Actualizacion automatica desde PC local"
REM Ignorar error si no hay cambios (exit code 1)

REM Paso 3: Push a GitHub
echo [3/3] Subiendo cambios a GitHub...
git push
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: No se pudo hacer push a GitHub
    pause
    exit /b 1
)

echo.
echo ========================================
echo  CAMBIOS SUBIDOS A GITHUB
echo ========================================
echo.
echo Ahora ejecuta en el servidor:
echo.
echo   ssh root@srv1208645.hstgr.cloud
echo   cd /home/user/web/srv1208645.hstgr.cloud/public_html
echo   git pull
echo.
echo O simplemente ejecuta este comando:
echo.
echo   ssh root@srv1208645.hstgr.cloud "cd /home/user/web/srv1208645.hstgr.cloud/public_html && git pull"
echo.
pause
