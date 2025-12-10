/**
 * Admin JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        // Initialize Select2 for user selection
        if ($('#cert_users').length) {
            $('#cert_users').select2({
                ajax: {
                    url: customCertAdmin.ajax_url,
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            action: 'search_users',
                            search: params.term,
                            nonce: customCertAdmin.search_nonce
                        };
                    },
                    processResults: function(response) {
                        if (response.success) {
                            return {
                                results: response.data
                            };
                        }
                        return {
                            results: []
                        };
                    },
                    cache: true
                },
                minimumInputLength: 2,
                placeholder: 'Buscar usuarios por nombre o email...',
                allowClear: true,
                language: {
                    inputTooShort: function() {
                        return 'Escribe al menos 2 caracteres para buscar';
                    },
                    searching: function() {
                        return 'Buscando...';
                    },
                    noResults: function() {
                        return 'No se encontraron usuarios';
                    }
                }
            });
        }

        // Handle certificate assignment form
        $('#assign-certificate-form').on('submit', function(e) {
            e.preventDefault();

            var $form = $(this);
            var $button = $form.find('button[type="submit"]');
            var $result = $('#assign-result');

            // Get form data
            var templateId = $('#cert_template').val();
            var userIds = $('#cert_users').val();
            var description = $('#cert_description').val();

            // Validate
            if (!templateId || !userIds || userIds.length === 0) {
                showResult('error', 'Por favor, selecciona una plantilla y al menos un usuario.');
                return;
            }

            // Disable button
            $button.prop('disabled', true).text(customCertAdmin.strings.assigning);

            // Send AJAX request
            $.ajax({
                url: customCertAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'assign_certificate',
                    nonce: customCertAdmin.assign_nonce,
                    template_id: templateId,
                    user_ids: userIds,
                    custom_data: {
                        description: description
                    }
                },
                success: function(response) {
                    if (response.success) {
                        showResult('success', response.data.message);

                        // Reset form
                        $form[0].reset();
                        $('#cert_users').val(null).trigger('change');

                        // Reload after 2 seconds
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else {
                        showResult('error', response.data.message || customCertAdmin.strings.error);
                    }
                },
                error: function() {
                    showResult('error', customCertAdmin.strings.error);
                },
                complete: function() {
                    $button.prop('disabled', false).html('<span class="dashicons dashicons-awards"></span> Asignar Certificado(s)');
                }
            });
        });

        // Show result message
        function showResult(type, message) {
            var $result = $('#assign-result');
            $result
                .removeClass('success error')
                .addClass(type)
                .html('<p>' + message + '</p>')
                .slideDown();

            // Auto-hide after 5 seconds
            setTimeout(function() {
                $result.slideUp();
            }, 5000);
        }

        // Handle certificate removal
        $(document).on('click', '.remove-certificate', function(e) {
            e.preventDefault();

            if (!confirm(customCertAdmin.strings.confirm_remove)) {
                return;
            }

            var $button = $(this);
            var certificateId = $button.data('cert-id');

            $button.prop('disabled', true);

            $.ajax({
                url: customCertAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'remove_certificate',
                    nonce: customCertAdmin.remove_nonce,
                    certificate_id: certificateId
                },
                success: function(response) {
                    if (response.success) {
                        $button.closest('tr').fadeOut(function() {
                            $(this).remove();
                        });
                    } else {
                        alert(response.data.message || 'Error al eliminar certificado');
                        $button.prop('disabled', false);
                    }
                },
                error: function() {
                    alert('Error al eliminar certificado');
                    $button.prop('disabled', false);
                }
            });
        });

        // Template selection preview (if applicable)
        $('#cert_template').on('change', function() {
            var templateId = $(this).val();
            // You can add template preview functionality here
        });

    });

})(jQuery);
