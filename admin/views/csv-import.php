<?php
/**
 * CSV Import Admin Page
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap custom-cert-csv-import-page">
    <h1><?php _e('Importar Certificados desde CSV', 'custom-certificates'); ?></h1>

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

    <div class="csv-import-container">
        <div class="csv-import-main">
            <!-- Step 1: Template Selection -->
            <div class="card csv-step" id="step-1">
                <h2 class="step-title">
                    <span class="step-number">1</span>
                    <?php _e('Seleccionar Plantilla', 'custom-certificates'); ?>
                </h2>

                <p class="description">
                    <?php _e('Selecciona la plantilla de certificado que se asignará a los usuarios importados.', 'custom-certificates'); ?>
                </p>

                <select id="csv-template-select" class="regular-text" style="width: 100%; max-width: 400px;">
                    <option value=""><?php _e('Selecciona una plantilla...', 'custom-certificates'); ?></option>
                    <?php foreach ($templates as $template): ?>
                        <option value="<?php echo esc_attr($template->ID); ?>">
                            <?php echo esc_html($template->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div id="template-info" class="template-info-box" style="display: none;">
                    <h4><?php _e('Variables requeridas en el CSV:', 'custom-certificates'); ?></h4>
                    <ul id="template-variables-list">
                        <li><code>email</code> - <?php _e('Email del usuario (obligatorio)', 'custom-certificates'); ?></li>
                    </ul>
                    <p class="info-note">
                        <span class="dashicons dashicons-info"></span>
                        <?php _e('Los campos de perfil de BuddyBoss se obtienen automáticamente del usuario, no es necesario incluirlos en el CSV.', 'custom-certificates'); ?>
                    </p>
                </div>
            </div>

            <!-- Step 2: CSV Upload -->
            <div class="card csv-step" id="step-2" style="display: none;">
                <h2 class="step-title">
                    <span class="step-number">2</span>
                    <?php _e('Subir Archivo CSV', 'custom-certificates'); ?>
                </h2>

                <p>
                    <a href="#" id="download-sample-csv" class="button button-secondary">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Descargar plantilla CSV de ejemplo', 'custom-certificates'); ?>
                    </a>
                </p>

                <div id="csv-upload-zone" class="upload-zone">
                    <div class="upload-zone-content">
                        <span class="dashicons dashicons-upload"></span>
                        <p><?php _e('Arrastra tu archivo CSV aquí', 'custom-certificates'); ?></p>
                        <p class="upload-zone-or"><?php _e('o', 'custom-certificates'); ?></p>
                        <label for="csv-file-input" class="button button-primary">
                            <?php _e('Seleccionar archivo', 'custom-certificates'); ?>
                        </label>
                        <input type="file" id="csv-file-input" accept=".csv" style="display: none;">
                    </div>
                    <div class="upload-zone-loading" style="display: none;">
                        <span class="spinner is-active"></span>
                        <p><?php _e('Procesando archivo...', 'custom-certificates'); ?></p>
                    </div>
                </div>

                <p class="description">
                    <?php _e('Formato: CSV con codificación UTF-8. Máximo 5MB.', 'custom-certificates'); ?>
                </p>
            </div>

            <!-- Step 3: Preview -->
            <div class="card csv-step" id="step-3" style="display: none;">
                <h2 class="step-title">
                    <span class="step-number">3</span>
                    <?php _e('Vista Previa y Validación', 'custom-certificates'); ?>
                </h2>

                <div id="csv-preview-stats" class="preview-stats">
                    <div class="stat-box stat-total">
                        <span class="stat-number" id="stat-total-rows">0</span>
                        <span class="stat-label"><?php _e('Filas detectadas', 'custom-certificates'); ?></span>
                    </div>
                    <div class="stat-box stat-valid">
                        <span class="stat-number" id="stat-valid-rows">0</span>
                        <span class="stat-label"><?php _e('Usuarios válidos', 'custom-certificates'); ?></span>
                    </div>
                    <div class="stat-box stat-errors">
                        <span class="stat-number" id="stat-error-rows">0</span>
                        <span class="stat-label"><?php _e('Errores', 'custom-certificates'); ?></span>
                    </div>
                </div>

                <div id="csv-preview-errors" class="preview-errors" style="display: none;">
                    <h4>
                        <span class="dashicons dashicons-warning"></span>
                        <?php _e('Errores encontrados:', 'custom-certificates'); ?>
                    </h4>
                    <ul id="error-list"></ul>
                    <p id="more-errors-note" style="display: none;">
                        <?php _e('... y más errores. Se mostrarán en el reporte final.', 'custom-certificates'); ?>
                    </p>
                </div>

                <div class="preview-actions">
                    <p>
                        <label>
                            <input type="checkbox" id="skip-errors-checkbox" checked>
                            <?php _e('Ignorar filas con errores y continuar con las válidas', 'custom-certificates'); ?>
                        </label>
                    </p>

                    <p class="submit-buttons">
                        <button type="button" id="start-import-btn" class="button button-primary button-large">
                            <span class="dashicons dashicons-upload"></span>
                            <?php _e('Iniciar Importación', 'custom-certificates'); ?>
                        </button>
                        <button type="button" id="cancel-preview-btn" class="button">
                            <?php _e('Cancelar', 'custom-certificates'); ?>
                        </button>
                    </p>
                </div>
            </div>

            <!-- Step 4: Progress -->
            <div class="card csv-step" id="step-4" style="display: none;">
                <h2 class="step-title">
                    <span class="step-number">4</span>
                    <?php _e('Importando...', 'custom-certificates'); ?>
                </h2>

                <div class="progress-container">
                    <div class="progress-bar">
                        <div class="progress-fill" id="progress-fill" style="width: 0%"></div>
                    </div>
                    <div class="progress-text" id="progress-text">0%</div>
                </div>

                <div class="progress-stats">
                    <p>
                        <strong><?php _e('Procesados:', 'custom-certificates'); ?></strong>
                        <span id="progress-processed">0</span> / <span id="progress-total">0</span>
                    </p>
                    <p>
                        <strong><?php _e('Exitosos:', 'custom-certificates'); ?></strong>
                        <span id="progress-success" class="text-success">0</span>
                    </p>
                    <p>
                        <strong><?php _e('Omitidos (duplicados):', 'custom-certificates'); ?></strong>
                        <span id="progress-skipped" class="text-warning">0</span>
                    </p>
                    <p>
                        <strong><?php _e('Con errores:', 'custom-certificates'); ?></strong>
                        <span id="progress-errors" class="text-error">0</span>
                    </p>
                </div>

                <p>
                    <button type="button" id="abort-import-btn" class="button">
                        <span class="dashicons dashicons-no"></span>
                        <?php _e('Cancelar importación', 'custom-certificates'); ?>
                    </button>
                </p>
            </div>

            <!-- Step 5: Results -->
            <div class="card csv-step" id="step-5" style="display: none;">
                <h2 class="step-title">
                    <span class="step-number">5</span>
                    <?php _e('Importación Completada', 'custom-certificates'); ?>
                </h2>

                <div id="import-results" class="import-results">
                    <div class="result-summary">
                        <div class="result-item result-success">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <span class="result-number" id="result-success">0</span>
                            <span class="result-label"><?php _e('Certificados asignados', 'custom-certificates'); ?></span>
                        </div>
                        <div class="result-item result-skipped">
                            <span class="dashicons dashicons-marker"></span>
                            <span class="result-number" id="result-skipped">0</span>
                            <span class="result-label"><?php _e('Ya existían (omitidos)', 'custom-certificates'); ?></span>
                        </div>
                        <div class="result-item result-errors">
                            <span class="dashicons dashicons-dismiss"></span>
                            <span class="result-number" id="result-errors">0</span>
                            <span class="result-label"><?php _e('Errores', 'custom-certificates'); ?></span>
                        </div>
                    </div>
                </div>

                <div class="result-actions">
                    <a href="#" id="download-error-report" class="button" style="display: none;">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Descargar reporte de errores', 'custom-certificates'); ?>
                    </a>
                    <a href="<?php echo admin_url('edit.php?post_type=bb_cert_assigned'); ?>" class="button">
                        <span class="dashicons dashicons-list-view"></span>
                        <?php _e('Ver certificados asignados', 'custom-certificates'); ?>
                    </a>
                    <button type="button" id="new-import-btn" class="button button-primary">
                        <span class="dashicons dashicons-update"></span>
                        <?php _e('Nueva importación', 'custom-certificates'); ?>
                    </button>
                </div>
            </div>
        </div>

        <div class="csv-import-sidebar">
            <div class="card">
                <h3><?php _e('Instrucciones', 'custom-certificates'); ?></h3>

                <h4><?php _e('Formato del CSV', 'custom-certificates'); ?></h4>
                <ul>
                    <li><?php _e('La primera fila debe contener los encabezados', 'custom-certificates'); ?></li>
                    <li><?php _e('Columna obligatoria: <code>email</code>', 'custom-certificates'); ?></li>
                    <li><?php _e('Codificación: UTF-8', 'custom-certificates'); ?></li>
                    <li><?php _e('Separador: coma (,) o punto y coma (;)', 'custom-certificates'); ?></li>
                </ul>

                <h4><?php _e('Variables personalizadas', 'custom-certificates'); ?></h4>
                <p><?php _e('Si la plantilla tiene variables personalizadas, debes incluirlas como columnas adicionales en el CSV con el nombre exacto de la variable.', 'custom-certificates'); ?></p>

                <h4><?php _e('Ejemplo', 'custom-certificates'); ?></h4>
                <pre class="csv-example">email,CATEGORIA,NIVEL
juan@ejemplo.com,Web,Avanzado
maria@ejemplo.com,Diseño,Básico</pre>

                <hr>

                <p>
                    <a href="<?php echo admin_url('edit.php?post_type=bb_cert_template&page=assign-certificates'); ?>" class="button button-secondary" style="width: 100%; text-align: center;">
                        <?php _e('Asignación Individual', 'custom-certificates'); ?>
                    </a>
                </p>
            </div>
        </div>
    </div>

    <?php endif; ?>
</div>

<style>
.custom-cert-csv-import-page .csv-import-container {
    display: flex;
    gap: 20px;
    margin-top: 20px;
}

.custom-cert-csv-import-page .csv-import-main {
    flex: 1;
    max-width: 800px;
}

.custom-cert-csv-import-page .csv-import-sidebar {
    width: 300px;
    flex-shrink: 0;
}

.custom-cert-csv-import-page .card {
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    padding: 20px;
    margin-bottom: 20px;
}

.custom-cert-csv-import-page .step-title {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 18px;
}

.custom-cert-csv-import-page .step-number {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    background: #2271b1;
    color: #fff;
    border-radius: 50%;
    font-size: 16px;
    font-weight: 600;
}

.custom-cert-csv-import-page .template-info-box {
    margin-top: 20px;
    padding: 15px;
    background: #f0f6fc;
    border-left: 4px solid #2271b1;
    border-radius: 4px;
}

.custom-cert-csv-import-page .template-info-box h4 {
    margin-top: 0;
    margin-bottom: 10px;
}

.custom-cert-csv-import-page .template-info-box ul {
    margin: 0 0 10px 20px;
}

.custom-cert-csv-import-page .info-note {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    margin: 0;
    font-size: 13px;
    color: #50575e;
}

.custom-cert-csv-import-page .info-note .dashicons {
    color: #2271b1;
    margin-top: 2px;
}

/* Upload Zone */
.custom-cert-csv-import-page .upload-zone {
    border: 2px dashed #c3c4c7;
    border-radius: 8px;
    padding: 40px 20px;
    text-align: center;
    background: #f9f9f9;
    transition: all 0.2s ease;
    margin: 15px 0;
}

