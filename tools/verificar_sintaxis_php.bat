@echo off
REM Revisa la sintaxis de TODOS los archivos PHP del proyecto con el PHP de XAMPP.
REM Uso: doble clic, o desde la carpeta del proyecto:  tools\verificar_sintaxis_php.bat
set PHP=C:\xampp\php\php.exe
if not exist "%PHP%" (
    echo No se encontro %PHP%. Edita la variable PHP en este archivo.
    pause
    exit /b 1
)
cd /d "%~dp0.."
set ERRORES=0
for /r %%f in (*.php) do (
    "%PHP%" -l "%%f" | findstr /v /c:"No syntax errors" && set ERRORES=1
)
if "%ERRORES%"=="0" (echo.& echo Todos los archivos PHP tienen sintaxis correcta.) else (echo.& echo Hay archivos con errores: revisa los mensajes de arriba.)
pause
