@echo off
TITLE Actualizador Restaurante El Sabor
COLOR 0A

echo ========================================================
echo   ASISTENTE DE ACTUALIZACION - RESTAURANTE EL SABOR
echo ========================================================
echo.
echo Este script descargara la ultima version del software.
echo Asegurese de tener conexion a internet.
echo.
pause

cd /d C:\xampp\htdocs\Restaurante

IF EXIST .git (
    echo.
    echo Buscando actualizaciones...
    git pull origin main
    echo.
    echo ========================================================
    echo   SISTEMA ACTUALIZADO EXITOSAMENTE
    echo ========================================================
) ELSE (
    COLOR 0C
    echo.
    echo ERROR: No se encontro la configuracion de Git.
    echo Contacte al soporte tecnico.
)

echo.
pause
