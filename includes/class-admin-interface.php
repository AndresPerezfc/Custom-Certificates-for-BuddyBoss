<?php
/**
 * Admin Interface
 *
 * Handles admin interface for certificate management
 */

if (!defined('ABSPATH')) {
    exit;
}

class Custom_Cert_Admin {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Add admin menus
        add_action('admin_menu', array($this, 'add_admin_menus'), 20);

        // Add meta boxes
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_template_meta'), 10, 2);

        // Add admin columns
        add_filter('manage_bb_cert_template_posts_columns', array($this, 'template_columns'));
        add_action('manage_bb_cert_template_posts_custom_column', array($this, 'template_column_content'), 10, 2);
        add_filter('manage_bb_cert_assigned_posts_columns', array($this, 'assigned_columns'));
        add_action('manage_bb_cert_assigned_posts_custom_column', array($this, 'assigned_column_content'), 10, 2);

        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // Add row actions
        add_filter('post_row_actions', array($this, 'modify_row_actions'), 10, 2);
    }

    /**
     * Add admin menus
     */
    public function add_admin_menus() {
        // Assign Certificates submenu
        add_submenu_page(
            'edit.php?post_type=bb_cert_template',
            __('Asignar Certificados', 'custom-certificates'),
            __('Asignar Certificados', 'custom-certificates'),
            'manage_options',
            'assign-certificates',
            array($this, 'assign_certificates_page')
        );

        // Settings submenu
        add_submenu_page(
            'edit.php?post_type=bb_cert_template',
            __('Configuración', 'custom-certificates'),
            __('Configuración', 'custom-certificates'),
            'manage_options',
            'cert-settings',
            array($this, 'settings_page')
        );
    }

    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        // Template configuration
        add_meta_box(
            'cert_template_config',
            __('Configuración del Certificado', 'custom-certificates'),
            array($this, 'template_config_metabox'),
            'bb_cert_template',
            'normal',
            'high'
        );

        // Certificate details
        add_meta_box(
            'cert_details',
            __('Detalles del Certificado', 'custom-certificates'),
            array($this, 'certificate_details_metabox'),
            'bb_cert_assigned',
            'side',
            'high'
        );
    }

    /**
     * Template configuration metabox
     */
    public function template_config_metabox($post) {
        wp_nonce_field('save_template_config', 'template_config_nonce');

        $config = json_decode(get_post_meta($post->ID, '_cert_config', true), true);
        if (!$config) {
            $config = array(
                'text_color' => '#000000',
                'bg_color' => '#ffffff',
                'font_size' => '24',
                'orientation' => 'landscape'
            );
        }

        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="cert_text_color"><?php _e('Color de Texto', 'custom-certificates'); ?></label>
                </th>
                <td>
                    <input type="color"
                           id="cert_text_color"
                           name="cert_config[text_color]"
                           value="<?php echo esc_attr($config['text_color']); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="cert_bg_color"><?php _e('Color de Fondo', 'custom-certificates'); ?></label>
                </th>
                <td>
                    <input type="color"
                           id="cert_bg_color"
                           name="cert_config[bg_color]"
                           value="<?php echo esc_attr($config['bg_color']); ?>">
                    <p class="description"><?php _e('Solo se usa si no hay imagen destacada', 'custom-certificates'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="cert_font_size"><?php _e('Tamaño de Fuente', 'custom-certificates'); ?></label>
                </th>
                <td>
                    <input type="number"
                           id="cert_font_size"
                           name="cert_config[font_size]"
                           value="<?php echo esc_attr($config['font_size']); ?>"
                           min="12"
                           max="72">
                    <span>px</span>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="cert_orientation"><?php _e('Orientación', 'custom-certificates'); ?></label>
                </th>
                <td>
                    <select id="cert_orientation" name="cert_config[orientation]">
                        <option value="landscape" <?php selected($config['orientation'], 'landscape'); ?>>
                            <?php _e('Horizontal (Landscape)', 'custom-certificates'); ?>
                        </option>
                        <option value="portrait" <?php selected($config['orientation'], 'portrait'); ?>>
                            <?php _e('Vertical (Portrait)', 'custom-certificates'); ?>
                        </option>
                    </select>
                </td>
            </tr>
        </table>

        <div style="margin-top: 20px; padding: 15px; background: #f9f9f9; border-left: 4px solid #2271b1;">
            <h4 style="margin-top: 0;"><?php _e('Variables Disponibles', 'custom-certificates'); ?></h4>
            <p><?php _e('Puedes usar estas variables en el contenido del editor:', 'custom-certificates'); ?></p>
            <ul style="margin-left: 20px;">
                <li><code>{NOMBRE_USUARIO}</code> - <?php _e('Nombre del usuario', 'custom-certificates'); ?></li>
                <li><code>{EMAIL_USUARIO}</code> - <?php _e('Email del usuario', 'custom-certificates'); ?></li>
                <li><code>{FECHA_EMISION}</code> - <?php _e('Fecha de emisión', 'custom-certificates'); ?></li>
                <li><code>{CODIGO_VERIFICACION}</code> - <?php _e('Código de verificación', 'custom-certificates'); ?></li>
            </ul>
        </div>
        <?php
    }

    /**
     * Certificate details metabox
     */
    public function certificate_details_metabox($post) {
        $user_id = get_post_meta($post->ID, '_cert_user_id', true);
        $template_id = get_post_meta($post->ID, '_cert_template_id', true);
        $verification_code = get_post_meta($post->ID, '_cert_verification_code', true);
        $issue_date = get_post_meta($post->ID, '_cert_issue_date', true);

        $user = get_userdata($user_id);
        $template = get_post($template_id);

        ?>
        <div class="cert-details">
            <p>
                <strong><?php _e('Usuario:', 'custom-certificates'); ?></strong><br>
                <?php echo $user ? esc_html($user->display_name) : __('Usuario no encontrado', 'custom-certificates'); ?>
            </p>

            <p>
                <strong><?php _e('Plantilla:', 'custom-certificates'); ?></strong><br>
                <?php echo $template ? esc_html($template->post_title) : __('Plantilla no encontrada', 'custom-certificates'); ?>
            </p>

            <p>
                <strong><?php _e('Código de Verificación:', 'custom-certificates'); ?></strong><br>
                <code><?php echo esc_html($verification_code); ?></code>
            </p>

            <p>
                <strong><?php _e('Fecha de Emisión:', 'custom-certificates'); ?></strong><br>
                <?php echo date_i18n(get_option('date_format'), strtotime($issue_date)); ?>
            </p>

            <p>
                <a href="<?php echo esc_url(Custom_Cert_PDF_Generator::get_download_url($post->ID)); ?>"
                   class="button button-primary"
                   target="_blank">
                    <span class="dashicons dashicons-download" style="vertical-align: middle;"></span>
                    <?php _e('Descargar PDF', 'custom-certificates'); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * Save template meta
     */
    public function save_template_meta($post_id, $post) {
        // Check if it's the right post type
        if ($post->post_type !== 'bb_cert_template') {
            return;
        }

        // Verify nonce
        if (!isset($_POST['template_config_nonce']) || !wp_verify_nonce($_POST['template_config_nonce'], 'save_template_config')) {
            return;
        }

        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save configuration
        if (isset($_POST['cert_config'])) {
            $config = array_map('sanitize_text_field', $_POST['cert_config']);
            update_post_meta($post_id, '_cert_config', json_encode($config));
        }
    }

    /**
     * Assign certificates page
     */
    public function assign_certificates_page() {
        // Get templates
        $templates = get_posts(array(
            'post_type' => 'bb_cert_template',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));

        // Get recently assigned certificates
        $recent_assignments = get_posts(array(
            'post_type' => 'bb_cert_assigned',
            'posts_per_page' => 10,
            'orderby' => 'date',
            'order' => 'DESC'
        ));

        include CUSTOM_CERT_PLUGIN_DIR . 'admin/views/assign-certificates.php';
    }

    /**
     * Settings page
     */
    public function settings_page() {
        include CUSTOM_CERT_PLUGIN_DIR . 'admin/views/settings.php';
    }

    /**
     * Template columns
     */
    public function template_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['thumbnail'] = __('Vista Previa', 'custom-certificates');
        $new_columns['assigned_count'] = __('Asignados', 'custom-certificates');
        $new_columns['date'] = $columns['date'];

        return $new_columns;
    }

    /**
     * Template column content
     */
    public function template_column_content($column, $post_id) {
        switch ($column) {
            case 'thumbnail':
                $thumbnail = get_the_post_thumbnail($post_id, array(100, 70));
                echo $thumbnail ? $thumbnail : '<span class="dashicons dashicons-format-image"></span>';
                break;

            case 'assigned_count':
                $count = $this->count_template_assignments($post_id);
                echo '<strong>' . $count . '</strong>';
                break;
        }
    }

    /**
     * Assigned certificates columns
     */
    public function assigned_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = __('Certificado', 'custom-certificates');
        $new_columns['user'] = __('Usuario', 'custom-certificates');
        $new_columns['template'] = __('Plantilla', 'custom-certificates');
        $new_columns['verification_code'] = __('Código', 'custom-certificates');
        $new_columns['date'] = __('Fecha de Emisión', 'custom-certificates');

        return $new_columns;
    }

    /**
     * Assigned column content
     */
    public function assigned_column_content($column, $post_id) {
        switch ($column) {
            case 'user':
                $user_id = get_post_meta($post_id, '_cert_user_id', true);
                $user = get_userdata($user_id);
                if ($user) {
                    echo '<strong>' . esc_html($user->display_name) . '</strong><br>';
                    echo '<small>' . esc_html($user->user_email) . '</small>';
                }
                break;

            case 'template':
                $template_id = get_post_meta($post_id, '_cert_template_id', true);
                $template = get_post($template_id);
                if ($template) {
                    echo '<a href="' . get_edit_post_link($template_id) . '">' . esc_html($template->post_title) . '</a>';
                }
                break;

            case 'verification_code':
                $code = get_post_meta($post_id, '_cert_verification_code', true);
                echo '<code>' . esc_html($code) . '</code>';
                break;
        }
    }

    /**
     * Count template assignments
     */
    private function count_template_assignments($template_id) {
        $args = array(
            'post_type' => 'bb_cert_assigned',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => '_cert_template_id',
                    'value' => $template_id,
                    'compare' => '='
                )
            )
        );

        $posts = get_posts($args);
        return count($posts);
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on our admin pages
        if (!in_array($hook, array('bb_cert_template_page_assign-certificates', 'post.php', 'post-new.php', 'edit.php'))) {
            return;
        }

        // Enqueue Select2 for user selection
        wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0');
        wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), '4.1.0', true);

        // Enqueue custom admin scripts
        wp_enqueue_style('custom-cert-admin', CUSTOM_CERT_PLUGIN_URL . 'admin/css/admin.css', array(), CUSTOM_CERT_VERSION);
        wp_enqueue_script('custom-cert-admin', CUSTOM_CERT_PLUGIN_URL . 'admin/js/admin.js', array('jquery', 'select2'), CUSTOM_CERT_VERSION, true);

        // Localize script
        wp_localize_script('custom-cert-admin', 'customCertAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'assign_nonce' => wp_create_nonce('assign_certificate'),
            'remove_nonce' => wp_create_nonce('remove_certificate'),
            'search_nonce' => wp_create_nonce('search_users'),
            'strings' => array(
                'confirm_remove' => __('¿Estás seguro de que quieres eliminar este certificado?', 'custom-certificates'),
                'assigning' => __('Asignando...', 'custom-certificates'),
                'success' => __('Certificado asignado correctamente', 'custom-certificates'),
                'error' => __('Error al asignar certificado', 'custom-certificates')
            )
        ));
    }

    /**
     * Modify row actions
     */
    public function modify_row_actions($actions, $post) {
        if ($post->post_type === 'bb_cert_assigned') {
            $download_url = Custom_Cert_PDF_Generator::get_download_url($post->ID);

            $actions['download'] = sprintf(
                '<a href="%s" target="_blank">%s</a>',
                esc_url($download_url),
                __('Descargar PDF', 'custom-certificates')
            );
        }

        return $actions;
    }
}
