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
        .background-image {
            position: fixed;
            left: 0;
            top: 0;
            right: 0;
            bottom: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }
        .background-image img {
            width: 100%;
            height: 100%;
            display: block;
        }
        .certificate-content {
            position: relative;
            z-index: 1;
            width: 100%;
            min-height: 100%;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
    </style>
</head>
<body>
    <?php if (!empty($data['background_image'])): ?>
        <div class="background-image">
            <img src="<?php echo esc_url($data['background_image']); ?>" alt="" />
        </div>
    <?php endif; ?>

    <?php if (!empty($data['template_content'])): ?>
        <div class="certificate-content">
            <?php
            // Output raw HTML without wpautop processing
            // This allows custom HTML from the editor to render properly
            echo $data['template_content'];
            ?>
        </div>
    <?php endif; ?>
</body>
</html>
