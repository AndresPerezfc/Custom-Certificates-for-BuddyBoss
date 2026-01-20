# Solución de Problemas - Custom Certificates

## ❌ Problemas Encontrados y Corregidos

### Problema 1: Instalador Automático Fallaba

**Error reportado:**
```
Error: No se pudo descargar mPDF: Not Found
```

**Causa:**
El instalador automático intentaba descargar mPDF desde una URL que no existe. mPDF solo está disponible a través de Composer.

**Solución implementada:**
- ✅ Eliminado el instalador automático fallido
- ✅ Actualizado el mensaje de error con instrucciones claras
- ✅ Mejorada la página de instrucciones de instalación

### Problema 2: Plugin no Funcionaba con vendor/ Incluido

**Causa probable:**
El autoloader de Composer no se estaba cargando correctamente al inicio del plugin.

**Solución implementada:**
- ✅ Agregada carga explícita del autoloader en `custom-certificates.php`
- ✅ El autoloader ahora se carga ANTES de cualquier clase del plugin
- ✅ Verificación de existencia del archivo antes de cargarlo

---

## ✅ Cómo Usar el Plugin Correctamente Ahora

### Opción 1: Subir Plugin Completo con vendor/ (RECOMENDADA)

Esta es la forma MÁS SIMPLE y la que debería funcionar:

#### Paso 1: Verificar que vendor/ existe

```bash
# En la carpeta del plugin
dir vendor
# O en Linux/Mac
ls -la vendor/
```

Deberías ver:
```
vendor/
├── autoload.php
├── composer/
├── mpdf/
├── myclabs/
├── paragonie/
├── psr/
└── setasign/
```

#### Paso 2: Crear el ZIP

**En Windows:**
```
Doble clic en: crear-zip.bat
```

O manualmente:
```powershell
Compress-Archive -Path * -DestinationPath custom-certificates-full.zip -Force
```

**En Linux/Mac:**
```bash
zip -r custom-certificates-full.zip . \
    -x "*.git*" -x "*.idea*" -x "*.log" -x "*.sh" -x "*.bat"
```

#### Paso 3: Subir a WordPress

1. Ir a **Plugins > Añadir nuevo**
2. Click en **Subir plugin**
3. Seleccionar `custom-certificates-full.zip`
4. Click en **Instalar ahora**
5. Click en **Activar plugin**

#### Paso 4: Verificar

Después de activar, verifica:

1. **NO debería aparecer error de dependencias**
   - Si aparece, significa que vendor/ no se subió correctamente

2. **Verificar en la página de instrucciones:**
   - Ir a Certificados (menú lateral)
   - Si aparece, click en cualquier opción
   - Si aparece aviso de dependencias, click en "Ver Instrucciones Completas"
   - Revisar la tabla de verificación

---

### Opción 2: Instalar Dependencias en el Servidor (Avanzada)

Si tienes acceso SSH al servidor:

#### Paso 1: Subir el plugin sin vendor/

```bash
zip -r custom-certificates-lite.zip . \
    -x "*/vendor/*" -x "*.git*" -x "*.idea*"
```

Subir y activar en WordPress

#### Paso 2: Conectar por SSH

```bash
ssh usuario@tuservidor.com
```

#### Paso 3: Navegar al plugin

```bash
cd /ruta/a/wordpress/wp-content/plugins/custom-certificates/
```

#### Paso 4: Instalar dependencias

```bash
composer install --no-dev --optimize-autoloader
```

#### Paso 5: Verificar

```bash
ls -la vendor/autoload.php
```

Si ves el archivo, las dependencias están instaladas.

---

## 🔍 Diagnóstico de Problemas

### ¿El plugin muestra error de dependencias?

**Síntoma:**
```
Custom Certificates - Error de Dependencias
El plugin requiere la librería mPDF para funcionar.
```

**Verificar:**

1. **¿Existe la carpeta vendor/ en el servidor?**
   ```
   - Conectar por FTP/SFTP
   - Navegar a: wp-content/plugins/custom-certificates/
   - Verificar que existe carpeta "vendor"
   ```

