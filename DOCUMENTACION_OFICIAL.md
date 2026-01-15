# Documentación Oficial: Custom Certificates for BuddyBoss

## Introducción
**Custom Certificates for BuddyBoss** es una solución robusta y ligera diseñada para la gestión, generación y asignación de certificados personalizados dentro de ecosistemas WordPress que utilizan BuddyBoss Platform. Este plugin opera de manera independiente a sistemas LMS como LearnDash, permitiendo una flexibilidad total en la certificación de usuarios basada en criterios personalizados o asignación manual.

## Características Principales

*   **Gestión de Plantillas Visuales**: Creación de plantillas de certificados utilizando el editor clásico de WordPress, con soporte para imágenes de fondo destacadas.
*   **Generación Dinámica de PDF**: Utiliza la librería **mPDF** para generar documentos PDF de alta calidad al vuelo.
*   **Integración Nativa con BuddyBoss**: Añade una pestaña "Mis Certificados" en el perfil de cada miembro, manteniendo la estética y funcionalidad de la red social.
*   **Configuración Personalizable**: Control total sobre la orientación (Apaisado/Vertical), colores de texto, tamaño de fuente y fondos por plantilla.
*   **Sistema de Verificación**: Cada certificado emitido incluye un **código único de 10 caracteres** para garantizar su autenticidad.
*   **Asignación Masiva**: Herramienta administrativa para asignar certificados a usuarios individuales o múltiples usuarios simultáneamente mediante búsqueda AJAX.
*   **Gestión de Dependencias**: Sistema integrado de comprobación e instalación de librerías necesarias (mPDF).

---

## Estructura del Proyecto

El plugin sigue una arquitectura modular orientada a objetos (Singleton Pattern), facilitando el mantenimiento y la escalabilidad.

### Directorios y Archivos Clave
```
custom-certificates/
├── admin/                  # Lógica y vistas del panel de administración
│   ├── css/                # Estilos específicos del admin
│   ├── js/                 # Scripts (Select2, AJAX)
│   └── views/              # Plantillas PHP para páginas de administración
├── assets/                 # Recursos estáticos públicos (si aplica)
├── includes/               # Núcleo funcional del plugin
│   ├── class-admin-interface.php       # Gestión de menús, metaboxes y columnas
│   ├── class-buddyboss-integration.php # Integración con perfiles de BuddyBoss
│   ├── class-certificate-assignment.php # Lógica de asignación y verificación
│   ├── class-certificate-post-type.php # Registro de CPTs (Plantillas y Asignaciones)
│   ├── class-dependency-installer.php  # Gestión de Composer/mPDF
│   ├── class-pdf-generator.php         # Motor de generación de PDFs
│   └── functions.php                   # Funciones helpers globales
├── public/                 # Vistas públicas (Templates de perfil frontend)
├── vendor/                 # Librerías externas (Composer - mPDF)
└── custom-certificates.php # Archivo principal (Bootstrap)
```

---

## Flujo de Trabajo

### 1. Creación de Plantilla
Desde el menú **Certificados > Añadir Nueva**:
1.  **Título**: Nombre interno de la plantilla (ej. "Certificado de Participación 2024").
2.  **Contenido**: Diseño del cuerpo del certificado. Se pueden usar variables dinámicas.
3.  **Imagen Destacada**: Se usa como **fondo** del certificado (se recomienda formato A4/Carta en alta resolución).
4.  **Configuración del Certificado**:
    *   *Color de Texto*: Hexadecimal.
    *   *Tamaño de Fuente*: Base en píxeles.
    *   *Orientación*: Horizontal (Landscape) o Vertical (Portrait).

### 2. Asignación de Certificados
Desde **Certificados > Asignar Certificados**:
1.  Seleccionar la plantilla deseada.
2.  Buscar usuarios (búsqueda predictiva por nombre o email).
3.  Confirmar la asignación.
   *   *Internamente se genera un registro `bb_cert_assigned` con un código único y fecha de emisión.*

### 3. Vista del Usuario
1.  El usuario accede a su perfil en BuddyBoss.
2.  Navega a la pestaña **Mis Certificados**.
3.  Visualiza una galería de sus certificados ganados.
4.  Clic en **Ver Certificado** para generar y descargar el PDF en tiempo real.

---

## Detalles Técnicos

### Custom Post Types (CPT)
1.  **`bb_cert_template`**: Almacena los diseños base.
    *   *Meta principal*: `_cert_config` (JSON con estilos y orientación).
2.  **`bb_cert_assigned`**: Registro de un certificado entregado a un usuario específico.
    *   *Relación*: Vincula un `user_id` con un `template_id`.
    *   *Meta*: `_cert_verification_code`, `_cert_issue_date`.

### Variables de Plantilla
Al diseñar una plantilla, utilice los siguientes marcadores que serán reemplazados dinámicamente al generar el PDF:
*   `{NOMBRE_USUARIO}`: Nombre visible del usuario.
*   `{EMAIL_USUARIO}`: Correo electrónico del usuario.
*   `{FECHA_EMISION}`: Fecha en que se asignó el certificado (formato local de WP).
*   `{CODIGO_VERIFICACION}`: Código alfanumérico único (10 caracteres).

### Motor de PDF
*   **Librería**: mPDF (vía Composer).
*   **Hook de Generación**: `template_redirect`. Detecta el parámetro `?download_certificate=1`.
*   **Seguridad**: Verifica Nonces y permisos (solo el dueño o un administrador pueden descargar).
*   **Renderizado**: Genera HTML intermedio y lo convierte a PDF. Si no hay plantilla HTML personalizada en el tema, usa una estructura por defecto centrada en la imagen de fondo.

### Integración BuddyBoss
*   Utiliza la API `bp_core_new_nav_item` para inyectar la navegación.
*   Slug del componente: `custom-certificates`.
*   Compatible con temas hijos: Busca plantillas en `tu-tema/custom-certificates/` antes de usar las del plugin.

---

## Requisitos del Sistema
*   **WordPress**: 5.8 o superior.
*   **PHP**: 7.4 o superior (Recomendado 8.0+ para mPDF).
*   **BuddyBoss Platform** (o BuddyPress).
*   **Composer**: Necesario para instalar dependencias si no se incluyen en el paquete.
