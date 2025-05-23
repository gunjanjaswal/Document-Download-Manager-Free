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
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ddm_nonce')) {
            wp_send_json_error('Invalid security token.');
            wp_die();
        }
        
        // Check required fields
        $required_fields = array('name', 'email', 'file_id', 'file_title', 'file_url');
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                wp_send_json_error('Missing required field: ' . $field);
                wp_die();
            }
        }
        
        // Sanitize input
        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);
        $file_id = sanitize_text_field($_POST['file_id']);
        $file_title = sanitize_text_field($_POST['file_title']);
        $file_url = esc_url_raw($_POST['file_url']);
        
        // Validate email
        if (!is_email($email)) {
            wp_send_json_error('Invalid email address.');
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
            
            // Check if Mailchimp integration is enabled
            $mailchimp_enabled = get_option('ddm_mailchimp_enabled', '0');
            
            if ($mailchimp_enabled === '1') {
                // Check if user has premium access via Freemius
                if (function_exists('ddm_is_premium') && ddm_is_premium()) {
                    // Send data to Mailchimp
                    $this->send_to_mailchimp($name, $email, $file_title);
                }
            }
        }
        
        // Return success response with file URL
        wp_send_json_success(array(
            'file_url' => $file_url,
            'message' => 'Thank you! Your download will start shortly.'
        ));
        
        wp_die();
    }
    
    /**
     * Send user data to Mailchimp
     *
     * @param string $name User's name
     * @param string $email User's email
     * @param string $file_title Title of the downloaded file
     * @return bool Success or failure
     */
    private function send_to_mailchimp($name, $email, $file_title) {
        // Get Mailchimp settings
        $api_key = get_option('ddm_mailchimp_api_key', '');
        $list_id = get_option('ddm_mailchimp_list_id', '');
        
        // If API key or list ID is missing, return false
        if (empty($api_key) || empty($list_id)) {
            return false;
        }
        
        // Extract API server from API key (e.g., us1, us2, etc.)
        $api_parts = explode('-', $api_key);
        if (count($api_parts) != 2) {
            return false; // Invalid API key format
        }
        $server = $api_parts[1];
        
        // Split name into first and last name
        $name_parts = explode(' ', $name, 2);
        $first_name = $name_parts[0];
        $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
        
        // Prepare data for Mailchimp API
        // Create a sanitized version of the file title for tagging
        $document_tag = sanitize_title($file_title);
        
        // Prepare tags - include both a generic tag and the specific document name
        $tags = array(
            'Document Download',
            'Downloaded: ' . $file_title
        );
        
        $data = array(
            'email_address' => $email,
            'status' => 'subscribed',
            'merge_fields' => array(
                'FNAME' => $first_name,
                'LNAME' => $last_name
            ),
            'tags' => $tags
        );
        
        // Convert data to JSON
        $json_data = json_encode($data);
        
        // Set up API endpoint
        $api_endpoint = "https://{$server}.api.mailchimp.com/3.0/lists/{$list_id}/members/";
        
        // Set up request arguments
        $args = array(
            'method' => 'POST',
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode('user:' . $api_key)
            ),
            'body' => $json_data
        );
        
        // Make the API request
        $response = wp_remote_post($api_endpoint, $args);
        
        // Check if request was successful
        if (is_wp_error($response)) {
            // Log error for debugging
            error_log('Mailchimp API Error: ' . $response->get_error_message());
            return false;
        }
        
        // Get response code
        $response_code = wp_remote_retrieve_response_code($response);
        
        // If subscriber already exists (response code 400), update their info instead
        if ($response_code == 400) {
            // Create MD5 hash of lowercase email for the API endpoint
            $subscriber_hash = md5(strtolower($email));
            
            // Update endpoint for existing subscriber
            $api_endpoint = "https://{$server}.api.mailchimp.com/3.0/lists/{$list_id}/members/{$subscriber_hash}";
            
            // Update data to use PUT method
            $args['method'] = 'PUT';
            
            // Make the update request
            $response = wp_remote_request($api_endpoint, $args);
            
            if (is_wp_error($response)) {
                error_log('Mailchimp API Update Error: ' . $response->get_error_message());
                return false;
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
        }
        
        // If successful (response code 200 or 201), add a note about the downloaded document
        if ($response_code == 200 || $response_code == 201) {
            // Create MD5 hash of lowercase email for the API endpoint
            $subscriber_hash = md5(strtolower($email));
            
            // Set up notes endpoint
            $notes_endpoint = "https://{$server}.api.mailchimp.com/3.0/lists/{$list_id}/members/{$subscriber_hash}/notes";
            
            // Prepare note data
            $note_data = array(
                'note' => "Downloaded document: {$file_title} on " . current_time('Y-m-d H:i:s')
            );
            
            // Set up request arguments for note
            $note_args = array(
                'method' => 'POST',
                'timeout' => 30,
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Basic ' . base64_encode('user:' . $api_key)
                ),
                'body' => json_encode($note_data)
            );
            
            // Make the API request to add a note
            wp_remote_post($notes_endpoint, $note_args);
            
            return true;
        }
        
        return false;
    }
}
