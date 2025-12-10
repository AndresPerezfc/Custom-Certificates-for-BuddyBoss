<?php
/**
 * Helper Functions
 *
 * Global helper functions for the plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get user certificates
 *
 * @param int $user_id User ID (optional, defaults to current user)
 * @return array Array of certificate posts
 */
function custom_cert_get_user_certificates($user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    $assignment = Custom_Cert_Assignment::get_instance();
    return $assignment->get_user_certificates($user_id);
}

/**
 * Count user certificates
 *
 * @param int $user_id User ID (optional, defaults to current user)
 * @return int Number of certificates
 */
function custom_cert_count_user_certificates($user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    $assignment = Custom_Cert_Assignment::get_instance();
    return $assignment->count_user_certificates($user_id);
}

/**
 * Get certificate download URL
 *
 * @param int $certificate_id Certificate ID
 * @return string Download URL
 */
function custom_cert_get_download_url($certificate_id) {
    return Custom_Cert_PDF_Generator::get_download_url($certificate_id);
}

/**
 * Check if user has certificate
 *
 * @param int $user_id User ID
 * @param int $template_id Template ID
 * @return bool True if user has the certificate
 */
function custom_cert_user_has_certificate($user_id, $template_id) {
    $assignment = Custom_Cert_Assignment::get_instance();
    $certificate = $assignment->get_user_certificate($user_id, $template_id);
    return !empty($certificate);
}

/**
 * Assign certificate to user (helper function)
 *
 * @param int $user_id User ID
 * @param int $template_id Template ID
 * @param array $custom_data Custom data (optional)
 * @return int|WP_Error Certificate ID or error
 */
function custom_cert_assign_certificate($user_id, $template_id, $custom_data = array()) {
    $assignment = Custom_Cert_Assignment::get_instance();
    return $assignment->assign_certificate($user_id, $template_id, $custom_data);
}

/**
 * Remove certificate from user
 *
 * @param int $certificate_id Certificate ID
 * @return bool Success
 */
function custom_cert_remove_certificate($certificate_id) {
    $assignment = Custom_Cert_Assignment::get_instance();
    return $assignment->remove_certificate($certificate_id);
}

/**
 * Verify certificate by code
 *
 * @param string $code Verification code
 * @return WP_Post|null Certificate post or null
 */
function custom_cert_verify_certificate($code) {
    $assignment = Custom_Cert_Assignment::get_instance();
    return $assignment->verify_certificate($code);
}

/**
 * Get certificate details
 *
 * @param int $certificate_id Certificate ID
 * @return array|false Certificate details or false
 */
function custom_cert_get_certificate_details($certificate_id) {
    $certificate = get_post($certificate_id);

    if (!$certificate || $certificate->post_type !== 'bb_cert_assigned') {
        return false;
    }

    $user_id = get_post_meta($certificate_id, '_cert_user_id', true);
    $template_id = get_post_meta($certificate_id, '_cert_template_id', true);
    $verification_code = get_post_meta($certificate_id, '_cert_verification_code', true);
    $issue_date = get_post_meta($certificate_id, '_cert_issue_date', true);
    $custom_data = maybe_unserialize(get_post_meta($certificate_id, '_cert_custom_data', true));

    $user = get_userdata($user_id);
    $template = get_post($template_id);

    return array(
        'certificate_id' => $certificate_id,
        'user_id' => $user_id,
        'user_name' => $user ? $user->display_name : '',
        'user_email' => $user ? $user->user_email : '',
        'template_id' => $template_id,
        'template_name' => $template ? $template->post_title : '',
        'verification_code' => $verification_code,
        'issue_date' => $issue_date,
        'issue_date_formatted' => date_i18n(get_option('date_format'), strtotime($issue_date)),
        'custom_data' => $custom_data
    );
}

/**
 * Get all certificate templates
 *
 * @return array Array of template posts
 */
function custom_cert_get_templates() {
    return get_posts(array(
        'post_type' => 'bb_cert_template',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'title',
        'order' => 'ASC'
    ));
}

/**
 * Send certificate notification email
 *
 * @param int $certificate_id Certificate ID
 * @param int $user_id User ID
 * @return bool Success
 */
function custom_cert_send_notification($certificate_id, $user_id) {
    // Check if notifications are enabled
    if (get_option('custom_cert_enable_notifications') !== '1') {
        return false;
    }

    $user = get_userdata($user_id);
    if (!$user) {
        return false;
    }

    $details = custom_cert_get_certificate_details($certificate_id);
    if (!$details) {
        return false;
    }

    // Get email templates
    $subject = get_option('custom_cert_notification_subject', __('Has recibido un nuevo certificado', 'custom-certificates'));
    $message = get_option('custom_cert_notification_message', __('Hola {NOMBRE_USUARIO},\n\nTe informamos que has recibido un nuevo certificado: {NOMBRE_CERTIFICADO}.', 'custom-certificates'));

    // Replace variables
    $subject = str_replace(
        array('{NOMBRE_USUARIO}', '{NOMBRE_CERTIFICADO}', '{CODIGO_VERIFICACION}'),
        array($details['user_name'], $details['template_name'], $details['verification_code']),
        $subject
    );

    $message = str_replace(
        array('{NOMBRE_USUARIO}', '{NOMBRE_CERTIFICADO}', '{CODIGO_VERIFICACION}'),
        array($details['user_name'], $details['template_name'], $details['verification_code']),
        $message
    );

    // Add download link
    $download_url = custom_cert_get_download_url($certificate_id);
    $message .= "\n\n" . sprintf(__('Descargar certificado: %s', 'custom-certificates'), $download_url);

    // Send email
    return wp_mail($user->user_email, $subject, $message);
}

// Hook to send notification when certificate is assigned
add_action('custom_cert_assigned', function($certificate_id, $user_id, $template_id, $custom_data) {
    custom_cert_send_notification($certificate_id, $user_id);
}, 10, 4);
