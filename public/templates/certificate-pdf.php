<?php
/**
 * Certificate PDF Template
 *
 * This template can be overridden by copying it to:
 * yourtheme/custom-certificates/certificate-pdf.php
 *
 * Available variables:
 * - $data['user_name']
 * - $data['user_email']
 * - $data['template_name']
 * - $data['verification_code']
 * - $data['issue_date_formatted']
 * - $data['background_image']
 * - $data['custom_data']
 * - $data['template_config']
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get template configuration
$bg_color = isset($data['template_config']['bg_color']) ? $data['template_config']['bg_color'] : '#ffffff';
$text_color = isset($data['template_config']['text_color']) ? $data['template_config']['text_color'] : '#000000';
$font_size = isset($data['template_config']['font_size']) ? $data['template_config']['font_size'] : '24';
$orientation = isset($data['template_config']['orientation']) ? $data['template_config']['orientation'] : 'landscape';

// Get custom data
$description = isset($data['custom_data']['description']) ? $data['custom_data']['description'] : '';

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 0;
            size: <?php echo $orientation === 'landscape' ? 'A4-L' : 'A4'; ?>;
        }

        body {
            font-family: 'DejaVu Sans', 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: <?php echo esc_attr($bg_color); ?>;
        }

        .certificate-container {
            position: relative;
            width: <?php echo $orientation === 'landscape' ? '297mm' : '210mm'; ?>;
            height: <?php echo $orientation === 'landscape' ? '210mm' : '297mm'; ?>;
            padding: 20mm;
            box-sizing: border-box;
            <?php if (!empty($data['background_image'])): ?>
            background-image: url('<?php echo esc_url($data['background_image']); ?>');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            <?php endif; ?>
        }

        .certificate-border {
            border: 3px solid <?php echo esc_attr($text_color); ?>;
            padding: 15mm;
            height: 100%;
            box-sizing: border-box;
        }

        .certificate-content {
            text-align: center;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .certificate-header {
            margin-bottom: 20mm;
        }

        .certificate-logo {
            width: 80mm;
            margin-bottom: 10mm;
        }

        .certificate-title {
            font-size: 48px;
            font-weight: bold;
            color: <?php echo esc_attr($text_color); ?>;
            margin-bottom: 5mm;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .certificate-subtitle {
            font-size: 20px;
            color: <?php echo esc_attr($text_color); ?>;
            margin-bottom: 15mm;
        }

        .certificate-body {
            margin: 15mm 0;
        }

        .certificate-text {
            font-size: 16px;
            color: <?php echo esc_attr($text_color); ?>;
            line-height: 1.8;
            margin-bottom: 8mm;
        }

        .certificate-label {
            font-size: 14px;
            color: <?php echo esc_attr($text_color); ?>;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5mm;
        }

        .user-name {
            font-size: <?php echo esc_attr($font_size + 12); ?>px;
            font-weight: bold;
            color: <?php echo esc_attr($text_color); ?>;
            margin: 10mm 0;
            padding: 5mm 0;
            border-bottom: 2px solid <?php echo esc_attr($text_color); ?>;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .certificate-description {
            font-size: 14px;
            color: <?php echo esc_attr($text_color); ?>;
            line-height: 1.6;
            margin: 10mm auto;
            max-width: 80%;
            font-style: italic;
        }

        .certificate-footer {
            margin-top: auto;
            display: table;
            width: 100%;
            padding-top: 15mm;
        }

        .footer-item {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            font-size: 12px;
            color: <?php echo esc_attr($text_color); ?>;
        }

        .footer-item.left {
            text-align: left;
        }

        .footer-item.right {
            text-align: right;
        }

        .footer-label {
            font-weight: bold;
            display: block;
            margin-bottom: 3px;
        }

        .footer-value {
            display: block;
        }

        .verification-code {
            font-family: 'Courier New', monospace;
            font-size: 10px;
            color: <?php echo esc_attr($text_color); ?>;
            opacity: 0.7;
        }

        /* Decorative elements */
        .decorative-line {
            width: 60mm;
            height: 2px;
            background: <?php echo esc_attr($text_color); ?>;
            margin: 10mm auto;
        }

        .seal {
            width: 30mm;
            height: 30mm;
            border: 3px solid <?php echo esc_attr($text_color); ?>;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            font-weight: bold;
            color: <?php echo esc_attr($text_color); ?>;
            margin: 10mm auto;
        }
    </style>
</head>
<body>
    <div class="certificate-container">
        <div class="certificate-border">
            <div class="certificate-content">
                <!-- Header -->
                <div class="certificate-header">
                    <!-- Si tienes un logo, puedes agregarlo aquí -->
                    <!-- <img src="URL_DEL_LOGO" class="certificate-logo" alt="Logo"> -->

                    <div class="certificate-title">Certificado</div>
                    <div class="certificate-subtitle"><?php echo esc_html($data['template_name']); ?></div>
                    <div class="decorative-line"></div>
                </div>

                <!-- Body -->
                <div class="certificate-body">
                    <div class="certificate-label">
                        Se otorga el presente certificado a
                    </div>

                    <div class="user-name">
                        <?php echo esc_html($data['user_name']); ?>
                    </div>

                    <div class="certificate-text">
                        Por haber completado satisfactoriamente los requisitos establecidos
                        para la obtención de este reconocimiento.
                    </div>

                    <?php if (!empty($description)): ?>
                    <div class="certificate-description">
                        <?php echo esc_html($description); ?>
                    </div>
                    <?php endif; ?>

                    <!-- Sello decorativo opcional -->
                    <!-- <div class="seal">✓</div> -->
                </div>

                <!-- Footer -->
                <div class="certificate-footer">
                    <div class="footer-item left">
                        <span class="footer-label">Fecha de Emisión:</span>
                        <span class="footer-value"><?php echo esc_html($data['issue_date_formatted']); ?></span>
                    </div>
                    <div class="footer-item right">
                        <span class="footer-label">Código de Verificación:</span>
                        <span class="footer-value verification-code"><?php echo esc_html($data['verification_code']); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
