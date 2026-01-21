/**
 * Certificate Verification Script
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        var $form = $('#cert-verification-form');
        var $input = $('#cert-verification-code');
        var $button = $form.find('.cert-verification-button');
        var $buttonText = $button.find('.button-text');
        var $buttonLoading = $button.find('.button-loading');
        var $result = $('#cert-verification-result');

        // Check for code in URL parameters on page load
        var urlCode = getUrlParameter('certificate_id') || getUrlParameter('code') || getUrlParameter('codigo');
        if (urlCode) {
            $input.val(urlCode.toUpperCase());
            // Auto-verify after a short delay
            setTimeout(function() {
                $form.trigger('submit');
            }, 500);
        }

        // Handle form submission
        $form.on('submit', function(e) {
            e.preventDefault();

            var code = $input.val().trim();

            // Validate input
            if (!code) {
                showError(certVerification.strings.empty_code);
                $input.focus();
                return;
            }

            // Disable form and show loading
            setLoading(true);

            // Make AJAX request
            $.ajax({
                url: certVerification.ajax_url,
                type: 'POST',
                data: {
                    action: 'verify_certificate_public',
                    nonce: certVerification.nonce,
                    code: code
                },
                success: function(response) {
                    if (response.success) {
                        showValidCertificate(response.data);
                    } else {
                        if (response.data && response.data.not_found) {
                            showInvalidCertificate(response.data.message);
                        } else {
                            showError(response.data ? response.data.message : certVerification.strings.error);
                        }
                    }
                },
                error: function() {
                    showError(certVerification.strings.error);
                },
                complete: function() {
                    setLoading(false);
                }
            });
        });

        // Convert input to uppercase as user types
        $input.on('input', function() {
            var value = $(this).val().toUpperCase();
            $(this).val(value);
        });

        /**
         * Get URL parameter by name
         */
        function getUrlParameter(name) {
            var urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(name);
        }

        /**
         * Set loading state
         */
        function setLoading(loading) {
            if (loading) {
                $button.prop('disabled', true);
                $buttonText.hide();
                $buttonLoading.show();
                $input.prop('disabled', true);
            } else {
                $button.prop('disabled', false);
                $buttonText.show();
                $buttonLoading.hide();
                $input.prop('disabled', false);
            }
        }

        /**
         * Show valid certificate result
         */
        function showValidCertificate(data) {
            var cert = data.certificate;

            var html = '<div class="result-header">' +
                '<div class="result-icon">' +
                    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">' +
                        '<polyline points="20 6 9 17 4 12"></polyline>' +
                    '</svg>' +
                '</div>' +
                '<div class="result-text">' +
                    '<h4 class="result-title">Certificado Valido</h4>' +
                    '<p class="result-subtitle">Este certificado es autentico y fue emitido oficialmente.</p>' +
                '</div>' +
            '</div>' +
            '<div class="cert-details-grid">' +
                '<div class="cert-detail-item">' +
                    '<span class="cert-detail-label">Titular del Certificado</span>' +
                    '<span class="cert-detail-value">' + escapeHtml(cert.holder_name) + '</span>' +
                '</div>' +
                '<div class="cert-detail-item">' +
                    '<span class="cert-detail-label">Certificado</span>' +
                    '<span class="cert-detail-value">' + escapeHtml(cert.certificate_name) + '</span>' +
                '</div>' +
                '<div class="cert-detail-item">' +
                    '<span class="cert-detail-label">Fecha de Emision</span>' +
                    '<span class="cert-detail-value">' + escapeHtml(cert.issue_date) + '</span>' +
                '</div>' +
                '<div class="cert-detail-item">' +
                    '<span class="cert-detail-label">Codigo de Verificacion</span>' +
                    '<span class="cert-detail-value code">' + escapeHtml(cert.verification_code) + '</span>' +
                '</div>' +
            '</div>';

            $result
                .removeClass('invalid error')
                .addClass('valid')
                .html(html)
                .slideDown(300);
        }

        /**
         * Show invalid certificate result
         */
        function showInvalidCertificate(message) {
            var html = '<div class="result-header">' +
                '<div class="result-icon">' +
                    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">' +
                        '<line x1="18" y1="6" x2="6" y2="18"></line>' +
                        '<line x1="6" y1="6" x2="18" y2="18"></line>' +
                    '</svg>' +
                '</div>' +
                '<div class="result-text">' +
                    '<h4 class="result-title">Certificado No Encontrado</h4>' +
                    '<p class="result-subtitle">' + escapeHtml(message) + '</p>' +
                '</div>' +
            '</div>';

            $result
                .removeClass('valid error')
                .addClass('invalid')
                .html(html)
                .slideDown(300);
        }

        /**
         * Show error message
         */
        function showError(message) {
            var html = '<div class="result-header">' +
                '<div class="result-icon">' +
                    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">' +
                        '<circle cx="12" cy="12" r="10"></circle>' +
                        '<line x1="12" y1="8" x2="12" y2="12"></line>' +
                        '<line x1="12" y1="16" x2="12.01" y2="16"></line>' +
                    '</svg>' +
                '</div>' +
                '<div class="result-text">' +
                    '<h4 class="result-title">' + escapeHtml(message) + '</h4>' +
                '</div>' +
            '</div>';

            $result
                .removeClass('valid invalid')
                .addClass('error')
                .html(html)
                .slideDown(300);
        }

        /**
         * Escape HTML special characters
         */
        function escapeHtml(text) {
            if (!text) return '';
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    });

})(jQuery);
