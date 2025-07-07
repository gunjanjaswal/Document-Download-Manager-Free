<?php
/**
 * Admin-specific functionality of the plugin.
 */
class Document_Download_Manager_Admin {
    
    /**
     * Initialize the class
     */
    public function __construct() {
        // Enqueue admin styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
    }
    
    /**
     * Enqueue admin styles
     */
    public function enqueue_admin_styles($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'document-download') !== false) {
            wp_enqueue_style('ddm-admin-styles', plugin_dir_url(dirname(__FILE__)) . 'assets/css/admin-styles.css', array(), DDM_VERSION);
        }
    }
    
    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        add_menu_page(
            'Document Download Manager', 
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
        
        // Add Email Marketing Settings page
        add_submenu_page(
            'document-download-manager',
            'Email Marketing',
            'Email Marketing',
            'manage_options',
            'document-download-email-marketing',
            array($this, 'display_email_marketing_settings')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('ddm_settings', 'ddm_document_files', array($this, 'sanitize_document_files'));
        
        // Register Email Marketing settings
        register_setting('ddm_email_marketing_settings', 'ddm_email_api_key', array($this, 'sanitize_text_field'));
        register_setting('ddm_email_marketing_settings', 'ddm_email_list_id', array($this, 'sanitize_text_field'));
        register_setting('ddm_email_marketing_settings', 'ddm_email_enabled', array($this, 'sanitize_checkbox'));
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
        $sanitized_input = array();
        
        // Sanitize each field in the array
        foreach ($input as $index => $file) {
            if (isset($file['title']) && isset($file['url']) && !empty($file['url'])) {
                $sanitized_file = array();
                
                // Sanitize each field appropriately
                $sanitized_file['title'] = sanitize_text_field($file['title']);
                $sanitized_file['url'] = esc_url_raw($file['url']);
                $sanitized_file['id'] = isset($file['id']) ? sanitize_text_field($file['id']) : sanitize_title($file['title']);
                
                $sanitized_input[] = $sanitized_file;
            }
        }
        
        return $sanitized_input;
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
        if (isset($_GET['page']) && $_GET['page'] === 'document-download-email-marketing') {
            $api_key_script = "
                jQuery(document).ready(function($) {
                    // Toggle API key visibility
                    $('#ddm_toggle_api_key').on('click', function() {
                        var input = $('#ddm_email_api_key_display');
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
                        $('.ddm-api-key-wrapper').hide();
                        $('.ddm-api-key-edit').show();
                        $('#ddm_email_api_key_edit').focus();
                    });
                    
                    // Cancel API key edit
                    $('#ddm_cancel_api_key').on('click', function() {
                        $('.ddm-api-key-wrapper').show();
                        $('.ddm-api-key-edit').hide();
                        $('#ddm_email_api_key_edit').val('');
                    });
                    
                    // Save API key
                    $('#ddm_save_api_key').on('click', function() {
                        var newKey = $('#ddm_email_api_key_edit').val();
                        
                        if (newKey) {
                            $('#ddm_email_api_key').val(newKey);
                            
                            // Create masked display version
                            var displayValue = '';
                            var keyLength = newKey.length;
                            
                            if (keyLength > 8) {
                                displayValue = newKey.substring(0, 4) + 
                                    Array(keyLength - 7).join('*') + 
                                    newKey.substring(keyLength - 4);
                            } else {
                                displayValue = Array(keyLength + 1).join('*');
                            }
                            
                            $('#ddm_email_api_key_display').val(displayValue);
                        }
                        
                        $('.ddm-api-key-wrapper').show();
                        $('.ddm-api-key-edit').hide();
                        $('#ddm_email_api_key_edit').val('');
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
     * Display Email Marketing settings page
     */
    public function display_email_marketing_settings() {
        // Check if user has proper permissions
        if (!current_user_can('manage_options')) {
            return;
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Email Marketing Integration', 'document-download-manager'); ?></h1>
            
            <div class="ddm-premium-notice">
                <h2><?php echo esc_html__('Email Marketing Integration', 'document-download-manager'); ?></h2>
                <p><?php echo esc_html__('Email marketing integration is available in the Pro version of this plugin.', 'document-download-manager'); ?></p>
                <p><?php echo esc_html__('The Pro version allows you to connect with email marketing services to grow your email list.', 'document-download-manager'); ?></p>
                <p>
                    <a href="https://checkout.freemius.com/plugin/19168/plan/31773/" class="button button-primary">
                        <?php echo esc_html__('Get Pro Version', 'document-download-manager'); ?>
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
            </style>
            
            <div class="ddm-feature-list">
                <h3><?php echo esc_html__('Pro Version Features', 'document-download-manager'); ?></h3>
                <ul style="list-style-type: disc; padding-left: 20px;">
                    <li><?php echo esc_html__('Connect with popular email marketing services', 'document-download-manager'); ?></li>
                    <li><?php echo esc_html__('Automatically add document downloaders to your email list', 'document-download-manager'); ?></li>
                    <li><?php echo esc_html__('Segment subscribers based on downloaded documents', 'document-download-manager'); ?></li>
                    <li><?php echo esc_html__('One-click sync of all existing download records', 'document-download-manager'); ?></li>
                    <li><?php echo esc_html__('Priority support for all your questions', 'document-download-manager'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    /**
     * Placeholder for email marketing functionality
     * This is a stub function for the free version
     *
     * @param string $name User's name
     * @param string $email User's email
     * @param string $file_title Title of the downloaded file
     * @param string $api_key Email marketing API key
     * @param string $list_id Email marketing List ID
     * @return bool Always returns false in free version
     */
    private function sync_record_to_email_service($name, $email, $file_title, $api_key, $list_id) {
        // This functionality is only available in the Pro version
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