2. **¿Existe vendor/autoload.php?**
   ```
   - Dentro de vendor/
   - Debe existir archivo: autoload.php
   ```

3. **¿Existe vendor/mpdf/?**
   ```
   - Dentro de vendor/
   - Debe existir carpeta: mpdf/
   ```

**Si falta algo:**
- Opción A: Descargar versión FULL del plugin (con vendor/)
- Opción B: Ejecutar `composer install` en el servidor

---

### ¿El plugin se activa pero al intentar descargar PDF da error?

**Síntoma:**
```
Error al generar PDF: Class 'Mpdf\Mpdf' not found
```

**Causa:**
mPDF no se está cargando correctamente

**Solución:**

1. Desactivar el plugin
2. Verificar que vendor/autoload.php existe
3. Reactivar el plugin
4. Limpiar caché del navegador
5. Intentar de nuevo

---

### ¿Aparece "Fatal error" al activar el plugin?

**Síntoma:**
```
Fatal error: Cannot redeclare class...
```

**Causas posibles:**
- Tienes dos versiones del plugin instaladas
- Otro plugin usa mPDF y conflictúa

**Solución:**

1. Desactivar TODOS los plugins
2. Eliminar la carpeta del plugin
3. Subir solo UNA versión limpia
4. Activar
5. Activar otros plugins uno por uno

---

## 📋 Checklist de Verificación

Antes de reportar un problema, verifica:

- [ ] La carpeta vendor/ existe en el plugin
- [ ] El archivo vendor/autoload.php existe
- [ ] La carpeta vendor/mpdf/ existe con archivos dentro
- [ ] Solo tienes UNA versión del plugin instalada
- [ ] El plugin está activado
- [ ] Has limpiado la caché del navegador
- [ ] Has revisado los logs de errores de WordPress

### Ver logs de errores

**En WordPress:**
```php
// Agregar a wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

**Luego revisar:**
```
wp-content/debug.log
```

---

## 🆘 Soluciones Rápidas

### Solución 1: Empezar de Cero

```bash
# 1. Desactivar plugin desde WordPress

# 2. Eliminar carpeta del plugin por FTP

# 3. En tu máquina local, verificar vendor/
ls -la vendor/

# 4. Crear ZIP limpio
zip -r custom-certificates.zip . -x "*.git*" -x "*.log"

# 5. Subir a WordPress
# Plugins > Añadir nuevo > Subir plugin

# 6. Activar
```

### Solución 2: Verificar Permisos

```bash
# En el servidor (SSH)
cd wp-content/plugins/custom-certificates/

# Verificar permisos
ls -la

# Ajustar si es necesario
chmod -R 755 .
```

### Solución 3: Forzar Recarga del Autoloader

En `wp-config.php`, temporalmente:

```php
// Forzar recarga de clases
define('WP_CACHE', false);
```

Luego:
1. Desactivar plugin
2. Reactivar plugin
3. Probar
4. Eliminar la línea de wp-config.php

---

## 📞 Información para Soporte

Si nada funciona, proporciona esta información:

```
1. Versión de WordPress:
2. Versión de PHP:
3. ¿vendor/ existe?: Sí / No
4. ¿vendor/autoload.php existe?: Sí / No
5. ¿vendor/mpdf/ existe?: Sí / No
6. ¿Qué error exacto aparece?:
7. ¿Logs de error (wp-content/debug.log)?:
```

---

## ✅ Confirmación de Funcionamiento

El plugin está funcionando correctamente si:

1. ✅ Se activa sin errores
2. ✅ Aparece "Certificados" en el menú de administración
3. ✅ Puedes crear una plantilla de certificado
4. ✅ Puedes asignar un certificado a un usuario
5. ✅ En el perfil del usuario aparece la pestaña "Certificados"
6. ✅ Puedes descargar el PDF sin errores

---

**Última actualización:** 2024-12-10
**Estado:** Problemas corregidos, plugin funcional
