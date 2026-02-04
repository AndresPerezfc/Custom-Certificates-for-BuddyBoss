<?php
/**
 * CSV Import
 *
 * Handles bulk certificate assignment via CSV file upload
 */

if (!defined('ABSPATH')) {
    exit;
}

class Custom_Cert_CSV_Import {

    private static $instance = null;

    /**
     * Number of rows to process per AJAX batch
     */
    private $batch_size = 25;

    /**
     * Maximum file size in bytes (5MB)
     */
    private $max_file_size = 5242880;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // AJAX handlers
        add_action('wp_ajax_csv_get_template_info', array($this, 'ajax_get_template_info'));
        add_action('wp_ajax_csv_upload_preview', array($this, 'ajax_upload_preview'));
        add_action('wp_ajax_csv_process_batch', array($this, 'ajax_process_batch'));
        add_action('wp_ajax_csv_download_sample', array($this, 'ajax_download_sample'));
        add_action('wp_ajax_csv_download_error_report', array($this, 'ajax_download_error_report'));
    }

    /**
     * Validate uploaded CSV file
     *
     * @param array $file $_FILES array element
     * @return true|WP_Error True on success, WP_Error on failure
     */
    public function validate_csv_file($file) {
        // Check upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error_messages = array(
                UPLOAD_ERR_INI_SIZE => __('El archivo excede el tamaño máximo permitido por el servidor.', 'custom-certificates'),
                UPLOAD_ERR_FORM_SIZE => __('El archivo excede el tamaño máximo permitido.', 'custom-certificates'),
                UPLOAD_ERR_PARTIAL => __('El archivo se subió parcialmente.', 'custom-certificates'),
                UPLOAD_ERR_NO_FILE => __('No se seleccionó ningún archivo.', 'custom-certificates'),
                UPLOAD_ERR_NO_TMP_DIR => __('Falta la carpeta temporal del servidor.', 'custom-certificates'),
                UPLOAD_ERR_CANT_WRITE => __('Error al escribir el archivo en disco.', 'custom-certificates'),
            );
            $message = isset($error_messages[$file['error']])
                ? $error_messages[$file['error']]
                : __('Error desconocido al subir el archivo.', 'custom-certificates');
            return new WP_Error('upload_error', $message);
        }

        // Check file size
        if ($file['size'] > $this->max_file_size) {
            return new WP_Error('file_too_large', __('El archivo excede el tamaño máximo de 5MB.', 'custom-certificates'));
        }

        // Check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($extension !== 'csv') {
            return new WP_Error('invalid_extension', __('El archivo debe tener extensión .csv', 'custom-certificates'));
        }

        // Verify MIME type (only if finfo is available)
        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime_type = $finfo->file($file['tmp_name']);
            $allowed_types = array('text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel', 'text/x-csv', 'application/octet-stream');

            if (!in_array($mime_type, $allowed_types)) {
                return new WP_Error('invalid_type', sprintf(
                    __('Tipo de archivo no permitido (%s). Debe ser un archivo CSV válido.', 'custom-certificates'),
                    $mime_type
                ));
            }
        }

        // Scan for potentially malicious content
        $content = file_get_contents($file['tmp_name']);
        $dangerous_patterns = array('<?php', '<?=', '<script', 'javascript:', 'data:text/html', 'vbscript:');
        foreach ($dangerous_patterns as $pattern) {
            if (stripos($content, $pattern) !== false) {
                return new WP_Error('suspicious_content', __('El archivo contiene contenido potencialmente malicioso.', 'custom-certificates'));
            }
        }

        return true;
    }

    /**
     * Parse CSV file and return structured data
     *
     * @param string $file_path Path to CSV file
     * @return array|WP_Error Parsed data or error
     */
    public function parse_csv($file_path) {
        if (!file_exists($file_path) || !is_readable($file_path)) {
            return new WP_Error('file_not_readable', __('No se puede leer el archivo CSV.', 'custom-certificates'));
        }

        // Detect encoding and convert to UTF-8 if needed
        $content = file_get_contents($file_path);
        $encoding = mb_detect_encoding($content, array('UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'), true);

        if ($encoding && $encoding !== 'UTF-8' && $encoding !== 'ASCII') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }

        // Remove BOM if present
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        // Detect delimiter
        $first_line = strtok($content, "\n");
        $delimiter = $this->detect_delimiter($first_line);

        // Parse CSV
        $lines = explode("\n", $content);
        $headers = array();
        $rows = array();

        foreach ($lines as $index => $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $parsed = str_getcsv($line, $delimiter);

            if ($index === 0) {
                // First row is headers - normalize to uppercase
                $headers = array_map(function($header) {
                    return strtoupper(trim($header));
                }, $parsed);
            } else {
                // Data row - create associative array
                if (count($parsed) === count($headers)) {
                    $row = array_combine($headers, $parsed);
                    $rows[] = $row;
                }
            }
        }

        // Validate headers
        if (!in_array('EMAIL', $headers) && !in_array('USER_ID', $headers)) {
            return new WP_Error('missing_identifier', __('El CSV debe contener una columna "email" o "user_id".', 'custom-certificates'));
        }

        return array(
            'headers' => $headers,
            'rows' => $rows,
            'total' => count($rows),
            'delimiter' => $delimiter
        );
    }

    /**
     * Detect CSV delimiter
     *
     * @param string $line First line of CSV
     * @return string Detected delimiter
     */
    private function detect_delimiter($line) {
        $delimiters = array(',' => 0, ';' => 0, "\t" => 0);

        foreach ($delimiters as $delimiter => &$count) {
            $count = count(str_getcsv($line, $delimiter));
        }

        return array_search(max($delimiters), $delimiters);
    }

    /**
     * Validate a single CSV row
     *
     * @param array $row Row data
     * @param int $template_id Template ID
     * @param int $row_number Row number for error messages
     * @return array Validation result
     */
    public function validate_row($row, $template_id, $row_number) {
        $errors = array();
        $user = null;

        // Get user by email or user_id
        if (!empty($row['EMAIL'])) {
            $email = sanitize_email($row['EMAIL']);
            if (!is_email($email)) {
                $errors[] = sprintf(
                    __('Fila %d: Email inválido (%s)', 'custom-certificates'),
                    $row_number,
                    $row['EMAIL']
                );
            } else {
                $user = get_user_by('email', $email);
                if (!$user) {
                    $errors[] = sprintf(
                        __('Fila %d: Usuario no encontrado (email: %s)', 'custom-certificates'),
                        $row_number,
                        $email
                    );
                }
            }
        } elseif (!empty($row['USER_ID'])) {
            $user_id = intval($row['USER_ID']);
            $user = get_userdata($user_id);
            if (!$user) {
                $errors[] = sprintf(
                    __('Fila %d: Usuario no encontrado (ID: %d)', 'custom-certificates'),
                    $row_number,
                    $user_id
                );
            }
        } else {
            $errors[] = sprintf(
                __('Fila %d: No se proporcionó email ni user_id', 'custom-certificates'),
                $row_number
            );
        }

        // Check for existing certificate (duplicate)
        if ($user) {
            $assignment = Custom_Cert_Assignment::get_instance();
            $existing = $assignment->get_user_certificate($user->ID, $template_id);
            if ($existing) {
                $errors[] = sprintf(
                    __('Fila %d: Certificado ya asignado a %s', 'custom-certificates'),
                    $row_number,
                    $user->display_name
                );
            }
        }

        // Validate custom variables
        $custom_vars = json_decode(get_post_meta($template_id, '_cert_custom_variables', true), true);
        if (!empty($custom_vars) && is_array($custom_vars)) {
            foreach ($custom_vars as $var) {
                $key = strtoupper($var['key']);
                $value = isset($row[$key]) ? trim($row[$key]) : '';

                if (empty($value)) {
                    $errors[] = sprintf(
                        __('Fila %d: Variable "%s" está vacía', 'custom-certificates'),
                        $row_number,
                        $var['label']
                    );
                }
            }
        }

        return array(
            'valid' => empty($errors),
            'errors' => $errors,
            'user' => $user,
            'is_duplicate' => ($user && isset($existing) && $existing) ? true : false
        );
    }

    /**
     * Process a batch of CSV rows
     *
     * @param array $rows Array of row data
     * @param int $template_id Template ID
     * @param int $offset Starting offset (for row numbering)
     * @param bool $skip_errors Whether to skip rows with errors
     * @return array Processing results
     */
    public function process_batch($rows, $template_id, $offset, $skip_errors = true) {
        $assignment = Custom_Cert_Assignment::get_instance();

        $results = array(
            'processed' => 0,
            'success' => 0,
            'skipped' => 0,
            'errors' => array()
        );

        foreach ($rows as $index => $row) {
            $row_number = $offset + $index + 2; // +2 for header row and 0-indexing
            $results['processed']++;

            // Get user
            $user = null;
            if (!empty($row['EMAIL'])) {
                $user = get_user_by('email', sanitize_email($row['EMAIL']));
            } elseif (!empty($row['USER_ID'])) {
                $user = get_userdata(intval($row['USER_ID']));
            }

            if (!$user) {
                $results['errors'][] = array(
                    'row' => $row_number,
                    'type' => 'user_not_found',
                    'message' => sprintf(__('Usuario no encontrado', 'custom-certificates')),
                    'data' => $row
                );
                continue;
            }

            // Build custom_data array
            $custom_data = array();
            $custom_vars = json_decode(get_post_meta($template_id, '_cert_custom_variables', true), true);

            if (!empty($custom_vars) && is_array($custom_vars)) {
                foreach ($custom_vars as $var) {
                    $key = $var['key'];
                    $upper_key = strtoupper($key);
                    $value = isset($row[$upper_key]) ? $row[$upper_key] : '';
                    $custom_data[$key] = sanitize_text_field($value);
                }
            }

            // Assign certificate
            $result = $assignment->assign_certificate($user->ID, $template_id, $custom_data);

            if (is_wp_error($result)) {
                if ($result->get_error_code() === 'already_assigned') {
                    $results['skipped']++;
                } else {
                    $results['errors'][] = array(
                        'row' => $row_number,
                        'type' => 'assignment_error',
                        'message' => $result->get_error_message(),
                        'data' => $row
                    );
                }
            } else {
                $results['success']++;
            }
        }

        return $results;
    }

    /**
     * Generate sample CSV for a template
     *
     * @param int $template_id Template ID
     * @return string CSV content
     */
    public function generate_sample_csv($template_id) {
        $headers = array('email');
        $sample_row = array('usuario@ejemplo.com');

        // Add custom variables as columns
        $custom_vars = json_decode(get_post_meta($template_id, '_cert_custom_variables', true), true);
        if (!empty($custom_vars) && is_array($custom_vars)) {
            foreach ($custom_vars as $var) {
                $headers[] = strtoupper($var['key']);

                // Generate sample value based on type
                if ($var['type'] === 'select' && !empty($var['options'])) {
                    $options = array_map('trim', explode(',', $var['options']));
                    $sample_row[] = $options[0];
                } else {
                    $sample_row[] = 'Valor de ejemplo';
                }
            }
        }

        // Build CSV content
        $output = fopen('php://temp', 'r+');

        // Add UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($output, $headers);
        fputcsv($output, $sample_row);

        // Add a few more example rows
        for ($i = 0; $i < 2; $i++) {
            $example_row = array('usuario' . ($i + 2) . '@ejemplo.com');
            if (!empty($custom_vars)) {
                foreach ($custom_vars as $var) {
                    if ($var['type'] === 'select' && !empty($var['options'])) {
                        $options = array_map('trim', explode(',', $var['options']));
                        $example_row[] = $options[min($i, count($options) - 1)];
                    } else {
                        $example_row[] = 'Valor ' . ($i + 2);
                    }
                }
            }
            fputcsv($output, $example_row);
        }

        rewind($output);
        $csv_content = stream_get_contents($output);
        fclose($output);

        return $csv_content;
    }

    /**
     * Generate error report CSV
     *
     * @param array $errors Array of errors
     * @return string CSV content
     */
    public function generate_error_report($errors) {
        $output = fopen('php://temp', 'r+');

        // Add UTF-8 BOM
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Headers
        fputcsv($output, array(
            __('Fila', 'custom-certificates'),
            __('Tipo', 'custom-certificates'),
            __('Mensaje', 'custom-certificates'),
            __('Datos', 'custom-certificates')
        ));

        foreach ($errors as $error) {
            fputcsv($output, array(
                $error['row'],
                $error['type'],
                $error['message'],
                isset($error['data']) ? json_encode($error['data'], JSON_UNESCAPED_UNICODE) : ''
            ));
        }

        rewind($output);
        $csv_content = stream_get_contents($output);
        fclose($output);

        return $csv_content;
    }

    /**
     * AJAX: Get template info and variables
     */
    public function ajax_get_template_info() {
        check_ajax_referer('csv_import', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos para realizar esta acción.', 'custom-certificates')));
        }

        $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;

        if (!$template_id) {
            wp_send_json_error(array('message' => __('ID de plantilla no válido.', 'custom-certificates')));
        }

        $template = get_post($template_id);
        if (!$template || $template->post_type !== 'bb_cert_template') {
            wp_send_json_error(array('message' => __('Plantilla no encontrada.', 'custom-certificates')));
        }

        // Get custom variables
        $custom_vars = json_decode(get_post_meta($template_id, '_cert_custom_variables', true), true);
        if (empty($custom_vars) || !is_array($custom_vars)) {
            $custom_vars = array();
        }

        wp_send_json_success(array(
            'template_name' => $template->post_title,
            'variables' => $custom_vars,
            'has_variables' => !empty($custom_vars)
        ));
    }

    /**
     * AJAX: Upload and preview CSV
     */
    public function ajax_upload_preview() {
        try {
            check_ajax_referer('csv_import', 'nonce');

            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => __('No tienes permisos para realizar esta acción.', 'custom-certificates')));
            }

            // Check file
            if (!isset($_FILES['file'])) {
                wp_send_json_error(array('message' => __('No se recibió ningún archivo.', 'custom-certificates')));
            }

            $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;
            if (!$template_id) {
                wp_send_json_error(array('message' => __('Debes seleccionar una plantilla.', 'custom-certificates')));
            }

            // Validate file
            $validation = $this->validate_csv_file($_FILES['file']);
            if (is_wp_error($validation)) {
                wp_send_json_error(array('message' => $validation->get_error_message()));
            }

            // Parse CSV
            $parsed = $this->parse_csv($_FILES['file']['tmp_name']);
            if (is_wp_error($parsed)) {
                wp_send_json_error(array('message' => $parsed->get_error_message()));
            }

            // Validate all rows for preview
            $preview_errors = array();
            $valid_count = 0;
            $duplicate_count = 0;

            foreach ($parsed['rows'] as $index => $row) {
                $row_number = $index + 2;
                $validation = $this->validate_row($row, $template_id, $row_number);

                if ($validation['valid']) {
                    $valid_count++;
                } else {
                    $preview_errors = array_merge($preview_errors, $validation['errors']);
                }

                if ($validation['is_duplicate']) {
                    $duplicate_count++;
                }
            }

            // Generate unique session ID
            $session_id = wp_generate_password(32, false);

            // Store data in transient
            set_transient('csv_import_' . $session_id, array(
                'user_id' => get_current_user_id(),
                'template_id' => $template_id,
                'rows' => $parsed['rows'],
                'headers' => $parsed['headers'],
                'total' => $parsed['total'],
                'created' => time()
            ), HOUR_IN_SECONDS);

            wp_send_json_success(array(
                'session_id' => $session_id,
                'total_rows' => $parsed['total'],
                'valid_rows' => $valid_count,
                'duplicate_rows' => $duplicate_count,
                'error_count' => count($preview_errors),
                'errors' => array_slice($preview_errors, 0, 20), // Limit preview errors
                'headers' => $parsed['headers']
            ));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Error: ' . $e->getMessage()));
        }
    }

    /**
     * AJAX: Process batch of rows
     */
    public function ajax_process_batch() {
        check_ajax_referer('csv_import', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos para realizar esta acción.', 'custom-certificates')));
        }

        $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;

        if (empty($session_id)) {
            wp_send_json_error(array('message' => __('Sesión inválida.', 'custom-certificates')));
        }

        // Get session data
        $session_data = get_transient('csv_import_' . $session_id);
        if (!$session_data) {
            wp_send_json_error(array('message' => __('La sesión ha expirado. Por favor, sube el archivo nuevamente.', 'custom-certificates')));
        }

        // Verify session ownership
        if ($session_data['user_id'] !== get_current_user_id()) {
            wp_send_json_error(array('message' => __('No tienes acceso a esta sesión.', 'custom-certificates')));
        }

        // Get batch of rows
        $batch_rows = array_slice($session_data['rows'], $offset, $this->batch_size);

        if (empty($batch_rows)) {
            wp_send_json_success(array(
                'completed' => true,
                'processed' => 0,
                'success' => 0,
                'skipped' => 0,
                'errors' => array()
            ));
        }

        // Process batch
        $results = $this->process_batch($batch_rows, $session_data['template_id'], $offset);

        // Store errors in session for later download
        $stored_errors = get_transient('csv_import_errors_' . $session_id);
        if (!$stored_errors) {
            $stored_errors = array();
        }
        $stored_errors = array_merge($stored_errors, $results['errors']);
        set_transient('csv_import_errors_' . $session_id, $stored_errors, HOUR_IN_SECONDS);

        // Check if completed
        $new_offset = $offset + $this->batch_size;
        $completed = $new_offset >= $session_data['total'];

        wp_send_json_success(array(
            'completed' => $completed,
            'processed' => $results['processed'],
            'success' => $results['success'],
            'skipped' => $results['skipped'],
            'errors' => $results['errors'],
            'next_offset' => $new_offset,
            'total' => $session_data['total']
        ));
    }

    /**
     * AJAX: Download sample CSV
     */
    public function ajax_download_sample() {
        check_ajax_referer('csv_import', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para realizar esta acción.', 'custom-certificates'));
        }

        $template_id = isset($_GET['template_id']) ? intval($_GET['template_id']) : 0;
        if (!$template_id) {
            wp_die(__('ID de plantilla no válido.', 'custom-certificates'));
        }

        $template = get_post($template_id);
        if (!$template) {
            wp_die(__('Plantilla no encontrada.', 'custom-certificates'));
        }

        $csv_content = $this->generate_sample_csv($template_id);
        $filename = sanitize_file_name('plantilla-csv-' . $template->post_name . '.csv');

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $csv_content;
        exit;
    }

    /**
     * AJAX: Download error report
     */
    public function ajax_download_error_report() {
        check_ajax_referer('csv_import', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para realizar esta acción.', 'custom-certificates'));
        }

        $session_id = isset($_GET['session_id']) ? sanitize_text_field($_GET['session_id']) : '';
        if (empty($session_id)) {
            wp_die(__('Sesión inválida.', 'custom-certificates'));
        }

        $errors = get_transient('csv_import_errors_' . $session_id);
        if (!$errors || empty($errors)) {
            wp_die(__('No hay errores para descargar.', 'custom-certificates'));
        }

        $csv_content = $this->generate_error_report($errors);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="reporte-errores-importacion.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $csv_content;
        exit;
    }

    /**
     * Get batch size
     *
     * @return int Batch size
     */
    public function get_batch_size() {
        return $this->batch_size;
    }
}
