<?php
/**
 * Admin-specific functionality of the plugin.
 */
class Document_Download_Manager_Admin {

    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        add_menu_page(
            'Document Download Manager With Mailchimp', 
            'Document Downloads', 
            'manage_options', 
            'document-download-manager', 
            array($this, 'display_admin_page'), 
            'dashicons-download', 
            30
        );
        
        add_submenu_page(
            'document-download-manager',
            'Download Records',
            'Download Records',
            'manage_options',
            'document-download-records',
            array($this, 'display_records_page')
        );
        
        // Add Mailchimp Settings page
        add_submenu_page(
            'document-download-manager',
            'Mailchimp Settings',
            'Mailchimp Settings',
            'manage_options',
            'document-download-mailchimp',
            array($this, 'display_mailchimp_settings')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('ddm_settings', 'ddm_document_files', array($this, 'sanitize_document_files'));
        
        // Register Mailchimp settings
        register_setting('ddm_mailchimp_settings', 'ddm_mailchimp_api_key', array($this, 'sanitize_text_field'));
        register_setting('ddm_mailchimp_settings', 'ddm_mailchimp_list_id', array($this, 'sanitize_text_field'));
        register_setting('ddm_mailchimp_settings', 'ddm_mailchimp_enabled', array($this, 'sanitize_checkbox'));
    }
    
    /**
     * Sanitize text field
     */
    public function sanitize_text_field($input) {
        return sanitize_text_field($input);
    }
    
    /**
     * Sanitize checkbox
     */
    public function sanitize_checkbox($input) {
        return isset($input) ? '1' : '0';
    }
    
    /**
     * Sanitize document files settings
     */
    public function sanitize_document_files($input) {
        $new_input = array();
        
        if (isset($input) && is_array($input)) {
            foreach ($input as $key => $file) {
                if (!empty($file['title']) && !empty($file['url'])) {
                    $new_input[$key]['title'] = sanitize_text_field($file['title']);
                    $new_input[$key]['url'] = esc_url_raw($file['url']);
                    $new_input[$key]['id'] = isset($file['id']) ? sanitize_text_field($file['id']) : sanitize_title($file['title']);
                }
            }
        }
        
        return $new_input;
    }
    
    /**
     * Enqueue admin styles
     */
    public function enqueue_styles() {
        wp_enqueue_style('ddm-admin-css', DDM_PLUGIN_URL . 'assets/css/admin.css', array(), DDM_VERSION);
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_script('ddm-admin-js', DDM_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), DDM_VERSION, true);
        
