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
        add_action('save_post', array($this, 'save_custom_variables_meta'), 10, 2);
        add_action('save_post', array($this, 'save_additional_pages_meta'), 10, 2);
        add_action('save_post', array($this, 'save_certificate_meta'), 10, 2);

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

        // CSV Import submenu
        add_submenu_page(
            'edit.php?post_type=bb_cert_template',
            __('Importar CSV', 'custom-certificates'),
            __('Importar CSV', 'custom-certificates'),
            'manage_options',
            'csv-import',
            array($this, 'csv_import_page')
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

        // Template custom variables
        add_meta_box(
            'cert_custom_variables',
            __('Variables Personalizadas', 'custom-certificates'),
            array($this, 'template_custom_variables_metabox'),
            'bb_cert_template',
            'normal',
            'default'
        );

        // Additional pages
        add_meta_box(
            'cert_additional_pages',
            __('Páginas Adicionales', 'custom-certificates'),
            array($this, 'additional_pages_metabox'),
            'bb_cert_template',
            'normal',
            'default'
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
                'orientation' => 'landscape',
                'font_family' => 'dejavusans'
            );
        }
        // Ensure font_family exists for older configs
        if (!isset($config['font_family'])) {
            $config['font_family'] = 'dejavusans';
        }

        // Available fonts
        $available_fonts = array(
            'dejavusans' => 'DejaVu Sans (Por defecto)',
            'montserrat' => 'Montserrat',
            'opensans' => 'Open Sans',
            'helvetica' => 'Helvetica',
            'roboto' => 'Roboto',
            'lato' => 'Lato'
        );

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
            <tr>
                <th scope="row">
                    <label for="cert_font_family"><?php _e('Fuente', 'custom-certificates'); ?></label>
                </th>
                <td>
                    <select id="cert_font_family" name="cert_config[font_family]" style="min-width: 200px;">
                        <?php foreach ($available_fonts as $font_key => $font_name): ?>
                            <option value="<?php echo esc_attr($font_key); ?>" <?php selected($config['font_family'], $font_key); ?> style="font-family: <?php echo esc_attr($font_name); ?>">
                                <?php echo esc_html($font_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php _e('Selecciona la fuente para el texto del certificado', 'custom-certificates'); ?></p>
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
            <?php
            // Show custom variables if defined
            $custom_variables = json_decode(get_post_meta($post->ID, '_cert_custom_variables', true), true);
            if (!empty($custom_variables)) {
                echo '<p style="margin-top: 15px;"><strong>' . __('Variables Personalizadas:', 'custom-certificates') . '</strong></p>';
                echo '<ul style="margin-left: 20px;">';
                foreach ($custom_variables as $var) {
                    echo '<li><code>{' . esc_html(strtoupper($var['key'])) . '}</code> - ' . esc_html($var['label']) . '</li>';
                }
                echo '</ul>';
            }

            // Show BuddyBoss/BuddyPress xprofile fields if available
            if (function_exists('bp_xprofile_get_groups')) {
                $xprofile_groups = bp_xprofile_get_groups(array(
                    'fetch_fields'      => true,
                    'hide_empty_groups' => true,
                    'hide_empty_fields' => true,
                ));

                if (!empty($xprofile_groups)) {
                    echo '<p style="margin-top: 15px;"><strong>' . __('Variables de Perfil BuddyBoss:', 'custom-certificates') . '</strong></p>';
                    echo '<p class="description" style="margin-bottom: 10px; font-style: italic;">' . __('Estos campos se obtienen del perfil extendido del usuario (solo si tienen valor).', 'custom-certificates') . '</p>';

                    foreach ($xprofile_groups as $group) {
                        if (empty($group->fields)) {
                            continue;
                        }

                        echo '<p style="margin: 10px 0 5px 0; font-weight: 600; color: #1d2327;">' . esc_html($group->name) . ':</p>';
                        echo '<ul style="margin-left: 20px; margin-top: 5px;">';

                        foreach ($group->fields as $field) {
                            // Sanitize field name to show what variable to use
                            $var_name = strtoupper(preg_replace('/[^A-Z0-9]+/i', '_', remove_accents($field->name)));
                            $var_name = trim($var_name, '_');

                            echo '<li><code>{' . esc_html($var_name) . '}</code> - ' . esc_html($field->name) . '</li>';
                        }

                        echo '</ul>';
                    }
                }
            }
            ?>
        </div>
        <?php
    }

    /**
     * Custom variables metabox for templates
     */
    public function template_custom_variables_metabox($post) {
        wp_nonce_field('save_custom_variables', 'custom_variables_nonce');

        $custom_variables = json_decode(get_post_meta($post->ID, '_cert_custom_variables', true), true);
        if (!$custom_variables) {
            $custom_variables = array();
        }
        ?>
        <div id="cert-custom-variables-wrapper">
            <p class="description">
                <?php _e('Define variables personalizadas que se completarán al asignar el certificado. Estas variables podrán usarse en el contenido con el formato {NOMBRE_VARIABLE}.', 'custom-certificates'); ?>
            </p>

            <table class="widefat" id="custom-variables-table" style="margin-top: 15px;">
                <thead>
                    <tr>
                        <th style="width: 20%;"><?php _e('Variable', 'custom-certificates'); ?></th>
                        <th style="width: 20%;"><?php _e('Etiqueta', 'custom-certificates'); ?></th>
                        <th style="width: 15%;"><?php _e('Tipo', 'custom-certificates'); ?></th>
                        <th style="width: 35%;"><?php _e('Opciones (para select)', 'custom-certificates'); ?></th>
                        <th style="width: 10%;"><?php _e('Acciones', 'custom-certificates'); ?></th>
                    </tr>
                </thead>
                <tbody id="custom-variables-body">
                    <?php if (!empty($custom_variables)): ?>
                        <?php foreach ($custom_variables as $index => $var): ?>
                            <tr class="variable-row">
                                <td>
                                    <input type="text"
                                           name="custom_vars[<?php echo $index; ?>][key]"
                                           value="<?php echo esc_attr($var['key']); ?>"
                                           placeholder="CATEGORIA"
                                           style="width: 100%; text-transform: uppercase;"
                                           pattern="[A-Z0-9_]+"
                                           title="<?php _e('Solo letras mayúsculas, números y guiones bajos', 'custom-certificates'); ?>">
                                </td>
                                <td>
                                    <input type="text"
                                           name="custom_vars[<?php echo $index; ?>][label]"
                                           value="<?php echo esc_attr($var['label']); ?>"
                                           placeholder="<?php _e('Categoría', 'custom-certificates'); ?>"
                                           style="width: 100%;">
                                </td>
                                <td>
                                    <select name="custom_vars[<?php echo $index; ?>][type]" style="width: 100%;" class="var-type-select">
                                        <option value="text" <?php selected($var['type'], 'text'); ?>><?php _e('Texto', 'custom-certificates'); ?></option>
                                        <option value="select" <?php selected($var['type'], 'select'); ?>><?php _e('Selección', 'custom-certificates'); ?></option>
                                        <option value="textarea" <?php selected($var['type'], 'textarea'); ?>><?php _e('Área de texto', 'custom-certificates'); ?></option>
                                    </select>
                                </td>
                                <td>
                                    <input type="text"
                                           name="custom_vars[<?php echo $index; ?>][options]"
                                           value="<?php echo esc_attr($var['options']); ?>"
                                           placeholder="<?php _e('Opción 1, Opción 2, Opción 3', 'custom-certificates'); ?>"
                                           style="width: 100%;"
                                           class="var-options-input"
                                           <?php echo $var['type'] !== 'select' ? 'disabled' : ''; ?>>
                                </td>
                                <td style="text-align: center;">
                                    <button type="button" class="button remove-variable" title="<?php _e('Eliminar', 'custom-certificates'); ?>">
                                        <span class="dashicons dashicons-trash" style="vertical-align: middle;"></span>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <p style="margin-top: 15px;">
                <button type="button" class="button button-secondary" id="add-custom-variable">
                    <span class="dashicons dashicons-plus-alt" style="vertical-align: middle;"></span>
                    <?php _e('Agregar Variable', 'custom-certificates'); ?>
                </button>
            </p>
        </div>

        <script type="text/template" id="variable-row-template">
            <tr class="variable-row">
                <td>
                    <input type="text"
                           name="custom_vars[{{INDEX}}][key]"
                           value=""
                           placeholder="CATEGORIA"
                           style="width: 100%; text-transform: uppercase;"
                           pattern="[A-Z0-9_]+"
                           title="<?php _e('Solo letras mayúsculas, números y guiones bajos', 'custom-certificates'); ?>">
                </td>
                <td>
                    <input type="text"
                           name="custom_vars[{{INDEX}}][label]"
                           value=""
                           placeholder="<?php _e('Categoría', 'custom-certificates'); ?>"
                           style="width: 100%;">
                </td>
                <td>
                    <select name="custom_vars[{{INDEX}}][type]" style="width: 100%;" class="var-type-select">
                        <option value="text"><?php _e('Texto', 'custom-certificates'); ?></option>
                        <option value="select"><?php _e('Selección', 'custom-certificates'); ?></option>
                        <option value="textarea"><?php _e('Área de texto', 'custom-certificates'); ?></option>
                    </select>
                </td>
                <td>
                    <input type="text"
                           name="custom_vars[{{INDEX}}][options]"
                           value=""
                           placeholder="<?php _e('Opción 1, Opción 2, Opción 3', 'custom-certificates'); ?>"
                           style="width: 100%;"
                           class="var-options-input"
                           disabled>
                </td>
                <td style="text-align: center;">
                    <button type="button" class="button remove-variable" title="<?php _e('Eliminar', 'custom-certificates'); ?>">
                        <span class="dashicons dashicons-trash" style="vertical-align: middle;"></span>
                    </button>
                </td>
            </tr>
        </script>

        <script>
        jQuery(document).ready(function($) {
            var variableIndex = <?php echo count($custom_variables); ?>;

            // Add new variable row
            $('#add-custom-variable').on('click', function() {
                var template = $('#variable-row-template').html();
                template = template.replace(/\{\{INDEX\}\}/g, variableIndex);
                $('#custom-variables-body').append(template);
                variableIndex++;
            });

            // Remove variable row
            $(document).on('click', '.remove-variable', function() {
                $(this).closest('tr').remove();
            });

            // Toggle options field based on type
            $(document).on('change', '.var-type-select', function() {
                var $row = $(this).closest('tr');
                var $optionsInput = $row.find('.var-options-input');
                if ($(this).val() === 'select') {
                    $optionsInput.prop('disabled', false);
                } else {
                    $optionsInput.prop('disabled', true).val('');
                }
            });

            // Auto uppercase variable key
            $(document).on('input', 'input[name*="[key]"]', function() {
                $(this).val($(this).val().toUpperCase().replace(/[^A-Z0-9_]/g, ''));
            });
        });
        </script>

        <style>
            #custom-variables-table th {
                background: #f9f9f9;
                padding: 10px;
            }
            #custom-variables-table td {
                padding: 8px;
                vertical-align: middle;
            }
            .variable-row:nth-child(even) {
                background: #f9f9f9;
            }
        </style>
        <?php
    }

    /**
     * Additional pages metabox for templates
     */
    public function additional_pages_metabox($post) {
        wp_nonce_field('save_additional_pages', 'additional_pages_nonce');

        $additional_pages = json_decode(get_post_meta($post->ID, '_cert_additional_pages', true), true);
        if (!$additional_pages || !is_array($additional_pages)) {
            $additional_pages = array();
        }

        // Ensure wp.media is available
        wp_enqueue_media();
        ?>
        <div id="cert-additional-pages-wrapper">
            <p class="description">
                <?php _e('Agrega páginas adicionales al certificado. Cada página puede tener una imagen de fondo y contenido HTML opcional.', 'custom-certificates'); ?>
            </p>

            <div id="additional-pages-container">
                <?php if (!empty($additional_pages)): ?>
                    <?php foreach ($additional_pages as $index => $page): ?>
                        <div class="additional-page-item" data-index="<?php echo $index; ?>">
                            <div class="page-header">
                                <h4>
                                    <span class="dashicons dashicons-format-image"></span>
                                    <?php printf(__('Página %d', 'custom-certificates'), $index + 2); ?>
                                </h4>
                                <button type="button" class="button remove-page" title="<?php _e('Eliminar página', 'custom-certificates'); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </div>
                            <div class="page-content">
                                <div class="page-image-section">
                                    <label><strong><?php _e('Imagen de Fondo:', 'custom-certificates'); ?></strong></label>
                                    <div class="image-preview-wrapper">
                                        <?php
                                        $image_id = isset($page['image_id']) ? intval($page['image_id']) : 0;
                                        $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'medium') : '';
                                        ?>
                                        <div class="image-preview" style="<?php echo $image_url ? '' : 'display:none;'; ?>">
                                            <img src="<?php echo esc_url($image_url); ?>" alt="">
                                        </div>
                                        <input type="hidden" name="additional_pages[<?php echo $index; ?>][image_id]" value="<?php echo esc_attr($image_id); ?>" class="page-image-id">
                                        <div class="image-buttons">
                                            <button type="button" class="button select-image"><?php _e('Seleccionar Imagen', 'custom-certificates'); ?></button>
                                            <button type="button" class="button remove-image" style="<?php echo $image_url ? '' : 'display:none;'; ?>"><?php _e('Quitar', 'custom-certificates'); ?></button>
                                        </div>
                                    </div>
                                </div>
                                <div class="page-content-section">
                                    <label><strong><?php _e('Contenido HTML (opcional):', 'custom-certificates'); ?></strong></label>
                                    <p class="description"><?php _e('Puedes usar las mismas variables que en la página principal ({NOMBRE_USUARIO}, etc.)', 'custom-certificates'); ?></p>
                                    <textarea name="additional_pages[<?php echo $index; ?>][content]" rows="6" class="large-text code"><?php echo esc_textarea(isset($page['content']) ? $page['content'] : ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <p style="margin-top: 15px;">
                <button type="button" class="button button-primary" id="add-additional-page">
                    <span class="dashicons dashicons-plus-alt" style="vertical-align: middle;"></span>
                    <?php _e('Agregar Página', 'custom-certificates'); ?>
                </button>
            </p>
        </div>

        <script type="text/template" id="additional-page-template">
            <div class="additional-page-item" data-index="{{INDEX}}">
                <div class="page-header">
                    <h4>
                        <span class="dashicons dashicons-format-image"></span>
                        <?php _e('Página', 'custom-certificates'); ?> {{PAGE_NUM}}
                    </h4>
                    <button type="button" class="button remove-page" title="<?php _e('Eliminar página', 'custom-certificates'); ?>">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>
                <div class="page-content">
                    <div class="page-image-section">
                        <label><strong><?php _e('Imagen de Fondo:', 'custom-certificates'); ?></strong></label>
                        <div class="image-preview-wrapper">
                            <div class="image-preview" style="display:none;">
                                <img src="" alt="">
                            </div>
                            <input type="hidden" name="additional_pages[{{INDEX}}][image_id]" value="" class="page-image-id">
                            <div class="image-buttons">
                                <button type="button" class="button select-image"><?php _e('Seleccionar Imagen', 'custom-certificates'); ?></button>
                                <button type="button" class="button remove-image" style="display:none;"><?php _e('Quitar', 'custom-certificates'); ?></button>
                            </div>
                        </div>
                    </div>
                    <div class="page-content-section">
                        <label><strong><?php _e('Contenido HTML (opcional):', 'custom-certificates'); ?></strong></label>
                        <p class="description"><?php _e('Puedes usar las mismas variables que en la página principal ({NOMBRE_USUARIO}, etc.)', 'custom-certificates'); ?></p>
                        <textarea name="additional_pages[{{INDEX}}][content]" rows="6" class="large-text code"></textarea>
                    </div>
                </div>
            </div>
        </script>

        <script>
        jQuery(document).ready(function($) {
            var pageIndex = <?php echo count($additional_pages); ?>;

            // Add new page
            $('#add-additional-page').on('click', function() {
                var template = $('#additional-page-template').html();
                template = template.replace(/\{\{INDEX\}\}/g, pageIndex);
                template = template.replace(/\{\{PAGE_NUM\}\}/g, pageIndex + 2);
                $('#additional-pages-container').append(template);
                pageIndex++;
                updatePageNumbers();
            });

            // Remove page
            $(document).on('click', '.remove-page', function() {
                $(this).closest('.additional-page-item').remove();
                updatePageNumbers();
            });

            // Update page numbers after removal
            function updatePageNumbers() {
                $('.additional-page-item').each(function(i) {
                    $(this).find('.page-header h4').html(
                        '<span class="dashicons dashicons-format-image"></span> <?php _e('Página', 'custom-certificates'); ?> ' + (i + 2)
                    );
                });
            }

            // Select image using WordPress Media Library
            $(document).on('click', '.select-image', function(e) {
                e.preventDefault();
                var $button = $(this);
                var $container = $button.closest('.image-preview-wrapper');
                var $imageInput = $container.find('.page-image-id');
                var $preview = $container.find('.image-preview');
                var $removeBtn = $container.find('.remove-image');

                var frame = wp.media({
                    title: '<?php _e('Seleccionar imagen de fondo', 'custom-certificates'); ?>',
                    button: { text: '<?php _e('Usar esta imagen', 'custom-certificates'); ?>' },
                    multiple: false,
                    library: { type: 'image' }
                });

                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    $imageInput.val(attachment.id);
                    $preview.find('img').attr('src', attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url);
                    $preview.show();
                    $removeBtn.show();
                });

                frame.open();
            });

            // Remove image
            $(document).on('click', '.remove-image', function(e) {
                e.preventDefault();
                var $container = $(this).closest('.image-preview-wrapper');
                $container.find('.page-image-id').val('');
                $container.find('.image-preview').hide().find('img').attr('src', '');
                $(this).hide();
            });
        });
        </script>

        <style>
            #cert-additional-pages-wrapper .additional-page-item {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                margin-bottom: 15px;
                overflow: hidden;
            }
            #cert-additional-pages-wrapper .page-header {
                background: #f6f7f7;
                padding: 10px 15px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                border-bottom: 1px solid #ccd0d4;
            }
            #cert-additional-pages-wrapper .page-header h4 {
                margin: 0;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            #cert-additional-pages-wrapper .page-content {
                padding: 15px;
            }
            #cert-additional-pages-wrapper .page-image-section {
                margin-bottom: 20px;
            }
            #cert-additional-pages-wrapper .image-preview-wrapper {
                margin-top: 10px;
            }
            #cert-additional-pages-wrapper .image-preview {
                max-width: 300px;
                margin-bottom: 10px;
                border: 1px solid #ddd;
                border-radius: 4px;
                overflow: hidden;
            }
            #cert-additional-pages-wrapper .image-preview img {
                display: block;
                width: 100%;
                height: auto;
            }
            #cert-additional-pages-wrapper .image-buttons {
                display: flex;
                gap: 10px;
            }
            #cert-additional-pages-wrapper .page-content-section label {
                display: block;
                margin-bottom: 5px;
            }
            #cert-additional-pages-wrapper .page-content-section .description {
                margin-top: 0;
                margin-bottom: 10px;
            }
        </style>
        <?php
    }

    /**
     * Certificate details metabox
     */
    public function certificate_details_metabox($post) {
        wp_nonce_field('save_certificate_details', 'certificate_details_nonce');

        $user_id = get_post_meta($post->ID, '_cert_user_id', true);
        $template_id = get_post_meta($post->ID, '_cert_template_id', true);
        $verification_code = get_post_meta($post->ID, '_cert_verification_code', true);
        $issue_date = get_post_meta($post->ID, '_cert_issue_date', true);
        $custom_data = maybe_unserialize(get_post_meta($post->ID, '_cert_custom_data', true));

        $user = get_userdata($user_id);
        $template = get_post($template_id);

        // Get template custom variables definition
        $template_variables = array();
        if ($template_id) {
            $template_variables = json_decode(get_post_meta($template_id, '_cert_custom_variables', true), true);
            if (!is_array($template_variables)) {
                $template_variables = array();
            }
        }

        // Format date for input field (Y-m-d)
        $issue_date_formatted = $issue_date ? date('Y-m-d', strtotime($issue_date)) : '';

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
                <strong><label for="cert_issue_date"><?php _e('Fecha de Emisión:', 'custom-certificates'); ?></label></strong><br>
                <input type="date"
                       id="cert_issue_date"
                       name="cert_issue_date"
                       value="<?php echo esc_attr($issue_date_formatted); ?>"
                       style="width: 100%;">
                <span class="description" style="font-size: 11px; color: #666;">
                    <?php _e('Modifica la fecha si es necesario', 'custom-certificates'); ?>
                </span>
            </p>

            <?php if (!empty($template_variables)): ?>
            <hr style="margin: 15px 0; border: none; border-top: 1px solid #ddd;">
            <div class="cert-custom-variables-edit">
                <h4 style="margin: 0 0 10px 0;"><?php _e('Variables Personalizadas', 'custom-certificates'); ?></h4>
                <p class="description" style="margin-bottom: 15px; font-size: 11px; color: #666;">
                    <?php _e('Puedes editar los valores de las variables personalizadas asignadas a este certificado.', 'custom-certificates'); ?>
                </p>

                <?php foreach ($template_variables as $variable): ?>
                    <?php
                    $var_key = $variable['key'];
                    $var_label = $variable['label'];
                    $var_type = isset($variable['type']) ? $variable['type'] : 'text';
                    $var_options = isset($variable['options']) ? $variable['options'] : '';
                    $current_value = isset($custom_data[$var_key]) ? $custom_data[$var_key] : '';
                    $field_id = 'cert_custom_var_' . sanitize_key($var_key);
                    ?>
                    <p>
                        <strong><label for="<?php echo esc_attr($field_id); ?>"><?php echo esc_html($var_label); ?>:</label></strong><br>
                        <?php if ($var_type === 'select' && !empty($var_options)): ?>
                            <?php $options_array = array_map('trim', explode(',', $var_options)); ?>
                            <select id="<?php echo esc_attr($field_id); ?>"
                                    name="cert_custom_data[<?php echo esc_attr($var_key); ?>]"
                                    style="width: 100%;">
                                <option value=""><?php _e('Selecciona una opción...', 'custom-certificates'); ?></option>
                                <?php foreach ($options_array as $option): ?>
                                    <option value="<?php echo esc_attr($option); ?>" <?php selected($current_value, $option); ?>>
                                        <?php echo esc_html($option); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ($var_type === 'textarea'): ?>
                            <textarea id="<?php echo esc_attr($field_id); ?>"
                                      name="cert_custom_data[<?php echo esc_attr($var_key); ?>]"
                                      rows="3"
                                      style="width: 100%;"><?php echo esc_textarea($current_value); ?></textarea>
                        <?php else: ?>
                            <input type="text"
                                   id="<?php echo esc_attr($field_id); ?>"
                                   name="cert_custom_data[<?php echo esc_attr($var_key); ?>]"
                                   value="<?php echo esc_attr($current_value); ?>"
                                   style="width: 100%;">
                        <?php endif; ?>
                        <span class="description" style="font-size: 10px; color: #999;">
                            <?php echo sprintf(__('Variable: {%s}', 'custom-certificates'), esc_html(strtoupper($var_key))); ?>
                        </span>
                    </p>
                <?php endforeach; ?>
            </div>
            <?php elseif (!empty($custom_data) && is_array($custom_data)): ?>
            <hr style="margin: 15px 0; border: none; border-top: 1px solid #ddd;">
            <div class="cert-custom-variables-edit">
                <h4 style="margin: 0 0 10px 0;"><?php _e('Datos Personalizados', 'custom-certificates'); ?></h4>
                <?php foreach ($custom_data as $key => $value): ?>
                    <?php if ($key === 'description') continue; ?>
                    <?php $field_id = 'cert_custom_var_' . sanitize_key($key); ?>
                    <p>
                        <strong><label for="<?php echo esc_attr($field_id); ?>"><?php echo esc_html(ucfirst($key)); ?>:</label></strong><br>
                        <input type="text"
                               id="<?php echo esc_attr($field_id); ?>"
                               name="cert_custom_data[<?php echo esc_attr($key); ?>]"
                               value="<?php echo esc_attr($value); ?>"
                               style="width: 100%;">
                    </p>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <hr style="margin: 15px 0; border: none; border-top: 1px solid #ddd;">
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
     * Save custom variables meta
     */
    public function save_custom_variables_meta($post_id, $post) {
        // Check if it's the right post type
        if ($post->post_type !== 'bb_cert_template') {
            return;
        }

        // Verify nonce
        if (!isset($_POST['custom_variables_nonce']) || !wp_verify_nonce($_POST['custom_variables_nonce'], 'save_custom_variables')) {
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

        // Save custom variables
        if (isset($_POST['custom_vars']) && is_array($_POST['custom_vars'])) {
            $custom_vars = array();
            foreach ($_POST['custom_vars'] as $var) {
                // Only save if key and label are provided
                if (!empty($var['key']) && !empty($var['label'])) {
                    $custom_vars[] = array(
                        'key' => strtoupper(sanitize_text_field($var['key'])),
                        'label' => sanitize_text_field($var['label']),
                        'type' => in_array($var['type'], array('text', 'select', 'textarea')) ? $var['type'] : 'text',
                        'options' => isset($var['options']) ? sanitize_text_field($var['options']) : ''
                    );
                }
            }
            update_post_meta($post_id, '_cert_custom_variables', json_encode($custom_vars));
        } else {
            // If no variables submitted, clear the meta
            delete_post_meta($post_id, '_cert_custom_variables');
        }
    }

    /**
     * Save additional pages meta
     */
    public function save_additional_pages_meta($post_id, $post) {
        // Check if it's the right post type
        if ($post->post_type !== 'bb_cert_template') {
            return;
        }

        // Verify nonce
        if (!isset($_POST['additional_pages_nonce']) || !wp_verify_nonce($_POST['additional_pages_nonce'], 'save_additional_pages')) {
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

        // Save additional pages
        if (isset($_POST['additional_pages']) && is_array($_POST['additional_pages'])) {
            $additional_pages = array();
            foreach ($_POST['additional_pages'] as $page) {
                // Only save if image_id is provided
                $image_id = isset($page['image_id']) ? intval($page['image_id']) : 0;
                if ($image_id > 0) {
                    $additional_pages[] = array(
                        'image_id' => $image_id,
                        'content' => isset($page['content']) ? wp_kses_post($page['content']) : ''
                    );
                }
            }
            if (!empty($additional_pages)) {
                update_post_meta($post_id, '_cert_additional_pages', json_encode($additional_pages));
            } else {
                delete_post_meta($post_id, '_cert_additional_pages');
            }
        } else {
            // If no pages submitted, clear the meta
            delete_post_meta($post_id, '_cert_additional_pages');
        }
    }

    /**
     * Save certificate assigned meta (issue date)
     */
    public function save_certificate_meta($post_id, $post) {
        // Check if it's the right post type
        if ($post->post_type !== 'bb_cert_assigned') {
            return;
        }

        // Verify nonce
        if (!isset($_POST['certificate_details_nonce']) || !wp_verify_nonce($_POST['certificate_details_nonce'], 'save_certificate_details')) {
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

        // Save issue date
        if (isset($_POST['cert_issue_date']) && !empty($_POST['cert_issue_date'])) {
            $issue_date = sanitize_text_field($_POST['cert_issue_date']);
            // Convert to MySQL datetime format
            $issue_date_mysql = date('Y-m-d H:i:s', strtotime($issue_date));
            update_post_meta($post_id, '_cert_issue_date', $issue_date_mysql);
        }

        // Save custom data (variables personalizadas)
        if (isset($_POST['cert_custom_data']) && is_array($_POST['cert_custom_data'])) {
            // Get existing custom data to preserve description and other fields
            $existing_data = maybe_unserialize(get_post_meta($post_id, '_cert_custom_data', true));
            if (!is_array($existing_data)) {
                $existing_data = array();
            }

            // Sanitize and merge new values
            $new_custom_data = array();
            foreach ($_POST['cert_custom_data'] as $key => $value) {
                $sanitized_key = sanitize_key($key);
                $sanitized_value = sanitize_text_field($value);
                $new_custom_data[$sanitized_key] = $sanitized_value;
            }

            // Preserve description if it existed
            if (isset($existing_data['description'])) {
                $new_custom_data['description'] = $existing_data['description'];
            }

            update_post_meta($post_id, '_cert_custom_data', maybe_serialize($new_custom_data));
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
     * CSV Import page
     */
    public function csv_import_page() {
        // Get templates for dropdown
        $templates = get_posts(array(
            'post_type' => 'bb_cert_template',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));

        include CUSTOM_CERT_PLUGIN_DIR . 'admin/views/csv-import.php';
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
        global $post_type;

        // Check if we're on our plugin pages
        $is_our_page = false;

        // Check for assign certificates page (submenu page)
        if (strpos($hook, 'assign-certificates') !== false) {
            $is_our_page = true;
        }

        // Check for CSV import page
        $is_csv_page = false;
        if (strpos($hook, 'csv-import') !== false) {
            $is_our_page = true;
            $is_csv_page = true;
        }

        // Check for certificate template or assigned post types
        if (in_array($hook, array('post.php', 'post-new.php', 'edit.php'))) {
            if (isset($post_type) && in_array($post_type, array('bb_cert_template', 'bb_cert_assigned'))) {
                $is_our_page = true;
            }
            // Also check via GET parameter
            if (isset($_GET['post_type']) && in_array($_GET['post_type'], array('bb_cert_template', 'bb_cert_assigned'))) {
                $is_our_page = true;
            }
            // Check for editing a specific post
            if (isset($_GET['post'])) {
                $editing_post = get_post($_GET['post']);
                if ($editing_post && in_array($editing_post->post_type, array('bb_cert_template', 'bb_cert_assigned'))) {
                    $is_our_page = true;
                }
            }
        }

        if (!$is_our_page) {
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

        // Enqueue CSV import scripts if on CSV page
        if ($is_csv_page) {
            wp_enqueue_script(
                'custom-cert-csv-import',
                CUSTOM_CERT_PLUGIN_URL . 'admin/js/csv-import.js',
                array('jquery'),
                CUSTOM_CERT_VERSION,
                true
            );

            wp_localize_script('custom-cert-csv-import', 'customCertCSV', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('csv_import'),
                'batch_size' => Custom_Cert_CSV_Import::get_instance()->get_batch_size(),
                'strings' => array(
                    'uploading' => __('Subiendo archivo...', 'custom-certificates'),
                    'validating' => __('Validando datos...', 'custom-certificates'),
                    'processing' => __('Procesando...', 'custom-certificates'),
                    'completed' => __('Importación completada', 'custom-certificates'),
                    'error' => __('Error durante la importación', 'custom-certificates'),
                    'confirm_cancel' => __('¿Estás seguro de cancelar la importación?', 'custom-certificates'),
                    'select_template' => __('Por favor, selecciona una plantilla primero.', 'custom-certificates'),
                    'select_file' => __('Por favor, selecciona un archivo CSV.', 'custom-certificates'),
                    'no_valid_rows' => __('No hay filas válidas para importar.', 'custom-certificates')
                )
            ));
        }
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
