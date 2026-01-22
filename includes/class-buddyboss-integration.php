<?php
/**
 * BuddyBoss Integration
 *
 * Integrates certificates with BuddyBoss profile tabs
 */

if (!defined('ABSPATH')) {
    exit;
}

class Custom_Cert_BuddyBoss
{

    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        // Check if BuddyBoss/BuddyPress is active
        if (!function_exists('bp_core_new_nav_item')) {
            add_action('admin_notices', array($this, 'buddyboss_missing_notice'));
            return;
        }

        // Setup profile tab
        add_action('bp_setup_nav', array($this, 'setup_nav'), 100);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Setup navigation tab
     */
    public function setup_nav()
    {
        // Only check count if we are on a user profile
        if (bp_is_user()) {
            $user_id = bp_displayed_user_id();

            // Privacy check: Only visible to owner or admin
            $current_user_id = get_current_user_id();
            if ($user_id !== $current_user_id && !current_user_can('manage_options')) {
                return;
            }

            $assignment = Custom_Cert_Assignment::get_instance();

            // If user has no certificates, do not create the tab
            if ($assignment->count_user_certificates($user_id) === 0) {
                return;
            }
        }

        // Main tab
        bp_core_new_nav_item(array(
            'name' => __('Certificados Innova', 'custom-certificates'),
            'slug' => 'certificados-innova',
            'screen_function' => array($this, 'certificates_screen'),
            'position' => 80,
            'item_css_id' => 'certificados-innova'
        ));
    }

    /**
     * Main certificates screen
     */
    public function certificates_screen()
    {
        add_action('bp_template_title', array($this, 'certificates_title'));
        add_action('bp_template_content', array($this, 'certificates_content'));
        bp_core_load_template(apply_filters('bp_core_template_plugin', 'members/single/plugins'));
    }

    /**
     * Certificates title
     * Returns empty - title is rendered inside content for better filter integration
     */
    public function certificates_title()
    {
        // Title is now rendered inside certificates_content() for better UI integration
        return;
    }

    /**
     * Certificates content
     */
    public function certificates_content()
    {
        $user_id = bp_displayed_user_id();
        $assignment = Custom_Cert_Assignment::get_instance();

        // Get filter parameters from URL
        $filters = array(
            'category' => isset($_GET['cert_category']) ? intval($_GET['cert_category']) : 0,
            'order' => isset($_GET['cert_order']) && $_GET['cert_order'] === 'ASC' ? 'ASC' : 'DESC'
        );

        // Get user certificates with filters
        $certificates = $assignment->get_user_certificates($user_id, $filters);

        // Get available categories for this user
        $categories = $assignment->get_user_certificate_categories($user_id);

        // Load template
        $template_file = $this->locate_template('profile-certificates.php');

        if ($template_file) {
            include $template_file;
        } else {
            $this->default_certificates_template($certificates, $user_id, $categories, $filters);
        }
    }