.custom-cert-csv-import-page .upload-zone.drag-over {
    border-color: #2271b1;
    background: #f0f6fc;
}

.custom-cert-csv-import-page .upload-zone-content .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #c3c4c7;
}

.custom-cert-csv-import-page .upload-zone-or {
    color: #666;
    margin: 10px 0;
}

/* Preview Stats */
.custom-cert-csv-import-page .preview-stats {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
}

.custom-cert-csv-import-page .stat-box {
    flex: 1;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
}

.custom-cert-csv-import-page .stat-box.stat-total {
    background: #f0f6fc;
    border: 1px solid #c3c4c7;
}

.custom-cert-csv-import-page .stat-box.stat-valid {
    background: #edfaef;
    border: 1px solid #46b450;
}

.custom-cert-csv-import-page .stat-box.stat-errors {
    background: #fcf0f1;
    border: 1px solid #dc3232;
}

.custom-cert-csv-import-page .stat-number {
    display: block;
    font-size: 28px;
    font-weight: 700;
    line-height: 1;
}

.custom-cert-csv-import-page .stat-label {
    display: block;
    font-size: 12px;
    color: #50575e;
    margin-top: 5px;
}

/* Preview Errors */
.custom-cert-csv-import-page .preview-errors {
    background: #fcf0f1;
    border: 1px solid #dc3232;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 20px;
}

