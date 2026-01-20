 Plan: Variables Personalizadas por Plantilla de Certificado

 Problema

 Para el evento "Innovafest B10 5.0" se necesita un solo certificado que pueda mostrar información diferente según el
 usuario (categoría: docente/estudiante/administrativo, subcategoría: video/podcast/curso, etc.), en lugar de crear
 múltiples plantillas.

 Solución Propuesta

 Sistema de variables personalizadas dinámicas por plantilla:
 1. El admin define qué variables adicionales necesita cada plantilla
 2. Al asignar el certificado, aparecen campos para completar esas variables
 3. Las variables se reemplazan en el PDF generado

 Flujo de Usuario

 1. Crear Plantilla

 Admin crea plantilla "Certificado Innovafest B10 5.0"
 ↓
 En el contenido usa: {NOMBRE_USUARIO} otorgamos este reconocimiento
 en la categoría {CATEGORIA} - {SUBCATEGORIA}
 ↓
 En nuevo metabox "Variables Personalizadas" define:
   - CATEGORIA: [Docente, Estudiante, Administrativo] (tipo: select)
   - SUBCATEGORIA: [Video, Podcast, Curso Online, Artículo] (tipo: select)

 2. Asignar Certificado

 Admin va a "Asignar Certificados"
 ↓
 Selecciona plantilla "Certificado Innovafest B10 5.0"
 ↓
 Automáticamente aparecen los campos:
   - Categoría: [dropdown]
   - Subcategoría: [dropdown]
 ↓
 Selecciona usuario(s) y completa los valores
 ↓
 Asigna certificado

 3. Usuario Descarga

 Usuario descarga su certificado
 ↓
 PDF muestra: "Juan Pérez otorgamos este reconocimiento
 en la categoría Docente - Video"

 ---
 Implementación Técnica

 Archivos a Modificar
 ┌───────────────────────────────────────────┬───────────────────────────────────────────────────────────┐
 │                  Archivo                  │                          Cambio                           │
 ├───────────────────────────────────────────┼───────────────────────────────────────────────────────────┤
 │ includes/class-admin-interface.php        │ Agregar metabox para definir variables en plantillas      │
 ├───────────────────────────────────────────┼───────────────────────────────────────────────────────────┤
 │ admin/views/assign-certificates.php       │ Mostrar campos dinámicos según plantilla seleccionada     │
 ├───────────────────────────────────────────┼───────────────────────────────────────────────────────────┤
 │ admin/js/admin.js                         │ Cargar campos dinámicos via AJAX al seleccionar plantilla │
 ├───────────────────────────────────────────┼───────────────────────────────────────────────────────────┤
 │ includes/class-certificate-assignment.php │ Agregar endpoint AJAX para obtener variables de plantilla │
 ├───────────────────────────────────────────┼───────────────────────────────────────────────────────────┤
 │ includes/class-pdf-generator.php          │ Procesar variables personalizadas en replace_variables()  │
 └───────────────────────────────────────────┴───────────────────────────────────────────────────────────┘
 Estructura de Datos

 Meta de plantilla (_cert_custom_variables):
 [
   {
     "key": "CATEGORIA",
     "label": "Categoría",
     "type": "select",
     "options": ["Docente", "Estudiante", "Administrativo"],
     "required": true
   },
   {
     "key": "SUBCATEGORIA",
     "label": "Subcategoría",
     "type": "select",
     "options": ["Video", "Podcast", "Curso Online"],
     "required": true
   }
 ]

 Meta de asignación (_cert_custom_data - ya existe):
 {
   "CATEGORIA": "Docente",
   "SUBCATEGORIA": "Video"
 }

 ---
 Pasos de Implementación

 Paso 1: Metabox para definir variables en plantilla

 Archivo: includes/class-admin-interface.php

 - Agregar nuevo metabox "Variables Personalizadas" en bb_cert_template
 - Interfaz para agregar/eliminar variables con:
   - Nombre de variable (ej: CATEGORIA)
   - Etiqueta para mostrar (ej: "Categoría")
   - Tipo: text, select, textarea
   - Opciones (si es select, separadas por coma)
   - ¿Requerido?
 - Guardar en meta _cert_custom_variables como JSON

 Paso 2: Endpoint AJAX para obtener variables

 Archivo: includes/class-certificate-assignment.php

 - Nueva función ajax_get_template_variables()
 - Recibe template_id
 - Retorna array de variables definidas en la plantilla

 Paso 3: Campos dinámicos en formulario de asignación

 Archivo: admin/views/assign-certificates.php

 - Agregar contenedor <div id="custom-variables-container"></div>
 - Los campos se cargan dinámicamente via AJAX

 Archivo: admin/js/admin.js

 - Al cambiar plantilla seleccionada, llamar AJAX
 - Renderizar campos según tipo (text/select/textarea)
 - Incluir valores en custom_data al enviar

 Paso 4: Procesar variables en PDF

 Archivo: includes/class-pdf-generator.php

 - En get_certificate_data(), agregar variables personalizadas al array de reemplazos
 - Modificar llamada a replace_variables() para incluir custom_data

 ---
 Código Clave

 Metabox de Variables (simplificado)

 // En template_custom_variables_metabox()
 <div id="cert-custom-variables">
   <div class="variable-row">
     <input type="text" name="custom_vars[0][key]" placeholder="NOMBRE_VARIABLE">
     <input type="text" name="custom_vars[0][label]" placeholder="Etiqueta">
     <select name="custom_vars[0][type]">
       <option value="text">Texto</option>
       <option value="select">Selección</option>
       <option value="textarea">Área de texto</option>
     </select>
     <input type="text" name="custom_vars[0][options]" placeholder="Opción1, Opción2">
   </div>
   <button type="button" id="add-variable">+ Agregar Variable</button>
 </div>

 AJAX para cargar campos

 $('#cert_template').on('change', function() {
     var templateId = $(this).val();
     $.post(ajaxurl, {
         action: 'get_template_variables',
         template_id: templateId,
         nonce: customCertAdmin.search_nonce
     }, function(response) {
         renderCustomFields(response.data);
     });
 });

 Reemplazo en PDF

 // En get_certificate_data(), línea ~158
 $replacements = array(
     'NOMBRE_USUARIO' => $user->display_name,
     'EMAIL_USUARIO' => $user->user_email,
     'FECHA_EMISION' => date_i18n(...),
     'CODIGO_VERIFICACION' => $verification_code
 );

 // Agregar variables personalizadas
 if (!empty($custom_data) && is_array($custom_data)) {
     foreach ($custom_data as $key => $value) {
         $replacements[strtoupper($key)] = $value;
     }
 }

 $template_content = $this->replace_variables($template_content, $replacements);

 ---
 Verificación

 1. Crear plantilla de prueba:
   - Contenido: {NOMBRE_USUARIO} - Categoría: {CATEGORIA} - Sub: {SUBCATEGORIA}
   - Definir variables: CATEGORIA (select: Docente,Estudiante), SUBCATEGORIA (select: Video,Podcast)
 2. Asignar certificado:
   - Verificar que aparecen los campos dinámicos
   - Seleccionar valores y asignar a un usuario
 3. Descargar PDF:
   - Verificar que las variables se reemplazan correctamente
 4. Asignación masiva:
   - Verificar que funciona con múltiples usuarios (mismos valores para todos)

 ---
 Consideraciones

 - Las variables se almacenan en MAYÚSCULAS para consistencia con las existentes
 - El tipo "select" es ideal para categorías predefinidas (evita errores de escritura)
 - El tipo "text" permite valores libres (ej: nombre de proyecto)
 - Compatible con la asignación masiva actual (mismo valor para todos los usuarios seleccionados)