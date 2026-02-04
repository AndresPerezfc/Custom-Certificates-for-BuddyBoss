 Plan: Importación CSV de Certificados

 Resumen

 Implementar una funcionalidad de importación masiva de certificados mediante archivos CSV, permitiendo asignar certificados a múltiples usuarios de forma eficiente, incluyendo soporte para variables
 personalizadas definidas en las plantillas.

 ---
 Formato CSV

 Columnas Requeridas
 ┌─────────┬─────────────────────────────────────────────┐
 │ Columna │                 Descripción                 │
 ├─────────┼─────────────────────────────────────────────┤
 │ email   │ Email del usuario (principal identificador) │
 └─────────┴─────────────────────────────────────────────┘
 Columnas Opcionales
 ┌────────────────┬─────────────────────────────────────────────────────┐
 │    Columna     │                     Descripción                     │
 ├────────────────┼─────────────────────────────────────────────────────┤
 │ user_id        │ ID del usuario (alternativa al email)               │
 ├────────────────┼─────────────────────────────────────────────────────┤
 │ {VARIABLE_KEY} │ Valores de variables personalizadas de la plantilla │
 └────────────────┴─────────────────────────────────────────────────────┘
 Ejemplos

 CSV básico (plantilla sin variables):
 email
 juan@example.com
 maria@example.com

 CSV con variables personalizadas:
 email,CATEGORIA,NIVEL
 juan@example.com,Desarrollo Web,Avanzado
 maria@example.com,Marketing,Intermedio

 Notas importantes:
 - Los campos xprofile de BuddyBoss NO van en el CSV - se obtienen automáticamente del perfil del usuario
 - Las variables estándar ({NOMBRE_USUARIO}, {FECHA_EMISION}, etc.) son automáticas

 ---
 Flujo de Usuario (UI/UX)

 Paso 1: Seleccionar Plantilla
     ↓
 Paso 2: Descargar CSV de ejemplo (opcional) + Subir archivo
     ↓
 Paso 3: Vista previa y validación (errores detectados)
     ↓
 Paso 4: Progreso de importación (barra de progreso)
     ↓
 Paso 5: Resumen de resultados + Descargar reporte de errores

 ---
 Archivos a Crear

 1. includes/class-csv-import.php

 Nueva clase singleton con:
 - Parseo de CSV (manejo de codificación UTF-8, delimitadores)
 - Validación de filas (usuario existe, variables completas, no duplicados)
 - Procesamiento por lotes (25 filas por request AJAX)
 - Generación de CSV de ejemplo
 - Generación de reporte de errores

 Métodos principales:
 class Custom_Cert_CSV_Import {
     public function validate_csv_file($file)      // Validar archivo subido
     public function parse_csv($file_path)         // Parsear CSV
     public function validate_row($row, $template_id, $row_number)  // Validar fila
     public function process_batch($rows, $template_id, $offset)    // Procesar lote
     public function generate_sample_csv($template_id)              // CSV de ejemplo

     // AJAX handlers
     public function ajax_get_template_info()      // Info de plantilla
     public function ajax_upload_preview()         // Subir y previsualizar
     public function ajax_process_batch()          // Procesar lote
     public function ajax_download_sample()        // Descargar ejemplo
 }

 2. admin/views/csv-import.php

 Página de administración con:
 - Selector de plantilla
 - Zona de carga de archivo
 - Vista previa con estadísticas
 - Barra de progreso
 - Resumen de resultados

 3. admin/js/csv-import.js

 JavaScript para:
 - Cambio dinámico de plantilla
 - Subida de archivo con preview
 - Procesamiento por lotes con AJAX
 - Actualización de barra de progreso
 - Manejo de cancelación

 ---
 Archivos a Modificar

 1. custom-certificates.php (línea ~96)

 require_once CUSTOM_CERT_PLUGIN_DIR . 'includes/class-csv-import.php';

 2. includes/class-admin-interface.php

 En add_admin_menus() (~línea 69):
 add_submenu_page(
     'edit.php?post_type=bb_cert_template',
     __('Importar CSV', 'custom-certificates'),
     __('Importar CSV', 'custom-certificates'),
     'manage_options',
     'csv-import',
     array($this, 'csv_import_page')
 );

 Nuevo método:
 public function csv_import_page() {
     include CUSTOM_CERT_PLUGIN_DIR . 'admin/views/csv-import.php';
 }

 En enqueue_admin_scripts() - añadir detección de página CSV:
 if (strpos($hook, 'csv-import') !== false) {
     $is_our_page = true;
     wp_enqueue_script('custom-cert-csv-import', ...);
     wp_localize_script('custom-cert-csv-import', 'customCertCSV', [...]);
 }

 3. admin/css/admin.css

 Añadir estilos para:
 - Zona de drag & drop
 - Barra de progreso
 - Cards de pasos
 - Tabla de errores

 ---
 Procesamiento por Lotes

 ¿Por qué lotes?
 - Evita timeout de PHP con archivos grandes
 - Muestra progreso en tiempo real
 - Permite cancelar a mitad de proceso

 Flujo:
 1. Usuario sube CSV → se guarda en transient con session_id
 2. JS envía requests AJAX procesando 25 filas a la vez
 3. Cada request devuelve: procesados, exitosos, errores
 4. JS actualiza barra de progreso
 5. Al terminar, muestra resumen

 ---
 Validaciones
 ┌─────────┬─────────────────────────┬────────────────────────────┐
 │  Tipo   │       Validación        │           Acción           │
 ├─────────┼─────────────────────────┼────────────────────────────┤
 │ Archivo │ Tipo MIME (text/csv)    │ Rechazar                   │
 ├─────────┼─────────────────────────┼────────────────────────────┤
 │ Archivo │ Tamaño > 5MB            │ Rechazar                   │
 ├─────────┼─────────────────────────┼────────────────────────────┤
 │ Archivo │ Contenido malicioso     │ Rechazar                   │
 ├─────────┼─────────────────────────┼────────────────────────────┤
 │ Fila    │ Usuario no encontrado   │ Reportar error, continuar  │
 ├─────────┼─────────────────────────┼────────────────────────────┤
 │ Fila    │ Variable vacía          │ Reportar error, continuar  │
 ├─────────┼─────────────────────────┼────────────────────────────┤
 │ Fila    │ Certificado ya asignado │ Reportar (skip), continuar │
 ├─────────┼─────────────────────────┼────────────────────────────┤
 │ Fila    │ Email inválido          │ Reportar error, continuar  │
 └─────────┴─────────────────────────┴────────────────────────────┘
 ---
 Seguridad

 - check_ajax_referer() en todos los handlers AJAX
 - current_user_can('manage_options') para permisos
 - sanitize_email(), intval(), sanitize_text_field() para inputs
 - Verificación de MIME type del archivo
 - Escaneo de contenido malicioso (<?php, <script, etc.)
 - Session ID único por importación para prevenir IDOR

 ---
 Verificación

 1. Test básico: Subir CSV con 5 usuarios sin variables
 2. Test con variables: Subir CSV con variables personalizadas
 3. Test de errores: CSV con emails inexistentes
 4. Test de duplicados: Intentar asignar certificado ya asignado
 5. Test de progreso: CSV con 100+ filas, verificar barra de progreso
 6. Test de cancelación: Cancelar importación a mitad de proceso
 7. Test de reporte: Descargar reporte de errores

 ---
 Archivos Críticos de Referencia

 - includes/class-certificate-assignment.php - método assign_certificate()
 - includes/class-admin-interface.php - patrones de páginas admin y AJAX
 - admin/views/assign-certificates.php - estructura de vista admin
 - admin/js/admin.js - patrones JavaScript existentes