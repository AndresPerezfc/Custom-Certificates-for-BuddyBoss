@echo off
REM Script para crear ZIP del plugin con dependencias (Windows)

echo ================================================
echo Creando ZIP del plugin Custom Certificates
echo ================================================
echo.

REM Verificar que vendor existe
if not exist "vendor" (
    echo ERROR: La carpeta vendor/ no existe
    echo Por favor ejecuta: composer install --no-dev
    echo.
    pause
    exit /b 1
)

REM Crear nombre del archivo
set ZIP_NAME=custom-certificates-full.zip

REM Eliminar ZIP anterior si existe
if exist "%ZIP_NAME%" (
    del "%ZIP_NAME%"
    echo ZIP anterior eliminado
)

echo Comprimiendo archivos...
echo.

REM Usar PowerShell para crear ZIP
powershell -Command "Compress-Archive -Path * -DestinationPath %ZIP_NAME% -Force"

if exist "%ZIP_NAME%" (
    echo.
    echo ================================================
    echo ZIP creado exitosamente: %ZIP_NAME%
    echo ================================================
    echo.
    echo Ahora puedes:
    echo 1. Subir este ZIP a WordPress
    echo 2. Ir a Plugins ^> Añadir nuevo ^> Subir plugin
    echo 3. Seleccionar %ZIP_NAME%
    echo 4. Instalar y activar
    echo.
) else (
    echo.
    echo ERROR: No se pudo crear el ZIP
    echo.
)

pause
