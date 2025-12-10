<?php
/**
 * Certificate Post Types
 *
 * Handles the registration and management of custom post types for certificates
 */

if (!defined('ABSPATH')) {
    exit;
}

class Custom_Cert_Post_Type {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_taxonomies'));
        add_filter('post_updated_messages', array($this, 'updated_messages'));
    }

    /**
     * Register custom post types
     */
    public static function register_post_types() {
        // Certificate Templates
        register_post_type('bb_cert_template', array(
            'labels' => array(
                'name' => __('Plantillas de Certificados', 'custom-certificates'),
                'singular_name' => __('Plantilla de Certificado', 'custom-certificates'),
                'add_new' => __('Añadir Nueva', 'custom-certificates'),
                'add_new_item' => __('Añadir Nueva Plantilla', 'custom-certificates'),
                'edit_item' => __('Editar Plantilla', 'custom-certificates'),
                'new_item' => __('Nueva Plantilla', 'custom-certificates'),
                'view_item' => __('Ver Plantilla', 'custom-certificates'),
                'search_items' => __('Buscar Plantillas', 'custom-certificates'),
                'not_found' => __('No se encontraron plantillas', 'custom-certificates'),
                'not_found_in_trash' => __('No hay plantillas en la papelera', 'custom-certificates'),
                'menu_name' => __('Certificados', 'custom-certificates')
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-awards',
            'menu_position' => 30,
            'capability_type' => 'post',
            'capabilities' => array(
                'edit_post' => 'manage_options',
                'delete_post' => 'manage_options',
                'read_post' => 'manage_options',
                'edit_posts' => 'manage_options',
                'edit_others_posts' => 'manage_options',
                'delete_posts' => 'manage_options',
                'publish_posts' => 'manage_options',
                'read_private_posts' => 'manage_options'
            ),
            'hierarchical' => false,
            'supports' => array('title', 'editor', 'thumbnail'),
            'has_archive' => false,
            'rewrite' => false,
            'query_var' => false,
            'show_in_rest' => false
        ));

        // Assigned Certificates (individual certificates assigned to users)
        register_post_type('bb_cert_assigned', array(
            'labels' => array(
                'name' => __('Certificados Asignados', 'custom-certificates'),
                'singular_name' => __('Certificado Asignado', 'custom-certificates'),
                'add_new' => __('Asignar Certificado', 'custom-certificates'),
                'add_new_item' => __('Asignar Nuevo Certificado', 'custom-certificates'),
                'edit_item' => __('Editar Certificado', 'custom-certificates'),
                'new_item' => __('Nuevo Certificado', 'custom-certificates'),
                'view_item' => __('Ver Certificado', 'custom-certificates'),
                'search_items' => __('Buscar Certificados', 'custom-certificates'),
                'not_found' => __('No se encontraron certificados', 'custom-certificates'),
                'not_found_in_trash' => __('No hay certificados en la papelera', 'custom-certificates')
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=bb_cert_template',
            'capability_type' => 'post',
            'capabilities' => array(
                'edit_post' => 'manage_options',
                'delete_post' => 'manage_options',
                'read_post' => 'read',
                'edit_posts' => 'manage_options',
                'edit_others_posts' => 'manage_options',
                'delete_posts' => 'manage_options',
                'publish_posts' => 'manage_options',
                'read_private_posts' => 'manage_options'
            ),
            'hierarchical' => false,
            'supports' => array('title'),
            'has_archive' => false,
            'rewrite' => false,
            'query_var' => false,
            'show_in_rest' => false
        ));
    }

    /**
     * Register taxonomies for certificate categorization
     */
    public function register_taxonomies() {
        register_taxonomy('cert_category', array('bb_cert_template'), array(
            'labels' => array(
                'name' => __('Categorías de Certificados', 'custom-certificates'),
                'singular_name' => __('Categoría de Certificado', 'custom-certificates'),
                'search_items' => __('Buscar Categorías', 'custom-certificates'),
                'all_items' => __('Todas las Categorías', 'custom-certificates'),
                'edit_item' => __('Editar Categoría', 'custom-certificates'),
                'update_item' => __('Actualizar Categoría', 'custom-certificates'),
                'add_new_item' => __('Añadir Nueva Categoría', 'custom-certificates'),
                'new_item_name' => __('Nueva Categoría', 'custom-certificates'),
                'menu_name' => __('Categorías', 'custom-certificates')
            ),
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => false,
            'public' => false,
            'show_in_rest' => false
        ));
    }

    /**
     * Custom update messages
     */
    public function updated_messages($messages) {
        global $post;

        $messages['bb_cert_template'] = array(
            0 => '',
            1 => __('Plantilla actualizada.', 'custom-certificates'),
            2 => __('Campo personalizado actualizado.', 'custom-certificates'),
            3 => __('Campo personalizado eliminado.', 'custom-certificates'),
            4 => __('Plantilla actualizada.', 'custom-certificates'),
            5 => isset($_GET['revision']) ? sprintf(__('Plantilla restaurada a la revisión del %s', 'custom-certificates'), wp_post_revision_title((int) $_GET['revision'], false)) : false,
            6 => __('Plantilla publicada.', 'custom-certificates'),
            7 => __('Plantilla guardada.', 'custom-certificates'),
            8 => __('Plantilla enviada.', 'custom-certificates'),
            9 => sprintf(__('Plantilla programada para: <strong>%1$s</strong>.', 'custom-certificates'), date_i18n(__('M j, Y @ G:i', 'custom-certificates'), strtotime($post->post_date))),
            10 => __('Borrador de plantilla actualizado.', 'custom-certificates')
        );

        $messages['bb_cert_assigned'] = array(
            0 => '',
            1 => __('Certificado actualizado.', 'custom-certificates'),
            2 => __('Campo personalizado actualizado.', 'custom-certificates'),
            3 => __('Campo personalizado eliminado.', 'custom-certificates'),
            4 => __('Certificado actualizado.', 'custom-certificates'),
            5 => isset($_GET['revision']) ? sprintf(__('Certificado restaurado a la revisión del %s', 'custom-certificates'), wp_post_revision_title((int) $_GET['revision'], false)) : false,
            6 => __('Certificado asignado.', 'custom-certificates'),
            7 => __('Certificado guardado.', 'custom-certificates'),
            8 => __('Certificado enviado.', 'custom-certificates'),
            9 => sprintf(__('Certificado programado para: <strong>%1$s</strong>.', 'custom-certificates'), date_i18n(__('M j, Y @ G:i', 'custom-certificates'), strtotime($post->post_date))),
            10 => __('Borrador de certificado actualizado.', 'custom-certificates')
        );

        return $messages;
    }
}