    /**
     * Default certificates template
     *
     * @param array $certificates Array of certificate posts
     * @param int $user_id User ID
     * @param array $categories Available categories
     * @param array $filters Current filters
     */
    private function default_certificates_template($certificates, $user_id, $categories = array(), $filters = array())
    {
        $is_own_profile = (get_current_user_id() === $user_id);
        $displayed_user = get_userdata($user_id);
        $current_url = bp_displayed_user_domain() . 'certificados-innova/';

        ?>
        <div class="custom-certificates-wrapper">
            <div class="certificates-header">
                <h2 class="certificates-title"><?php _e('Certificados Innova', 'custom-certificates'); ?></h2>

                <?php if (!empty($categories) || !empty($certificates)): ?>
                    <form method="get" action="<?php echo esc_url($current_url); ?>" class="certificates-filter-form">
                        <?php if (!empty($categories)): ?>
                            <select name="cert_category" id="cert_category" class="cert-filter-select" onchange="this.form.submit()" title="<?php esc_attr_e('Filtrar por categoría', 'custom-certificates'); ?>">
                                <option value=""><?php _e('Todas las categorías', 'custom-certificates'); ?></option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo esc_attr($category->term_id); ?>" <?php selected($filters['category'], $category->term_id); ?>>
                                        <?php echo esc_html($category->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>

                        <select name="cert_order" id="cert_order" class="cert-filter-select" onchange="this.form.submit()" title="<?php esc_attr_e('Ordenar por fecha', 'custom-certificates'); ?>">
                            <option value="DESC" <?php selected($filters['order'], 'DESC'); ?>><?php _e('Más recientes', 'custom-certificates'); ?></option>
                            <option value="ASC" <?php selected($filters['order'], 'ASC'); ?>><?php _e('Más antiguos', 'custom-certificates'); ?></option>
                        </select>
                    </form>
                <?php endif; ?>
            </div>

            <?php if (!empty($certificates)): ?>
                <div class="certificates-grid">
                    <?php foreach ($certificates as $certificate): ?>
                        <?php
                        $template_id = get_post_meta($certificate->ID, '_cert_template_id', true);
                        $template = get_post($template_id);
                        $verification_code = get_post_meta($certificate->ID, '_cert_verification_code', true);
                        $issue_date = get_post_meta($certificate->ID, '_cert_issue_date', true);
                        $thumbnail = get_the_post_thumbnail_url($template_id, 'medium');
                        $download_url = Custom_Cert_PDF_Generator::get_download_url($certificate->ID);
                        ?>

                        <div class="certificate-item" data-cert-id="<?php echo esc_attr($certificate->ID); ?>">
                            <div class="certificate-thumbnail">
                                <?php if ($thumbnail): ?>
                                    <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr($template->post_title); ?>">
                                <?php else: ?>
                                    <div class="certificate-placeholder">
                                        <span class="dashicons dashicons-awards"></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="certificate-info">
                                <h3 class="certificate-name"><?php echo esc_html($template->post_title); ?></h3>

                                <div class="certificate-meta">
                                    <span class="certificate-date">
                                        <span class="dashicons dashicons-calendar-alt"></span>
                                        <?php echo date_i18n(get_option('date_format'), strtotime($issue_date)); ?>
                                    </span>
                                    <span class="certificate-code">
                                        <span class="dashicons dashicons-admin-network"></span>
                                        <?php echo esc_html($verification_code); ?>
                                    </span>
                                </div>

                                <?php if ($is_own_profile || current_user_can('manage_options')): ?>
                                    <div class="certificate-actions">
                                        <a href="<?php echo esc_url($download_url); ?>" class="button certificate-download" target="_blank">
                                            <span class="dashicons dashicons-download"></span>
                                            <?php _e('Descargar Certificado', 'custom-certificates'); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php endforeach; ?>
                </div>

            <?php else: ?>
                <div class="no-certificates-message">
                    <div class="no-certificates-icon">
                        <span class="dashicons dashicons-awards"></span>
                    </div>
                    <p>
                        <?php
                        $has_active_filter = !empty($filters['category']);
                        if ($has_active_filter) {
                            _e('No hay certificados en esta categoría.', 'custom-certificates');
                        } elseif ($is_own_profile) {
                            _e('Aún no tienes certificados.', 'custom-certificates');
                        } else {
                            printf(
                                __('%s aún no tiene certificados.', 'custom-certificates'),
                                esc_html($displayed_user->display_name)
                            );
                        }
                        ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <style>
            .custom-certificates-wrapper {
                padding: 20px;
            }

            .certificates-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 16px;
                margin-bottom: 24px;
                flex-wrap: wrap;
            }

            .certificates-title {
                margin: 0;
                font-size: 22px;
                font-weight: 600;
                color: #1e1e1e;
            }

            .certificates-filter-form {
                display: flex;
                gap: 10px;
                align-items: center;
            }

            .cert-filter-select {
                padding: 6px 28px 6px 10px;
                font-size: 13px;
                border: 1px solid #dcdcdc;
                border-radius: 6px;
                background-color: #fff;
                background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
                background-position: right 6px center;
                background-repeat: no-repeat;
                background-size: 14px 10px;
                appearance: none;
                -webkit-appearance: none;
                -moz-appearance: none;
                cursor: pointer;
                color: #495057;
                transition: border-color 0.2s ease, box-shadow 0.2s ease;
            }

            .cert-filter-select:hover {
                border-color: #667eea;
            }

            .cert-filter-select:focus {
                outline: none;
                border-color: #667eea;
                box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.15);
            }

            @media (max-width: 540px) {
                .certificates-header {
                    flex-direction: column;
                    align-items: flex-start;
                }

                .certificates-filter-form {
                    width: 100%;
                }

                .cert-filter-select {
                    flex: 1;
                    min-width: 0;
                }
            }

