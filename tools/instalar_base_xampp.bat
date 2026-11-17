@echo off
REM Instala la base de datos en el MySQL de XAMPP (debe estar iniciado desde el panel de XAMPP).
REM Ejecuta, en orden: 01_esquema_y_datos.sql y 02_auditoria_y_trigger.sql
REM Si prefieres phpMyAdmin: pestaña "Importar" y subi esos dos archivos en ese orden.
set MYSQL=C:\xampp\mysql\bin\mysql.exe
if not exist "%MYSQL%" (
    echo No se encontro %MYSQL%. Edita la variable MYSQL en este archivo.
    pause
    exit /b 1
)
cd /d "%~dp0..\database\instalacion"
echo Instalando esquema y datos...
"%MYSQL%" -u root --default-character-set=utf8mb4 < 01_esquema_y_datos.sql || goto error
echo Instalando tabla de auditoria y trigger...
"%MYSQL%" -u root --default-character-set=utf8mb4 centro_medico_salud < 02_auditoria_y_trigger.sql || goto error
echo.
echo Listo. Abri http://localhost/Poli_Medic_salud/
pause
exit /b 0
:error
echo.
echo Fallo la instalacion. Revisa que MySQL este iniciado y que root no tenga contrasena.
pause
exit /b 1
