/**
 * Document Download Manager - Admin JavaScript
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Email Marketing API Key functionality
        if ($('#docdownman_toggle_api_key').length) {
            // Toggle API key visibility
            $('#docdownman_toggle_api_key').on('click', function() {
                var $display = $('#docdownman_email_api_key_display');
                if ($display.attr('type') === 'password') {
                    $display.attr('type', 'text');
                    $(this).find('.dashicons').removeClass('dashicons-visibility').addClass('dashicons-hidden');
                } else {
                    $display.attr('type', 'password');
                    $(this).find('.dashicons').removeClass('dashicons-hidden').addClass('dashicons-visibility');
                }
            });

            // Edit API key
            $('#docdownman_edit_api_key').on('click', function() {
                $('.docdownman-api-key-wrapper').hide();
                $('.docdownman-api-key-edit').show();
                $('#docdownman_email_api_key_edit').focus();
            });

            // Save API key
            $('#docdownman_save_api_key').on('click', function() {
                var newKey = $('#docdownman_email_api_key_edit').val();
                if (newKey) {
                    // Update hidden input with actual value
                    $('#docdownman_email_api_key').val(newKey);
                    
                    // Show masked version in display field
                    var maskedKey = '';
                    if (newKey.length > 8) {
                        maskedKey = newKey.substring(0, 4) + newKey.substring(4, newKey.length - 4).replace(/./g, '*') + newKey.substring(newKey.length - 4);
                    } else {
                        maskedKey = newKey.replace(/./g, '*');
                    }
                    $('#docdownman_email_api_key_display').val(maskedKey);
                    
                    // Reset edit field and hide edit form
                    $('#docdownman_email_api_key_edit').val('');
                    $('.docdownman-api-key-edit').hide();
                    $('.docdownman-api-key-wrapper').show();
                }
            });

            // Cancel API key edit
            $('#docdownman_cancel_api_key').on('click', function() {
                $('#docdownman_email_api_key_edit').val('');
                $('.docdownman-api-key-edit').hide();
                $('.docdownman-api-key-wrapper').show();
            });
        }
        // Add new document file row
        $('.docdownman-add-document-file').on('click', function() {
            var timestamp = new Date().getTime();
            var newRow = $('<tr></tr>');
            
            // Title field
            newRow.append(
                $('<td></td>').append(
                    $('<input>').attr({
                        type: 'text',
                        name: 'docdownman_document_files[' + timestamp + '][title]',
                        class: 'regular-text',
                        required: 'required',
                        placeholder: 'Document Title'
                    })
                ).append(
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'docdownman_document_files[' + timestamp + '][id]',
                        value: 'document-' + timestamp
                    })
                )
            );
            
            // URL field
            newRow.append(
                $('<td></td>').append(
                    $('<input>').attr({
                        type: 'url',
                        name: 'docdownman_document_files[' + timestamp + '][url]',
                        class: 'regular-text',
                        required: 'required',
                        placeholder: 'https://example.com/document.pdf'
                    })
                )
            );
            
            // File type field (will be populated after URL is entered)
            newRow.append(
                $('<td></td>').append(
                    $('<span>').text('Type will be detected from URL')
                )
            );
            
            // Shortcode field (will be populated after saving)
            newRow.append(
                $('<td></td>').append(
                    $('<span>').text('Save to generate shortcode')
                )
            );
            
            // Actions field
            newRow.append(
                $('<td></td>').append(
                    $('<button>').attr({
                        type: 'button',
                        class: 'button remove-file'
                    }).text('Remove')
                )
            );
            
            // Add the new row to the table before the last row
            $(this).closest('form').find('table tr:last').before(newRow);
        });
        
        // Remove file row
        $(document).on('click', '.remove-file', function() {
            $(this).closest('tr').remove();
        });
        
        // Export CSV functionality
        $('#export-csv').on('click', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var originalText = $button.text();
            
            $button.text('Exporting...');
            
            // Get all table data
            var tableData = [];
            var headers = [];
            
            // Get headers
            $('.wp-list-table thead th').each(function() {
                var header = $(this).text().trim();
                if (header !== 'Actions') {
                    headers.push(header);
                }
            });
            
            tableData.push(headers);
            
            // Get row data
            $('.wp-list-table tbody tr').each(function() {
                var rowData = [];
                $(this).find('td').each(function(index) {
                    // Skip the Actions column
                    if (index < headers.length) {
                        rowData.push($(this).text().trim());
                    }
                });
                tableData.push(rowData);
            });
            
            // Convert to CSV
            var csvContent = '';
            tableData.forEach(function(row) {
                csvContent += row.map(function(cell) {
                    // Escape quotes and wrap in quotes if contains comma
                    if (cell.includes(',') || cell.includes('"')) {
                        return '"' + cell.replace(/"/g, '""') + '"';
                    }
                    return cell;
                }).join(',') + '\r\n';
            });
            
            // Create download link
            var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            var url = URL.createObjectURL(blob);
            var link = document.createElement('a');
            link.setAttribute('href', url);
            link.setAttribute('download', 'document_downloads_' + new Date().toISOString().slice(0, 10) + '.csv');
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            // Reset button text and show success message
            $button.text(originalText);
            
            // Add success message
            if ($('.docdownman-export-success').length === 0) {
                $('<div class="notice notice-success is-dismissible docdownman-export-success"><p>CSV file exported successfully!</p></div>').insertBefore('.docdownman-export-container');
                
                // Auto-remove the message after 3 seconds
                setTimeout(function() {
                    $('.docdownman-export-success').fadeOut(function() {
                        $(this).remove();
                    });
                }, 3000);
            }
        });
        
        // URL input change - detect file type
        $(document).on('change', 'input[name*="[url]"]', function() {
            var url = $(this).val();
            var fileTypeCell = $(this).closest('tr').find('td:nth-child(3)');
            
            if (url) {
                var extension = url.split('.').pop().toLowerCase();
                var fileType, fileIcon;
                
                if (extension === 'pdf') {
                    fileType = 'PDF';
                    fileIcon = 'dashicons-pdf';
                } else if (['xlsx', 'xls', 'xlsm', 'xlsb', 'csv'].includes(extension)) {
                    fileType = 'Excel';
                    fileIcon = 'dashicons-media-spreadsheet';
                } else {
                    fileType = 'Unknown';
                    fileIcon = 'dashicons-media-default';
                }
                
                fileTypeCell.html('<span class="dashicons ' + fileIcon + '"></span> ' + fileType);
            } else {
                fileTypeCell.text('Type will be detected from URL');
            }
        });
    });
})(jQuery);
