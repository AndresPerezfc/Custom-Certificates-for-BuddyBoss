/**
 * CSV Import JavaScript
 * Handles the CSV import functionality for bulk certificate assignment
 */

(function($) {
    'use strict';

    var CSVImport = {
        // State
        sessionId: null,
        templateId: null,
        totalRows: 0,
        processedRows: 0,
        successCount: 0,
        skippedCount: 0,
        errorCount: 0,
        batchSize: 25,
        isProcessing: false,
        aborted: false,
        errors: [],

        /**
         * Initialize
         */
        init: function() {
            this.batchSize = customCertCSV.batch_size || 25;
            this.bindEvents();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            var self = this;

            // Template selection
            $('#csv-template-select').on('change', function() {
                self.onTemplateSelect($(this).val());
            });

            // File input change
            $('#csv-file-input').on('change', function(e) {
                if (e.target.files.length > 0) {
                    self.onFileSelect(e.target.files[0]);
                }
            });

            // Drag and drop
            var $uploadZone = $('#csv-upload-zone');
            $uploadZone.on('dragover dragenter', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('drag-over');
            });

            $uploadZone.on('dragleave dragend drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('drag-over');
            });

            $uploadZone.on('drop', function(e) {
                var files = e.originalEvent.dataTransfer.files;
                if (files.length > 0) {
                    self.onFileSelect(files[0]);
                }
            });

            // Download sample CSV
            $('#download-sample-csv').on('click', function(e) {
                e.preventDefault();
                self.downloadSampleCSV();
            });

            // Start import
            $('#start-import-btn').on('click', function() {
                self.startImport();
            });

            // Cancel preview
            $('#cancel-preview-btn').on('click', function() {
                self.resetToStep2();
            });

            // Abort import
            $('#abort-import-btn').on('click', function() {
                if (confirm(customCertCSV.strings.confirm_cancel)) {
                    self.abortImport();
                }
            });

            // Download error report
            $('#download-error-report').on('click', function(e) {
                e.preventDefault();
                self.downloadErrorReport();
            });

            // New import
            $('#new-import-btn').on('click', function() {
                self.resetImport();
            });
        },

        /**
         * Handle template selection
         */
        onTemplateSelect: function(templateId) {
            var self = this;

            if (!templateId) {
                $('#step-2').hide();
                $('#template-info').hide();
                this.templateId = null;
                return;
            }

            this.templateId = templateId;

            // Get template info via AJAX
            $.ajax({
                url: customCertCSV.ajax_url,
                type: 'POST',
                data: {
                    action: 'csv_get_template_info',
                    template_id: templateId,
                    nonce: customCertCSV.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.renderTemplateInfo(response.data);
                        $('#step-2').slideDown();
                    } else {
                        alert(response.data.message || customCertCSV.strings.error);
                    }
                },
                error: function() {
                    alert(customCertCSV.strings.error);
                }
            });
        },

        /**
         * Render template info
         */
        renderTemplateInfo: function(data) {
            var $list = $('#template-variables-list');
            $list.empty();

            // Always show email as required
            $list.append('<li><code>email</code> - Email del usuario (obligatorio)</li>');

            // Add custom variables if any
            if (data.has_variables && data.variables.length > 0) {
                $.each(data.variables, function(i, variable) {
                    var typeLabel = variable.type === 'select' ? ' (selección)' : '';
                    $list.append(
                        '<li><code>' + variable.key.toUpperCase() + '</code> - ' +
                        variable.label + typeLabel + '</li>'
                    );
                });
            }

            $('#template-info').show();
        },

        /**
         * Handle file selection
         */
        onFileSelect: function(file) {
            var self = this;

            // Validate file type
            if (!file.name.toLowerCase().endsWith('.csv')) {
                alert('El archivo debe ser un archivo CSV (.csv)');
                return;
            }

            // Validate file size (5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('El archivo excede el tamaño máximo de 5MB.');
                return;
            }

            if (!this.templateId) {
                alert(customCertCSV.strings.select_template);
                return;
            }

            // Show loading state
            $('.upload-zone-content').hide();
            $('.upload-zone-loading').show();

            // Create form data
            var formData = new FormData();
            formData.append('action', 'csv_upload_preview');
            formData.append('file', file);
            formData.append('template_id', this.templateId);
            formData.append('nonce', customCertCSV.nonce);

            // Upload and preview
            $.ajax({
                url: customCertCSV.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $('.upload-zone-content').show();
                    $('.upload-zone-loading').hide();

                    if (response.success) {
                        self.sessionId = response.data.session_id;
                        self.totalRows = response.data.total_rows;
                        self.renderPreview(response.data);
                        $('#step-3').slideDown();
                    } else {
                        alert(response.data.message || customCertCSV.strings.error);
                    }
                },
                error: function(xhr, status, error) {
                    $('.upload-zone-content').show();
                    $('.upload-zone-loading').hide();

                    // Try to get more detailed error info
                    var errorMsg = customCertCSV.strings.error;
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorMsg = xhr.responseJSON.data.message;
                    } else if (xhr.responseText) {
                        console.log('Server response:', xhr.responseText);
                        errorMsg += '\n\nDetalles: ' + error;
                    }
                    alert(errorMsg);
                }
            });
        },

        /**
         * Render preview data
         */
        renderPreview: function(data) {
            // Update stats
            $('#stat-total-rows').text(data.total_rows);
            $('#stat-valid-rows').text(data.valid_rows);
            $('#stat-error-rows').text(data.error_count);

            // Show errors if any
            var $errorSection = $('#csv-preview-errors');
            var $errorList = $('#error-list');
            $errorList.empty();

            if (data.errors && data.errors.length > 0) {
                $.each(data.errors, function(i, error) {
                    $errorList.append('<li>' + error + '</li>');
                });

                if (data.error_count > data.errors.length) {
                    $('#more-errors-note').show();
                } else {
                    $('#more-errors-note').hide();
                }

                $errorSection.show();
            } else {
                $errorSection.hide();
            }

            // Enable/disable start button based on valid rows
            if (data.valid_rows === 0) {
                $('#start-import-btn').prop('disabled', true);
            } else {
                $('#start-import-btn').prop('disabled', false);
            }
        },

        /**
         * Download sample CSV
         */
        downloadSampleCSV: function() {
            if (!this.templateId) {
                alert(customCertCSV.strings.select_template);
                return;
            }

            var url = customCertCSV.ajax_url +
                '?action=csv_download_sample' +
                '&template_id=' + this.templateId +
                '&nonce=' + customCertCSV.nonce;

            window.location.href = url;
        },

        /**
         * Start import process
         */
        startImport: function() {
            if (!this.sessionId) {
                alert(customCertCSV.strings.select_file);
                return;
            }

            if (this.totalRows === 0) {
                alert(customCertCSV.strings.no_valid_rows);
                return;
            }

            // Reset counters
            this.processedRows = 0;
            this.successCount = 0;
            this.skippedCount = 0;
            this.errorCount = 0;
            this.errors = [];
            this.isProcessing = true;
            this.aborted = false;

            // Update UI
            $('#step-3').hide();
            $('#step-4').slideDown();
            $('#progress-total').text(this.totalRows);

            // Start processing batches
            this.processBatch(0);
        },

        /**
         * Process a batch of rows
         */
        processBatch: function(offset) {
            var self = this;

            if (this.aborted) {
                this.showResults();
                return;
            }

            $.ajax({
                url: customCertCSV.ajax_url,
                type: 'POST',
                data: {
                    action: 'csv_process_batch',
                    session_id: this.sessionId,
                    offset: offset,
                    nonce: customCertCSV.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update counters
                        self.processedRows += response.data.processed;
                        self.successCount += response.data.success;
                        self.skippedCount += response.data.skipped;
                        self.errorCount += response.data.errors.length;

                        // Collect errors
                        if (response.data.errors && response.data.errors.length > 0) {
                            self.errors = self.errors.concat(response.data.errors);
                        }

                        // Update progress UI
                        self.updateProgress();

                        // Continue or finish
                        if (response.data.completed || self.processedRows >= self.totalRows) {
                            self.showResults();
                        } else {
                            self.processBatch(response.data.next_offset);
                        }
                    } else {
                        alert(response.data.message || customCertCSV.strings.error);
                        self.showResults();
                    }
                },
                error: function() {
                    alert(customCertCSV.strings.error);
                    self.showResults();
                }
            });
        },

        /**
         * Update progress UI
         */
        updateProgress: function() {
            var progress = this.totalRows > 0
                ? Math.round((this.processedRows / this.totalRows) * 100)
                : 0;

            $('#progress-fill').css('width', progress + '%');
            $('#progress-text').text(progress + '%');
            $('#progress-processed').text(this.processedRows);
            $('#progress-success').text(this.successCount);
            $('#progress-skipped').text(this.skippedCount);
            $('#progress-errors').text(this.errorCount);
        },

        /**
         * Abort import
         */
        abortImport: function() {
            this.aborted = true;
        },

        /**
         * Show final results
         */
        showResults: function() {
            this.isProcessing = false;

            // Update result counters
            $('#result-success').text(this.successCount);
            $('#result-skipped').text(this.skippedCount);
            $('#result-errors').text(this.errorCount);

            // Show/hide error report button
            if (this.errorCount > 0) {
                $('#download-error-report').show();
            } else {
                $('#download-error-report').hide();
            }

            // Show results step
            $('#step-4').hide();
            $('#step-5').slideDown();
        },

        /**
         * Download error report
         */
        downloadErrorReport: function() {
            if (!this.sessionId) {
                return;
            }

            var url = customCertCSV.ajax_url +
                '?action=csv_download_error_report' +
                '&session_id=' + this.sessionId +
                '&nonce=' + customCertCSV.nonce;

            window.location.href = url;
        },

        /**
         * Reset to step 2
         */
        resetToStep2: function() {
            this.sessionId = null;
            this.totalRows = 0;
            $('#step-3').hide();
            $('#csv-file-input').val('');
        },

        /**
         * Reset entire import
         */
        resetImport: function() {
            // Reset state
            this.sessionId = null;
            this.templateId = null;
            this.totalRows = 0;
            this.processedRows = 0;
            this.successCount = 0;
            this.skippedCount = 0;
            this.errorCount = 0;
            this.errors = [];
            this.isProcessing = false;
            this.aborted = false;

            // Reset UI
            $('#csv-template-select').val('');
            $('#csv-file-input').val('');
            $('#template-info').hide();
            $('#step-2').hide();
            $('#step-3').hide();
            $('#step-4').hide();
            $('#step-5').hide();

            // Reset progress
            $('#progress-fill').css('width', '0%');
            $('#progress-text').text('0%');

            // Scroll to top
            $('html, body').animate({ scrollTop: 0 }, 300);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        CSVImport.init();
    });

})(jQuery);
