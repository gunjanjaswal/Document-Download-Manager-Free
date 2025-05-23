/**
 * Document Download Manager - Public JavaScript
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Open modal when download button is clicked
        $('.ddm-download-button').on('click', function() {
            var fileId = $(this).data('file-id');
            $('#ddm-modal-' + fileId).css('display', 'block');
        });

        // Close modal when close button is clicked
        $('.ddm-close').on('click', function() {
            $(this).closest('.ddm-modal').css('display', 'none');
        });

        // Close modal when clicking outside the modal content
        $('.ddm-modal').on('click', function(e) {
            if (e.target === this) {
                $(this).css('display', 'none');
            }
        });

        // Handle form submission
        $('.ddm-form').on('submit', function(e) {
            e.preventDefault();
            
            var form = $(this);
            var formData = form.serialize();
            var modal = form.closest('.ddm-modal');
            var modalContent = form.closest('.ddm-modal-content');
            
            // Remove any existing messages
            $('.ddm-message').remove();
            
            // Add loading message
            modalContent.append('<div class="ddm-message">Processing your request...</div>');
            
            // Submit form data via AJAX
            $.ajax({
                url: ddm_ajax.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        // Replace form with success message
                        form.replaceWith('<div class="ddm-message success">' + response.data.message + '</div>');
                        
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
                        $('.ddm-message').remove();
                        modalContent.append('<div class="ddm-message error">' + response.data + '</div>');
                    }
                },
                error: function() {
                    // Show error message
                    $('.ddm-message').remove();
                    modalContent.append('<div class="ddm-message error">An error occurred. Please try again.</div>');
                }
            });
        });
    });
})(jQuery);
