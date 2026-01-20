# Plan de Implementación: Sistema de Certificados Personalizados

## 1. Resumen Ejecutivo

### Objetivo
Crear un sistema de certificados personalizados para WordPress que funcione junto a BuddyBoss y LearnDash, permitiendo otorgar certificados por razones diferentes a la finalización de cursos (ej: competencias digitales, eventos, logros especiales).

### Solución Propuesta
Desarrollar un **plugin personalizado de WordPress** que:
- No modifica el tema BuddyBoss
- Es independiente de LearnDash
- Se integra perfectamente con BuddyBoss Profile tabs
- Genera PDFs dinámicamente (no almacenados)
- Permite gestión fácil desde el administrador

---

## 2. Arquitectura de la Solución

### 2.1. Componentes Principales

```
custom-certificates/
├── custom-certificates.php           (Plugin principal)
├── includes/
│   ├── class-certificate-post-type.php    (CPT de certificados)
│   ├── class-certificate-assignment.php   (Lógica de asignación)
│   ├── class-pdf-generator.php            (Generación de PDFs)
│   ├── class-buddyboss-integration.php    (Tab de perfil)
│   └── class-admin-interface.php          (Interfaz admin)
├── admin/
│   ├── css/
│   ├── js/
│   └── views/                              (Vistas del admin)
├── public/
│   ├── css/
│   ├── js/
│   └── templates/                          (Templates de certificados)
└── assets/
    └── certificate-templates/              (Plantillas de fondo PDF)
```

### 2.2. Custom Post Types

**Tipo 1: Plantilla de Certificado (`bb_cert_template`)**
- Nombre del certificado
- Plantilla de fondo (imagen/PDF)
- Campos personalizables (nombre, fecha, competencia, etc.)
- Estilo y posicionamiento de texto
- Estado (activo/inactivo)

**Tipo 2: Certificado Asignado (`bb_cert_assigned`)**
- ID del usuario
- ID de la plantilla
- Fecha de emisión
- Datos personalizados (ej: "Competencias Digitales")
- Código único de verificación
- Estado

---

## 3. Funcionalidades Detalladas

### 3.1. Panel de Administración

#### Gestión de Plantillas
- **Crear/Editar plantillas de certificados**
  - Nombre de la plantilla
  - Upload de imagen de fondo
  - Editor visual para posicionar elementos:
    - {NOMBRE_USUARIO}
    - {FECHA_EMISION}
    - {TIPO_CERTIFICADO}
    - {CODIGO_VERIFICACION}
  - Configuración de fuentes, colores, tamaños

#### Asignación de Certificados
- **Búsqueda y selección de usuarios**
  - Búsqueda por nombre, email
  - Selección múltiple
  - Filtros (roles, grupos de BuddyBoss)

- **Asignación masiva**
  - Seleccionar plantilla
  - Seleccionar usuarios
  - Establecer fecha de emisión
  - Notas adicionales (opcional)
  - Botón "Asignar Certificados"

- **Listado de certificados asignados**
  - Tabla con: Usuario, Certificado, Fecha, Acciones
  - Filtros y búsqueda
  - Acciones: Ver, Descargar PDF, Eliminar

#### Importación Masiva
- **Upload de CSV**
  - Columnas: email, tipo_certificado, fecha_emision
  - Validación de datos
  - Preview antes de confirmar
  - Log de resultados

### 3.2. Integración con BuddyBoss

#### Nueva Pestaña en Perfil
- **Tab "Certificados"** (bb_custom_certificates)
  - Separada de la pestaña de Cursos/LearnDash
  - Listado de certificados del usuario
  - Grid o lista con:
    - Miniatura del certificado
    - Nombre del certificado
    - Fecha de emisión
    - Botón "Descargar PDF"
  - Mensaje cuando no hay certificados
  - Diseño responsive

#### Visibilidad
- El usuario solo ve sus propios certificados en su perfil
- Los demás usuarios pueden ver los certificados según configuración de privacidad
- Admin puede ver todos los certificados de cualquier usuario

### 3.3. Generación de PDFs

#### Biblioteca Recomendada
**mPDF** o **TCPDF** (bibliotecas PHP populares para PDFs)

