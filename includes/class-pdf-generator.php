<?php
/**
 * PDF Generator
 *
 * Generates PDF certificates dynamically
 */

if (!defined('ABSPATH')) {
    exit;
}

class Custom_Cert_PDF_Generator {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('template_redirect', array($this, 'handle_pdf_download'));
    }

    /**
     * Handle PDF download request
     */
    public function handle_pdf_download() {
        if (!isset($_GET['download_certificate']) || !isset($_GET['cert_id'])) {
            return;
        }

        $certificate_id = intval($_GET['cert_id']);
        $nonce = isset($_GET['_wpnonce']) ? $_GET['_wpnonce'] : '';

        // Verify nonce
        if (!wp_verify_nonce($nonce, 'download_cert_' . $certificate_id)) {
            wp_die(__('Enlace de descarga inválido o expirado', 'custom-certificates'));
        }

        // Get certificate
        $certificate = get_post($certificate_id);
        if (!$certificate || $certificate->post_type !== 'bb_cert_assigned') {
            wp_die(__('Certificado no encontrado', 'custom-certificates'));
        }

        // Check permissions
        $user_id = get_post_meta($certificate_id, '_cert_user_id', true);
        $current_user_id = get_current_user_id();

        if ($current_user_id != $user_id && !current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para descargar este certificado', 'custom-certificates'));
        }

        // Generate and download PDF
        $this->generate_and_download($certificate_id);
        exit;
    }

    /**
     * Generate and download PDF
     *
     * @param int $certificate_id Certificate ID
     */
    public function generate_and_download($certificate_id) {
        // Get certificate data
        $data = $this->get_certificate_data($certificate_id);

        if (!$data) {
            wp_die(__('Error al obtener datos del certificado', 'custom-certificates'));
        }

        // Load mPDF library
        $this->load_mpdf();

        try {
            // Determine format based on template configuration
            $orientation = 'landscape'; // Default
            if (isset($data['template_config']['orientation'])) {
                $orientation = $data['template_config']['orientation'];
            }

            // Set format: Letter-L for landscape, Letter-P for portrait
            $format = ($orientation === 'portrait') ? 'Letter-P' : 'Letter-L';

            // Get font family from config
            $font_family = 'dejavusans'; // Default
            if (isset($data['template_config']['font_family'])) {
                $font_family = $data['template_config']['font_family'];
            }

            // Map font keys to mPDF font names
            $font_map = $this->get_font_map();
            $mpdf_font = isset($font_map[$font_family]) ? $font_map[$font_family]['mpdf'] : 'dejavusans';

            // Custom fonts directory
            $custom_font_dir = CUSTOM_CERT_PLUGIN_DIR . 'assets/fonts/';

            // Define custom fonts for mPDF
            $custom_font_data = array(
                'montserrat' => [
                    'R' => 'Montserrat-Regular.ttf',
                    'B' => 'Montserrat-Bold.ttf',
                    'I' => 'Montserrat-Italic.ttf',
                    'BI' => 'Montserrat-BoldItalic.ttf',
                ],
                'montserratblack' => [
                    'R' => 'Montserrat-Black.ttf',
                    'B' => 'Montserrat-Black.ttf',
                    'I' => 'Montserrat-Black.ttf',
                    'BI' => 'Montserrat-Black.ttf',
                ],
                'montserratextrabold' => [
                    'R' => 'Montserrat-ExtraBold.ttf',
                    'B' => 'Montserrat-ExtraBold.ttf',
                    'I' => 'Montserrat-ExtraBold.ttf',
                    'BI' => 'Montserrat-ExtraBold.ttf',
                ],
                'opensans' => [
                    'R' => 'OpenSans-Regular.ttf',
                    'B' => 'OpenSans-Bold.ttf',
                    'I' => 'OpenSans-Italic.ttf',
                    'BI' => 'OpenSans-BoldItalic.ttf',
                ],
                'opensansextrabold' => [
                    'R' => 'OpenSans-ExtraBold.ttf',
                    'B' => 'OpenSans-ExtraBold.ttf',
                    'I' => 'OpenSans-ExtraBoldItalic.ttf',
                    'BI' => 'OpenSans-ExtraBoldItalic.ttf',
                ],
                'roboto' => [
                    'R' => 'Roboto-Regular.ttf',
                    'B' => 'Roboto-Bold.ttf',
                    'I' => 'Roboto-Italic.ttf',
                    'BI' => 'Roboto-BoldItalic.ttf',
                ],
                'robotoblack' => [
                    'R' => 'Roboto-Black.ttf',
                    'B' => 'Roboto-Black.ttf',
                    'I' => 'Roboto-BlackItalic.ttf',
                    'BI' => 'Roboto-BlackItalic.ttf',
                ],
                'lato' => [
                    'R' => 'Lato-Regular.ttf',
                    'B' => 'Lato-Bold.ttf',
                    'I' => 'Lato-Italic.ttf',
                    'BI' => 'Lato-BoldItalic.ttf',
                ],
                'latoblack' => [
                    'R' => 'Lato-Black.ttf',
                    'B' => 'Lato-Black.ttf',
                    'I' => 'Lato-BlackItalic.ttf',
                    'BI' => 'Lato-BlackItalic.ttf',
                ],
            );

            // Build mPDF config
            $mpdf_config = array(
                'mode' => 'utf-8',
                'format' => $format,
                'margin_left' => 0,
                'margin_right' => 0,
                'margin_top' => 0,
                'margin_bottom' => 0,
                'margin_header' => 0,
                'margin_footer' => 0,
                'default_font_size' => 12,
                'default_font' => $mpdf_font
            );

            // Add custom font directory if it exists
            if (is_dir($custom_font_dir)) {
                $mpdf_config['fontDir'] = array_merge(
                    (new \Mpdf\Config\ConfigVariables())->getDefaults()['fontDir'],
                    [$custom_font_dir]
                );
                $mpdf_config['fontdata'] = array_merge(
                    (new \Mpdf\Config\FontVariables())->getDefaults()['fontdata'],
                    $custom_font_data
                );
            }

            // Create PDF with configured orientation and font
            $mpdf = new \Mpdf\Mpdf($mpdf_config);

            // Disable automatic page breaks
            $mpdf->shrink_tables_to_fit = 0;
            $mpdf->keep_table_proportions = false;

            // Generate HTML
            $html = $this->generate_html($data);

            // Write HTML to PDF
            $mpdf->WriteHTML($html);

            // Output PDF inline (display in browser)
            $filename = sanitize_file_name(sprintf(
                'certificado-%s-%s.pdf',
                $data['user_name'],
                $data['verification_code']
            ));

            $mpdf->Output($filename, 'I'); // I = Inline (display in browser)

        } catch (Exception $e) {
            wp_die(__('Error al generar PDF: ', 'custom-certificates') . $e->getMessage());
        }
    }

    /**
     * Get certificate data
     *
     * @param int $certificate_id Certificate ID
     * @return array|false Certificate data or false
     */
    private function get_certificate_data($certificate_id) {
        $certificate = get_post($certificate_id);
        if (!$certificate) {
            return false;
        }

        $user_id = get_post_meta($certificate_id, '_cert_user_id', true);
        $template_id = get_post_meta($certificate_id, '_cert_template_id', true);
        $verification_code = get_post_meta($certificate_id, '_cert_verification_code', true);
        $issue_date = get_post_meta($certificate_id, '_cert_issue_date', true);
        $custom_data = maybe_unserialize(get_post_meta($certificate_id, '_cert_custom_data', true));

        $user = get_userdata($user_id);
        $template = get_post($template_id);

        if (!$user || !$template) {
            return false;
        }

        $template_config = json_decode(get_post_meta($template_id, '_cert_config', true), true);
        if (!$template_config) {
            $template_config = array();
        }

        // Get template content and replace variables
        $template_content = $template->post_content;

        // Build replacements array with standard variables
        $replacements = array(
            'NOMBRE_USUARIO' => $user->display_name,
            'EMAIL_USUARIO' => $user->user_email,
            'FECHA_EMISION' => date_i18n(get_option('date_format'), strtotime($issue_date)),
            'CODIGO_VERIFICACION' => $verification_code
        );

        // Add custom variables from custom_data
        if (!empty($custom_data) && is_array($custom_data)) {
            foreach ($custom_data as $key => $value) {
                // Skip description field (it's not a template variable)
                if ($key === 'description') {
                    continue;
                }
                // Add custom variable (convert key to uppercase for consistency)
                $replacements[strtoupper($key)] = $value;
            }
        }

        $template_content = $this->replace_variables($template_content, $replacements);

        $data = array(
            'certificate_id' => $certificate_id,
            'user_id' => $user_id,
            'user_name' => $user->display_name,
            'user_email' => $user->user_email,
            'template_id' => $template_id,
            'template_name' => $template->post_title,
            'verification_code' => $verification_code,
            'issue_date' => $issue_date,
            'issue_date_formatted' => date_i18n(get_option('date_format'), strtotime($issue_date)),
            'custom_data' => $custom_data,
            'template_config' => $template_config,
            'background_image' => get_the_post_thumbnail_url($template_id, 'full'),
            'template_content' => $template_content // Processed content with variables replaced
        );

        return apply_filters('custom_cert_pdf_data', $data, $certificate_id);
    }

    /**
     * Generate HTML for PDF
     *
     * @param array $data Certificate data
     * @return string HTML content
     */
    private function generate_html($data) {
        // Start output buffering
        ob_start();

        // Check if custom template exists
        $template_file = $this->locate_template('certificate-pdf.php');

        if ($template_file) {
            include $template_file;
        } else {
            // Use default template
            $this->default_template($data);
        }

        // Get buffer content
        $html = ob_get_clean();

        return apply_filters('custom_cert_pdf_html', $html, $data);
    }

    /**
     * Default PDF template
     *
     * @param array $data Certificate data
     */
    private function default_template($data) {
        // Get background image
        $background_image = $data['background_image'];

        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                @page {
                    margin: 0;
                }
                body {
                    margin: 0;
                    padding: 0;
                    width: 100%;
                    height: 100%;
                }
                .certificate-page {
                    position: relative;
                    width: 100%;
                    height: 100%;
                    margin: 0;
                    padding: 0;
                }
                <?php if ($background_image): ?>
                .certificate-background {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    margin: 0;
                    padding: 0;
                }
                .certificate-background img {
                    width: 100%;
                    height: 100%;
                    display: block;
                }
                <?php endif; ?>
            </style>
        </head>
        <body>
            <div class="certificate-page">
                <?php if ($background_image): ?>
                    <div class="certificate-background">
                        <img src="<?php echo esc_url($background_image); ?>" alt="Certificate Background" />
                    </div>
                <?php endif; ?>
            </div>
        </body>
        </html>
        <?php
    }

    /**
     * Locate template file
     *
     * @param string $template_name Template file name
     * @return string|false Template path or false
     */
    private function locate_template($template_name) {
        // Check theme directory first
        $theme_template = locate_template(array(
            'custom-certificates/' . $template_name,
            $template_name
        ));

        if ($theme_template) {
            return $theme_template;
        }

        // Check plugin directory
        $plugin_template = CUSTOM_CERT_PLUGIN_DIR . 'public/templates/' . $template_name;
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }

        return false;
    }

    /**
     * Load mPDF library
     */
    private function load_mpdf() {
        // Check if mPDF is already loaded
        if (class_exists('\\Mpdf\\Mpdf')) {
            return;
        }

        // Try to load from Composer autoloader
        $autoload_path = CUSTOM_CERT_PLUGIN_DIR . 'vendor/autoload.php';

        if (file_exists($autoload_path)) {
            require_once $autoload_path;
        } else {
            // Display error message
            wp_die(
                __('La librería mPDF no está instalada. Por favor, ejecuta "composer install" en el directorio del plugin.', 'custom-certificates'),
                __('Error de dependencias', 'custom-certificates'),
                array('response' => 500)
            );
        }
    }

    /**
     * Get download URL for certificate
     *
     * @param int $certificate_id Certificate ID
     * @return string Download URL
     */
    public static function get_download_url($certificate_id) {
        return add_query_arg(array(
            'download_certificate' => '1',
            'cert_id' => $certificate_id,
            '_wpnonce' => wp_create_nonce('download_cert_' . $certificate_id)
        ), home_url());
    }

    /**
     * Replace variables in content
     *
     * @param string $content Content with variables
     * @param array $replacements Array of variable => value pairs
     * @return string Content with variables replaced
     */
    private function replace_variables($content, $replacements) {
        foreach ($replacements as $variable => $value) {
            // Replace {VARIABLE_NAME} format
            $content = str_replace('{' . $variable . '}', $value, $content);
        }

        return $content;
    }

    /**
     * Get font mapping for mPDF
     *
     * Maps user-friendly font names to mPDF compatible fonts
     * and provides CSS font-family strings
     *
     * @return array Font mapping
     */
    private function get_font_map() {
        return array(
            'dejavusans' => array(
                'mpdf' => 'dejavusans',
                'css' => 'DejaVu Sans, sans-serif'
            ),
            'montserrat' => array(
                'mpdf' => 'montserrat',
                'css' => 'Montserrat, sans-serif'
            ),
            'opensans' => array(
                'mpdf' => 'opensans',
                'css' => 'Open Sans, sans-serif'
            ),
            'helvetica' => array(
                'mpdf' => 'helvetica',
                'css' => 'Helvetica, Arial, sans-serif'
            ),
            'roboto' => array(
                'mpdf' => 'roboto',
                'css' => 'Roboto, sans-serif'
            ),
            'lato' => array(
                'mpdf' => 'lato',
                'css' => 'Lato, sans-serif'
            )
        );
    }
}
