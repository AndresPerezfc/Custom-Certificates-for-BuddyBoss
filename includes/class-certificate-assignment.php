<?php
/**
 * Certificate Assignment
 *
 * Handles assigning certificates to users
 */

if (!defined('ABSPATH')) {
    exit;
}

class Custom_Cert_Assignment {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Handle AJAX requests
        add_action('wp_ajax_assign_certificate', array($this, 'ajax_assign_certificate'));
        add_action('wp_ajax_remove_certificate', array($this, 'ajax_remove_certificate'));
        add_action('wp_ajax_search_users', array($this, 'ajax_search_users'));
        add_action('wp_ajax_get_template_variables', array($this, 'ajax_get_template_variables'));
    }

    /**
     * Assign certificate to user
     *
     * @param int $user_id User ID
     * @param int $template_id Certificate template ID
     * @param array $custom_data Custom data for the certificate
     * @return int|WP_Error Certificate ID or error
     */
    public function assign_certificate($user_id, $template_id, $custom_data = array()) {
        // Validate user
        $user = get_userdata($user_id);
        if (!$user) {
            return new WP_Error('invalid_user', __('Usuario no válido', 'custom-certificates'));
        }

        // Validate template
        $template = get_post($template_id);
        if (!$template || $template->post_type !== 'bb_cert_template') {
            return new WP_Error('invalid_template', __('Plantilla no válida', 'custom-certificates'));
        }

        // Check if certificate already exists
        $existing = $this->get_user_certificate($user_id, $template_id);
        if ($existing) {
            return new WP_Error('already_assigned', __('Este certificado ya ha sido asignado a este usuario', 'custom-certificates'));
        }

        // Generate verification code
        $verification_code = $this->generate_verification_code();

        // Create certificate post
        $certificate_id = wp_insert_post(array(
            'post_title' => sprintf(
                __('Certificado #%s - %s', 'custom-certificates'),
                $verification_code,
                $user->display_name
            ),
            'post_type' => 'bb_cert_assigned',
            'post_status' => 'publish',
            'post_author' => $user_id,
            'meta_input' => array(
                '_cert_user_id' => $user_id,
                '_cert_template_id' => $template_id,
                '_cert_verification_code' => $verification_code,
                '_cert_issue_date' => current_time('mysql'),
                '_cert_custom_data' => maybe_serialize($custom_data)
            )
        ));

        if (is_wp_error($certificate_id)) {
            return $certificate_id;
        }

        // Fire action hook
        do_action('custom_cert_assigned', $certificate_id, $user_id, $template_id, $custom_data);

        return $certificate_id;
    }

    /**
     * Assign certificate to multiple users
     *
     * @param array $user_ids Array of user IDs
     * @param int $template_id Certificate template ID
     * @param array $custom_data Custom data for certificates
     * @return array Results with success and error counts
     */
    public function assign_certificate_bulk($user_ids, $template_id, $custom_data = array()) {
        $results = array(
            'success' => 0,
            'errors' => 0,
            'error_messages' => array()
        );

        foreach ($user_ids as $user_id) {
            $result = $this->assign_certificate($user_id, $template_id, $custom_data);

            if (is_wp_error($result)) {
                $results['errors']++;
                $results['error_messages'][] = sprintf(
                    __('Usuario ID %d: %s', 'custom-certificates'),
                    $user_id,
                    $result->get_error_message()
                );
            } else {
                $results['success']++;
            }
        }

        return $results;
    }

    /**
     * Remove certificate assignment
     *
     * @param int $certificate_id Certificate ID
     * @return bool Success
     */
    public function remove_certificate($certificate_id) {
        $certificate = get_post($certificate_id);

        if (!$certificate || $certificate->post_type !== 'bb_cert_assigned') {
            return false;
        }

        $user_id = get_post_meta($certificate_id, '_cert_user_id', true);
        $template_id = get_post_meta($certificate_id, '_cert_template_id', true);

        // Delete the post
        $deleted = wp_delete_post($certificate_id, true);

        if ($deleted) {
            do_action('custom_cert_removed', $certificate_id, $user_id, $template_id);
            return true;
        }

        return false;
    }

    /**
     * Get user certificates
     *
     * @param int $user_id User ID
     * @param array $filters Optional filters (category, order)
     * @return array Array of certificate posts
     */
    public function get_user_certificates($user_id, $filters = array()) {
        $args = array(
            'post_type' => 'bb_cert_assigned',
            'author' => $user_id,
            'posts_per_page' => -1,
            'orderby' => 'meta_value',
            'meta_key' => '_cert_issue_date',
            'order' => isset($filters['order']) && $filters['order'] === 'ASC' ? 'ASC' : 'DESC'
        );

        // Filter by category
        if (!empty($filters['category'])) {
            $category_id = intval($filters['category']);

            // Get all template IDs that belong to this category
            $template_ids = get_posts(array(
                'post_type' => 'bb_cert_template',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'tax_query' => array(
                    array(
                        'taxonomy' => 'cert_category',
                        'field' => 'term_id',
                        'terms' => $category_id
                    )
                )
            ));

            if (!empty($template_ids)) {
                $args['meta_query'] = array(
                    array(
                        'key' => '_cert_template_id',
                        'value' => $template_ids,
                        'compare' => 'IN'
                    )
                );
            } else {
                // No templates in this category, return empty
                return array();
            }
        }

        return get_posts($args);
    }

    /**
     * Get specific user certificate
     *
     * @param int $user_id User ID
     * @param int $template_id Template ID
     * @return WP_Post|null Certificate post or null
     */
    public function get_user_certificate($user_id, $template_id) {
        $args = array(
            'post_type' => 'bb_cert_assigned',
            'author' => $user_id,
            'posts_per_page' => 1,
            'meta_query' => array(
                array(
                    'key' => '_cert_template_id',
                    'value' => $template_id,
                    'compare' => '='
                )
            )
        );

        $certificates = get_posts($args);
        return !empty($certificates) ? $certificates[0] : null;
    }

    /**
     * Count user certificates
     *
     * @param int $user_id User ID
     * @return int Number of certificates
     */
    public function count_user_certificates($user_id) {
        $args = array(
            'post_type' => 'bb_cert_assigned',
            'author' => $user_id,
            'posts_per_page' => -1,
            'fields' => 'ids'
        );

        $certificates = get_posts($args);
        return count($certificates);
    }

    /**
     * Get categories that have certificates assigned to a user
     *
     * @param int $user_id User ID
     * @return array Array of term objects
     */
    public function get_user_certificate_categories($user_id) {
        // Get all user certificates
        $certificates = $this->get_user_certificates($user_id);

        if (empty($certificates)) {
            return array();
        }

        // Collect unique template IDs
        $template_ids = array();
        foreach ($certificates as $cert) {
            $template_id = get_post_meta($cert->ID, '_cert_template_id', true);
            if ($template_id) {
                $template_ids[] = $template_id;
            }
        }

        $template_ids = array_unique($template_ids);

        if (empty($template_ids)) {
            return array();
        }

        // Get categories from these templates
        $categories = array();
        foreach ($template_ids as $template_id) {
            $terms = wp_get_post_terms($template_id, 'cert_category');
            if (!is_wp_error($terms) && !empty($terms)) {
                foreach ($terms as $term) {
                    $categories[$term->term_id] = $term;
                }
            }
        }

        return array_values($categories);
    }

    /**
     * Generate unique verification code
     *
     * @return string Verification code
     */
    private function generate_verification_code() {
        do {
            $code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));

            // Check if code already exists
            $existing = get_posts(array(
                'post_type' => 'bb_cert_assigned',
                'posts_per_page' => 1,
                'meta_key' => '_cert_verification_code',
                'meta_value' => $code
            ));
        } while (!empty($existing));

        return $code;
    }

    /**
     * Verify certificate by code
     *
     * @param string $code Verification code
     * @return WP_Post|null Certificate post or null
     */
    public function verify_certificate($code) {
        $args = array(
            'post_type' => 'bb_cert_assigned',
            'posts_per_page' => 1,
            'meta_key' => '_cert_verification_code',
            'meta_value' => strtoupper($code)
        );

        $certificates = get_posts($args);
        return !empty($certificates) ? $certificates[0] : null;
    }

    /**
     * AJAX: Assign certificate
     */
    public function ajax_assign_certificate() {
        check_ajax_referer('assign_certificate', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos', 'custom-certificates')));
        }

        $user_ids = isset($_POST['user_ids']) ? array_map('intval', $_POST['user_ids']) : array();
        $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;
        $custom_data = isset($_POST['custom_data']) ? $_POST['custom_data'] : array();

        if (empty($user_ids) || !$template_id) {
            wp_send_json_error(array('message' => __('Datos incompletos', 'custom-certificates')));
        }

        if (count($user_ids) === 1) {
            $result = $this->assign_certificate($user_ids[0], $template_id, $custom_data);

            if (is_wp_error($result)) {
                wp_send_json_error(array('message' => $result->get_error_message()));
            } else {
                wp_send_json_success(array(
                    'message' => __('Certificado asignado correctamente', 'custom-certificates'),
                    'certificate_id' => $result
                ));
            }
        } else {
            $results = $this->assign_certificate_bulk($user_ids, $template_id, $custom_data);

            wp_send_json_success(array(
                'message' => sprintf(
                    __('Asignados: %d, Errores: %d', 'custom-certificates'),
                    $results['success'],
                    $results['errors']
                ),
                'results' => $results
            ));
        }
    }

    /**
     * AJAX: Remove certificate
     */
    public function ajax_remove_certificate() {
        check_ajax_referer('remove_certificate', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos', 'custom-certificates')));
        }

        $certificate_id = isset($_POST['certificate_id']) ? intval($_POST['certificate_id']) : 0;

        if (!$certificate_id) {
            wp_send_json_error(array('message' => __('ID de certificado inválido', 'custom-certificates')));
        }

        $result = $this->remove_certificate($certificate_id);

        if ($result) {
            wp_send_json_success(array('message' => __('Certificado eliminado', 'custom-certificates')));
        } else {
            wp_send_json_error(array('message' => __('Error al eliminar certificado', 'custom-certificates')));
        }
    }

    /**
     * AJAX: Search users
     */
    public function ajax_search_users() {
        check_ajax_referer('search_users', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos', 'custom-certificates')));
        }

        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

        $users = get_users(array(
            'search' => '*' . $search . '*',
            'search_columns' => array('user_login', 'user_email', 'display_name'),
            'number' => 20
        ));

        $results = array();
        foreach ($users as $user) {
            $results[] = array(
                'id' => $user->ID,
                'text' => sprintf('%s (%s)', $user->display_name, $user->user_email)
            );
        }

        wp_send_json_success($results);
    }

    /**
     * AJAX: Get template custom variables
     */
    public function ajax_get_template_variables() {
        check_ajax_referer('search_users', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos', 'custom-certificates')));
        }

        $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;

        if (!$template_id) {
            wp_send_json_success(array('variables' => array()));
        }

        // Get template custom variables
        $custom_variables = json_decode(get_post_meta($template_id, '_cert_custom_variables', true), true);

        if (empty($custom_variables) || !is_array($custom_variables)) {
            wp_send_json_success(array('variables' => array()));
        }

        // Process options for select fields
        foreach ($custom_variables as &$var) {
            if ($var['type'] === 'select' && !empty($var['options'])) {
                // Convert comma-separated string to array
                $options_array = array_map('trim', explode(',', $var['options']));
                $var['options_array'] = $options_array;
            }
        }

        wp_send_json_success(array('variables' => $custom_variables));
    }
}
