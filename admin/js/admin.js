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

            // Build custom_data object including dynamic variables
            var customData = {
                description: description
            };

            // Collect custom variable values
            $('#custom-variables-fields input, #custom-variables-fields select, #custom-variables-fields textarea').each(function() {
                var $field = $(this);
                var varKey = $field.data('var-key');
                if (varKey) {
                    customData[varKey] = $field.val();
                }
            });

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
                    custom_data: customData
                },
                success: function(response) {
                    if (response.success) {
                        showResult('success', response.data.message);

                        // Reset form
                        $form[0].reset();
                        $('#cert_users').val(null).trigger('change');
                        $('#custom-variables-container').hide();
                        $('#custom-variables-fields').empty();

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

        // Template selection - load custom variables
        $('#cert_template').on('change', function() {
            var templateId = $(this).val();
            var $container = $('#custom-variables-container');
            var $fieldsTable = $('#custom-variables-fields');

            // Clear previous fields
            $fieldsTable.empty();

            if (!templateId) {
                $container.hide();
                return;
            }

            // Load custom variables for this template
            $.ajax({
                url: customCertAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_template_variables',
                    template_id: templateId,
                    nonce: customCertAdmin.search_nonce
                },
                success: function(response) {
                    if (response.success && response.data.variables && response.data.variables.length > 0) {
                        renderCustomVariableFields(response.data.variables);
                        $container.slideDown();
                    } else {
                        $container.hide();
                    }
                },
                error: function() {
                    $container.hide();
                }
            });
        });

        /**
         * Render custom variable fields based on template configuration
         */
        function renderCustomVariableFields(variables) {
            var $fieldsTable = $('#custom-variables-fields');
            $fieldsTable.empty();

            variables.forEach(function(variable) {
                var fieldHtml = '';
                var fieldId = 'custom_var_' + variable.key.toLowerCase();

                // Build field based on type
                switch (variable.type) {
                    case 'select':
                        fieldHtml = '<select id="' + fieldId + '" data-var-key="' + variable.key + '" style="width: 100%; max-width: 400px;">';
                        fieldHtml += '<option value="">' + 'Selecciona una opción...' + '</option>';
                        if (variable.options_array && variable.options_array.length > 0) {
                            variable.options_array.forEach(function(option) {
                                fieldHtml += '<option value="' + escapeHtml(option) + '">' + escapeHtml(option) + '</option>';
                            });
                        }
                        fieldHtml += '</select>';
                        break;

                    case 'textarea':
                        fieldHtml = '<textarea id="' + fieldId + '" data-var-key="' + variable.key + '" rows="3" style="width: 100%; max-width: 400px;"></textarea>';
                        break;

                    case 'text':
                    default:
                        fieldHtml = '<input type="text" id="' + fieldId + '" data-var-key="' + variable.key + '" style="width: 100%; max-width: 400px;">';
                        break;
                }

                // Build table row
                var rowHtml = '<tr>';
                rowHtml += '<th scope="row"><label for="' + fieldId + '">' + escapeHtml(variable.label) + '</label></th>';
                rowHtml += '<td>' + fieldHtml;
                rowHtml += '<p class="description">Variable: <code>{' + variable.key + '}</code></p>';
                rowHtml += '</td></tr>';

                $fieldsTable.append(rowHtml);
            });
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