.custom-cert-csv-import-page .preview-errors h4 {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 0;
    color: #dc3232;
}

.custom-cert-csv-import-page .preview-errors ul {
    margin: 10px 0 0 20px;
    font-size: 13px;
}

/* Progress */
.custom-cert-csv-import-page .progress-container {
    margin-bottom: 20px;
}

.custom-cert-csv-import-page .progress-bar {
    height: 24px;
    background: #e0e0e0;
    border-radius: 12px;
    overflow: hidden;
}

.custom-cert-csv-import-page .progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #2271b1, #135e96);
    border-radius: 12px;
    transition: width 0.3s ease;
}

.custom-cert-csv-import-page .progress-text {
    text-align: center;
    font-size: 14px;
    font-weight: 600;
    margin-top: 8px;
}

.custom-cert-csv-import-page .progress-stats p {
    margin: 8px 0;
}

.custom-cert-csv-import-page .text-success {
    color: #46b450;
}

.custom-cert-csv-import-page .text-warning {
    color: #dba617;
}

.custom-cert-csv-import-page .text-error {
    color: #dc3232;
}

/* Results */
.custom-cert-csv-import-page .result-summary {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.custom-cert-csv-import-page .result-item {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
}

.custom-cert-csv-import-page .result-item .dashicons {
    font-size: 36px;
    width: 36px;
    height: 36px;
    margin-bottom: 10px;
}

.custom-cert-csv-import-page .result-item.result-success {
    background: #edfaef;
}

.custom-cert-csv-import-page .result-item.result-success .dashicons {
    color: #46b450;
}

.custom-cert-csv-import-page .result-item.result-skipped {
    background: #fff8e5;
}

.custom-cert-csv-import-page .result-item.result-skipped .dashicons {
    color: #dba617;
}

.custom-cert-csv-import-page .result-item.result-errors {
    background: #fcf0f1;
}

.custom-cert-csv-import-page .result-item.result-errors .dashicons {
    color: #dc3232;
}

.custom-cert-csv-import-page .result-number {
    font-size: 32px;
    font-weight: 700;
}

.custom-cert-csv-import-page .result-label {
    font-size: 13px;
    color: #50575e;
}

.custom-cert-csv-import-page .result-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.custom-cert-csv-import-page .result-actions .button .dashicons {
    vertical-align: middle;
    margin-right: 4px;
}

/* Sidebar */
.custom-cert-csv-import-page .csv-import-sidebar .card h3 {
    margin-top: 0;
}

.custom-cert-csv-import-page .csv-import-sidebar h4 {
    margin-bottom: 8px;
}

.custom-cert-csv-import-page .csv-import-sidebar ul {
    margin-left: 20px;
    margin-bottom: 15px;
}

.custom-cert-csv-import-page .csv-example {
    background: #f6f7f7;
    padding: 10px;
    border-radius: 4px;
    font-size: 12px;
    overflow-x: auto;
}

.custom-cert-csv-import-page .submit-buttons {
    display: flex;
    gap: 10px;
}

.custom-cert-csv-import-page .submit-buttons .button .dashicons {
    vertical-align: middle;
    margin-right: 4px;
}

/* Responsive */
@media (max-width: 782px) {
    .custom-cert-csv-import-page .csv-import-container {
        flex-direction: column;
    }

    .custom-cert-csv-import-page .csv-import-sidebar {
        width: 100%;
    }

    .custom-cert-csv-import-page .preview-stats,
    .custom-cert-csv-import-page .result-summary {
        flex-direction: column;
    }
}
</style>