#### Proceso de Generación
1. Usuario hace clic en "Descargar"
2. Plugin obtiene datos del certificado y usuario
3. Carga la plantilla de fondo
4. Superpone datos personalizados
5. Genera PDF en memoria
6. Envía al navegador con headers de descarga
7. **No se guarda en servidor**

#### Estructura del PDF
```php
// Pseudocódigo
$pdf = new mPDF();
$pdf->AddPage();
$pdf->WriteHTML($html_template);
$pdf->Output('Certificado-{NOMBRE}.pdf', 'D'); // D = Download
```

#### Elementos del Certificado
- Imagen de fondo (template)
- Nombre del usuario (desde WordPress)
- Fecha de emisión
- Tipo de certificado
- Código de verificación único
- Logo institucional (opcional)
- Firma digital (opcional)

---

## 4. Base de Datos

### 4.1. Custom Post Types (usa wp_posts)

**`bb_cert_template`** - Plantillas de certificados
- post_title: Nombre de la plantilla
- post_content: Configuración JSON
- post_status: publish/draft
- post_meta:
  - `_cert_background_image_id`
  - `_cert_config` (JSON con posiciones, fuentes, etc.)

**`bb_cert_assigned`** - Certificados asignados
- post_title: "Certificado #{ID} - {Usuario}"
- post_author: ID del usuario que recibe
- post_date: Fecha de emisión
- post_meta:
  - `_cert_template_id`
  - `_cert_user_id`
  - `_cert_verification_code`
  - `_cert_custom_data` (JSON)

### 4.2. Tablas Personalizadas (opcional)

Si necesitas búsquedas complejas, podrías crear:

```sql
CREATE TABLE {prefix}_custom_certificates (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) NOT NULL,
    template_id bigint(20) NOT NULL,
    issue_date datetime NOT NULL,
    verification_code varchar(50) NOT NULL,
    custom_data longtext,
    created_at datetime NOT NULL,
    PRIMARY KEY (id),
    KEY user_id (user_id),
    KEY template_id (template_id),
    KEY verification_code (verification_code)
);
```

**Recomendación**: Empezar con Custom Post Types (más nativo de WordPress) y solo crear tabla personalizada si el rendimiento lo requiere.

---

## 5. Tecnologías y Librerías

### Backend
- **PHP 7.4+**
- **WordPress 5.8+**
- **BuddyBoss Platform API**
- **mPDF** o **TCPDF** para generación de PDFs
- **WordPress REST API** (para funcionalidades AJAX)

### Frontend
- **HTML5/CSS3**
- **JavaScript vanilla** o **jQuery** (ya incluido en WordPress)
- **Select2** (para búsqueda de usuarios)
- **SortableJS** (para ordenar certificados, si es necesario)

### Seguridad
- **WordPress Nonces** (para formularios)
- **Sanitización y validación** de datos
- **Capability checks** (current_user_can)
- **Prepared statements** para queries

---

## 6. Flujos de Trabajo

### 6.1. Flujo de Asignación de Certificados

```
[Admin] → Panel de Certificados
    ↓
Selecciona plantilla de certificado
    ↓
Busca/Selecciona usuarios (individual o masivo)
    ↓
Confirma asignación
    ↓
Sistema crea registros en BD
    ↓
(Opcional) Envía email de notificación
    ↓
Certificados disponibles en perfil de usuarios
```

### 6.2. Flujo de Descarga de Certificado

```
[Usuario] → Perfil → Tab "Certificados"
    ↓
Ve listado de sus certificados
    ↓
Click en "Descargar PDF"
    ↓
Sistema verifica permisos
    ↓
Genera PDF dinámicamente
    ↓
PDF se descarga en el navegador
```

### 6.3. Flujo de Verificación (Opcional pero recomendado)

```
[Tercero] → Página de verificación
    ↓
Ingresa código de verificación
    ↓
Sistema busca certificado
    ↓
Muestra: Usuario, Tipo, Fecha, Estado
```

---

## 7. Integración con BuddyBoss

### 7.1. Registro de Tab Personalizado

```php
// En class-buddyboss-integration.php
function register_certificates_tab() {
    bp_core_new_nav_item(array(
        'name'                => __('Certificados', 'custom-certificates'),
        'slug'                => 'custom-certificates',
        'screen_function'     => 'custom_certificates_screen',
        'position'            => 80,
        'default_subnav_slug' => 'my-certificates'
    ));
}
add_action('bp_setup_nav', 'register_certificates_tab', 100);
```

