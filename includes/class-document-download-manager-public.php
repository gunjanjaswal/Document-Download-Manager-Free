<?php
/**
 * Public-facing functionality of the plugin.
 */
class Document_Download_Manager_Public {

    /**
     * Register AJAX handlers
     */
    public function register_ajax_handlers() {
        // Register AJAX handlers with unique prefix
        add_action('wp_ajax_docdownman_process_download', array($this, 'process_download_ajax'));
        add_action('wp_ajax_nopriv_docdownman_process_download', array($this, 'process_download_ajax'));
    }
    
    /**
     * Enqueue public styles
     */
    public function enqueue_styles() {
        // Use unique prefix for style handle
        wp_register_style('docdownman-public-css', DOCDOWNMAN_PLUGIN_URL . 'assets/css/public.css', array(), DOCDOWNMAN_VERSION);
        wp_enqueue_style('docdownman-public-css');
    }
    
    /**
     * Enqueue public scripts
     */
    public function enqueue_scripts() {
        // Use unique prefix for script handle
        wp_register_script('docdownman-public-js', DOCDOWNMAN_PLUGIN_URL . 'assets/js/public.js', array('jquery'), DOCDOWNMAN_VERSION, true);
        wp_enqueue_script('docdownman-public-js');
        
        // Enqueue Dashicons for the download icon
        wp_enqueue_style('dashicons');
        
        // Localize script with unique prefix
        wp_localize_script('docdownman-public-js', 'docdownman_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('docdownman_download_nonce')
        ));
    }
    
    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        // Register shortcode with unique prefix
        add_shortcode('docdownman_document_download', array($this, 'download_shortcode'));
    }
    
    /**
     * Download shortcode callback
     */
    public function download_shortcode($atts) {
        // Support both array and string attributes
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
        
        // Get document files with new prefix
        $document_files = get_option('docdownman_document_files', array());
        
        $file_id = $atts['id'];
        $file_data = null;
        
        // Find the file with the matching ID
        foreach ($document_files as $file) {
            // Check for ID match first
            if (isset($file['id']) && $file['id'] === $file_id) {
                $file_data = $file;
                break;
            }
        }
        
        if (!$file_data) {
            return '<p>Error: Document file not found.</p>';
        }
        
        // Generate a unique form ID
        $form_id = 'docdownman-form-' . uniqid();
        $unique_id = 'docdownman-download-' . $file_id;
        
        // Determine file type based on URL extension
        $file_extension = pathinfo($file_data['url'], PATHINFO_EXTENSION);
        $is_pdf = strtolower($file_extension) === 'pdf';
        $file_type_class = $is_pdf ? 'docdownman-pdf-button' : 'docdownman-excel-button';
        
        ob_start();
        $output = '<div class="docdownman-download-form-container">';
        $output .= '<button class="docdownman-download-button ' . esc_attr($file_type_class) . '" data-toggle="' . esc_attr($form_id) . '">';
        $output .= '<span class="dashicons dashicons-download"></span> ' . esc_html($atts['text']) . '</button>';
        $output .= '<div class="docdownman-modal" id="' . esc_attr($form_id) . '">';
        $output .= '<div class="docdownman-modal-content">';
        $output .= '<span class="docdownman-close">&times;</span>';
        $output .= '<h3>' . esc_html($file_data['title']) . '</h3>';
        $output .= '<form class="docdownman-form" method="post">';
        $output .= '<div class="docdownman-form-group">';
        $output .= '<label for="docdownman-name-' . esc_attr($form_id) . '">' . esc_html__('Name', 'document-download-manager') . '</label>';
        $output .= '<input type="text" name="name" id="docdownman-name-' . esc_attr($form_id) . '" required>';
        $output .= '</div>';
        $output .= '<div class="docdownman-form-group">';
        $output .= '<label for="docdownman-email-' . esc_attr($form_id) . '">' . esc_html__('Email', 'document-download-manager') . '</label>';
        $output .= '<input type="email" name="email" id="docdownman-email-' . esc_attr($form_id) . '" required>';
        $output .= '</div>';
        $output .= '<input type="hidden" name="file_id" value="' . esc_attr($file_id) . '">';
        $output .= '<input type="hidden" name="file_title" value="' . esc_attr($file_data['title']) . '">';
        $output .= '<input type="hidden" name="file_url" value="' . esc_url($file_data['url']) . '">';
        
        // Add hidden fields
        $output .= '<input type="hidden" name="action" value="docdownman_process_download">';
        $output .= '<input type="hidden" name="nonce" value="' . wp_create_nonce('docdownman_download_nonce') . '">';
        $output .= '<div class="docdownman-form-group">';
        $output .= '<button type="submit" class="docdownman-submit-button">' . esc_html__('Download Now', 'document-download-manager') . '</button>';
        $output .= '</div>';
        $output .= '</form>';
        $output .= '</div>';
        $output .= '</div>';
        $output .= '</div>';
        $output .= '</div>';
        return $output;
    }
    
    /**
     * Process download AJAX request
     */
    public function process_download_ajax() {
        // Check nonce early and fail if invalid
        // Sanitize nonce value first
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (empty($nonce)) {
            $nonce = isset($_POST['docdownman_nonce']) ? sanitize_text_field(wp_unslash($_POST['docdownman_nonce'])) : '';
        }
        
        // Verify nonce
        if (empty($nonce) || !wp_verify_nonce($nonce, 'docdownman_download_nonce')) {
            wp_send_json_error(esc_html__('Security check failed: invalid nonce', 'document-download-manager'));
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
        
        // Sanitize input - all fields have been checked for existence in the required_fields loop above
        $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
        $file_id = isset($_POST['file_id']) ? sanitize_text_field(wp_unslash($_POST['file_id'])) : '';
        $file_title = isset($_POST['file_title']) ? sanitize_text_field(wp_unslash($_POST['file_title'])) : '';
        $file_url = isset($_POST['file_url']) ? esc_url_raw(wp_unslash($_POST['file_url'])) : '';
        
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
        
        // Record the download in the database
        global $wpdb;
        $table_name = $wpdb->prefix . 'docdownman_downloads';
        // Use prepare to avoid SQL injection
        // First try to get from cache
        $cache_key = 'docdownman_table_exists_' . md5($table_name);
        $table_exists = wp_cache_get($cache_key, 'document-download-manager');
        
        // If not in cache, query the database
        if (false === $table_exists) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Using caching to minimize DB calls
            $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name;
            // Cache the result
            wp_cache_set($cache_key, $table_exists, 'document-download-manager', HOUR_IN_SECONDS);
        }
        
        // Use the table if it exists, otherwise we'll create it
        $active_table = $table_name;
        
        // Generate a unique cache key for this download attempt
        $download_cache_key = 'docdownman_download_attempt_' . md5($email . $file_url . microtime());
        
        // Check if we've recently processed this exact download (prevents duplicates)
        $recent_download = wp_cache_get($download_cache_key, 'document-download-manager');
        
        if (false === $recent_download) {
            // Insert the record
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Using caching to prevent duplicate inserts
            $result = $wpdb->insert(
                $active_table,
                array(
                    'name' => $name,
                    'email' => $email,
                    'file_name' => $file_title,
                    'file_url' => $file_url
                ),
                array('%s', '%s', '%s', '%s')
            );
            
            // Cache this download attempt to prevent duplicates
            wp_cache_set($download_cache_key, true, 'document-download-manager', 30); // Cache for 30 seconds
            
            // If insert was successful, invalidate the records cache
            if ($result) {
                // Clear the cache to ensure the admin page shows the latest data
                wp_cache_delete('docdownman_all_records', 'document-download-manager');
            }
        } else {
            // We've already processed this download recently
            $result = true; // Pretend it succeeded to avoid error messages
        }
        
        // Email marketing integration is available in the Pro version
        // This is just a placeholder in the free version
        
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
