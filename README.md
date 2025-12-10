# Custom Certificates for BuddyBoss

Sistema de certificados personalizados para WordPress que se integra perfectamente con BuddyBoss Platform, permitiendo otorgar certificados por razones diferentes a la finalización de cursos de LearnDash.

## Características

- ✅ **Independiente de LearnDash**: Certificados personalizados para cualquier propósito (competencias digitales, eventos, logros, etc.)
- ✅ **Integración con BuddyBoss**: Nueva pestaña "Certificados" en el perfil de usuario
- ✅ **Generación Dinámica de PDFs**: Los certificados se generan al momento de descarga, no se almacenan
- ✅ **Gestión Fácil**: Interfaz intuitiva en el administrador de WordPress
- ✅ **Asignación Masiva**: Asigna certificados a múltiples usuarios a la vez
- ✅ **Personalizable**: Configura colores, fuentes y diseño de certificados
- ✅ **Código de Verificación**: Cada certificado tiene un código único de verificación
- ✅ **Notificaciones por Email**: Notifica automáticamente a los usuarios cuando reciben un certificado

## Requisitos

- WordPress 5.8 o superior
- PHP 7.4 o superior
- BuddyBoss Platform o BuddyPress
- Composer (para instalar dependencias)

## Instalación

El plugin está disponible en **dos versiones**:

### 📦 Versión Full (Recomendada)

**Para usuarios que quieren instalación inmediata sin configuración técnica**

1. **Descarga** el archivo `custom-certificates-full-vX.X.X.zip`
2. **Sube el plugin:**
   - Ve a **Plugins > Añadir nuevo** en WordPress
   - Haz clic en **Subir plugin**
   - Selecciona el archivo ZIP
   - Haz clic en **Instalar ahora**
3. **Activa** el plugin
4. **¡Listo!** - Empieza a crear certificados

✅ Sin Composer, sin SSH, sin configuración técnica

### 💨 Versión Lite

**Para usuarios con servidor conectado a internet**

1. **Descarga** el archivo `custom-certificates-lite-vX.X.X.zip`
2. **Sube y activa** igual que la versión Full
3. **Instalación automática de dependencias:**
   - Aparecerá un aviso: "El plugin necesita instalar dependencias"
   - Haz clic en **"Instalar Dependencias Automáticamente"**
   - Espera 10-20 segundos
4. **¡Listo!** - Empieza a crear certificados

⚡ Archivo más ligero, instalación automática con un clic

---

### Instalación Manual (Solo para desarrolladores)

Si prefieres instalar desde el código fuente:

```bash
# 1. Clonar o descargar el repositorio
git clone https://github.com/tu-usuario/custom-certificates.git
cd custom-certificates

# 2. Instalar dependencias con Composer
composer install --no-dev --optimize-autoloader

# 3. Subir a WordPress
# Copia la carpeta completa a /wp-content/plugins/

# 4. Activar desde WordPress Admin
```

## Uso

### Crear una Plantilla de Certificado

1. Ve a **Certificados > Añadir Nueva** en el menú de administración
2. Dale un nombre a la plantilla (ej: "Competencias Digitales")
3. Sube una **imagen destacada** que será el fondo del certificado (recomendado: 297x210 mm en orientación horizontal)
4. Configura los colores y tamaños de fuente
5. Publica la plantilla

### Asignar Certificados

#### Asignación Individual o Masiva

1. Ve a **Certificados > Asignar Certificados**
2. Selecciona la **plantilla de certificado**
3. Busca y selecciona uno o varios **usuarios**
4. Opcionalmente, añade una **descripción adicional**
5. Haz clic en **Asignar Certificado(s)**

Los usuarios recibirán inmediatamente el certificado y podrán verlo en su perfil.

#### Importación por CSV (Próximamente)

En futuras versiones podrás importar asignaciones masivas mediante archivos CSV.

### Ver Certificados en el Perfil

Los usuarios pueden ver sus certificados en:

1. **Perfil de Usuario** > **Certificados** > **Mis Certificados**
2. Desde ahí pueden descargar el PDF haciendo clic en **Descargar PDF**

### Gestionar Certificados Asignados

1. Ve a **Certificados > Certificados Asignados**
2. Verás una lista de todos los certificados asignados
3. Puedes **editar**, **eliminar** o **descargar** cualquier certificado

## Personalización

### Plantillas de PDF Personalizadas

Puedes crear tu propia plantilla de PDF copiando el archivo:

```
plugins/custom-certificates/public/templates/certificate-pdf.php
```

A tu tema:

```
themes/tu-tema/custom-certificates/certificate-pdf.php
```

### Variables Disponibles

En tus plantillas puedes usar estas variables:

- `{NOMBRE_USUARIO}` - Nombre completo del usuario
- `{EMAIL_USUARIO}` - Email del usuario
- `{FECHA_EMISION}` - Fecha de emisión del certificado
- `{CODIGO_VERIFICACION}` - Código único de verificación
- `{NOMBRE_CERTIFICADO}` - Nombre de la plantilla

