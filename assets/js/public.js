/**
 * Document Download Manager - Public JavaScript
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Open modal when download button is clicked
        $('.ddmanager-download-button, .docdownman-download-button').on('click', function() {
            var fileId = $(this).data('toggle');
            $('#' + fileId).css('display', 'block');
        });

        // Close modal when close button is clicked
        $('.ddmanager-close, .docdownman-close').on('click', function() {
            $(this).closest('.ddmanager-modal, .docdownman-modal').css('display', 'none');
        });

        // Close modal when clicking outside the modal content
        $('.ddmanager-modal, .docdownman-modal').on('click', function(e) {
            if (e.target === this) {
                $(this).css('display', 'none');
            }
        });

        // Handle form submission
        $('.ddmanager-form, .docdownman-form').on('submit', function(e) {
            e.preventDefault();
            
            var form = $(this);
            var formData = form.serialize();
            var modal = form.closest('.ddmanager-modal, .docdownman-modal');
            var modalContent = form.closest('.ddmanager-modal-content, .docdownman-modal-content');
            
            // Remove any existing messages
            $('.ddmanager-message, .docdownman-message').remove();
            
            // Add loading message
            var messageClass = form.hasClass('ddmanager-form') ? 'ddmanager-message' : 'docdownman-message';
            modalContent.append('<div class="' + messageClass + '">Processing your request...</div>');
            
            // Submit form data via AJAX
            $.ajax({
                url: (typeof ddmanager_ajax !== 'undefined' ? ddmanager_ajax.ajax_url : docdownman_ajax.ajax_url),
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        // Replace form with success message
                        form.replaceWith('<div class="' + messageClass + ' success">' + response.data.message + '</div>');
                        
                        // Start download after a short delay
                        setTimeout(function() {
                            window.location.href = response.data.file_url;
                            
                            // Close modal after download starts
                            setTimeout(function() {
                                modal.css('display', 'none');
                            }, 2000);
                        }, 1000);
                    } else {
                        // Show error message
                        $('.ddmanager-message, .docdownman-message').remove();
                        modalContent.append('<div class="' + messageClass + ' error">' + response.data + '</div>');
                    }
                },
                error: function() {
                    // Show error message
                    $('.ddmanager-message, .docdownman-message').remove();
                    modalContent.append('<div class="' + messageClass + ' error">An error occurred. Please try again.</div>');
                }
            });
        });
    });
})(jQuery);
