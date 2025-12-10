# Guía de Instalación Rápida

## Requisitos Previos

Antes de instalar el plugin, asegúrate de tener:

- [x] WordPress 5.8 o superior instalado
- [x] PHP 7.4 o superior
- [x] BuddyBoss Platform o BuddyPress activado
- [x] Acceso SSH o FTP al servidor
- [x] Composer instalado (para dependencias)

## Pasos de Instalación

### Paso 1: Subir el Plugin

Existen dos formas de subir el plugin a WordPress:

#### Opción A: Usando FTP/SFTP

1. Comprime la carpeta `custom-certificates` en un archivo ZIP
2. Conecta a tu servidor vía FTP/SFTP
3. Sube la carpeta a `/wp-content/plugins/`

#### Opción B: Usando el Panel de WordPress

1. Comprime la carpeta `custom-certificates` en un archivo ZIP
2. Ve a **Plugins > Añadir nuevo** en WordPress
3. Haz clic en **Subir plugin**
4. Selecciona el archivo ZIP y haz clic en **Instalar ahora**

### Paso 2: Instalar Dependencias (IMPORTANTE)

El plugin requiere la librería mPDF para generar PDFs. Debes instalarla usando Composer:

#### Si tienes acceso SSH:

```bash
# Navega al directorio del plugin
cd /ruta/a/wordpress/wp-content/plugins/custom-certificates

# Instala las dependencias
composer install --no-dev
```

#### Si NO tienes acceso SSH:

1. Instala las dependencias en tu máquina local:
   ```bash
   composer install --no-dev
   ```

2. Sube la carpeta `vendor` generada al servidor mediante FTP/SFTP
   - Destino: `/wp-content/plugins/custom-certificates/vendor/`

### Paso 3: Activar el Plugin

1. Ve a **Plugins** en el panel de WordPress
2. Busca "Custom Certificates for BuddyBoss"
3. Haz clic en **Activar**

### Paso 4: Verificar Instalación

Después de activar, deberías ver:

- ✅ Nuevo menú "Certificados" en el panel de administración
- ✅ Sin errores en la página
- ✅ La pestaña "Certificados" en los perfiles de BuddyBoss

### Paso 5: Configuración Inicial

#### 5.1 Crear tu Primera Plantilla

1. Ve a **Certificados > Añadir nueva**
2. Título: "Certificado de Competencias Digitales" (o el nombre que prefieras)
3. **Imagen destacada**: Sube una imagen de fondo (recomendado 297x210 mm, orientación horizontal)
4. Configura colores y fuentes en la caja "Configuración del Certificado"
5. Haz clic en **Publicar**

#### 5.2 Configurar Notificaciones (Opcional)

1. Ve a **Certificados > Configuración**
2. Activa la casilla "Enviar email al usuario cuando recibe un certificado"
3. Personaliza el asunto y mensaje del email
4. Guarda los cambios

#### 5.3 Asignar tu Primer Certificado

1. Ve a **Certificados > Asignar Certificados**
2. Selecciona la plantilla que creaste
3. Busca un usuario de prueba
4. Haz clic en **Asignar Certificado(s)**

#### 5.4 Verificar el Certificado

1. Ve al perfil del usuario asignado
2. Haz clic en la pestaña **Certificados**
3. Deberías ver el certificado asignado
4. Haz clic en **Descargar PDF** para verificar que funciona

## Solución de Problemas Comunes

### Error: "La librería mPDF no está instalada"

**Causa**: No se instalaron las dependencias de Composer.

**Solución**:
```bash
cd /wp-content/plugins/custom-certificates
composer install --no-dev
```

### La pestaña "Certificados" no aparece en BuddyBoss

**Causa**: Enlaces permanentes no actualizados.

**Solución**:
1. Ve a **Configuración > Enlaces permanentes**
2. Haz clic en **Guardar cambios** (sin cambiar nada)
3. Limpia la caché del navegador

### Error 500 al activar el plugin

**Causa**: Versión de PHP incompatible.

**Solución**:
1. Verifica la versión de PHP: `php -v`
2. Actualiza a PHP 7.4 o superior
3. Contacta con tu proveedor de hosting si no puedes actualizar

### Los PDFs no se descargan

**Causa**: Permisos de escritura o mPDF no cargado.

**Solución**:
1. Verifica que la carpeta `vendor` existe
2. Verifica permisos de lectura en `/wp-content/plugins/custom-certificates/`
3. Revisa el log de errores de WordPress

### Error: "No tienes permisos"

**Causa**: El usuario actual no tiene el rol adecuado.

**Solución**:
- Solo usuarios con rol de **Administrador** pueden asignar certificados
- Los usuarios normales solo pueden ver y descargar SUS propios certificados

## Verificación Final

Usa esta checklist para verificar que todo funciona correctamente:

- [ ] Plugin activado sin errores
- [ ] Menú "Certificados" visible en admin
- [ ] Carpeta `vendor` existe con mPDF instalado
- [ ] Puedes crear una plantilla de certificado
- [ ] Puedes asignar un certificado a un usuario
- [ ] La pestaña "Certificados" aparece en perfiles de BuddyBoss
- [ ] Los usuarios pueden ver sus certificados
- [ ] Los PDFs se descargan correctamente
- [ ] Las notificaciones por email funcionan (si están activadas)

## Próximos Pasos

Una vez instalado exitosamente:

1. **Lee el [README.md](README.md)** para aprender sobre todas las características
2. **Revisa el [PLAN-CERTIFICADOS-PERSONALIZADOS.md](PLAN-CERTIFICADOS-PERSONALIZADOS.md)** para entender la arquitectura
3. **Personaliza las plantillas de PDF** según tus necesidades
4. **Configura las notificaciones** para tus usuarios

## Soporte

Si tienes problemas durante la instalación:

1. Revisa los logs de error de WordPress: `/wp-content/debug.log`
2. Verifica los requisitos del servidor
3. Contacta con el desarrollador

## Desinstalación

Si necesitas desinstalar el plugin:

1. Ve a **Plugins** en WordPress
2. Desactiva "Custom Certificates for BuddyBoss"
3. Haz clic en **Eliminar**

**Nota**: Los certificados asignados se eliminarán permanentemente. Haz un backup antes de desinstalar.

---

**¿Todo funcionando?** ¡Excelente! Ahora puedes empezar a asignar certificados a tus usuarios. 🎉