### 7.2. Template Override

El plugin proporcionará templates que se pueden sobreescribir:
- `plugins/custom-certificates/public/templates/profile-certificates.php`

Los usuarios avanzados pueden copiar a:
- `themes/buddyboss-theme-child/custom-certificates/profile-certificates.php`

---

## 8. Características Adicionales (Opcionales)

### 8.1. Sistema de Notificaciones
- Email al usuario cuando recibe un certificado
- Notificación en BuddyBoss (bell icon)
- Template de email personalizable

### 8.2. Verificación Pública
- Página pública: `/verificar-certificado/`
- Formulario para ingresar código
- Muestra información del certificado sin datos sensibles

### 8.3. Estadísticas
- Dashboard con métricas:
  - Total de certificados emitidos
  - Certificados por tipo
  - Certificados por mes
  - Usuarios con más certificados

### 8.4. Expiración de Certificados
- Algunos certificados pueden tener fecha de vencimiento
- Badge de "Vigente" o "Vencido"
- Notificación antes del vencimiento

### 8.5. Gamificación
- Integración con plugins de badges (ej: GamiPress)
- Achievements por cantidad de certificados
- Leaderboard

---

## 9. Plan de Desarrollo (Fases)

### Fase 1: MVP (Mínimo Producto Viable) - 2-3 semanas
**Objetivo**: Funcionalidad básica operativa

- [x] Estructura del plugin
- [x] Custom Post Type para plantillas
- [x] Custom Post Type para certificados asignados
- [x] Panel admin básico para asignación
- [x] Integración de tab en BuddyBoss
- [x] Generación básica de PDF
- [x] Descarga de certificados

### Fase 2: Mejoras de UX - 1-2 semanas
**Objetivo**: Mejorar experiencia de usuario

- [ ] Búsqueda avanzada de usuarios
- [ ] Asignación masiva (selección múltiple)
- [ ] Diseño mejorado del tab de certificados
- [ ] Importación por CSV
- [ ] Notificaciones por email

### Fase 3: Personalización - 1-2 semanas
**Objetivo**: Flexibilidad en diseño

- [ ] Editor visual de plantillas
- [ ] Múltiples plantillas de certificados
- [ ] Campos personalizables
- [ ] Preview antes de asignar

### Fase 4: Features Avanzadas - 2-3 semanas
**Objetivo**: Funcionalidades premium

- [ ] Sistema de verificación pública
- [ ] Dashboard de estadísticas
- [ ] Certificados con expiración
- [ ] Integración con GamiPress
- [ ] API REST para integraciones externas

---

## 10. Consideraciones Técnicas

### 10.1. Rendimiento

**Problema**: Generación de PDFs puede ser lenta
**Solución**:
- Optimizar imágenes de fondo
- Usar caching de templates
- Limitar tamaño de imágenes
- Considerar generación asíncrona para asignaciones masivas

### 10.2. Seguridad

**Riesgos**:
- Descarga de certificados de otros usuarios
- Inyección de código en templates
- Upload de archivos maliciosos

**Mitigaciones**:
- Verificar permisos en cada descarga
- Sanitizar todos los inputs
- Validar tipos de archivo (solo JPG/PNG para fondos)
- Usar nonces en todos los formularios
- Capability checks en admin

### 10.3. Compatibilidad

**Probar con**:
- WordPress 5.8+, 6.x
- BuddyBoss Theme 2.x
- BuddyBoss Platform 2.x
- LearnDash 4.x
- PHP 7.4, 8.0, 8.1, 8.2

**Posibles conflictos**:
- Otros plugins de certificados
- Plugins de PDF
- Cache plugins (WP Rocket, W3 Total Cache)

### 10.4. Internacionalización

- Todos los strings deben ser traducibles
- Usar text domain: `custom-certificates`
- Generar archivo .pot
- Soportar RTL languages

---

## 11. Estructura del Plugin Principal

