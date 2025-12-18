@echo off
title Tunel Ngrok - Restaurante
color 0A

echo ==========================================
echo   INICIANDO TUNEL NGROK PARA RESTAURANTE
echo ==========================================
echo.
echo IMPORTANTE: 
echo - Asegurate de que XAMPP este corriendo
echo - Apache debe estar en verde
echo - MySQL debe estar en verde
echo.
echo Presiona Ctrl+C para detener el tunel
echo ==========================================
echo.

REM Cambia esta ruta si instalaste ngrok en otro lugar
cd /d C:\ngrok

REM Si ngrok no está en C:\ngrok, descomenta y ajusta esta línea:
REM cd /d "%USERPROFILE%\Downloads\ngrok"

echo Iniciando tunel en puerto 80...
echo.
ngrok.exe http 80

pause
