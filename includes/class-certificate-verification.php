<?php
/**
 * Certificate Verification
 *
 * Handles public certificate verification via shortcode
 */

if (!defined('ABSPATH')) {
    exit;
}

class Custom_Cert_Verification {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Register shortcode
        add_shortcode('verificar_certificado', array($this, 'verification_shortcode'));

        // AJAX handlers (for both logged in and non-logged in users)
        add_action('wp_ajax_verify_certificate_public', array($this, 'ajax_verify_certificate'));
        add_action('wp_ajax_nopriv_verify_certificate_public', array($this, 'ajax_verify_certificate'));

        // Enqueue frontend assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        global $post;

        // Only load on pages that contain our shortcode
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'verificar_certificado')) {
            wp_enqueue_style(
                'custom-cert-verification',
                CUSTOM_CERT_PLUGIN_URL . 'public/css/verification.css',
                array(),
                CUSTOM_CERT_VERSION
            );

            wp_enqueue_script(
                'custom-cert-verification',
                CUSTOM_CERT_PLUGIN_URL . 'public/js/verification.js',
                array('jquery'),
                CUSTOM_CERT_VERSION,
                true
            );

            wp_localize_script('custom-cert-verification', 'certVerification', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('verify_certificate_public'),
                'strings' => array(
                    'verifying' => __('Verificando...', 'custom-certificates'),
                    'error' => __('Error al verificar. Intenta de nuevo.', 'custom-certificates'),
                    'empty_code' => __('Por favor, ingresa un código de verificación.', 'custom-certificates')
                )
            ));
        }
    }

    /**
     * Verification shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function verification_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => __('Verificar Certificado', 'custom-certificates'),
            'description' => __('Ingresa el código de verificación que aparece en el certificado para comprobar su autenticidad.', 'custom-certificates'),
            'button_text' => __('Verificar', 'custom-certificates'),
            'placeholder' => __('Ej: ABC123XYZ0', 'custom-certificates')
        ), $atts, 'verificar_certificado');

        ob_start();
        ?>
        <div class="cert-verification-wrapper">
            <div class="cert-verification-form-container">
                <div class="cert-verification-logo">
                    <img src="<?php echo esc_url(CUSTOM_CERT_PLUGIN_URL . 'assets/image/logos-B10-Innova.png'); ?>" alt="<?php esc_attr_e('Logo', 'custom-certificates'); ?>">
                </div>
                                
                <?php if (!empty($atts['title'])): ?>
                    <h3 class="cert-verification-title"><?php echo esc_html($atts['title']); ?></h3>
                <?php endif; ?>

                <?php if (!empty($atts['description'])): ?>
                    <p class="cert-verification-description"><?php echo esc_html($atts['description']); ?></p>
                <?php endif; ?>

                <form id="cert-verification-form" class="cert-verification-form">
                    <div class="cert-verification-input-group">
                        <input type="text"
                               id="cert-verification-code"
                               name="verification_code"
                               class="cert-verification-input"
                               placeholder="<?php echo esc_attr($atts['placeholder']); ?>"
                               maxlength="20"
                               autocomplete="off"
                               required>
                        <button type="submit" class="cert-verification-button">
                            <span class="button-text"><?php echo esc_html($atts['button_text']); ?></span>
                            <span class="button-loading" style="display: none;">
                                <svg class="spinner" viewBox="0 0 24 24" width="18" height="18">
                                    <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="3" stroke-dasharray="31.4" stroke-linecap="round"/>
                                </svg>
                            </span>
                        </button>
                    </div>
                </form>

                <div id="cert-verification-result" class="cert-verification-result" style="display: none;"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX handler for certificate verification
     */
    public function ajax_verify_certificate() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'verify_certificate_public')) {
            wp_send_json_error(array(
                'message' => __('Error de seguridad. Recarga la página e intenta de nuevo.', 'custom-certificates')
            ));
        }

        // Get and sanitize verification code
        $code = isset($_POST['code']) ? sanitize_text_field(strtoupper(trim($_POST['code']))) : '';

        if (empty($code)) {
            wp_send_json_error(array(
                'message' => __('Por favor, ingresa un código de verificación.', 'custom-certificates')
            ));
        }

        // Search for certificate with this code
        $certificate = $this->find_certificate_by_code($code);

        if (!$certificate) {
            wp_send_json_error(array(
                'message' => __('Certificado no encontrado. Verifica que el código sea correcto.', 'custom-certificates'),
                'not_found' => true
            ));
        }

        // Get certificate details
        $user_id = get_post_meta($certificate->ID, '_cert_user_id', true);
        $template_id = get_post_meta($certificate->ID, '_cert_template_id', true);
        $issue_date = get_post_meta($certificate->ID, '_cert_issue_date', true);
        $verification_code = get_post_meta($certificate->ID, '_cert_verification_code', true);

        $user = get_userdata($user_id);
        $template = get_post($template_id);

        // Format issue date
        $formatted_date = date_i18n('j \d\e F \d\e Y', strtotime($issue_date));

        // Build response
        $response = array(
            'valid' => true,
            'certificate' => array(
                'holder_name' => $user ? $user->display_name : __('Usuario no disponible', 'custom-certificates'),
                'certificate_name' => $template ? $template->post_title : __('Certificado', 'custom-certificates'),
                'issue_date' => $formatted_date,
                'verification_code' => $verification_code
            ),
            'message' => __('Certificado válido', 'custom-certificates')
        );

        // Allow filtering of response data
        $response = apply_filters('custom_cert_verification_response', $response, $certificate);

        wp_send_json_success($response);
    }

    /**
     * Find certificate by verification code
     *
     * @param string $code Verification code
     * @return WP_Post|null Certificate post or null
     */
    private function find_certificate_by_code($code) {
        $args = array(
            'post_type' => 'bb_cert_assigned',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'meta_query' => array(
                array(
                    'key' => '_cert_verification_code',
                    'value' => $code,
                    'compare' => '='
                )
            )
        );

        $certificates = get_posts($args);

        return !empty($certificates) ? $certificates[0] : null;
    }
}