```php
<?php
/**
 * Plugin Name: Custom Certificates for BuddyBoss
 * Plugin URI: https://tudominio.com
 * Description: Sistema de certificados personalizados independiente de LearnDash
 * Version: 1.0.0
 * Author: Tu Nombre
 * Text Domain: custom-certificates
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires at least: 5.8
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CUSTOM_CERT_VERSION', '1.0.0');
define('CUSTOM_CERT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CUSTOM_CERT_PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoloader
spl_autoload_register(function ($class) {
    // Implementar autoloader
});

// Initialize plugin
function custom_certificates_init() {
    // Load classes
    require_once CUSTOM_CERT_PLUGIN_DIR . 'includes/class-certificate-post-type.php';
    require_once CUSTOM_CERT_PLUGIN_DIR . 'includes/class-certificate-assignment.php';
    require_once CUSTOM_CERT_PLUGIN_DIR . 'includes/class-pdf-generator.php';
    require_once CUSTOM_CERT_PLUGIN_DIR . 'includes/class-buddyboss-integration.php';
    require_once CUSTOM_CERT_PLUGIN_DIR . 'includes/class-admin-interface.php';

    // Initialize components
    Custom_Cert_Post_Type::init();
    Custom_Cert_Assignment::init();
    Custom_Cert_BuddyBoss::init();
    Custom_Cert_Admin::init();
}
add_action('plugins_loaded', 'custom_certificates_init');

// Activation hook
register_activation_hook(__FILE__, 'custom_certificates_activate');
function custom_certificates_activate() {
    // Crear tablas si es necesario
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'custom_certificates_deactivate');
function custom_certificates_deactivate() {
    flush_rewrite_rules();
}
```

---

## 12. Testing

### 12.1. Testing Manual

**Casos de prueba**:
1. Crear plantilla de certificado
2. Asignar certificado a un usuario
3. Asignar certificados a múltiples usuarios
4. Ver certificados en perfil de usuario
5. Descargar PDF de certificado
6. Verificar que el PDF contiene datos correctos
7. Intentar descargar certificado de otro usuario (debe fallar)
8. Eliminar asignación de certificado
9. Importar certificados por CSV

### 12.2. Testing Automatizado (Opcional)

```php
// PHPUnit tests
class Test_Certificate_Assignment extends WP_UnitTestCase {
    public function test_assign_certificate_to_user() {
        $user_id = $this->factory->user->create();
        $template_id = $this->factory->post->create([
            'post_type' => 'bb_cert_template'
        ]);

        $assigned = assign_certificate($user_id, $template_id);

        $this->assertTrue($assigned);
        $this->assertEquals(1, count_user_certificates($user_id));
    }
}
```

---

## 13. Documentación

### 13.1. Para Administradores

**Manual de usuario** (en WordPress admin):
- Cómo crear una plantilla
- Cómo asignar certificados
- Cómo importar por CSV
- Solución de problemas comunes

### 13.2. Para Desarrolladores

**Developer documentation**:
- Hooks disponibles
- Filtros para personalizar
- Cómo extender el plugin
- API de funciones

**Hooks importantes**:
```php
// Antes de generar PDF
do_action('custom_cert_before_generate_pdf', $certificate_id, $user_id);

// Modificar datos del certificado
apply_filters('custom_cert_pdf_data', $data, $certificate_id, $user_id);

// Después de asignar certificado
do_action('custom_cert_assigned', $certificate_id, $user_id, $template_id);
```

---

## 14. Roadmap Futuro

### Versión 2.0
- [ ] Editor drag-and-drop para diseño de certificados
- [ ] Múltiples idiomas en certificados
- [ ] Firma digital con blockchain
- [ ] App móvil companion

### Versión 3.0
- [ ] Certificados con video/multimedia
- [ ] Integración con LinkedIn Learning
- [ ] Badges NFT en blockchain
- [ ] API pública para terceros

---

## 15. Presupuesto Estimado (Referencia)

| Fase | Tiempo | Desarrollador Junior | Desarrollador Senior |
|------|--------|---------------------|---------------------|
| Fase 1 (MVP) | 2-3 semanas | $2,000 - $3,000 | $4,000 - $6,000 |
| Fase 2 (UX) | 1-2 semanas | $1,000 - $2,000 | $2,000 - $4,000 |
| Fase 3 (Personalización) | 1-2 semanas | $1,000 - $2,000 | $2,000 - $4,000 |
| Fase 4 (Avanzado) | 2-3 semanas | $2,000 - $3,000 | $4,000 - $6,000 |
| **Total** | **6-10 semanas** | **$6,000 - $10,000** | **$12,000 - $20,000** |

