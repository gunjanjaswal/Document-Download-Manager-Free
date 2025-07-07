<?php
/**
 * Public-facing functionality of the plugin.
 */
class Document_Download_Manager_Public {

    /**
     * Enqueue public styles
     */
    public function enqueue_styles() {
        wp_enqueue_style('ddm-public-css', DDM_PLUGIN_URL . 'assets/css/public.css', array(), DDM_VERSION);
    }
    
    /**
     * Enqueue public scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_script('ddm-public-js', DDM_PLUGIN_URL . 'assets/js/public.js', array('jquery'), DDM_VERSION, true);
        
        // Enqueue Dashicons for the download icon
        wp_enqueue_style('dashicons');
        
        wp_localize_script('ddm-public-js', 'ddm_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ddm_nonce')
        ));
    }
    
    /**
     * Download shortcode callback
     */
    public function download_shortcode($atts) {
        // Support both array and string attributes for backward compatibility
        if (!is_array($atts)) {
            $atts = array('id' => $atts);
        }
        
        $atts = shortcode_atts(array(
            'id' => '',
            'text' => 'Free Download'
        ), $atts);
        
        if (empty($atts['id'])) {
            return '<p>Error: Document file ID is required.</p>';
        }
        
        $document_files = get_option('ddm_document_files', array());
        $file_id = $atts['id'];
        $file_data = null;
        
        // Find the file with the matching ID
        foreach ($document_files as $file) {
            // Check for ID match first
            if (isset($file['id']) && $file['id'] === $file_id) {
                $file_data = $file;
                break;
            }
            
            // For backward compatibility, also check if the ID matches the sanitized title
            if (sanitize_title($file['title']) === $file_id) {
                $file_data = $file;
                // Ensure the file has an ID for future use
                if (!isset($file_data['id'])) {
                    $file_data['id'] = $file_id;
                }
                break;
            }
        }
        
        if (!$file_data) {
            return '<p>Error: Document file not found.</p>';
        }
        
        // Generate a unique ID for this download button
        $unique_id = 'ddm-download-' . $file_id;
        
        // Determine file type based on URL extension
        $file_extension = pathinfo($file_data['url'], PATHINFO_EXTENSION);
        $is_pdf = strtolower($file_extension) === 'pdf';
        $file_type_class = $is_pdf ? 'ddm-pdf-button' : 'ddm-excel-button';
        
        ob_start();
        ?>
        <div class="ddm-download-container">
            <button class="ddm-download-button <?php echo esc_attr($file_type_class); ?>" data-file-id="<?php echo esc_attr($file_id); ?>" data-file-title="<?php echo esc_attr($file_data['title']); ?>" data-file-url="<?php echo esc_url($file_data['url']); ?>" data-file-type="<?php echo $is_pdf ? 'pdf' : 'excel'; ?>">
                <?php echo esc_html($atts['text']); ?>
            </button>
        </div>
        
        <div id="ddm-modal-<?php echo esc_attr($file_id); ?>" class="ddm-modal">
            <div class="ddm-modal-content">
                <span class="ddm-close">&times;</span>
                <h2>Download <?php echo esc_html($file_data['title']); ?></h2>
                <p>Please provide your information to download this file:</p>
                
                <form id="ddm-form-<?php echo esc_attr($file_id); ?>" class="ddm-form">
                    <div class="ddm-form-group">
                        <label for="ddm-name-<?php echo esc_attr($file_id); ?>">Name</label>
                        <input type="text" id="ddm-name-<?php echo esc_attr($file_id); ?>" name="name" required>
                    </div>
                    
                    <div class="ddm-form-group">
                        <label for="ddm-email-<?php echo esc_attr($file_id); ?>">Email</label>
                        <input type="email" id="ddm-email-<?php echo esc_attr($file_id); ?>" name="email" required>
                    </div>
                    
                    <input type="hidden" name="file_id" value="<?php echo esc_attr($file_id); ?>">
                    <input type="hidden" name="file_title" value="<?php echo esc_attr($file_data['title']); ?>">
                    <input type="hidden" name="file_url" value="<?php echo esc_url($file_data['url']); ?>">
                    <input type="hidden" name="action" value="ddm_process_download">
                    <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('ddm_nonce')); ?>">
                    
                    <div class="ddm-form-group">
                        <button type="submit" class="ddm-submit-button">Download</button>
                    </div>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Process download AJAX request
     */
    public function process_download() {
        // Check nonce with proper sanitization
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'ddm_nonce')) {
            wp_send_json_error(esc_html__('Invalid security token.', 'document-download-manager'));
            wp_die();
        }
        
        // Check user permissions - anyone can download but we still check for bots
        if (!is_user_logged_in() && empty($_SERVER['HTTP_USER_AGENT'])) {
            wp_send_json_error(esc_html__('Invalid request.', 'document-download-manager'));
            wp_die();
        }
        
        // Check required fields
        $required_fields = array('name', 'email', 'file_id', 'file_title', 'file_url');
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                wp_send_json_error(esc_html__('Missing required field: ', 'document-download-manager') . esc_html($field));
                wp_die();
            }
        }
        
        // Sanitize input
        $name = sanitize_text_field(wp_unslash($_POST['name']));
        $email = sanitize_email(wp_unslash($_POST['email']));
        $file_id = sanitize_text_field(wp_unslash($_POST['file_id']));
        $file_title = sanitize_text_field(wp_unslash($_POST['file_title']));
        $file_url = esc_url_raw(wp_unslash($_POST['file_url']));
        $consent = isset($_POST['consent']) ? (bool) $_POST['consent'] : false;
        
        // Validate email
        if (!is_email($email)) {
            wp_send_json_error(esc_html__('Invalid email address.', 'document-download-manager'));
            wp_die();
        }
        
        // Validate URL
        if (empty($file_url) || !filter_var($file_url, FILTER_VALIDATE_URL)) {
            wp_send_json_error(esc_html__('Invalid file URL.', 'document-download-manager'));
            wp_die();
        }
        
        // Save download record to database
        global $wpdb;
        $table_name = $wpdb->prefix . 'ddm_downloads';
        
        // Generate a cache key for this download record
        $cache_key = 'ddm_download_' . md5($email . $file_url . time());
        
        // Insert the record
        $result = $wpdb->insert(
            $table_name,
            array(
                'name' => $name,
                'email' => $email,
                'file_name' => $file_title,
                'file_url' => $file_url
            ),
            array('%s', '%s', '%s', '%s')
        );
        
        // If insert was successful, invalidate the records cache
        if ($result) {
            // Clear the all records cache to ensure the admin page shows the latest data
            wp_cache_delete('ddm_all_records', 'document-download-manager');
            
            // Email marketing integration is available in the Pro version
            // This is just a placeholder in the free version
        }
        
        // Return success response with file URL
        wp_send_json_success(array(
            'file_url' => $file_url,
            'message' => 'Thank you! Your download will start shortly.'
        ));
        
        wp_die();
    }
    
    /**
     * Placeholder for email marketing functionality
     * This function is a stub in the free version
     */
    private function send_to_email_service($name, $email, $file_title) {
        // This functionality is only available in the Pro version
        return false;
    }
}
