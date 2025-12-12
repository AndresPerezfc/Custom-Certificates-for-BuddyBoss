<?php
/**
 * Certificate PDF Template
 *
 * Clean template - Only displays background image
 * All text should be added from the certificate template editor
 *
 * Available variables for future use:
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

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 0;
        }
        body {
            margin: 0;
            padding: 0;
        }
        .certificate-page {
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
        }
        <?php if (!empty($data['background_image'])): ?>
        .certificate-background {
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
        }
        .certificate-background img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
        }
        <?php endif; ?>
    </style>
</head>
<body>
    <div class="certificate-page">
        <?php if (!empty($data['background_image'])): ?>
            <div class="certificate-background">
                <img src="<?php echo esc_url($data['background_image']); ?>" alt="Certificate" />
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