### Hooks y Filtros

#### Actions (Acciones)

```php
// Después de asignar un certificado
do_action('custom_cert_assigned', $certificate_id, $user_id, $template_id, $custom_data);

// Después de eliminar un certificado
do_action('custom_cert_removed', $certificate_id, $user_id, $template_id);

// Antes de generar el PDF
do_action('custom_cert_before_generate_pdf', $certificate_id, $user_id);
```

#### Filters (Filtros)

```php
// Modificar datos del certificado antes de generar PDF
apply_filters('custom_cert_pdf_data', $data, $certificate_id);

// Modificar el HTML del PDF
apply_filters('custom_cert_pdf_html', $html, $data);
```

### Ejemplo de Uso

```php
// Asignar certificado programáticamente
$certificate_id = custom_cert_assign_certificate(
    123, // User ID
    456, // Template ID
    array( // Custom data
        'description' => 'Por completar el curso de Competencias Digitales Avanzadas'
    )
);

// Verificar si un usuario tiene un certificado
if (custom_cert_user_has_certificate(123, 456)) {
    echo 'El usuario ya tiene este certificado';
}

// Obtener certificados de un usuario
$certificates = custom_cert_get_user_certificates(123);
foreach ($certificates as $cert) {
    echo $cert->post_title;
}
```

## Funciones Útiles

```php
// Obtener certificados de un usuario
custom_cert_get_user_certificates($user_id);

// Contar certificados de un usuario
custom_cert_count_user_certificates($user_id);

// Obtener URL de descarga
custom_cert_get_download_url($certificate_id);

// Verificar certificado por código
custom_cert_verify_certificate($code);

// Asignar certificado
custom_cert_assign_certificate($user_id, $template_id, $custom_data);

// Eliminar certificado
custom_cert_remove_certificate($certificate_id);
```

## Solución de Problemas

### El PDF no se genera

**Problema**: Al hacer clic en "Descargar PDF" aparece un error.

**Solución**:
1. Verifica que las dependencias estén instaladas: `composer install`
2. Verifica que la carpeta `vendor` existe en el directorio del plugin
3. Verifica los permisos de escritura en el servidor

### La pestaña no aparece en BuddyBoss

**Problema**: No veo la pestaña "Certificados" en el perfil.

**Solución**:
1. Verifica que BuddyBoss Platform o BuddyPress esté activo
2. Ve a **Configuración > Enlaces permanentes** y guarda de nuevo
3. Limpia la caché del navegador

### Los usuarios no ven sus certificados

**Problema**: Los certificados están asignados pero los usuarios no los ven.

**Solución**:
1. Verifica que el certificado esté **publicado** (no en borrador)
2. Verifica que el usuario correcto esté asignado en los metadatos
3. Limpia la caché si usas un plugin de caché

## Estructura de Archivos

```
custom-certificates/
├── custom-certificates.php       # Archivo principal del plugin
├── composer.json                 # Dependencias de Composer
├── README.md                     # Este archivo
├── includes/                     # Clases principales
│   ├── class-certificate-post-type.php
│   ├── class-certificate-assignment.php
│   ├── class-pdf-generator.php
│   ├── class-buddyboss-integration.php
│   ├── class-admin-interface.php
│   └── functions.php
├── admin/                        # Interfaz de administración
│   ├── views/
│   ├── css/
│   └── js/
├── public/                       # Templates y assets públicos
│   ├── templates/
│   ├── css/
│   └── js/
├── assets/                       # Recursos del plugin
│   └── certificate-templates/
└── languages/                    # Traducciones
```

## Roadmap

### Versión 1.1 (Próximamente)
- [ ] Importación masiva por CSV
- [ ] Editor visual de plantillas (drag & drop)
- [ ] Página pública de verificación de certificados
- [ ] Más opciones de personalización

### Versión 1.2
- [ ] Dashboard de estadísticas
- [ ] Certificados con fecha de expiración
- [ ] Integración con GamiPress
- [ ] API REST

## Soporte

Si encuentras algún problema o tienes alguna sugerencia:

1. Verifica la sección de **Solución de Problemas** en este README
2. Revisa la documentación completa en `PLAN-CERTIFICADOS-PERSONALIZADOS.md`
3. Contacta con el desarrollador

## Changelog

### Versión 1.0.0 (2024-12-10)
- ✨ Lanzamiento inicial
- ✅ Custom Post Types para plantillas y certificados
- ✅ Integración con BuddyBoss Profile tabs
- ✅ Generación dinámica de PDFs con mPDF
- ✅ Interfaz de administración completa
- ✅ Asignación individual y masiva
- ✅ Sistema de notificaciones por email
- ✅ Códigos de verificación únicos

## Créditos

Desarrollado por [Tu Nombre]

Librerías utilizadas:
- [mPDF](https://mpdf.github.io/) para generación de PDFs
- [Select2](https://select2.org/) para búsqueda de usuarios

## Licencia

GPL v2 or later

---

**¿Te gusta el plugin?** ⭐ ¡Dale una estrella en GitHub!
