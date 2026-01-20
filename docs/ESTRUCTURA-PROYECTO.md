# Estructura del Proyecto - Custom Certificates

## Árbol de Archivos

```
custom-certificates/
│
├── 📄 custom-certificates.php          # Archivo principal del plugin
├── 📄 composer.json                    # Dependencias (mPDF)
├── 📄 LICENSE.txt                      # Licencia GPL v2
├── 📄 .gitignore                       # Archivos ignorados por Git
│
├── 📚 DOCUMENTACIÓN
│   ├── README.md                       # Documentación principal
│   ├── INSTALACION.md                  # Guía de instalación detallada
│   ├── INICIO-RAPIDO.md                # Tutorial rápido 5 minutos
│   ├── PLAN-CERTIFICADOS-PERSONALIZADOS.md  # Plan completo del proyecto
│   ├── CHANGELOG.md                    # Historial de versiones
│   └── ESTRUCTURA-PROYECTO.md          # Este archivo
│
├── 📁 includes/                        # Clases principales del plugin
│   ├── class-certificate-post-type.php     # Custom Post Types
│   ├── class-certificate-assignment.php    # Lógica de asignación
│   ├── class-pdf-generator.php             # Generación de PDFs
│   ├── class-buddyboss-integration.php     # Integración BuddyBoss
│   ├── class-admin-interface.php           # Interfaz de administración
│   └── functions.php                       # Funciones helper globales
│
├── 📁 admin/                           # Interfaz de administración
│   ├── 📁 views/
│   │   ├── assign-certificates.php         # Página de asignación
│   │   └── settings.php                    # Página de configuración
│   ├── 📁 css/
│   │   └── admin.css                       # Estilos del admin
│   └── 📁 js/
│       └── admin.js                        # JavaScript del admin
│
├── 📁 public/                          # Frontend (usuarios)
│   ├── 📁 templates/
│   │   └── certificate-pdf.php             # Template PDF personalizable
│   ├── 📁 css/
│   └── 📁 js/
│
├── 📁 assets/                          # Recursos del plugin
│   └── 📁 certificate-templates/           # Plantillas de ejemplo
│
├── 📁 languages/                       # Traducciones
│   └── (archivos .po/.mo)
│
└── 📁 vendor/                          # Dependencias de Composer
    └── (se crea con composer install)
```

## Descripción de Componentes

### 🔧 Archivo Principal

**`custom-certificates.php`**
- Punto de entrada del plugin
- Registra hooks de activación/desactivación
- Carga todas las clases
- Inicializa componentes

### 📦 Clases Principales (includes/)

#### `class-certificate-post-type.php`
**Responsabilidad**: Gestión de Custom Post Types
- Registra `bb_cert_template` (plantillas)
- Registra `bb_cert_assigned` (certificados asignados)
- Registra taxonomía `cert_category`
- Define mensajes personalizados

#### `class-certificate-assignment.php`
**Responsabilidad**: Lógica de asignación de certificados
- `assign_certificate()` - Asignar a un usuario
- `assign_certificate_bulk()` - Asignación masiva
- `remove_certificate()` - Eliminar asignación
- `get_user_certificates()` - Obtener certificados de usuario
- `verify_certificate()` - Verificar por código
- Maneja peticiones AJAX

#### `class-pdf-generator.php`
**Responsabilidad**: Generación de PDFs
- `generate_and_download()` - Genera PDF y lo envía al navegador
- `get_certificate_data()` - Obtiene datos del certificado
- `generate_html()` - Genera HTML del certificado
- Carga librería mPDF
- Maneja templates personalizables
- **NO guarda PDFs** en servidor (generación on-demand)

#### `class-buddyboss-integration.php`
**Responsabilidad**: Integración con BuddyBoss
- Crea pestaña "Certificados" en perfiles
- Renderiza listado de certificados del usuario
- Maneja estilos del tab
- Verifica compatibilidad con BuddyBoss/BuddyPress

#### `class-admin-interface.php`
**Responsabilidad**: Interfaz de administración
- Añade menús y submenús
- Meta boxes de configuración
- Columnas personalizadas en listas
- Página de asignación de certificados
- Página de configuración
- Enqueue de scripts y estilos

#### `functions.php`
**Responsabilidad**: Funciones helper globales
- `custom_cert_get_user_certificates()`
- `custom_cert_assign_certificate()`
- `custom_cert_get_download_url()`
- `custom_cert_send_notification()`
- Y más...

### 🎨 Admin (admin/)

#### Views
- **`assign-certificates.php`**: Formulario de asignación con Select2
- **`settings.php`**: Configuración de notificaciones

#### CSS
- **`admin.css`**: Estilos para interfaz de admin, Select2, tablas

#### JavaScript
- **`admin.js`**: AJAX para asignación, búsqueda de usuarios con Select2

### 👤 Public (public/)

#### Templates
- **`certificate-pdf.php`**: Template HTML/CSS para el PDF
  - Personalizable copiando a tema
  - Usa variables de datos del certificado
  - Soporte para imágenes de fondo

### 📚 Documentación

- **`README.md`**: Documentación completa del plugin
- **`INSTALACION.md`**: Guía paso a paso de instalación
- **`INICIO-RAPIDO.md`**: Tutorial de 5 minutos
- **`PLAN-CERTIFICADOS-PERSONALIZADOS.md`**: Plan detallado del proyecto
- **`CHANGELOG.md`**: Historial de cambios