*Nota: Precios referenciales en USD. Varían según región y experiencia.*

---

## 16. Conclusiones y Recomendaciones

### ✅ Por qué esta solución es ideal:

1. **Mantenibilidad**: Plugin independiente, fácil de mantener
2. **Escalabilidad**: Arquitectura modular, fácil de extender
3. **Compatibilidad**: No interfiere con BuddyBoss ni LearnDash
4. **Flexibilidad**: Certificados para cualquier propósito
5. **UX**: Integración nativa con BuddyBoss
6. **Performance**: PDFs generados on-demand, no ocupan espacio

### 📋 Próximos Pasos Inmediatos:

1. **Aprobar este plan** y ajustar según necesidades específicas
2. **Definir prioridades**: ¿Qué features son must-have vs nice-to-have?
3. **Diseñar mockups**: Crear diseños visuales de la interfaz admin y user
4. **Crear template de certificado**: Diseñar la primera plantilla en Illustrator/Photoshop
5. **Setup de entorno de desarrollo**: Preparar WordPress local con BuddyBoss
6. **Iniciar Fase 1**: Comenzar desarrollo del MVP

### ⚠️ Advertencias:

- **No modificar core de BuddyBoss**: Siempre usar hooks y filtros
- **Testing exhaustivo**: Probar en staging antes de producción
- **Backup**: Siempre tener backup antes de instalar
- **Documentar cambios**: Mantener changelog actualizado

---

## 17. Recursos Útiles

### Documentación Oficial
- [BuddyBoss Developer Docs](https://www.buddyboss.com/resources/dev-docs/)
- [BuddyPress Codex](https://codex.buddypress.org/)
- [LearnDash Developers](https://developers.learndash.com/)
- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)

### Librerías de PDF
- [mPDF Documentation](https://mpdf.github.io/)
- [TCPDF Documentation](https://tcpdf.org/)
- [Dompdf](https://github.com/dompdf/dompdf)

### Inspiración
- [Credly](https://www.credly.com/) - Platform de badges digitales
- [Accredible](https://www.accredible.com/) - Certificados digitales
- [Certif.me](https://certif.me/) - Sistema de certificación online

---

## Apéndice A: Ejemplo de Template de Certificado

```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 0;
        }
        body {
            font-family: 'Georgia', serif;
            margin: 0;
            padding: 0;
        }
        .certificate-container {
            position: relative;
            width: 297mm;  /* A4 landscape width */
            height: 210mm; /* A4 landscape height */
            background-image: url('background.jpg');
            background-size: cover;
        }
        .user-name {
            position: absolute;
            top: 95mm;
            width: 100%;
            text-align: center;
            font-size: 36pt;
            font-weight: bold;
            color: #2c3e50;
        }
        .certificate-type {
            position: absolute;
            top: 125mm;
            width: 100%;
            text-align: center;
            font-size: 24pt;
            color: #34495e;
        }
        .issue-date {
            position: absolute;
            bottom: 30mm;
            right: 40mm;
            font-size: 12pt;
            color: #7f8c8d;
        }
        .verification-code {
            position: absolute;
            bottom: 15mm;
            right: 40mm;
            font-size: 10pt;
            color: #95a5a6;
        }
    </style>
</head>
<body>
    <div class="certificate-container">
        <div class="user-name">{NOMBRE_USUARIO}</div>
        <div class="certificate-type">Certificado en {TIPO_CERTIFICADO}</div>
        <div class="issue-date">Fecha de emisión: {FECHA_EMISION}</div>
        <div class="verification-code">Código: {CODIGO_VERIFICACION}</div>
    </div>
</body>
</html>
```

---

## Apéndice B: Ejemplo de CSV para Importación

```csv
email,tipo_certificado,fecha_emision,notas
juan.perez@example.com,Competencias Digitales,2024-01-15,Nivel Avanzado
maria.garcia@example.com,Competencias Digitales,2024-01-15,
carlos.lopez@example.com,Liderazgo Transformacional,2024-02-01,Workshop 2024
```

---

**Fin del Documento**

*Versión 1.0 - Creado: 2024-12-10*
*Última actualización: 2024-12-10*
