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
            // Use new prefix for style handle
            wp_enqueue_style('ddmanager-admin-styles', plugin_dir_url(dirname(__FILE__)) . 'assets/css/admin.css', array(), DDMANAGER_VERSION);
            
            // Add premium notice styles with new prefix
            $premium_notice_css = "
                /* New prefix styles */
                .ddmanager-premium-notice {
                    background: #fff;
                    border-left: 4px solid #00a0d2;
                    box-shadow: 0 1px 1px rgba(0,0,0,.04);
                    margin: 20px 0;
                    padding: 15px;
                }
                .ddmanager-premium-notice h2 {
                    margin-top: 0;
                    color: #00a0d2;
                }
                .ddmanager-feature-list ul {
                    list-style-type: disc;
                    padding-left: 20px;
                }
                
                /* For backward compatibility */
                .docdownman-premium-notice {
                    background: #fff;
                    border-left: 4px solid #00a0d2;
                    box-shadow: 0 1px 1px rgba(0,0,0,.04);
                    margin: 20px 0;
                    padding: 15px;
                }
                .docdownman-premium-notice h2 {
                    margin-top: 0;
                    color: #00a0d2;
                }
                .docdownman-feature-list ul {
                    list-style-type: disc;
                    padding-left: 20px;
                }
            ";
            wp_add_inline_style('ddmanager-admin-styles', $premium_notice_css);
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
        // Register Document Files settings with more unique prefix
        register_setting(
            'ddmanager_settings', 
            'ddmanager_document_files', 
            array($this, 'sanitize_document_files')
        );
        
        // For backward compatibility, register with old prefix too
        register_setting(
            'docdownman_settings', 
            'docdownman_document_files', 
            array($this, 'sanitize_document_files')
        );
        
        // Register Email Marketing settings with more unique prefix
        register_setting('ddmanager_email_marketing_settings', 'ddmanager_email_api_key', array($this, 'sanitize_text_field'));
        register_setting('ddmanager_email_marketing_settings', 'ddmanager_email_list_id', array($this, 'sanitize_text_field'));
        register_setting('ddmanager_email_marketing_settings', 'ddmanager_email_enabled', array($this, 'sanitize_checkbox'));
        
        // For backward compatibility, register with old prefix too
        register_setting('docdownman_email_marketing_settings', 'docdownman_email_api_key', array($this, 'sanitize_text_field'));
        register_setting('docdownman_email_marketing_settings', 'docdownman_email_list_id', array($this, 'sanitize_text_field'));
        register_setting('docdownman_email_marketing_settings', 'docdownman_email_enabled', array($this, 'sanitize_checkbox'));
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
     * Enqueue admin styles
     */
    public function enqueue_styles() {
        wp_enqueue_style('ddmanager-admin-css', DDMANAGER_PLUGIN_URL . 'assets/css/admin.css', array(), DDMANAGER_VERSION);
        
        // For backward compatibility
        wp_register_style('docdownman-admin-css', DDMANAGER_PLUGIN_URL . 'assets/css/admin.css', array(), DDMANAGER_VERSION);
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_script('ddmanager-admin-js', DDMANAGER_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), DDMANAGER_VERSION, true);
        
        // For backward compatibility
        wp_register_script('docdownman-admin-js', DDMANAGER_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), DDMANAGER_VERSION, true);
        
        // Add inline script for API key masking
        if (isset($_GET['page']) && $_GET['page'] === 'document-download-email-marketing') {
            $api_key_script = "
                jQuery(document).ready(function($) {
                    // Toggle API key visibility with new prefix
                    $('#ddmanager_toggle_api_key, #docdownman_toggle_api_key').on('click', function() {
                        var input = $(this).hasClass('ddmanager_toggle_api_key') ? 
                            $('#ddmanager_email_api_key_display') : 
                            $('#docdownman_email_api_key_display');
                        var icon = $(this).find('.dashicons');
                        
                        if (input.attr('type') === 'password') {
                            input.attr('type', 'text');
                            icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
                        } else {
                            input.attr('type', 'password');
                            icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
                        }
                    });
                    
                    // Edit API key with new prefix
                    $('#ddmanager_edit_api_key, #docdownman_edit_api_key').on('click', function() {
                        $('.ddmanager-api-key-wrapper, .docdownman-api-key-wrapper').hide();
                        $('.ddmanager-api-key-edit, .docdownman-api-key-edit').show();
                        $('#ddmanager_email_api_key_edit, #docdownman_email_api_key_edit').focus();
                    });
                    
                    // Cancel API key edit with new prefix
                    $('#ddmanager_cancel_api_key, #docdownman_cancel_api_key').on('click', function() {
                        $('.ddmanager-api-key-edit, .docdownman-api-key-edit').hide();
                        $('.ddmanager-api-key-wrapper, .docdownman-api-key-wrapper').show();
                    });
                    
                    // Save API key with new prefix
                    $('#ddmanager_save_api_key, #docdownman_save_api_key').on('click', function() {
                        var newKey = $(this).attr('id') === 'ddmanager_save_api_key' ? 
                            $('#ddmanager_email_api_key_edit').val() : 
                            $('#docdownman_email_api_key_edit').val();
                        
                        if (newKey) {
                            // Save to both prefixed fields for compatibility
                            $('#ddmanager_email_api_key, #docdownman_email_api_key').val(newKey);
                            
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
                            
                            // Update both prefixed display fields
                            $('#ddmanager_email_api_key_display, #docdownman_email_api_key_display').val(displayValue);
                        }
                        
                        // Hide edit form, show display wrapper
                        $('.ddmanager-api-key-edit, .docdownman-api-key-edit').hide();
                        $('.ddmanager-api-key-wrapper, .docdownman-api-key-wrapper').show();
                        $('#ddmanager_email_api_key_edit, #docdownman_email_api_key_edit').val('');
                    });
                });
            ";
            
            // Add inline script to both old and new prefixed scripts for backward compatibility
            wp_add_inline_script('ddmanager-admin-js', $api_key_script);
            wp_add_inline_script('docdownman-admin-js', $api_key_script);
            
            // Add inline styles for API key masking with new prefix
            $api_key_styles = "
                /* New prefix styles */
                .ddmanager-api-key-wrapper {
                    display: flex;
                    align-items: center;
                    gap: 5px;
                }
                
                .ddmanager-api-key-edit {
                    display: flex;
                    align-items: center;
                    gap: 5px;
                }
                
                /* Backward compatibility styles */
                .docdownman-api-key-wrapper {
                    display: flex;
                    align-items: center;
                    gap: 5px;
                }
                
                .docdownman-api-key-edit {
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
        // Get existing files with new prefix
        $document_files = get_option('ddmanager_document_files', array());
        
        // If no files found with new prefix, try the old prefix for backward compatibility
        if (empty($document_files)) {
            $document_files = get_option('docdownman_document_files', array());
        }
        
        // Handle form submission manually if needed
        if (isset($_POST['submit'])) {
            // Verify nonce for security
            check_admin_referer('ddmanager_settings-options');
            
            // Check which form field is present (new or old prefix)
            $post_field_name = isset($_POST['ddmanager_document_files']) ? 'ddmanager_document_files' : 'docdownman_document_files';
            
            // Properly unslash and sanitize the input
            // First ensure it's an array and unslash it
            $raw_input = isset($_POST[$post_field_name]) && is_array($_POST[$post_field_name]) 
                ? wp_unslash($_POST[$post_field_name]) 
                : array();
                
            // Map through each item to sanitize it properly
            $input = array_map(function($item) {
                return is_array($item) ? array_map('sanitize_text_field', $item) : sanitize_text_field($item);
            }, $raw_input);
            
            // Finally, run it through our comprehensive sanitization method
            $sanitized_input = $this->sanitize_document_files($input);
            
            // Save the sanitized input to the database with new prefix
            update_option('ddmanager_document_files', $sanitized_input);
            
            // For backward compatibility, also update the old option
            update_option('docdownman_document_files', $sanitized_input);
            
            // Refresh the data with the newly saved values
            $document_files = get_option('ddmanager_document_files', array());
            
            // Add success message
            add_settings_error('ddmanager_settings', 'settings_updated', 'Document files saved successfully.', 'updated');
        }
        ?>
        <div class="wrap">
            <h1>Document Download Manager</h1>
            
            <?php 
            // Show both old and new settings errors for backward compatibility
            settings_errors('ddmanager_settings'); 
            settings_errors('docdownman_settings'); 
            ?>
            
            <div class="notice notice-info is-dismissible">
                <p><strong>Important:</strong> You can add both Excel (.xlsx, .xls, .csv) and PDF (.pdf) files. The file type will be automatically detected based on the file extension in the URL.</p>
                <p>Make sure your file URL ends with the correct extension (e.g., <code>.pdf</code> for PDF files or <code>.xlsx</code> for Excel files).</p>
            </div>
            
            <form method="post" action="">
                <?php wp_nonce_field('ddmanager_settings-options'); ?>
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
                                    <input type="text" name="ddmanager_document_files[<?php echo esc_attr($key); ?>][title]" value="<?php echo esc_attr($file['title']); ?>" class="regular-text" required />
                                    <input type="hidden" name="ddmanager_document_files[<?php echo esc_attr($key); ?>][id]" value="<?php echo esc_attr(isset($file['id']) ? $file['id'] : sanitize_title($file['title'])); ?>" />
                                </td>
                                <td>
                                    <input type="url" name="ddmanager_document_files[<?php echo esc_attr($key); ?>][url]" value="<?php echo esc_url($file['url']); ?>" class="regular-text" required />
                                </td>
                                <td>
                                    <span class="dashicons <?php echo esc_attr($file_icon); ?>"></span> <?php echo esc_html($file_type); ?>
                                </td>
                                <td>
                                    <code>[ddmanager_document_download id="<?php echo esc_attr(isset($file['id']) ? $file['id'] : sanitize_title($file['title'])); ?>"]</code>
                                    <br><small><em>Legacy shortcodes also supported</em></small>
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
                    <button type="button" class="button button-secondary ddmanager-add-document-file">Add Document File</button>
                </p>
                <input type="submit" name="submit" class="button button-primary" value="Save Changes">
            </form>
            
            <div class="ddmanager-instructions">
                <h2>How to Use</h2>
                <ol>
                    <li>Add Excel or PDF files using the form above</li>
                    <li>The file type will be automatically detected based on the file extension (.xlsx, .xls, .pdf, etc.)</li>
                    <li>Copy the shortcode for each file</li>
                    <li>Paste the shortcode in any post or page where you want to display the download button</li>
                    <li>Alternatively, you can use the shortcode with a custom button text: <code>[ddmanager_document_download id="file-id" text="Free Download"]</code></li>
                </ol>
                <h3>Supported File Types</h3>
                <ul>
                    <li><strong>Excel Files:</strong> .xlsx, .xls, .xlsm, .xlsb, .csv</li>
                    <li><strong>PDF Files:</strong> .pdf</li>
                </ul>
                <p><small>Note: Legacy shortcodes <code>[docdownman_document_download]</code> and <code>[document_download]</code> are also supported for backward compatibility.</small></p>
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
            
            <div class="ddmanager-premium-notice">
                <h2><?php echo esc_html__('Email Marketing Integration', 'document-download-manager'); ?></h2>
                <p><?php echo esc_html__('Email marketing integration is available in the Pro version of this plugin.', 'document-download-manager'); ?></p>
                <p><?php echo esc_html__('The Pro version allows you to connect with email marketing services to grow your email list.', 'document-download-manager'); ?></p>
                <p>
                    <a href="https://checkout.freemius.com/plugin/19168/plan/31773/" class="button button-primary">
                        <?php echo esc_html__('Get Pro Version', 'document-download-manager'); ?>
                    </a>
                </p>
            </div>
            
            <!-- Premium notice styling is added via enqueued CSS -->
            
            <div class="ddmanager-feature-list">
                <h3><?php echo esc_html__('Pro Version Features', 'document-download-manager'); ?></h3>
                <ul>
                    <li><?php echo esc_html__('Connect with popular email marketing services', 'document-download-manager'); ?></li>
                    <li><?php echo esc_html__('Automatically add document downloaders to your email list', 'document-download-manager'); ?></li>
                    <li><?php echo esc_html__('Track conversion rates and downloads', 'document-download-manager'); ?></li>
                    <li><?php echo esc_html__('Change button color', 'document-download-manager'); ?></li>
                    <li><?php echo esc_html__('Delete records', 'document-download-manager'); ?></li>
                    <li><?php echo esc_html__('Export CSV', 'document-download-manager'); ?></li>
                    <li><?php echo esc_html__('Priority support', 'document-download-manager'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    /**
     * Display records page
     */
    public function display_records_page() {
        // Check if user has proper permissions
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Get download records from database
        global $wpdb;
        $table_name = $wpdb->prefix . 'ddmanager_downloads';
        $old_table_name = $wpdb->prefix . 'docdownman_downloads';
        
        // Check if either new or old table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        $old_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$old_table_name'") === $old_table_name;
        
        // Use old table if new one doesn't exist yet (for backward compatibility)
        if (!$table_exists && $old_table_exists) {
            $table_name = $old_table_name;
            $table_exists = true;
        }
        
        if (!$table_exists) {
            // Table doesn't exist, show error
            ?>
            <div class="wrap">
                <h1><?php echo esc_html__('Download Records', 'document-download-manager'); ?></h1>
                <div class="notice notice-error">
                    <p><?php echo esc_html__('The downloads database table does not exist. Please deactivate and reactivate the plugin to create it.', 'document-download-manager'); ?></p>
                </div>
            </div>
            <?php
            return;
        }
        
        // Get records from cache or database
        $records = wp_cache_get('ddmanager_all_records', 'document-download-manager');
        
        // For backward compatibility, check old cache key if new one is empty
        if (false === $records) {
            $records = wp_cache_get('docdownman_all_records', 'document-download-manager');
        }
        
        if (false === $records) {
            // Cache miss, get from database
            $records = $wpdb->get_results(
                "SELECT * FROM $table_name ORDER BY time DESC LIMIT 1000",
                ARRAY_A
            );
            
            // Cache the results for 5 minutes using new prefix
            wp_cache_set('ddmanager_all_records', $records, 'document-download-manager', 5 * MINUTE_IN_SECONDS);
            
            // Also update old cache key for backward compatibility
            wp_cache_set('docdownman_all_records', $records, 'document-download-manager', 5 * MINUTE_IN_SECONDS);
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Download Records', 'document-download-manager'); ?></h1>
            
            <?php if (empty($records)) : ?>
                <div class="notice notice-info">
                    <p><?php echo esc_html__('No download records found yet.', 'document-download-manager'); ?></p>
                </div>
            <?php else : ?>
                <div class="notice notice-info is-dismissible">
                    <p><?php echo esc_html__('These are the records of users who have downloaded your documents.', 'document-download-manager'); ?></p>
                </div>
                
                <div class="ddmanager-premium-notice">
                    <h3><?php echo esc_html__('Need More Features?', 'document-download-manager'); ?></h3>
                    <p><?php echo esc_html__('Upgrade to Pro for email marketing integration with Mailchimp, advanced filtering, CSV export, and more!', 'document-download-manager'); ?></p>
                    <p>
                        <a href="https://checkout.freemius.com/plugin/19168/plan/31773/" class="button button-primary">
                            <?php echo esc_html__('Get Pro Version', 'document-download-manager'); ?>
                        </a>
                    </p>
                </div>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Date & Time', 'document-download-manager'); ?></th>
                            <th><?php echo esc_html__('Name', 'document-download-manager'); ?></th>
                            <th><?php echo esc_html__('Email', 'document-download-manager'); ?></th>
                            <th><?php echo esc_html__('Document', 'document-download-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $record) : ?>
                            <tr>
                                <td><?php echo esc_html(get_date_from_gmt($record['time'], get_option('date_format') . ' ' . get_option('time_format'))); ?></td>
                                <td><?php echo esc_html($record['name']); ?></td>
                                <td><?php echo esc_html($record['email']); ?></td>
                                <td><?php echo esc_html($record['file_name']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Sanitize document files input
     *
     * @param array $input The input array to sanitize
     * @return array The sanitized input array
     */
    public function sanitize_document_files($input) {
        $sanitized_input = array();
        
        if (is_array($input)) {
            foreach ($input as $key => $file) {
                if (isset($file['title']) && isset($file['url'])) {
                    $sanitized_input[$key] = array(
                        'title' => sanitize_text_field($file['title']),
                        'url' => esc_url_raw($file['url']),
                        'id' => isset($file['id']) ? sanitize_key($file['id']) : sanitize_title($file['title'])
                    );
                }
            }
        }
        
        return $sanitized_input;
    }
}