            .certificates-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 20px;
            }

            .certificate-item {
                background: #fff;
                border: 1px solid #e0e0e0;
                border-radius: 12px;
                overflow: hidden;
                transition: transform 0.3s ease, box-shadow 0.3s ease;
            }

            .certificate-item:hover {
                transform: translateY(-4px);
                box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            }

            .certificate-thumbnail {
                position: relative;
                padding-top: 70.7%;
                /* A4 Landscape ratio */
                background: #f5f5f5;
                overflow: hidden;
            }

            .certificate-thumbnail img {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                object-fit: cover;
                transition: transform 0.5s ease;
            }

            .certificate-item:hover .certificate-thumbnail img {
                transform: scale(1.05);
            }

            .certificate-placeholder {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            }

            .certificate-placeholder .dashicons {
                font-size: 60px;
                width: 60px;
                height: 60px;
                color: #fff;
            }

            .certificate-info {
                padding: 20px;
            }

            .certificate-name {
                margin: 0 0 10px 0;
                font-size: 18px;
                font-weight: 600;
                color: #2c3e50;
                line-height: 1.3;
            }

            .certificate-meta {
                display: flex;
                flex-direction: column;
                gap: 8px;
                margin-bottom: 20px;
                font-size: 13px;
                color: #7f8c8d;
            }

            .certificate-meta span {
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .certificate-meta .dashicons {
                font-size: 16px;
                width: 16px;
                height: 16px;
                color: #95a5a6;
            }

            .certificate-actions {
                display: flex;
            }

            a.button.certificate-download {
                display: inline-flex !important;
                align-items: center;
                justify-content: center;
                gap: 8px;
                padding: 10px 24px;
                background-color: transparent !important;
                color: #667eea !important;
                border: 1px solid #667eea !important;
                border-radius: 50px;
                /* Pill shape */
                text-decoration: none;
                font-size: 14px;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.2s ease;
                width: 100%;
                /* Full width for better mobile touch target */
                line-height: normal !important;
                /* Fix vertical alignment */
                box-sizing: border-box;
                box-shadow: none !important;
            }

            a.button.certificate-download:hover {
                background-color: #667eea !important;
                color: #fff !important;
                box-shadow: 0 4px 10px rgba(102, 126, 234, 0.3) !important;
                text-decoration: none !important;
                transform: translateY(-1px);
            }

            a.button.certificate-download:focus,
            a.button.certificate-download:active {
                outline: none;
                transform: translateY(1px);
            }

            .certificate-download .dashicons {
                font-size: 18px;
                width: 18px;
                height: 18px;
                line-height: 1 !important;
                /* Reset dashicon line height */
                margin-top: -1px;
                /* Micro adjustment for perfect visual center */
                color: inherit;
            }

            .no-certificates-message {
                text-align: center;
                padding: 60px 20px;
                background: #f9f9f9;
                border-radius: 12px;
                border: 1px dashed #dce1e5;
            }

            .no-certificates-icon {
                margin-bottom: 20px;
            }

            .no-certificates-icon .dashicons {
                font-size: 64px;
                width: 64px;
                height: 64px;
                color: #dce1e5;
            }

            .no-certificates-message p {
                margin: 0;
                font-size: 16px;
                color: #7f8c8d;
            }

            @media (max-width: 768px) {
                .certificates-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>
        <?php
    }

    /**
     * Locate template file
     *
     * @param string $template_name Template file name
     * @return string|false Template path or false
     */
    private function locate_template($template_name)
    {
        // Check theme directory
        $theme_template = locate_template(array(
            'buddypress/members/single/certificados-innova/' . $template_name,
            'certificados-innova/' . $template_name,
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
     * Enqueue scripts and styles
     */
    public function enqueue_scripts()
    {
        if (!bp_is_user() || !bp_is_current_component('certificados-innova')) {
            return;
        }

        wp_enqueue_style('dashicons');
    }

    /**
     * BuddyBoss missing notice
     */
    public function buddyboss_missing_notice()
    {
        ?>
        <div class="notice notice-error">
            <p>
                <?php _e('El plugin Custom Certificates requiere que BuddyBoss Platform o BuddyPress esté activo.', 'custom-certificates'); ?>
            </p>
        </div>
        <?php
    }
}
