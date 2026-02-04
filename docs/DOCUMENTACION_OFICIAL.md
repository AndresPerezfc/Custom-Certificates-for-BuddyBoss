# Documentación Oficial: Custom Certificates for BuddyBoss

## Introducción
**Custom Certificates for BuddyBoss** es una solución robusta y ligera diseñada para la gestión, generación y asignación de certificados personalizados dentro de ecosistemas WordPress que utilizan BuddyBoss Platform. Este plugin opera de manera independiente a sistemas LMS como LearnDash, permitiendo una flexibilidad total en la certificación de usuarios basada en criterios personalizados o asignación manual.

## Características Principales

*   **Gestión de Plantillas Visuales**: Creación de plantillas de certificados utilizando el editor clásico de WordPress, con soporte para imágenes de fondo destacadas.
*   **Generación Dinámica de PDF**: Utiliza la librería **mPDF** para generar documentos PDF de alta calidad al vuelo.
*   **Certificados Multi-Página**: Soporte para agregar páginas adicionales a los certificados, cada una con su propia imagen de fondo.
*   **Integración Nativa con BuddyBoss**: Añade una pestaña "Mis Certificados" en el perfil de cada miembro, manteniendo la estética y funcionalidad de la red social.
*   **Configuración Personalizable**: Control total sobre la orientación (Apaisado/Vertical), colores de texto, tamaño de fuente, tipografía y fondos por plantilla.
*   **Sistema de Verificación**: Cada certificado emitido incluye un **código único de 10 caracteres** para garantizar su autenticidad.
*   **Verificación Pública**: Página pública con shortcode para que terceros verifiquen la autenticidad de certificados.
*   **Asignación Masiva**: Herramienta administrativa para asignar certificados a usuarios individuales o múltiples usuarios simultáneamente mediante búsqueda AJAX.
*   **Importación CSV**: Sistema de importación masiva que permite asignar certificados a cientos de usuarios mediante archivo CSV.
*   **Edición de Variables Post-Asignación**: Posibilidad de editar los valores de variables personalizadas en certificados ya asignados.
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
│   ├── class-certificate-verification.php # Sistema de verificación pública
│   ├── class-csv-import.php            # Sistema de importación CSV masiva
│   ├── class-dependency-installer.php  # Gestión de Composer/mPDF
│   ├── class-pdf-generator.php         # Motor de generación de PDFs
│   └── functions.php                   # Funciones helpers globales
├── public/                 # Recursos públicos del frontend
│   ├── css/verification.css            # Estilos del formulario de verificación
│   ├── js/verification.js              # JavaScript para verificación AJAX
│   └── templates/                      # Plantillas de perfil frontend
├── vendor/                 # Librerías externas (Composer - mPDF)
└── custom-certificates.php # Archivo principal (Bootstrap)
```

---

## Flujo de Trabajo

### 1. Creación de Plantilla
Desde el menú **Certificados > Añadir Nueva**:
1.  **Título**: Nombre interno de la plantilla (ej. "Certificado de Participación 2024").
2.  **Contenido**: Diseño del cuerpo del certificado. Se pueden usar variables dinámicas.
3.  **Imagen Destacada**: Se usa como **fondo** de la primera página del certificado (se recomienda formato Carta/Letter en alta resolución).
4.  **Configuración del Certificado**:
    *   *Color de Texto*: Hexadecimal.
    *   *Tamaño de Fuente*: Base en píxeles.
    *   *Orientación*: Horizontal (Landscape) o Vertical (Portrait).
    *   *Tipografía*: Selección de fuentes Google Fonts (Montserrat, Open Sans, Roboto, Lato).
5.  **Variables Personalizadas** (opcional): Definir variables adicionales específicas para esta plantilla que se completarán al momento de asignar.
6.  **Páginas Adicionales** (opcional): Agregar páginas extra al certificado con sus propias imágenes de fondo.

### 2. Asignación de Certificados
Desde **Certificados > Asignar Certificados**:
1.  Seleccionar la plantilla deseada.
2.  Si la plantilla tiene **variables personalizadas**, aparecerán campos para completar sus valores.
3.  Buscar usuarios (búsqueda predictiva por nombre o email).
4.  Confirmar la asignación.
    *   *Internamente se genera un registro `bb_cert_assigned` con un código único y fecha de emisión.*

#### Edición de Certificados Asignados
En la sección **"Certificados Asignados"** (parte inferior de la página de asignación), es posible:
*   Ver todos los certificados asignados con sus detalles.
*   **Editar variables personalizadas**: Si un certificado tiene variables personalizadas, aparece un botón "Editar" que permite modificar los valores sin necesidad de eliminar y reasignar el certificado.
*   Eliminar certificados asignados.

#### Importación CSV Masiva
Para asignar certificados a muchos usuarios de forma eficiente, desde **Certificados > Importar CSV**:

1.  **Seleccionar plantilla**: Elegir la plantilla de certificado a asignar.
2.  **Descargar plantilla de ejemplo**: Obtener un CSV con el formato correcto para la plantilla seleccionada.
3.  **Subir archivo CSV**: Arrastrar o seleccionar el archivo CSV.
4.  **Vista previa y validación**: El sistema muestra cuántos usuarios son válidos y cuántos tienen errores.
5.  **Procesar importación**: La importación se realiza por lotes mostrando el progreso en tiempo real.
6.  **Resultados**: Resumen de certificados asignados, omitidos (duplicados) y errores.

##### Formato del CSV

| Columna | Obligatoria | Descripción |
|---------|-------------|-------------|
| `email` | Sí | Email del usuario en WordPress |
| `user_id` | No | Alternativa al email - ID numérico del usuario |
| `{VARIABLE}` | Condicional | Columnas adicionales para variables personalizadas de la plantilla |

**Ejemplo de CSV básico:**
```csv
email
juan@ejemplo.com
maria@ejemplo.com
carlos@ejemplo.com
```

**Ejemplo de CSV con variables personalizadas:**
```csv
email,CATEGORIA,NIVEL
juan@ejemplo.com,Desarrollo Web,Avanzado
maria@ejemplo.com,Marketing Digital,Intermedio
carlos@ejemplo.com,Diseño Gráfico,Básico
```

> **Importante:** Los campos de perfil de BuddyBoss (xprofile) NO se incluyen en el CSV. Estos se obtienen automáticamente del perfil de cada usuario al generar el PDF.

##### Requisitos del archivo
*   **Codificación**: UTF-8
*   **Separador**: Coma (,) o punto y coma (;) - se detecta automáticamente
*   **Tamaño máximo**: 5MB
*   **Primera fila**: Debe contener los encabezados de columna

##### Manejo de errores
*   **Usuario no encontrado**: Se reporta el error y se continúa con los demás
*   **Certificado duplicado**: Se omite automáticamente (no se puede asignar el mismo certificado dos veces al mismo usuario)
*   **Variable faltante**: Se reporta el error indicando qué variable está vacía
*   **Al finalizar**: Se puede descargar un reporte CSV con todos los errores

### 3. Vista del Usuario
1.  El usuario accede a su perfil en BuddyBoss.
2.  Navega a la pestaña **Mis Certificados**.
3.  Visualiza una galería de sus certificados ganados.
4.  **Filtrar y ordenar** (opcional):
    *   *Filtrar por Categoría*: Dropdown para mostrar solo certificados de una categoría específica.
    *   *Ordenar por Fecha*: Mostrar los más recientes o más antiguos primero.
5.  Clic en **Ver Certificado** para generar y descargar el PDF en tiempo real.

#### Filtros de Certificados
Cuando un usuario tiene múltiples certificados, puede utilizar los controles de filtrado:

| Control | Opciones | Descripción |
|---------|----------|-------------|
| Categoría | Todas / [Categorías disponibles] | Filtra certificados por la categoría de la plantilla |
| Ordenar | Más recientes / Más antiguos | Ordena por fecha de asignación |

> **Nota:** El filtro de categoría solo aparece si las plantillas de certificados tienen categorías asignadas y el usuario tiene certificados en más de una categoría.

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

#### Variables Estándar
*   `{NOMBRE_USUARIO}`: Nombre visible del usuario.
*   `{EMAIL_USUARIO}`: Correo electrónico del usuario.
*   `{FECHA_EMISION}`: Fecha en que se asignó el certificado (formato: "20 de enero de 2026").
*   `{CODIGO_VERIFICACION}`: Código alfanumérico único (10 caracteres).

#### Variables de Campos de Perfil BuddyBoss (xprofile)
El plugin detecta automáticamente **todos los campos de perfil extendido** configurados en BuddyBoss y los hace disponibles como variables. El nombre del campo se convierte automáticamente:
- Se eliminan acentos
- Se convierte a mayúsculas
- Los espacios se reemplazan por guiones bajos

**Ejemplos:**
| Campo en BuddyBoss | Variable a usar |
|--------------------|-----------------|
| Identificación | `{IDENTIFICACION}` |
| Número de Documento | `{NUMERO_DE_DOCUMENTO}` |
| Fecha de Nacimiento | `{FECHA_DE_NACIMIENTO}` |
| Cargo | `{CARGO}` |

> **Nota:** Solo se incluyen los campos que tienen valor para el usuario. Si un campo está vacío, la variable no será reemplazada.

#### Variables Personalizadas por Plantilla
Además de las variables automáticas, se pueden definir variables personalizadas específicas para cada plantilla desde el metabox "Variables Personalizadas". Estas se completan al momento de asignar el certificado.

### Motor de PDF
*   **Librería**: mPDF (vía Composer).
*   **Hook de Generación**: `template_redirect`. Detecta el parámetro `?download_certificate=1`.
*   **Seguridad**: Verifica Nonces y permisos (solo el dueño o un administrador pueden descargar).
*   **Renderizado**: Genera HTML intermedio y lo convierte a PDF. Si no hay plantilla HTML personalizada en el tema, usa una estructura por defecto centrada en la imagen de fondo.
*   **Formato de Página**: Tamaño Carta (Letter) - 279.4mm x 215.9mm (landscape) o 215.9mm x 279.4mm (portrait).

### Páginas Adicionales

El plugin permite agregar múltiples páginas a un certificado. Cada página adicional puede tener su propia imagen de fondo.

#### Configuración
Desde el metabox **"Páginas Adicionales"** en la edición de plantilla:
1.  Clic en **"Agregar Página"** para crear una nueva página.
2.  Seleccionar una imagen de fondo desde la **Biblioteca de Medios** de WordPress.
3.  Opcionalmente, agregar contenido HTML/texto para la página.
4.  Repetir para agregar más páginas según sea necesario.
5.  Las páginas se pueden eliminar individualmente con el botón "Eliminar".

#### Características Técnicas
*   **Almacenamiento**: Las páginas adicionales se guardan en el meta `_cert_additional_pages` como JSON.
*   **Estructura de datos**:
    ```json
    [
      {
        "image_id": 123,
        "content": "<p>Contenido opcional</p>"
      }
    ]
    ```
*   **Variables**: El contenido de las páginas adicionales también soporta todas las variables de plantilla ({NOMBRE_USUARIO}, {FECHA_EMISION}, variables personalizadas, etc.).
*   **Renderizado**: Cada página adicional se genera con `AddPage()` de mPDF, manteniendo la misma orientación que la página principal.
*   **Imagen de fondo**: Las imágenes se escalan automáticamente para cubrir el 100% del área de la página.

#### Recomendaciones
*   Usar imágenes con las mismas dimensiones que la página principal para mantener consistencia visual.
*   Las imágenes deben tener resolución adecuada (mínimo 150 DPI para impresión).
*   Formato recomendado: PNG o JPG de alta calidad.

### Integración BuddyBoss
*   Utiliza la API `bp_core_new_nav_item` para inyectar la navegación.
*   Slug del componente: `certificados-innova`.
*   Compatible con temas hijos: Busca plantillas en `tu-tema/custom-certificates/` antes de usar las del plugin.

### Verificación Pública de Certificados

El plugin incluye un sistema de verificación pública que permite a cualquier persona comprobar la autenticidad de un certificado mediante su código de verificación.

#### Shortcode
```
[verificar_certificado]
```

#### Atributos Disponibles
| Atributo | Descripción | Valor por defecto |
|----------|-------------|-------------------|
| `title` | Título del formulario | "Verificar Certificado" |
| `description` | Texto descriptivo | "Ingresa el código de verificación..." |
| `button_text` | Texto del botón | "Verificar" |
| `placeholder` | Placeholder del campo | "Ej: ABC123XYZ0" |

#### Ejemplo de Uso
```
[verificar_certificado title="Validar Certificado" button_text="Comprobar"]
```

#### Cómo Crear la Página de Verificación
1. Crear una nueva página en WordPress (ej: "Verificar Certificado")
2. Agregar el shortcode `[verificar_certificado]` en el contenido
3. Publicar la página
4. Compartir la URL de la página para que terceros puedan verificar certificados

#### Verificación Automática por URL
El sistema soporta verificación automática mediante parámetros en la URL. Al acceder a la página de verificación con uno de estos parámetros, el código se auto-completa y se verifica automáticamente:

```
https://tusitio.com/verificar-certificado/?certificate_id=ABC123XYZ0
https://tusitio.com/verificar-certificado/?code=ABC123XYZ0
https://tusitio.com/verificar-certificado/?codigo=ABC123XYZ0
```

Esto permite incluir enlaces de verificación directamente en los certificados PDF o compartirlos por correo electrónico.

#### Resultado de la Verificación
- **Certificado válido**: Muestra nombre del titular, nombre del certificado, fecha de emisión y código
- **Certificado no encontrado**: Mensaje indicando que el código no corresponde a ningún certificado

---

## Requisitos del Sistema
*   **WordPress**: 5.8 o superior.
*   **PHP**: 7.4 o superior (Recomendado 8.0+ para mPDF).
*   **BuddyBoss Platform** (o BuddyPress).
*   **Composer**: Necesario para instalar dependencias si no se incluyen en el paquete.
