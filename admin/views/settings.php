<?php
/**
 * Settings Page
 */

if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission
if (isset($_POST['submit_settings']) && check_admin_referer('cert_settings', 'cert_settings_nonce')) {
    // Save settings here
    update_option('custom_cert_enable_notifications', isset($_POST['enable_notifications']) ? '1' : '0');
    update_option('custom_cert_notification_subject', sanitize_text_field($_POST['notification_subject']));
    update_option('custom_cert_notification_message', wp_kses_post($_POST['notification_message']));

    echo '<div class="notice notice-success"><p>' . __('Configuración guardada correctamente.', 'custom-certificates') . '</p></div>';
}

$enable_notifications = get_option('custom_cert_enable_notifications', '0');
$notification_subject = get_option('custom_cert_notification_subject', __('Has recibido un nuevo certificado', 'custom-certificates'));
$notification_message = get_option('custom_cert_notification_message', __('Hola {NOMBRE_USUARIO},\n\nTe informamos que has recibido un nuevo certificado: {NOMBRE_CERTIFICADO}.\n\nPuedes verlo y descargarlo desde tu perfil.', 'custom-certificates'));

?>

<div class="wrap custom-cert-settings-page">
    <h1><?php _e('Configuración de Certificados', 'custom-certificates'); ?></h1>

    <div class="settings-container">
        <div class="settings-main">
            <div class="card">
                <h2><?php _e('Notificaciones', 'custom-certificates'); ?></h2>

                <form method="post" action="">
                    <?php wp_nonce_field('cert_settings', 'cert_settings_nonce'); ?>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <?php _e('Habilitar Notificaciones', 'custom-certificates'); ?>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox"
                                           name="enable_notifications"
                                           value="1"
                                           <?php checked($enable_notifications, '1'); ?>>
                                    <?php _e('Enviar email al usuario cuando recibe un certificado', 'custom-certificates'); ?>
                                </label>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="notification_subject"><?php _e('Asunto del Email', 'custom-certificates'); ?></label>
                            </th>
                            <td>
                                <input type="text"
                                       id="notification_subject"
                                       name="notification_subject"
                                       value="<?php echo esc_attr($notification_subject); ?>"
                                       class="regular-text">
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="notification_message"><?php _e('Mensaje del Email', 'custom-certificates'); ?></label>
                            </th>
                            <td>
                                <textarea id="notification_message"
                                          name="notification_message"
                                          rows="8"
                                          class="large-text"><?php echo esc_textarea($notification_message); ?></textarea>
                                <p class="description">
                                    <?php _e('Variables disponibles:', 'custom-certificates'); ?>
                                    <code>{NOMBRE_USUARIO}</code>,
                                    <code>{NOMBRE_CERTIFICADO}</code>,
                                    <code>{CODIGO_VERIFICACION}</code>
                                </p>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="submit" name="submit_settings" class="button button-primary">
                            <?php _e('Guardar Configuración', 'custom-certificates'); ?>
                        </button>
                    </p>
                </form>
            </div>

            <div class="card" style="margin-top: 20px;">
                <h2><?php _e('Importación CSV', 'custom-certificates'); ?></h2>

                <p><?php _e('Próximamente: Podrás importar múltiples asignaciones de certificados usando un archivo CSV.', 'custom-certificates'); ?></p>

                <h4><?php _e('Formato esperado del CSV:', 'custom-certificates'); ?></h4>
                <pre style="background: #f5f5f5; padding: 10px; border: 1px solid #ddd;">email,tipo_certificado,fecha_emision,notas
juan.perez@example.com,Competencias Digitales,2024-01-15,Nivel Avanzado
maria.garcia@example.com,Competencias Digitales,2024-01-15,</pre>

                <p>
                    <button class="button" disabled>
                        <span class="dashicons dashicons-upload"></span>
                        <?php _e('Importar CSV (Próximamente)', 'custom-certificates'); ?>
                    </button>
                </p>
            </div>
        </div>

        <div class="settings-sidebar">
            <div class="card">
                <h3><?php _e('Información del Plugin', 'custom-certificates'); ?></h3>
                <p><strong><?php _e('Versión:', 'custom-certificates'); ?></strong> <?php echo CUSTOM_CERT_VERSION; ?></p>

                <hr>

                <h4><?php _e('Estadísticas', 'custom-certificates'); ?></h4>
                <?php
                $total_templates = wp_count_posts('bb_cert_template');
                $total_assigned = wp_count_posts('bb_cert_assigned');
                ?>
                <ul>
                    <li><?php printf(__('Plantillas: %d', 'custom-certificates'), $total_templates->publish); ?></li>
                    <li><?php printf(__('Certificados asignados: %d', 'custom-certificates'), $total_assigned->publish); ?></li>
                </ul>

                <hr>

                <h4><?php _e('Enlaces Útiles', 'custom-certificates'); ?></h4>
                <ul>
                    <li><a href="<?php echo admin_url('edit.php?post_type=bb_cert_template'); ?>"><?php _e('Plantillas', 'custom-certificates'); ?></a></li>
                    <li><a href="<?php echo admin_url('edit.php?post_type=bb_cert_assigned'); ?>"><?php _e('Certificados Asignados', 'custom-certificates'); ?></a></li>
                    <li><a href="<?php echo admin_url('edit.php?post_type=bb_cert_template&page=assign-certificates'); ?>"><?php _e('Asignar Certificados', 'custom-certificates'); ?></a></li>
                </ul>
            </div>

            <div class="card" style="margin-top: 20px;">
                <h3><?php _e('Soporte', 'custom-certificates'); ?></h3>
                <p><?php _e('¿Necesitas ayuda? Consulta la documentación o contacta con soporte.', 'custom-certificates'); ?></p>
            </div>
        </div>
    </div>
</div>

<style>
.custom-cert-settings-page .settings-container {
    display: flex;
    gap: 20px;
    margin-top: 20px;
}

.custom-cert-settings-page .settings-main {
    flex: 1;
}

.custom-cert-settings-page .settings-sidebar {
    width: 300px;
    flex-shrink: 0;
}

.custom-cert-settings-page .card {
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    padding: 20px;
}

.custom-cert-settings-page .card h2,
.custom-cert-settings-page .card h3 {
    margin-top: 0;
}

.custom-cert-settings-page .card h4 {
    margin-bottom: 10px;
}

.custom-cert-settings-page .card ul {
    margin-left: 20px;
}

.custom-cert-settings-page .button .dashicons {
    vertical-align: middle;
    margin-right: 5px;
}

@media (max-width: 768px) {
    .custom-cert-settings-page .settings-container {
        flex-direction: column;
    }

    .custom-cert-settings-page .settings-sidebar {
        width: 100%;
    }
}
</style>
