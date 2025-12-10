<?php
/**
 * Dependency Installer
 *
 * Auto-installs mPDF if vendor directory is missing
 */

if (!defined('ABSPATH')) {
    exit;
}

class Custom_Cert_Dependency_Installer {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Check if dependencies are installed
     *
     * @return bool
     */
    public static function dependencies_installed() {
        $vendor_autoload = CUSTOM_CERT_PLUGIN_DIR . 'vendor/autoload.php';
        return file_exists($vendor_autoload) && class_exists('\\Mpdf\\Mpdf');
    }

    /**
     * Install dependencies
     *
     * NOTE: Auto-installation is NOT available because mPDF requires Composer.
     * This method returns an error directing users to download the full version.
     *
     * @return WP_Error
     */
    public static function install_dependencies() {
        return new WP_Error(
            'composer_required',
            __('La instalación automática no está disponible. Por favor, descarga la versión FULL del plugin que incluye todas las dependencias.', 'custom-certificates')
        );
    }

    /**
     * Show admin notice if dependencies are missing
     */
    public static function admin_notice_missing_dependencies() {
        ?>
        <div class="notice notice-error">
            <p>
                <strong><?php _e('Custom Certificates - Error de Dependencias', 'custom-certificates'); ?></strong>
            </p>
            <p>
                <?php _e('El plugin requiere la librería mPDF para funcionar. Las dependencias no están instaladas.', 'custom-certificates'); ?>
            </p>
            <p>
                <strong><?php _e('Soluciones:', 'custom-certificates'); ?></strong>
            </p>
            <ol>
                <li>
                    <strong><?php _e('Opción 1 (Recomendada):', 'custom-certificates'); ?></strong>
                    <?php _e('Descarga la versión FULL del plugin que incluye todas las dependencias pre-instaladas.', 'custom-certificates'); ?>
                </li>
                <li>
                    <strong><?php _e('Opción 2 (Avanzada):', 'custom-certificates'); ?></strong>
                    <?php _e('Si tienes acceso SSH, ejecuta:', 'custom-certificates'); ?>
                    <code style="background: #f0f0f0; padding: 2px 6px; display: inline-block; margin: 5px 0;">
                        cd <?php echo CUSTOM_CERT_PLUGIN_DIR; ?> && composer install --no-dev
                    </code>
                </li>
            </ol>
            <p>
                <a href="<?php echo admin_url('admin.php?page=cert-install-dependencies'); ?>" class="button">
                    <?php _e('Ver Instrucciones Completas', 'custom-certificates'); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * Handle dependency installation page
     */
    public static function dependency_installation_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para realizar esta acción.', 'custom-certificates'));
        }

        ?>
        <div class="wrap">
            <h1><?php _e('Instalación de Dependencias - Custom Certificates', 'custom-certificates'); ?></h1>

            <div class="notice notice-info">
                <p><strong><?php _e('Nota:', 'custom-certificates'); ?></strong> <?php _e('La instalación automática no está disponible porque mPDF requiere Composer.', 'custom-certificates'); ?></p>
            </div>

            <div class="card">
                <h2><?php _e('Opción 1: Descarga la Versión FULL (Recomendada)', 'custom-certificates'); ?></h2>
                <p><?php _e('La forma más sencilla de usar este plugin es descargar la versión FULL que ya incluye todas las dependencias.', 'custom-certificates'); ?></p>

                <ol>
                    <li><?php _e('Desactiva y elimina la versión actual del plugin', 'custom-certificates'); ?></li>
                    <li><?php _e('Descarga:', 'custom-certificates'); ?> <code>custom-certificates-full-vX.X.X.zip</code></li>
                    <li><?php _e('Sube e instala la nueva versión desde Plugins > Añadir nuevo > Subir plugin', 'custom-certificates'); ?></li>
                    <li><?php _e('Activa el plugin', 'custom-certificates'); ?></li>
                </ol>

                <p>
                    <a href="https://github.com/tu-usuario/custom-certificates/releases" class="button button-primary" target="_blank">
                        <?php _e('Descargar Versión FULL', 'custom-certificates'); ?>
                    </a>
                </p>
            </div>

