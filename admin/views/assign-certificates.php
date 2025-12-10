<?php
/**
 * Assign Certificates Admin Page
 */

if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="wrap custom-cert-admin-page">
    <h1><?php _e('Asignar Certificados', 'custom-certificates'); ?></h1>

    <?php if (empty($templates)): ?>
        <div class="notice notice-warning">
            <p>
                <?php _e('No hay plantillas de certificados disponibles.', 'custom-certificates'); ?>
                <a href="<?php echo admin_url('post-new.php?post_type=bb_cert_template'); ?>">
                    <?php _e('Crear una plantilla', 'custom-certificates'); ?>
                </a>
            </p>
        </div>
    <?php else: ?>

        <div class="cert-assign-container">
            <div class="cert-assign-form-wrapper">
                <div class="card">
                    <h2><?php _e('Asignar Nuevo Certificado', 'custom-certificates'); ?></h2>

                    <form id="assign-certificate-form" method="post">
                        <?php wp_nonce_field('assign_certificate', 'assign_cert_nonce'); ?>

                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="cert_template"><?php _e('Plantilla de Certificado', 'custom-certificates'); ?> *</label>
                                </th>
                                <td>
                                    <select id="cert_template" name="template_id" required style="width: 100%; max-width: 400px;">
                                        <option value=""><?php _e('Selecciona una plantilla...', 'custom-certificates'); ?></option>
                                        <?php foreach ($templates as $template): ?>
                                            <option value="<?php echo esc_attr($template->ID); ?>">
                                                <?php echo esc_html($template->post_title); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description">
                                        <?php _e('Selecciona la plantilla de certificado que se asignará a los usuarios.', 'custom-certificates'); ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="cert_users"><?php _e('Usuarios', 'custom-certificates'); ?> *</label>
                                </th>
                                <td>
                                    <select id="cert_users" name="user_ids[]" multiple="multiple" required style="width: 100%; max-width: 400px;">
                                        <!-- Users will be loaded dynamically via Select2 -->
                                    </select>
                                    <p class="description">
                                        <?php _e('Busca y selecciona uno o varios usuarios. Puedes escribir el nombre o email.', 'custom-certificates'); ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="cert_description"><?php _e('Descripción Adicional', 'custom-certificates'); ?></label>
                                </th>
                                <td>
                                    <textarea id="cert_description"
                                              name="custom_data[description]"
                                              rows="3"
                                              style="width: 100%; max-width: 400px;"
                                              placeholder="<?php esc_attr_e('Ej: Por completar el curso de Competencias Digitales Nivel Avanzado', 'custom-certificates'); ?>"></textarea>
                                    <p class="description">
                                        <?php _e('Descripción opcional que aparecerá en el certificado (si la plantilla lo soporta).', 'custom-certificates'); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>

                        <p class="submit">
                            <button type="submit" class="button button-primary button-large">
                                <span class="dashicons dashicons-awards"></span>
                                <?php _e('Asignar Certificado(s)', 'custom-certificates'); ?>
                            </button>
                        </p>
                    </form>

                    <div id="assign-result" style="display: none; margin-top: 20px;"></div>
                </div>
            </div>

            <div class="cert-assign-sidebar">
                <div class="card">
                    <h3><?php _e('Asignación Rápida', 'custom-certificates'); ?></h3>
                    <p><?php _e('Usa este formulario para asignar certificados de manera individual o masiva.', 'custom-certificates'); ?></p>

                    <h4><?php _e('Consejos:', 'custom-certificates'); ?></h4>
                    <ul>
                        <li><?php _e('Puedes seleccionar múltiples usuarios a la vez', 'custom-certificates'); ?></li>
                        <li><?php _e('Los usuarios recibirán el certificado inmediatamente', 'custom-certificates'); ?></li>
                        <li><?php _e('Puedes ver todos los certificados asignados en la lista de Certificados Asignados', 'custom-certificates'); ?></li>
                    </ul>

                    <hr>

                    <h4><?php _e('Importación Masiva', 'custom-certificates'); ?></h4>
                    <p><?php _e('Para asignar certificados a muchos usuarios, considera usar un archivo CSV:', 'custom-certificates'); ?></p>
                    <a href="<?php echo admin_url('edit.php?post_type=bb_cert_template&page=cert-settings'); ?>" class="button">
                        <?php _e('Ir a Importación CSV', 'custom-certificates'); ?>
                    </a>
                </div>

                <?php if (!empty($recent_assignments)): ?>
                <div class="card" style="margin-top: 20px;">
                    <h3><?php _e('Últimos Certificados Asignados', 'custom-certificates'); ?></h3>
                    <ul class="recent-assignments">
                        <?php foreach ($recent_assignments as $cert): ?>
                            <?php
                            $user_id = get_post_meta($cert->ID, '_cert_user_id', true);
                            $template_id = get_post_meta($cert->ID, '_cert_template_id', true);
                            $user = get_userdata($user_id);
                            $template = get_post($template_id);
                            ?>
                            <li>
                                <strong><?php echo esc_html($user ? $user->display_name : 'Usuario desconocido'); ?></strong><br>
                                <small><?php echo esc_html($template ? $template->post_title : 'Plantilla desconocida'); ?></small><br>
                                <small class="cert-date"><?php echo human_time_diff(strtotime($cert->post_date), current_time('timestamp')); ?> <?php _e('atrás', 'custom-certificates'); ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>

    <?php endif; ?>
</div>

<style>
.custom-cert-admin-page .cert-assign-container {
    display: flex;
    gap: 20px;
    margin-top: 20px;
}

.custom-cert-admin-page .cert-assign-form-wrapper {
    flex: 1;
}

.custom-cert-admin-page .cert-assign-sidebar {
    width: 300px;
    flex-shrink: 0;
}

.custom-cert-admin-page .card {
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    padding: 20px;
}

.custom-cert-admin-page .card h2,
.custom-cert-admin-page .card h3 {
    margin-top: 0;
}

.custom-cert-admin-page .card h4 {
    margin-bottom: 10px;
}

.custom-cert-admin-page .recent-assignments {
    list-style: none;
    margin: 0;
    padding: 0;
}

.custom-cert-admin-page .recent-assignments li {
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
}

.custom-cert-admin-page .recent-assignments li:last-child {
    border-bottom: none;
}

.custom-cert-admin-page .cert-date {
    color: #666;
}

.custom-cert-admin-page .button .dashicons {
    vertical-align: middle;
    margin-right: 5px;
}

.custom-cert-admin-page #assign-result.success {
    padding: 12px;
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
    border-radius: 4px;
}

.custom-cert-admin-page #assign-result.error {
    padding: 12px;
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
    border-radius: 4px;
}

@media (max-width: 768px) {
    .custom-cert-admin-page .cert-assign-container {
        flex-direction: column;
    }

    .custom-cert-admin-page .cert-assign-sidebar {
        width: 100%;
    }
}
</style>
