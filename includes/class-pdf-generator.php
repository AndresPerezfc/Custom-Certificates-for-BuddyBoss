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
        add_action('init', array($this, 'handle_pdf_download'));
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
            // Create PDF
            $mpdf = new \Mpdf\Mpdf(array(
                'mode' => 'utf-8',
                'format' => 'A4-L', // A4 Landscape
                'margin_left' => 0,
                'margin_right' => 0,
                'margin_top' => 0,
                'margin_bottom' => 0,
                'margin_header' => 0,
                'margin_footer' => 0
            ));

            // Generate HTML
            $html = $this->generate_html($data);

            // Write HTML to PDF
            $mpdf->WriteHTML($html);

            // Output PDF for download
            $filename = sanitize_file_name(sprintf(
                'certificado-%s-%s.pdf',
                $data['user_name'],
                $data['verification_code']
            ));

            $mpdf->Output($filename, 'D'); // D = Download

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
            'background_image' => get_the_post_thumbnail_url($template_id, 'full')
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
        $bg_color = isset($data['template_config']['bg_color']) ? $data['template_config']['bg_color'] : '#ffffff';
        $text_color = isset($data['template_config']['text_color']) ? $data['template_config']['text_color'] : '#000000';
        $font_size = isset($data['template_config']['font_size']) ? $data['template_config']['font_size'] : '24';

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
                    font-family: 'DejaVu Sans', Arial, sans-serif;
                    margin: 0;
                    padding: 0;
                    background-color: <?php echo esc_attr($bg_color); ?>;
                }
                .certificate-container {
                    position: relative;
                    width: 297mm;
                    height: 210mm;
                    padding: 20mm;
                    box-sizing: border-box;
                    <?php if ($data['background_image']): ?>
                    background-image: url('<?php echo esc_url($data['background_image']); ?>');
                    background-size: cover;
                    background-position: center;
                    <?php endif; ?>
                }
                .certificate-header {
                    text-align: center;
                    margin-bottom: 30mm;
                }
                .certificate-title {
                    font-size: 48px;
                    font-weight: bold;
                    color: <?php echo esc_attr($text_color); ?>;
                    margin-bottom: 10mm;
                    text-transform: uppercase;
                }
                .certificate-subtitle {
                    font-size: 24px;
                    color: <?php echo esc_attr($text_color); ?>;
                }
                .certificate-body {
                    text-align: center;
                    margin: 30mm 0;
                }
                .certificate-text {
                    font-size: 18px;
                    color: <?php echo esc_attr($text_color); ?>;
                    line-height: 1.8;
                    margin-bottom: 10mm;
                }
                .user-name {
                    font-size: <?php echo esc_attr($font_size); ?>px;
                    font-weight: bold;
                    color: <?php echo esc_attr($text_color); ?>;
                    margin: 15mm 0;
                    padding: 5mm 0;
                    border-top: 2px solid <?php echo esc_attr($text_color); ?>;
                    border-bottom: 2px solid <?php echo esc_attr($text_color); ?>;
                }
                .certificate-footer {
                    position: absolute;
                    bottom: 20mm;
                    left: 20mm;
                    right: 20mm;
                    display: flex;
                    justify-content: space-between;
                }
                .issue-date, .verification-code {
                    font-size: 12px;
                    color: <?php echo esc_attr($text_color); ?>;
                }
                .verification-code {
                    text-align: right;
                }
            </style>
        </head>
        <body>
            <div class="certificate-container">
                <div class="certificate-header">
                    <div class="certificate-title">Certificado</div>
                    <div class="certificate-subtitle"><?php echo esc_html($data['template_name']); ?></div>
                </div>

                <div class="certificate-body">
                    <div class="certificate-text">
                        Se otorga el presente certificado a:
                    </div>

                    <div class="user-name">
                        <?php echo esc_html($data['user_name']); ?>
                    </div>

                    <div class="certificate-text">
                        Por haber completado satisfactoriamente los requisitos establecidos
                        para la obtención de este reconocimiento.
                    </div>

                    <?php if (!empty($data['custom_data']['description'])): ?>
                    <div class="certificate-text">
                        <?php echo esc_html($data['custom_data']['description']); ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="certificate-footer">
                    <div class="issue-date">
                        <strong>Fecha de emisión:</strong><br>
                        <?php echo esc_html($data['issue_date_formatted']); ?>
                    </div>
                    <div class="verification-code">
                        <strong>Código de verificación:</strong><br>
                        <?php echo esc_html($data['verification_code']); ?>
                    </div>
                </div>
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
}
