<?php
/**
 * Plugin Name: Custom Certificates for BuddyBoss
 * Plugin URI: https://tudominio.com
 * Description: Sistema de certificados personalizados independiente de LearnDash, integrado con BuddyBoss
 * Version: 1.0.0
 * Author: Tu Nombre
 * Author URI: https://tudominio.com
 * Text Domain: custom-certificates
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires at least: 5.8
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('CUSTOM_CERT_VERSION', '1.0.0');
define('CUSTOM_CERT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CUSTOM_CERT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CUSTOM_CERT_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class
 */
class Custom_Certificates {

    /**
     * Single instance of the class
     */
    private static $instance = null;

    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->includes();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('init', array($this, 'init'));

        // Check dependencies
        add_action('admin_notices', array($this, 'check_dependencies'));
        add_action('admin_menu', array($this, 'add_dependency_installer_page'), 999);

        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    /**
     * Include required files
     */
    private function includes() {
        // Load Composer autoloader if available
        $autoloader = CUSTOM_CERT_PLUGIN_DIR . 'vendor/autoload.php';
        if (file_exists($autoloader)) {
            require_once $autoloader;
        }

        // Dependency installer (always load this first)
        require_once CUSTOM_CERT_PLUGIN_DIR . 'includes/class-dependency-installer.php';

        // Core classes
        require_once CUSTOM_CERT_PLUGIN_DIR . 'includes/class-certificate-post-type.php';
        require_once CUSTOM_CERT_PLUGIN_DIR . 'includes/class-certificate-assignment.php';
        require_once CUSTOM_CERT_PLUGIN_DIR . 'includes/class-pdf-generator.php';
        require_once CUSTOM_CERT_PLUGIN_DIR . 'includes/class-buddyboss-integration.php';
        require_once CUSTOM_CERT_PLUGIN_DIR . 'includes/class-admin-interface.php';

        // Helper functions
        require_once CUSTOM_CERT_PLUGIN_DIR . 'includes/functions.php';
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize components
        Custom_Cert_Post_Type::get_instance();
        Custom_Cert_Assignment::get_instance();
        Custom_Cert_PDF_Generator::get_instance();
        Custom_Cert_BuddyBoss::get_instance();

        // Admin interface (only in admin)
        if (is_admin()) {
            Custom_Cert_Admin::get_instance();
        }

        do_action('custom_cert_init');
    }

    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'custom-certificates',
            false,
            dirname(CUSTOM_CERT_PLUGIN_BASENAME) . '/languages'
        );
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Create custom post types
        Custom_Cert_Post_Type::register_post_types();

        // Flush rewrite rules
        flush_rewrite_rules();

        // Create default template (optional)
        $this->create_default_template();

        do_action('custom_cert_activated');
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();

        do_action('custom_cert_deactivated');
    }

    /**
     * Check if dependencies are installed
     */
    public function check_dependencies() {
        if (!Custom_Cert_Dependency_Installer::dependencies_installed()) {
            Custom_Cert_Dependency_Installer::admin_notice_missing_dependencies();
        }
    }

    /**
     * Add dependency installer page
     */
    public function add_dependency_installer_page() {
        add_submenu_page(
            null, // Hidden from menu
            __('Instalar Dependencias', 'custom-certificates'),
            __('Instalar Dependencias', 'custom-certificates'),
            'manage_options',
            'cert-install-dependencies',
            array('Custom_Cert_Dependency_Installer', 'dependency_installation_page')
        );
    }

    /**
     * Create default certificate template
     */
    private function create_default_template() {
        // Check if default template already exists
        $existing = get_posts(array(
            'post_type' => 'bb_cert_template',
            'posts_per_page' => 1,
            'meta_key' => '_is_default_template',
            'meta_value' => '1'
        ));

        if (empty($existing)) {
            $template_id = wp_insert_post(array(
                'post_title' => __('Certificado General', 'custom-certificates'),
                'post_type' => 'bb_cert_template',
                'post_status' => 'publish',
                'post_content' => __('Plantilla de certificado por defecto', 'custom-certificates')
            ));

            if ($template_id) {
                update_post_meta($template_id, '_is_default_template', '1');
                update_post_meta($template_id, '_cert_config', json_encode(array(
                    'text_color' => '#000000',
                    'font_size' => '24',
                    'orientation' => 'landscape'
                )));
            }
        }
    }
}

/**
 * Initialize plugin
 */
function custom_certificates() {
    return Custom_Certificates::get_instance();
}

// Start the plugin
custom_certificates();