## Flujo de Datos

### Asignación de Certificado

```
[Admin Panel]
    ↓ (Usuario selecciona plantilla y usuarios)
[admin.js] → AJAX Request
    ↓
[class-certificate-assignment.php]
    → assign_certificate()
    → Crea post 'bb_cert_assigned'
    → Genera código de verificación
    → Guarda metadatos
    ↓
[functions.php]
    → custom_cert_send_notification()
    → Envía email al usuario
    ↓
[Hook: custom_cert_assigned]
```

### Descarga de PDF

```
[User Profile]
    ↓ (Click en "Descargar PDF")
[Download URL con nonce]
    ↓
[class-pdf-generator.php]
    → handle_pdf_download()
    → Verifica permisos
    → get_certificate_data()
    → generate_html()
    ↓
[mPDF Library]
    → Genera PDF en memoria
    → Output con headers de descarga
    ↓
[Navegador del Usuario]
    → Descarga el archivo PDF
```

### Visualización en Perfil

```
[BuddyBoss Profile]
    ↓
[class-buddyboss-integration.php]
    → setup_nav() (crea tab)
    → my_certificates_content()
    ↓
[class-certificate-assignment.php]
    → get_user_certificates()
    ↓
[Template]
    → Renderiza certificados
    → Muestra botón de descarga
```

## Base de Datos

### Custom Post Types

**wp_posts** (con post_type = 'bb_cert_template')
```
ID | post_title | post_content | post_status
---|------------|--------------|-------------
1  | Cert. Dig. | Descripción  | publish
```

**wp_posts** (con post_type = 'bb_cert_assigned')
```
ID | post_title      | post_author | post_date
---|-----------------|-------------|----------
10 | Cert #ABC - Juan| 5 (user_id) | 2024-12-10
```

### Post Meta

**wp_postmeta** (para bb_cert_template)
```
post_id | meta_key      | meta_value
--------|---------------|------------------
1       | _cert_config  | {"text_color":"#000",...}
1       | _thumbnail_id | 123
```

**wp_postmeta** (para bb_cert_assigned)
```
post_id | meta_key                | meta_value
--------|-------------------------|------------
10      | _cert_user_id           | 5
10      | _cert_template_id       | 1
10      | _cert_verification_code | ABC123XYZ
10      | _cert_issue_date        | 2024-12-10 15:30:00
10      | _cert_custom_data       | serialized array
```

### Taxonomías

**wp_term_taxonomy** (taxonomy = 'cert_category')
```
term_id | taxonomy      | description
--------|---------------|------------------
1       | cert_category | Competencias
2       | cert_category | Liderazgo
```

## Hooks Disponibles

### Actions

```php
// Cuando se inicializa el plugin
do_action('custom_cert_init');

// Cuando se activa el plugin
do_action('custom_cert_activated');

// Cuando se desactiva el plugin
do_action('custom_cert_deactivated');

// Después de asignar certificado
do_action('custom_cert_assigned', $certificate_id, $user_id, $template_id, $custom_data);

// Después de eliminar certificado
do_action('custom_cert_removed', $certificate_id, $user_id, $template_id);

// Antes de generar PDF
do_action('custom_cert_before_generate_pdf', $certificate_id, $user_id);
```

### Filters

```php
// Modificar datos del certificado
$data = apply_filters('custom_cert_pdf_data', $data, $certificate_id);

// Modificar HTML del PDF
$html = apply_filters('custom_cert_pdf_html', $html, $data);
```

## Seguridad Implementada

- ✅ Nonces en todos los formularios
- ✅ Capability checks (`current_user_can()`)
- ✅ Sanitización de inputs (`sanitize_text_field()`, etc.)
- ✅ Escapado de outputs (`esc_html()`, `esc_url()`, etc.)
- ✅ Prepared statements para queries (usa WordPress APIs)
- ✅ Verificación de permisos en descarga de PDFs
- ✅ CSRF protection en AJAX

## Dependencias

### PHP (vía Composer)
- **mPDF v8.1+**: Generación de PDFs

### JavaScript (vía CDN)
- **Select2 4.1.0**: Búsqueda avanzada de usuarios
- **jQuery**: Incluido en WordPress

### WordPress
- WordPress 5.8+
- BuddyBoss Platform o BuddyPress

## Rendimiento

### Optimizaciones
- PDFs generados on-demand (no se almacenan)
- AJAX para operaciones sin recargar página
- Queries optimizadas usando WordPress APIs
- Lazy loading de Select2 (búsqueda bajo demanda)

### Posibles Mejoras Futuras
- Caché de templates de certificados
- Generación asíncrona para asignaciones masivas
- CDN para assets estáticos

## Internacionalización

- Text domain: `custom-certificates`
- Todos los strings traducibles
- Carpeta `/languages/` para archivos .po/.mo
- Compatible con WPML/Polylang (sin conflictos)

## Testing

### Manual
Ver `INICIO-RAPIDO.md` para casos de prueba básicos

### Automatizado (Futuro)
- PHPUnit para tests unitarios
- WP Browser para tests de integración

---

**Última actualización**: 2024-12-10
**Versión del plugin**: 1.0.0
