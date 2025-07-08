/**
 * Document Download Manager - Public JavaScript
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Open modal when download button is clicked
        $('.docdownman-download-button').on('click', function() {
            var fileId = $(this).data('toggle');
            $('#' + fileId).css('display', 'block');
        });

        // Close modal when close button is clicked
        $('.docdownman-close').on('click', function() {
            $(this).closest('.docdownman-modal').css('display', 'none');
        });

        // Close modal when clicking outside the modal content
        $('.docdownman-modal').on('click', function(e) {
            if (e.target === this) {
                $(this).css('display', 'none');
            }
        });

        // Handle form submission
        $('.docdownman-form').on('submit', function(e) {
            e.preventDefault();
            
            var form = $(this);
            var formData = form.serialize();
            var modal = form.closest('.docdownman-modal');
            var modalContent = form.closest('.docdownman-modal-content');
            
            // Remove any existing messages
            $('.docdownman-message').remove();
            
            // Add loading message
            modalContent.append('<div class="docdownman-message">Processing your request...</div>');
            
            // Submit form data via AJAX
            $.ajax({
                url: docdownman_ajax.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        // Replace form with success message
                        form.replaceWith('<div class="docdownman-message success">' + response.data.message + '</div>');
                        
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
                        $('.docdownman-message').remove();
                        modalContent.append('<div class="docdownman-message error">' + response.data + '</div>');
                    }
                },
                error: function() {
                    // Show error message
                    $('.docdownman-message').remove();
                    modalContent.append('<div class="docdownman-message error">An error occurred. Please try again.</div>');
                }
            });
        });
    });
})(jQuery);
