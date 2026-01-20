# 📦 Resumen Rápido: Distribución del Plugin

## ¿Qué problema resolvemos?

El plugin usa **mPDF** (librería PHP) para generar PDFs. Esta librería normalmente se instala con Composer ejecutando `composer install`, pero **los usuarios finales no deberían tener que hacer esto**.

## ✅ Soluciones Implementadas

### Opción 1: Plugin Completo (RECOMENDADA para usuarios no técnicos)

**Qué hacer:**
```bash
# 1. Instalar dependencias en tu máquina
composer install --no-dev --optimize-autoloader

# 2. Crear ZIP con TODO incluido (incluyendo vendor/)
./build-release.sh 1.0.0
# O en Windows:
.\build-release.ps1 -Version "1.0.0"
```

**Resultado:**
- ZIP de ~15-20 MB
- Los usuarios SOLO suben el plugin y activan
- **CERO configuración técnica**
- Funciona inmediatamente

**Para el usuario:**
```
1. Sube custom-certificates-full-v1.0.0.zip
2. Activa
3. Listo - empieza a usar
```

---

### Opción 2: Plugin con Auto-Instalador (Para usuarios con servidor conectado)

**Qué hacer:**
```bash
# Crear ZIP SIN vendor/
./build-release.sh 1.0.0
# Esto crea también la versión LITE
```

**Resultado:**
- ZIP de ~2-3 MB (mucho más ligero)
- Al activar, el plugin muestra un aviso
- El usuario hace clic en "Instalar Dependencias Automáticamente"
- El plugin descarga mPDF desde GitHub
- Listo

**Para el usuario:**
```
1. Sube custom-certificates-lite-v1.0.0.zip
2. Activa
3. Click en "Instalar Dependencias Automáticamente"
4. Espera 10-20 segundos
5. Listo - empieza a usar
```

---

## 🎯 ¿Cuál elegir?

| Situación | Opción Recomendada |
|-----------|-------------------|
| Distribuir a clientes no técnicos | **Opción 1 (Full)** |
| Vender en marketplace | **Opción 1 (Full)** |
| Publicar en WordPress.org | **Opción 1 (Full)** |
| Compartir con desarrolladores | **Opción 2 (Lite)** |
| Servidor con restricciones de red | **Opción 1 (Full)** |
| Quieres archivo pequeño | **Opción 2 (Lite)** |

---

## 🚀 Proceso Rápido para Distribuir

### MÉTODO AUTOMÁTICO (Usar scripts):

**En Linux/Mac:**
```bash
# Ejecutar el script de build
./build-release.sh 1.0.0

# Esto crea:
# - custom-certificates-full-v1.0.0.zip (con vendor/)
# - custom-certificates-lite-v1.0.0.zip (sin vendor/)
```

**En Windows:**
```powershell
# Ejecutar el script de PowerShell
.\build-release.ps1 -Version "1.0.0"

# Crea los mismos dos archivos
```

### MÉTODO MANUAL:

**Versión Full:**
```bash
# 1. Instalar dependencias
composer install --no-dev --optimize-autoloader

# 2. Crear ZIP (incluye vendor/)
zip -r custom-certificates-v1.0.0.zip custom-certificates/ \
    -x "*.git*" -x "*.idea*" -x "*.log"
```

**Versión Lite:**
```bash
# Crear ZIP (excluye vendor/)
zip -r custom-certificates-lite-v1.0.0.zip custom-certificates/ \
    -x "*/vendor/*" -x "*.git*" -x "*.idea*" -x "*.log"
```

---

## 📋 Checklist Final

Antes de distribuir, verifica:

- [ ] **Versión actualizada** en `custom-certificates.php`
- [ ] **CHANGELOG.md** actualizado
- [ ] **README.md** tiene instrucciones claras
- [ ] **Probado en WordPress limpio**
- [ ] **Probado en BuddyBoss**
- [ ] **Sin errores de PHP**
- [ ] **Dependencias incluidas** (si es versión Full)
- [ ] **Archivos de desarrollo eliminados** (.git, .idea, *.log)

---

## 💡 Ejemplo Real de Distribución

