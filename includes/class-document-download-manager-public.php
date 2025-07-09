<?php
/**
 * Public-facing functionality of the plugin.
 */
class Document_Download_Manager_Public {

    /**
     * Register AJAX handlers
     */
    public function register_ajax_handlers() {
        // Register AJAX handlers with new prefix
        add_action('wp_ajax_ddmanager_process_download', array($this, 'process_download_ajax'));
        add_action('wp_ajax_nopriv_ddmanager_process_download', array($this, 'process_download_ajax'));
        
        // For backward compatibility, also register with old prefix
        add_action('wp_ajax_docdownman_process_download', array($this, 'process_download_ajax'));
        add_action('wp_ajax_nopriv_docdownman_process_download', array($this, 'process_download_ajax'));
    }
    
    /**
     * Enqueue public styles
     */
    public function enqueue_styles() {
        // Use new prefix for style handle
        wp_enqueue_style('ddmanager-public-css', DDMANAGER_PLUGIN_URL . 'assets/css/public.css', array(), DDMANAGER_VERSION);
    }
    
    /**
     * Enqueue public scripts
     */
    public function enqueue_scripts() {
        // Use new prefix for script handle
        wp_enqueue_script('ddmanager-public-js', DDMANAGER_PLUGIN_URL . 'assets/js/public.js', array('jquery'), DDMANAGER_VERSION, true);
        
        // Enqueue Dashicons for the download icon
        wp_enqueue_style('dashicons');
        
        // Localize script with new prefix
        wp_localize_script('ddmanager-public-js', 'ddmanager_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ddmanager_download_nonce')
        ));
        
        // For backward compatibility, also localize with old prefix
        wp_localize_script('ddmanager-public-js', 'docdownman_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('docdownman_nonce')
        ));
    }
    
    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        // Register shortcodes with more unique prefix
        add_shortcode('ddmanager_document_download', array($this, 'download_shortcode'));
        
        // Add backward compatibility for the old shortcodes
        add_shortcode('docdownman_document_download', array($this, 'download_shortcode'));
        add_shortcode('document_download', array($this, 'download_shortcode'));
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
        
        // Get document files with new prefix
        $document_files = get_option('ddmanager_document_files', array());
        
        // If no files found with new prefix, try the old prefix for backward compatibility
        if (empty($document_files)) {
            $document_files = get_option('docdownman_document_files', array());
        }
        
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
        
        // Generate a unique form ID
        $form_id = 'ddmanager-form-' . uniqid();
        $unique_id = 'ddmanager-download-' . $file_id;
        
        // Determine file type based on URL extension
        $file_extension = pathinfo($file_data['url'], PATHINFO_EXTENSION);
        $is_pdf = strtolower($file_extension) === 'pdf';
        $file_type_class = $is_pdf ? 'ddmanager-pdf-button' : 'ddmanager-excel-button';
        
        ob_start();
        $output = '<div class="ddmanager-download-form-container">';
        $output .= '<button class="ddmanager-download-button ' . esc_attr($file_type_class) . '" data-toggle="' . esc_attr($form_id) . '">';
        $output .= '<span class="dashicons dashicons-download"></span> ' . esc_html($atts['text']) . '</button>';
        $output .= '<div class="ddmanager-modal" id="' . esc_attr($form_id) . '">';
        $output .= '<div class="ddmanager-modal-content">';
        $output .= '<span class="ddmanager-close">&times;</span>';
        $output .= '<h3>' . esc_html($file_data['title']) . '</h3>';
        $output .= '<form class="ddmanager-form" method="post">';
        $output .= '<div class="ddmanager-form-group">';
        $output .= '<label for="ddmanager-name-' . esc_attr($form_id) . '">' . esc_html__('Name', 'document-download-manager') . '</label>';
        $output .= '<input type="text" name="name" id="ddmanager-name-' . esc_attr($form_id) . '" required>';
        $output .= '</div>';
        $output .= '<div class="ddmanager-form-group">';
        $output .= '<label for="ddmanager-email-' . esc_attr($form_id) . '">' . esc_html__('Email', 'document-download-manager') . '</label>';
        $output .= '<input type="email" name="email" id="ddmanager-email-' . esc_attr($form_id) . '" required>';
        $output .= '</div>';
        $output .= '<input type="hidden" name="file_id" value="' . esc_attr($file_id) . '">';
        $output .= '<input type="hidden" name="file_title" value="' . esc_attr($file_data['title']) . '">';
        $output .= '<input type="hidden" name="file_url" value="' . esc_url($file_data['url']) . '">';
        $output .= '<input type="hidden" name="action" value="ddmanager_process_download">';
        $output .= '<input type="hidden" name="nonce" value="' . wp_create_nonce('ddmanager_download_nonce') . '">';
        $output .= '<div class="ddmanager-form-group">';
        $output .= '<button type="submit" class="ddmanager-submit-button">' . esc_html__('Download Now', 'document-download-manager') . '</button>';
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
        // Verify nonce with proper sanitization
        if (!isset($_POST['nonce'])) {
            wp_send_json_error(esc_html__('Security check failed', 'document-download-manager'));
            wp_die();
        }
        
        $nonce = sanitize_text_field(wp_unslash($_POST['nonce']));
        
        // Try new nonce name first, then fall back to old one for backward compatibility
        if (!wp_verify_nonce($nonce, 'ddmanager_download_nonce') && !wp_verify_nonce($nonce, 'docdownman_nonce')) {
            wp_send_json_error(esc_html__('Security check failed', 'document-download-manager'));
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
        
        // Record the download in the database
        global $wpdb;
        $table_name = $wpdb->prefix . 'ddmanager_downloads';
        $old_table_name = $wpdb->prefix . 'docdownman_downloads';
        
        // Check which table exists and use the appropriate one
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        $old_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$old_table_name'") === $old_table_name;
        
        // Use the new table if it exists, otherwise fall back to the old one
        $active_table = $table_exists ? $table_name : ($old_table_exists ? $old_table_name : $table_name);
        
        // Generate a cache key for this download record
        $cache_key = 'ddmanager_download_' . md5($email . $file_url . time());
        
        // Insert the record
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
        
        // If insert was successful, invalidate the records cache
        if ($result) {
            // Clear both new and old cache keys to ensure the admin page shows the latest data
            wp_cache_delete('ddmanager_all_records', 'document-download-manager');
            wp_cache_delete('docdownman_all_records', 'document-download-manager');
            
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