            <div class="card" style="margin-top: 20px;">
                <h2><?php _e('Opción 2: Instalación Manual con Composer', 'custom-certificates'); ?></h2>
                <p><?php _e('Si tienes acceso SSH al servidor, puedes instalar las dependencias manualmente:', 'custom-certificates'); ?></p>

                <h3><?php _e('Paso 1: Conectar por SSH', 'custom-certificates'); ?></h3>
                <pre style="background: #f5f5f5; padding: 10px; border: 1px solid #ddd;">ssh usuario@tuservidor.com</pre>

                <h3><?php _e('Paso 2: Navegar al directorio del plugin', 'custom-certificates'); ?></h3>
                <pre style="background: #f5f5f5; padding: 10px; border: 1px solid #ddd;">cd <?php echo CUSTOM_CERT_PLUGIN_DIR; ?></pre>

                <h3><?php _e('Paso 3: Instalar dependencias con Composer', 'custom-certificates'); ?></h3>
                <pre style="background: #f5f5f5; padding: 10px; border: 1px solid #ddd;">composer install --no-dev --optimize-autoloader</pre>

                <h3><?php _e('Paso 4: Verificar la instalación', 'custom-certificates'); ?></h3>
                <pre style="background: #f5f5f5; padding: 10px; border: 1px solid #ddd;">ls -la vendor/autoload.php</pre>

                <p><?php _e('Si ves el archivo autoload.php, las dependencias están instaladas correctamente.', 'custom-certificates'); ?></p>
            </div>

            <div class="card" style="margin-top: 20px;">
                <h2><?php _e('Verificación', 'custom-certificates'); ?></h2>
                <p><?php _e('Estado actual de las dependencias:', 'custom-certificates'); ?></p>

                <?php
                $vendor_path = CUSTOM_CERT_PLUGIN_DIR . 'vendor/autoload.php';
                $mpdf_path = CUSTOM_CERT_PLUGIN_DIR . 'vendor/mpdf/mpdf/src/Mpdf.php';
                ?>

                <table class="widefat">
                    <tr>
                        <td><strong><?php _e('Carpeta vendor/', 'custom-certificates'); ?></strong></td>
                        <td>
                            <?php if (file_exists(CUSTOM_CERT_PLUGIN_DIR . 'vendor')): ?>
                                <span style="color: green;">✓ <?php _e('Existe', 'custom-certificates'); ?></span>
                            <?php else: ?>
                                <span style="color: red;">✗ <?php _e('No existe', 'custom-certificates'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Autoloader (vendor/autoload.php)', 'custom-certificates'); ?></strong></td>
                        <td>
                            <?php if (file_exists($vendor_path)): ?>
                                <span style="color: green;">✓ <?php _e('Existe', 'custom-certificates'); ?></span>
                            <?php else: ?>
                                <span style="color: red;">✗ <?php _e('No existe', 'custom-certificates'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('mPDF instalado', 'custom-certificates'); ?></strong></td>
                        <td>
                            <?php if (file_exists($mpdf_path)): ?>
                                <span style="color: green;">✓ <?php _e('Instalado', 'custom-certificates'); ?></span>
                            <?php else: ?>
                                <span style="color: red;">✗ <?php _e('No instalado', 'custom-certificates'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Clase Mpdf cargable', 'custom-certificates'); ?></strong></td>
                        <td>
                            <?php
                            if (file_exists($vendor_path)) {
                                require_once $vendor_path;
                            }
                            if (class_exists('\\Mpdf\\Mpdf')): ?>
                                <span style="color: green;">✓ <?php _e('Disponible', 'custom-certificates'); ?></span>
                            <?php else: ?>
                                <span style="color: red;">✗ <?php _e('No disponible', 'custom-certificates'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>

                <?php if (self::dependencies_installed()): ?>
                    <div class="notice notice-success inline" style="margin-top: 20px;">
                        <p><strong><?php _e('¡Perfecto!', 'custom-certificates'); ?></strong> <?php _e('Todas las dependencias están instaladas correctamente.', 'custom-certificates'); ?></p>
                        <p>
                            <a href="<?php echo admin_url('edit.php?post_type=bb_cert_template'); ?>" class="button button-primary">
                                <?php _e('Ir a Certificados', 'custom-certificates'); ?>
                            </a>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
