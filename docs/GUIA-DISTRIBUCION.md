# Guía de Distribución del Plugin

Esta guía explica cómo preparar y distribuir el plugin **Custom Certificates for BuddyBoss** sin complicaciones para los usuarios finales.

## 📦 Opciones de Distribución

### ✅ Opción 1: Incluir Dependencias (RECOMENDADA)

**Para qué:** Distribución lista para usar, Plug & Play

#### Pasos:

1. **Instalar dependencias en tu máquina local:**
   ```bash
   cd custom-certificates/
   composer install --no-dev --optimize-autoloader
   ```

2. **Verificar que la carpeta `vendor/` se creó:**
   ```bash
   ls -la vendor/
   # Deberías ver: autoload.php, mpdf/, composer/, etc.
   ```

3. **Crear el archivo ZIP del plugin:**
   ```bash
   # Desde el directorio padre de custom-certificates/
   zip -r custom-certificates-v1.0.0.zip custom-certificates/ \
       -x "custom-certificates/.git/*" \
       -x "custom-certificates/.idea/*" \
       -x "custom-certificates/node_modules/*" \
       -x "custom-certificates/*.log"
   ```

   **En Windows (PowerShell):**
   ```powershell
   Compress-Archive -Path custom-certificates -DestinationPath custom-certificates-v1.0.0.zip
   ```

4. **Distribuir el archivo ZIP:**
   - Súbelo a tu sitio web
   - Compártelo por email
   - Publícalo en GitHub Releases
   - Envíalo a tus clientes

#### Ventajas:
- ✅ Los usuarios solo suben y activan
- ✅ Cero configuración técnica
- ✅ Funciona inmediatamente
- ✅ No requiere acceso SSH
- ✅ No requiere Composer en el servidor

#### Desventajas:
- ❌ Archivo ZIP más grande (~15-20 MB)

---

### ⚡ Opción 2: Auto-Instalador de Dependencias

**Para qué:** Plugin ligero con instalación automática de dependencias

El plugin ahora incluye un **sistema de auto-instalación** de dependencias.

#### Cómo Funciona:

1. **El usuario sube el plugin SIN la carpeta `vendor/`**
2. **Al activar, aparece un aviso:**
   ```
   ⚠️ Custom Certificates necesita instalar dependencias
   [Instalar Dependencias Automáticamente] [Instrucciones Manuales]
   ```

3. **El usuario hace clic en "Instalar Dependencias Automáticamente"**
4. **El plugin descarga mPDF automáticamente desde GitHub**
5. **Listo - El plugin funciona**

#### Preparar para esta opción:

1. **NO incluyas la carpeta `vendor/` en el ZIP:**
   ```bash
   zip -r custom-certificates-lite-v1.0.0.zip custom-certificates/ \
       -x "custom-certificates/vendor/*" \
       -x "custom-certificates/.git/*" \
       -x "custom-certificates/.idea/*" \
       -x "custom-certificates/*.log"
   ```

2. **El ZIP será mucho más pequeño (~2-3 MB)**

#### Ventajas:
- ✅ Archivo ZIP muy ligero
- ✅ Instalación automática con un clic
- ✅ Descarga solo lo necesario

#### Desventajas:
- ❌ Requiere conexión a internet en el servidor
- ❌ Un paso adicional para el usuario
- ❌ Puede fallar si el servidor tiene firewall restrictivo

---

## 🎯 Recomendación: Estrategia Dual

**Ofrece ambas versiones:**

1. **`custom-certificates-full-v1.0.0.zip`** (con vendor/)
   - Para usuarios sin acceso SSH
   - Para servidores con restricciones de red
   - Tamaño: ~15-20 MB

2. **`custom-certificates-lite-v1.0.0.zip`** (sin vendor/)
   - Para usuarios con buena conexión
   - Para reducir tamaño de descarga
   - Tamaño: ~2-3 MB

---

## 📋 Checklist Pre-Distribución

Antes de crear el ZIP de distribución, verifica:

### Código
- [ ] Todas las funcionalidades probadas
- [ ] No hay errores de PHP
- [ ] No hay warnings en el log
- [ ] Textos traducibles con `__()`, `_e()`, etc.
- [ ] Código comentado apropiadamente

### Seguridad
- [ ] Todos los inputs sanitizados
- [ ] Todos los outputs escapados
- [ ] Nonces implementados
- [ ] Capability checks en todas las acciones admin

### Archivos
- [ ] `.gitignore` configurado correctamente
- [ ] `README.md` actualizado con versión correcta
- [ ] `CHANGELOG.md` actualizado
- [ ] Versión en `custom-certificates.php` actualizada
- [ ] Sin archivos de desarrollo (.log, .tmp, etc.)

### Dependencias (Opción 1: Full)
- [ ] `composer install --no-dev --optimize-autoloader` ejecutado
- [ ] Carpeta `vendor/` incluida
- [ ] `vendor/autoload.php` existe
- [ ] mPDF se carga correctamente

### Testing
- [ ] Plugin probado en WordPress 5.8+
- [ ] Plugin probado con BuddyBoss Platform
- [ ] Probado en PHP 7.4, 8.0, 8.1
- [ ] Activación/desactivación sin errores
- [ ] Creación de plantilla funciona
- [ ] Asignación de certificado funciona
- [ ] Descarga de PDF funciona
- [ ] Tab en perfil aparece correctamente

---