**Escenario:** Quieres compartir el plugin con clientes

```bash
# Paso 1: Preparar versión Full
composer install --no-dev --optimize-autoloader
./build-release.sh 1.0.0

# Paso 2: Probar el ZIP
# - Sube a un WordPress de prueba
# - Verifica que todo funciona

# Paso 3: Distribuir
# Opción A: Email
#   Adjunta: custom-certificates-full-v1.0.0.zip
#   Instrucciones: "Sube este plugin y actívalo"

# Opción B: Descarga directa
#   Sube a: https://tudominio.com/downloads/
#   Comparte el link

# Opción C: GitHub Release
#   git tag -a v1.0.0 -m "Release v1.0.0"
#   git push origin v1.0.0
#   Sube ZIP en GitHub Releases
```

---

## 🎓 Para Usuarios Finales

Cuando compartas el plugin, incluye estas instrucciones:

### Instalación Versión Full (Recomendada)

```
INSTALACIÓN DEL PLUGIN CUSTOM CERTIFICATES

1. Descarga: custom-certificates-full-v1.0.0.zip

2. En WordPress:
   - Ve a Plugins > Añadir nuevo
   - Click en "Subir plugin"
   - Selecciona el archivo ZIP
   - Click en "Instalar ahora"

3. Activar:
   - Click en "Activar plugin"

4. ¡Listo!
   - Ve a Certificados en el menú
   - Crea tu primera plantilla
   - Empieza a asignar certificados

No se requiere configuración técnica.
No se requiere Composer.
No se requiere SSH.
```

### Instalación Versión Lite

```
INSTALACIÓN DEL PLUGIN CUSTOM CERTIFICATES (Versión Lite)

1. Descarga: custom-certificates-lite-v1.0.0.zip

2. Sube y activa igual que la versión Full

3. Instalación de dependencias:
   - Aparecerá un aviso amarillo
   - Click en "Instalar Dependencias Automáticamente"
   - Espera 10-20 segundos
   - ¡Listo!

Requiere conexión a internet en el servidor.
```

---

## ❓ FAQ para Distribución

### ¿Puedo vender este plugin?

Sí, el plugin usa licencia GPL v2, puedes venderlo o distribuirlo libremente.

### ¿Debo incluir siempre vendor/?

**Para distribución comercial o a usuarios finales:** SÍ
**Para compartir con desarrolladores:** Opcional (pueden hacer composer install)

### ¿El auto-instalador siempre funciona?

Funciona en ~95% de servidores. Puede fallar si:
- No hay conexión a internet
- Firewall muy restrictivo
- Sin permisos de escritura

En esos casos, ofrece la versión Full.

### ¿Qué pasa con las actualizaciones?

Los usuarios pueden:
1. Desactivar plugin actual
2. Eliminar plugin actual
3. Subir nueva versión
4. Activar

O usar un plugin de actualización automática (si lo publicas en repositorio).

---

## 📊 Comparación de Versiones

| Característica | Full | Lite |
|---------------|------|------|
| Tamaño ZIP | ~18 MB | ~2 MB |
| Incluye vendor/ | ✅ Sí | ❌ No |
| Instalación inmediata | ✅ Sí | ⚠️ Requiere 1 paso extra |
| Requiere internet | ❌ No | ✅ Sí (solo al instalar) |
| Mejor para clientes | ✅ | ❌ |
| Mejor para desarrolladores | ❌ | ✅ |
| Compatible con todos los servidores | ✅ | ⚠️ 95% |

---

## 🎉 Resumen Ejecutivo

### Si quieres la opción MÁS SIMPLE para usuarios:

```bash
composer install --no-dev --optimize-autoloader
./build-release.sh 1.0.0
```

Distribuye: `custom-certificates-full-v1.0.0.zip`

**Los usuarios solo suben y activan. NADA MÁS.**

---

### Si quieres ofrecer AMBAS opciones:

```bash
./build-release.sh 1.0.0
```

Esto crea ambas versiones automáticamente.

Distribuye ambas y deja que el usuario elija.

---

**¿Dudas?** Lee la guía completa en `GUIA-DISTRIBUCION.md`

**Última actualización:** 2024-12-10