        // Add inline script for API key masking
        if (isset($_GET['page']) && $_GET['page'] === 'document-download-mailchimp') {
            $api_key_script = "
                jQuery(document).ready(function($) {
                    // Toggle API key visibility
                    $('#ddm_toggle_api_key').on('click', function() {
                        var input = $('#ddm_mailchimp_api_key_display');
                        var icon = $(this).find('.dashicons');
                        
                        if (input.attr('type') === 'password') {
                            input.attr('type', 'text');
                            icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
                        } else {
                            input.attr('type', 'password');
                            icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
                        }
                    });
                    
                    // Edit API key
                    $('#ddm_edit_api_key').on('click', function() {
                        $('.ddm-api-key-edit').show();
                        $('#ddm_mailchimp_api_key_edit').focus();
                    });
                    
                    // Cancel API key edit
                    $('#ddm_cancel_api_key').on('click', function() {
                        $('.ddm-api-key-edit').hide();
                        $('#ddm_mailchimp_api_key_edit').val('');
                    });
                    
                    // Save API key
                    $('#ddm_save_api_key').on('click', function() {
                        var newKey = $('#ddm_mailchimp_api_key_edit').val();
                        if (newKey) {
                            // Update hidden input with actual value
                            $('#ddm_mailchimp_api_key').val(newKey);
                            
                            // Update display field with masked value
                            var keyLength = newKey.length;
                            var displayValue = '';
                            
                            if (keyLength > 8) {
                                displayValue = newKey.substring(0, 4) + '*'.repeat(keyLength - 8) + newKey.substring(keyLength - 4);
                            } else {
                                displayValue = '*'.repeat(keyLength);
                            }
                            
                            $('#ddm_mailchimp_api_key_display').val(displayValue);
                            
                            // Hide edit section
                            $('.ddm-api-key-edit').hide();
                            $('#ddm_mailchimp_api_key_edit').val('');
                        }
                    });
                });
            ";
            
            wp_add_inline_script('ddm-admin-js', $api_key_script);
            
            // Add inline styles for API key masking
            $api_key_styles = "
                .ddm-api-key-wrapper {
                    display: flex;
                    align-items: center;
                    gap: 5px;
                }
                
                .ddm-api-key-edit {
                    display: flex;
                    align-items: center;
                    gap: 5px;
                }
            ";
            
            wp_add_inline_style('wp-admin', $api_key_styles);
        }
    }
    
    /**
     * Display admin page
     */
    public function display_admin_page() {
        // Get existing files
        $document_files = get_option('ddm_document_files', array());
        
        // Handle form submission manually if needed
        if (isset($_POST['submit']) && isset($_POST['ddm_document_files'])) {
            // Verify nonce for security
            check_admin_referer('ddm_settings-options');
            
            // Properly unslash and sanitize the input
            // First sanitize the raw input to ensure it's an array
            $raw_input = isset($_POST['ddm_document_files']) && is_array($_POST['ddm_document_files']) 
                ? $_POST['ddm_document_files'] 
                : array();
                
            // Then unslash the sanitized input
            $input = wp_unslash($raw_input);
            
            // Finally, run it through our comprehensive sanitization method
            $sanitized_input = $this->sanitize_document_files($input);
            
            // Update the option with sanitized data
            update_option('ddm_document_files', $sanitized_input);
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved successfully!', 'document-download-manager') . '</p></div>';
            
            // Refresh the data
            $document_files = get_option('ddm_document_files', array());
        }
        ?>
        <div class="wrap">
            <h1>Document Download Manager</h1>
            
            <div class="notice notice-info is-dismissible">
                <p><strong>Important:</strong> You can add both Excel (.xlsx, .xls, .csv) and PDF (.pdf) files. The file type will be automatically detected based on the file extension in the URL.</p>
                <p>Make sure your file URL ends with the correct extension (e.g., <code>.pdf</code> for PDF files or <code>.xlsx</code> for Excel files).</p>
            </div>
            
            <form method="post" action="">
                <?php wp_nonce_field('ddm_settings-options'); ?>
                <table class="form-table">
                    <tr>
                        <th colspan="5">
                            <h2>Document Files</h2>
                            <p>Add Excel or PDF files that users can download after providing their information.</p>
                        </th>
                    </tr>
                    <tr>
                        <th>Title</th>
                        <th>File URL</th>
                        <th>File Type</th>
                        <th>Shortcode</th>
                        <th>Actions</th>
                    </tr>
                    <?php if (!empty($document_files)) : ?>
                        <?php foreach ($document_files as $key => $file) : ?>
                            <?php 
                            // Determine file type based on URL extension
                            $file_extension = pathinfo($file['url'], PATHINFO_EXTENSION);
                            $file_type = strtolower($file_extension) === 'pdf' ? 'PDF' : 'Excel';
                            $file_icon = strtolower($file_extension) === 'pdf' ? 'dashicons-pdf' : 'dashicons-media-spreadsheet';
                            ?>
                            <tr>
                                <td>
                                    <input type="text" name="ddm_document_files[<?php echo esc_attr($key); ?>][title]" value="<?php echo esc_attr($file['title']); ?>" class="regular-text" required />
                                    <input type="hidden" name="ddm_document_files[<?php echo esc_attr($key); ?>][id]" value="<?php echo esc_attr(isset($file['id']) ? $file['id'] : sanitize_title($file['title'])); ?>" />
                                </td>
                                <td>
                                    <input type="url" name="ddm_document_files[<?php echo esc_attr($key); ?>][url]" value="<?php echo esc_url($file['url']); ?>" class="regular-text" required />
                                </td>
                                <td>
                                    <span class="dashicons <?php echo esc_attr($file_icon); ?>"></span> <?php echo esc_html($file_type); ?>
                                </td>
                                <td>
                                    <code>[document_download id="<?php echo esc_attr(isset($file['id']) ? $file['id'] : sanitize_title($file['title'])); ?>"]</code>
                                </td>
                                <td>
                                    <button type="button" class="button remove-file">Remove</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <!-- Template row will be added by JavaScript -->
                </table>
                <p>
                    <button type="button" class="button button-secondary" id="add-document-file">Add Document File</button>
                </p>
                <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
            </form>
            
            <div class="ddm-instructions">
                <h2>How to Use</h2>
                <ol>
                    <li>Add Excel or PDF files using the form above</li>
                    <li>The file type will be automatically detected based on the file extension (.xlsx, .xls, .pdf, etc.)</li>
                    <li>Copy the shortcode for each file</li>
                    <li>Paste the shortcode in any post or page where you want to display the download button</li>
                    <li>Alternatively, you can use the shortcode with a custom button text: <code>[document_download id="file-id" text="Free Download"]</code></li>
                </ol>
                <h3>Supported File Types</h3>
                <ul>
                    <li><strong>Excel Files:</strong> .xlsx, .xls, .xlsm, .xlsb, .csv</li>
                    <li><strong>PDF Files:</strong> .pdf</li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    /**
     * Display Mailchimp settings page
     */
    public function display_mailchimp_settings() {
        // Check if user has proper permissions
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Check if user has premium access
        $has_premium = function_exists('ddm_is_premium') ? ddm_is_premium() : false;
        
        // Process sync request for existing records
        if (isset($_POST['ddm_sync_mailchimp']) && isset($_POST['submit_sync']) && 
            check_admin_referer('ddm_sync_mailchimp_action', 'ddm_sync_mailchimp_nonce')) {
            
            // Check if Mailchimp is configured
            $api_key = get_option('ddm_mailchimp_api_key', '');
            $list_id = get_option('ddm_mailchimp_list_id', '');
            
            if (empty($api_key) || empty($list_id)) {
                echo '<div class="notice notice-error"><p>' . 
                    esc_html__('Mailchimp API key and List ID must be configured before syncing.', 'document-download-manager') . 
                    '</p></div>';
            } else {
                // Get all download records from the database
                global $wpdb;
                $table_name = $wpdb->prefix . 'ddm_downloads';
                $records = $wpdb->get_results(
                    "SELECT * FROM {$table_name} ORDER BY time DESC",
                    ARRAY_A
                );
                
                if (empty($records)) {
                    echo '<div class="notice notice-warning"><p>' . 
                        esc_html__('No download records found to sync.', 'document-download-manager') . 
                        '</p></div>';
                } else {
                    // Initialize counters
                    $success_count = 0;
                    $error_count = 0;
                    
                    // Create an instance of the public class to use its send_to_mailchimp method
                    require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-document-download-manager-public.php';
                    $public_instance = new Document_Download_Manager_Public();
                    
                    // Process each record
                    foreach ($records as $record) {
                        $result = $this->sync_record_to_mailchimp(
                            $record['name'],
                            $record['email'],
                            $record['file_name'],
                            $api_key,
                            $list_id
                        );
                        
                        if ($result) {
                            $success_count++;
                        } else {
                            $error_count++;
                        }
                        
                        // Add a small delay to avoid rate limiting
                        usleep(100000); // 100ms delay
                    }
                    
                    // Display results
                    echo '<div class="notice notice-success"><p>' . 
                        sprintf(
                            esc_html__('Sync completed. Successfully added/updated %1$d records. Failed: %2$d records.', 'document-download-manager'),
                            $success_count,
                            $error_count
                        ) . 
                        '</p></div>';
                }
            }
        }
        
        // Process form submission
        if (isset($_POST['submit']) && check_admin_referer('ddm_mailchimp_settings-options')) {
            // API Key
            if (isset($_POST['ddm_mailchimp_api_key'])) {
                $api_key = sanitize_text_field(wp_unslash($_POST['ddm_mailchimp_api_key']));
                update_option('ddm_mailchimp_api_key', $api_key);
            }
            
            // List ID
            if (isset($_POST['ddm_mailchimp_list_id'])) {
                $list_id = sanitize_text_field(wp_unslash($_POST['ddm_mailchimp_list_id']));
                update_option('ddm_mailchimp_list_id', $list_id);
            }
            
            // Enabled/Disabled
            $enabled = isset($_POST['ddm_mailchimp_enabled']) ? '1' : '0';
            update_option('ddm_mailchimp_enabled', $enabled);
            
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Mailchimp settings saved successfully!', 'document-download-manager') . '</p></div>';
        }
        
        // Get current settings
        $api_key = get_option('ddm_mailchimp_api_key', '');
        $list_id = get_option('ddm_mailchimp_list_id', '');
        $enabled = get_option('ddm_mailchimp_enabled', '0');
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Mailchimp Integration Settings', 'document-download-manager'); ?></h1>
            
            <?php if (!$has_premium) : ?>
                <div class="ddm-premium-notice">
                    <h2><?php echo esc_html__('Pro Feature: Mailchimp Integration', 'document-download-manager'); ?></h2>
                    <p><?php echo esc_html__('Enhance your document downloads with Mailchimp integration to grow your email list. The Pro version allows you to:', 'document-download-manager'); ?></p>
                    <ul style="list-style-type: disc; margin-left: 20px;">
                        <li><?php echo esc_html__('Automatically add document downloaders to your Mailchimp audience', 'document-download-manager'); ?></li>
                        <li><?php echo esc_html__('Tag subscribers based on which documents they download', 'document-download-manager'); ?></li>
                        <li><?php echo esc_html__('Sync existing download records to Mailchimp with one click', 'document-download-manager'); ?></li>
                    </ul>
                    <p>
                        <a href="<?php echo esc_url(ddm_get_upgrade_url()); ?>" class="button button-primary">
                            <?php echo esc_html__('Upgrade to Pro', 'document-download-manager'); ?>
                        </a>
                    </p>
                </div>
                
                <style>
                    .ddm-premium-notice {
                        background: #fff;
                        border-left: 4px solid #00a0d2;
                        box-shadow: 0 1px 1px rgba(0,0,0,.04);
                        margin: 20px 0;
                        padding: 15px;
                    }
                    .ddm-premium-notice h2 {
                        margin-top: 0;
                        color: #00a0d2;
                    }
                    .form-table input[type="text"],
                    .form-table input[type="checkbox"] {
                        opacity: 0.7;
                    }
                </style>
            <?php endif; ?>
            
            <form method="post" action="">
                <?php settings_fields('ddm_mailchimp_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="ddm_mailchimp_enabled"><?php echo esc_html__('Enable Mailchimp Integration', 'document-download-manager'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="ddm_mailchimp_enabled" name="ddm_mailchimp_enabled" value="1" <?php checked('1', $enabled); ?> <?php echo !$has_premium ? 'disabled' : ''; ?> />
                            <p class="description"><?php echo esc_html__('Enable to add users to your Mailchimp list when they download documents.', 'document-download-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="ddm_mailchimp_api_key"><?php echo esc_html__('Mailchimp API Key', 'document-download-manager'); ?></label>
                        </th>
                        <td>
                            <?php if ($has_premium): ?>
                                <?php 
                                // If API key exists, show masked version
                                $display_value = '';
                                if (!empty($api_key)) {
                                    // Show first 4 and last 4 characters, mask the rest
                                    $key_length = strlen($api_key);
                                    if ($key_length > 8) {
                                        $display_value = substr($api_key, 0, 4) . str_repeat('*', $key_length - 8) . substr($api_key, -4);
                                    } else {
                                        $display_value = str_repeat('*', $key_length);
                                    }
                                }
                                ?>
                                <div class="ddm-api-key-wrapper">
                                    <input type="password" id="ddm_mailchimp_api_key_display" value="<?php echo esc_attr($display_value); ?>" class="regular-text" readonly />
                                    <input type="hidden" id="ddm_mailchimp_api_key" name="ddm_mailchimp_api_key" value="<?php echo esc_attr($api_key); ?>" />
                                    <button type="button" id="ddm_toggle_api_key" class="button button-secondary">
                                        <span class="dashicons dashicons-visibility"></span>
                                    </button>
                                    <button type="button" id="ddm_edit_api_key" class="button button-secondary">
                                        <span class="dashicons dashicons-edit"></span> <?php echo esc_html__('Edit', 'document-download-manager'); ?>
                                    </button>
                                </div>
                                <div class="ddm-api-key-edit" style="display:none; margin-top: 10px;">
                                    <input type="text" id="ddm_mailchimp_api_key_edit" class="regular-text" placeholder="<?php echo esc_attr__('Enter new API key', 'document-download-manager'); ?>" />
                                    <button type="button" id="ddm_save_api_key" class="button button-secondary">
                                        <span class="dashicons dashicons-yes"></span> <?php echo esc_html__('Save', 'document-download-manager'); ?>
                                    </button>
                                    <button type="button" id="ddm_cancel_api_key" class="button button-secondary">
                                        <span class="dashicons dashicons-no"></span> <?php echo esc_html__('Cancel', 'document-download-manager'); ?>
                                    </button>
                                </div>
                            <?php else: ?>
                                <input type="text" id="ddm_mailchimp_api_key" name="ddm_mailchimp_api_key" value="" placeholder="<?php echo esc_attr__('Enter your Mailchimp API key (Premium feature)', 'document-download-manager'); ?>" class="regular-text" disabled />
                            <?php endif; ?>
                            <p class="description"><?php echo esc_html__('Enter your Mailchimp API key. You can find this in your Mailchimp account under Account > Extras > API Keys.', 'document-download-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="ddm_mailchimp_list_id"><?php echo esc_html__('Mailchimp List/Audience ID', 'document-download-manager'); ?></label>
                        </th>
                        <td>
                            <?php if ($has_premium): ?>
                                <input type="text" id="ddm_mailchimp_list_id" name="ddm_mailchimp_list_id" value="<?php echo esc_attr($list_id); ?>" class="regular-text" />
                            <?php else: ?>
                                <input type="text" id="ddm_mailchimp_list_id" name="ddm_mailchimp_list_id" value="" placeholder="<?php echo esc_attr__('Enter your Mailchimp List/Audience ID (Premium feature)', 'document-download-manager'); ?>" class="regular-text" disabled />
                            <?php endif; ?>
                            <p class="description"><?php echo esc_html__('Enter your Mailchimp List/Audience ID. You can find this in Mailchimp under Audience > Settings > Audience name and defaults.', 'document-download-manager'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo esc_attr__('Save Changes', 'document-download-manager'); ?>" <?php echo !$has_premium ? 'disabled' : ''; ?> />
                </p>
            </form>
            
            <div class="ddm-disclaimer" style="margin-top: 30px; padding: 15px; background-color: #f8f8f8; border-left: 4px solid #ddd;">
                <h3><?php echo esc_html__('Mailchimp Integration Disclaimer', 'document-download-manager'); ?></h3>
                <p><em><?php echo esc_html__('This plugin is not affiliated with, endorsed, or sponsored by Mailchimp®. Mailchimp® is a registered trademark of The Rocket Science Group LLC. This plugin uses the Mailchimp API but is not certified or officially tested by Mailchimp. All Mailchimp® logos and trademarks displayed on this plugin are property of The Rocket Science Group LLC.', 'document-download-manager'); ?></em></p>
            </div>

            <div class="ddm-instructions">
                <h2><?php echo esc_html__('How Mailchimp Integration Works', 'document-download-manager'); ?></h2>
                <ol>
                    <li><?php echo esc_html__('When a user downloads a document, their name and email will be added to your Mailchimp list.', 'document-download-manager'); ?></li>
                    <li><?php echo esc_html__('The document title will be stored as a note in the subscriber\'s profile.', 'document-download-manager'); ?></li>
                    <li><?php echo esc_html__('Make sure your form includes name and email fields for this to work.', 'document-download-manager'); ?></li>
                </ol>
                
                <h3><?php echo esc_html__('Sync Existing Download Records', 'document-download-manager'); ?></h3>
                <p><?php echo esc_html__('You can push all your existing download records to Mailchimp with the button below:', 'document-download-manager'); ?></p>
                
                <form method="post" action="">
                    <?php wp_nonce_field('ddm_sync_mailchimp_action', 'ddm_sync_mailchimp_nonce'); ?>
                    <input type="hidden" name="ddm_sync_mailchimp" value="1">
                    <p>
                        <input type="submit" name="submit_sync" class="button button-secondary" value="<?php echo esc_attr__('Sync Existing Records to Mailchimp', 'document-download-manager'); ?>" <?php echo !$has_premium ? 'disabled' : ''; ?>>
                    </p>
                </form>
                
                <h3><?php echo esc_html__('Troubleshooting', 'document-download-manager'); ?></h3>
                <ul>
                    <li><?php echo esc_html__('If users aren\'t being added to your list, verify your API key and List ID.', 'document-download-manager'); ?></li>
                    <li><?php echo esc_html__('Check that the Mailchimp integration is enabled using the checkbox above.', 'document-download-manager'); ?></li>
                    <li><?php echo esc_html__('Ensure your Mailchimp account is active and in good standing.', 'document-download-manager'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    /**
     * Display records page
     */
    /**
     * Sync a single download record to Mailchimp
     *
     * @param string $name User's name
     * @param string $email User's email
     * @param string $file_title Title of the downloaded file
     * @param string $api_key Mailchimp API key
     * @param string $list_id Mailchimp List ID
     * @return bool Success or failure
     */
    private function sync_record_to_mailchimp($name, $email, $file_title, $api_key, $list_id) {
        // Validate email
        if (empty($email) || !is_email($email)) {
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
        
        // Prepare tags - include both a generic tag and the specific document name
        $tags = array(
            'Document Download',
            'Downloaded: ' . $file_title,
            'Synced Record'
        );
        
        // Prepare data for Mailchimp API
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
        
        // Create MD5 hash of lowercase email for the API endpoint (for upsert operation)
        $subscriber_hash = md5(strtolower($email));
        
        // Set up API endpoint for PUT operation (update or insert)
        $api_endpoint = "https://{$server}.api.mailchimp.com/3.0/lists/{$list_id}/members/{$subscriber_hash}";
        
        // Set up request arguments
        $args = array(
            'method' => 'PUT', // Use PUT for upsert operation
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode('user:' . $api_key)
            ),
            'body' => $json_data
        );
        
        // Make the API request
        $response = wp_remote_request($api_endpoint, $args);
        
        // Check if request was successful
        if (is_wp_error($response)) {
            // Log error for debugging
            error_log('Mailchimp API Error: ' . $response->get_error_message());
            return false;
        }
        
        // Get response code
        $response_code = wp_remote_retrieve_response_code($response);
        
        // If successful (response code 200), add a note about the downloaded document
        if ($response_code == 200) {
            // Set up notes endpoint
            $notes_endpoint = "https://{$server}.api.mailchimp.com/3.0/lists/{$list_id}/members/{$subscriber_hash}/notes";
            
            // Prepare note data
            $note_data = array(
                'note' => "Synced record: Downloaded {$file_title} (original download date unknown)"
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
    
    public function display_records_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ddm_downloads';
        
        // Handle record deletion
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            // Verify nonce for security
            $nonce = isset($_GET['_wpnonce']) ? sanitize_key($_GET['_wpnonce']) : '';
            if (!wp_verify_nonce($nonce, 'delete_record_' . intval($_GET['id']))) {
                // Nonce verification failed
                echo '<div class="notice notice-error"><p>' . esc_html__('Security check failed. Please try again.', 'document-download-manager') . '</p></div>';
            } else {
                // Nonce verified, proceed with deletion
                $id = intval($_GET['id']);
                
                // Delete the record with proper data types
                $wpdb->delete($table_name, array('id' => $id), array('%d'));
                
                // Clear any cached records
                wp_cache_delete('ddm_all_records', 'document-download-manager');
                
                echo '<div class="notice notice-success"><p>' . esc_html__('Record deleted successfully.', 'document-download-manager') . '</p></div>';
            }
        }
        
        // Try to get records from cache first
        $records = wp_cache_get('ddm_all_records', 'document-download-manager');
        
        // If not in cache, fetch from database and cache the results
        if (false === $records) {
            // For database queries, always use $wpdb->prepare when possible
            // Even for simple queries, this is a best practice
            $table_name = $wpdb->prefix . 'ddm_downloads';
            $records = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$table_name} ORDER BY time DESC LIMIT %d",
                    100 // Reasonable limit to prevent performance issues
                ),
                ARRAY_A
            );
            
            // Cache the results for 1 hour
            wp_cache_set('ddm_all_records', $records, 'document-download-manager', HOUR_IN_SECONDS);
        }
        ?>
        <div class="wrap">
            <h1>Download Records</h1>
            
            <?php if (empty($records)) : ?>
                <p>No download records found.</p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date & Time</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>File</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $record) : ?>
                            <tr>
                                <td><?php echo esc_html($record['id']); ?></td>
                                <td><?php echo esc_html($record['time']); ?></td>
                                <td><?php echo esc_html($record['name']); ?></td>
                                <td><?php echo esc_html($record['email']); ?></td>
                                <td><?php echo esc_html($record['file_name']); ?></td>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=document-download-records&action=delete&id=' . $record['id'])); ?>" class="delete" onclick="return confirm('Are you sure you want to delete this record?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="ddm-export-container" style="margin-top: 20px; background: #f9f9f9; padding: 15px; border-left: 4px solid #D4AF37;">
                    <h3>Export Download Records</h3>
                    <p>Export all download records to a CSV file for your records or marketing purposes.</p>
                    <a href="#" id="export-csv" class="button button-primary" style="background: #D4AF37; border-color: #D4AF37;">Export User Data to CSV</a>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