## 🚀 Proceso Completo de Distribución

### Paso 1: Preparar el Código

```bash
# Actualizar versión en archivos
# Editar: custom-certificates.php (línea 6: Version: 1.0.0)
# Editar: README.md
# Editar: CHANGELOG.md

# Instalar dependencias optimizadas
composer install --no-dev --optimize-autoloader --no-interaction

# Limpiar archivos innecesarios
rm -rf .git .idea *.log
```

### Paso 2: Crear ZIPs de Distribución

```bash
# VERSION FULL (con vendor/)
cd ..
zip -r custom-certificates-full-v1.0.0.zip custom-certificates/ \
    -x "*.git*" \
    -x "*.idea*" \
    -x "*.log" \
    -x "*.DS_Store" \
    -x "*node_modules/*"

# VERSION LITE (sin vendor/)
zip -r custom-certificates-lite-v1.0.0.zip custom-certificates/ \
    -x "*/vendor/*" \
    -x "*.git*" \
    -x "*.idea*" \
    -x "*.log" \
    -x "*.DS_Store" \
    -x "*node_modules/*"
```

### Paso 3: Verificar los ZIPs

```bash
# Extraer en directorio temporal
mkdir test-install
cd test-install
unzip ../custom-certificates-full-v1.0.0.zip

# Verificar estructura
ls -la custom-certificates/
ls -la custom-certificates/vendor/  # Solo en versión full

# Verificar que archivos críticos existen
test -f custom-certificates/custom-certificates.php && echo "✓ Plugin file OK"
test -f custom-certificates/README.md && echo "✓ README OK"
test -f custom-certificates/composer.json && echo "✓ composer.json OK"
```

### Paso 4: Test de Instalación

1. **En un WordPress de prueba:**
   - Sube el ZIP desde **Plugins > Añadir nuevo > Subir plugin**
   - Activa el plugin
   - Verifica que no hay errores
   - Crea una plantilla de prueba
   - Asigna un certificado
   - Descarga el PDF

2. **Verificar la versión LITE (sin vendor/):**
   - Activa el plugin
   - Debería aparecer aviso de dependencias
   - Haz clic en "Instalar Dependencias Automáticamente"
   - Verifica que funciona

### Paso 5: Publicar

#### En GitHub:

```bash
# Crear tag
git tag -a v1.0.0 -m "Release version 1.0.0"
git push origin v1.0.0

# Crear release en GitHub
# Sube los dos ZIPs como assets
```

#### En tu sitio web:

```markdown
## Descarga Custom Certificates v1.0.0

### Versión Completa (Recomendada)
- [Descargar custom-certificates-full-v1.0.0.zip](link) (18 MB)
- Incluye todas las dependencias
- Instalación: Sube, activa, usa

### Versión Ligera
- [Descargar custom-certificates-lite-v1.0.0.zip](link) (2 MB)
- Requiere instalación de dependencias (automática con un clic)
```

---

## 📝 Documentación para Usuarios

Incluye siempre:

1. **README.md** - Dentro del ZIP
2. **INSTALACION.md** - Guía de instalación
3. **INICIO-RAPIDO.md** - Tutorial de 5 minutos
4. **FAQ.md** (opcional)

### Ejemplo de FAQ.md

```markdown
# Preguntas Frecuentes

## ¿Qué versión debo descargar?

- **Versión Full**: Si quieres instalar y usar inmediatamente
- **Versión Lite**: Si prefieres un archivo más pequeño

## ¿El plugin funciona sin Composer?

Sí, si descargas la versión Full, no necesitas Composer.

## ¿Necesito acceso SSH?

No, el plugin incluye instalador automático de dependencias.
```

---

## 🔄 Actualizaciones Futuras

### Para actualizar el plugin:

1. Incrementa la versión en `custom-certificates.php`
2. Actualiza `CHANGELOG.md`
3. Repite el proceso de distribución
4. Los usuarios pueden actualizar:
   - Manualmente (subiendo nuevo ZIP)
   - Vía actualizador de WordPress (si publicas en repositorio oficial)

---

## 📤 Opciones de Hosting del Plugin

### 1. GitHub Releases (Gratis)
```
https://github.com/tu-usuario/custom-certificates/releases
```

### 2. Tu propio sitio
```
https://tudominio.com/plugins/custom-certificates/
```

### 3. WordPress.org (Repositorio oficial)
- Requiere cumplir con sus estándares
- Actualizaciones automáticas para usuarios
- Mayor alcance

### 4. Marketplaces
- CodeCanyon
- Creative Market
- Freemius (para venta)

---

## ✅ Resumen Ejecutivo

### Para distribución inmediata (sin complicaciones):

```bash
# 1. Instalar dependencias
composer install --no-dev --optimize-autoloader

# 2. Crear ZIP
zip -r custom-certificates-v1.0.0.zip custom-certificates/

# 3. Compartir
# Los usuarios solo suben y activan
```

### Instrucciones para el usuario final:

```
1. Descarga custom-certificates-v1.0.0.zip
2. Ve a Plugins > Añadir nuevo > Subir plugin
3. Selecciona el ZIP y haz clic en "Instalar ahora"
4. Activa el plugin
5. ¡Listo! Empieza a crear certificados
```

**Sin Composer. Sin SSH. Sin configuración técnica.**

---

**¿Preguntas?** Revisa la sección de troubleshooting en `README.md`

**Última actualización:** 2024-12-10
