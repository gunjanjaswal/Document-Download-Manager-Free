<?php
/**
 * Admin-specific functionality of the plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Document_Download_Manager_Admin {
    
    /**
     * Check if a database table exists
     * 
     * @param string $table_name The table name to check
     * @return bool True if table exists, false otherwise
     */
    private function table_exists($table_name) {
        global $wpdb;
        
        // Generate a cache key based on the table name
        $cache_key = 'docdownman_table_exists_' . md5($table_name);
        
        // Try to get from cache first
        $table_exists = wp_cache_get($cache_key, 'document-download-manager');
        
        // If not in cache, check the database
        if (false === $table_exists) {
            // We can't use $wpdb->prepare for table names in the FROM clause
            // But we can use a different approach that's still safe
            $table_name_esc = esc_sql($table_name);
            
            // Build and execute a query that WordPress coding standards approve of
            // Using a format string with no variables for the query structure
            // Execute the query directly without storing it in a variable first
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Using caching to minimize DB calls
            $result = $wpdb->get_var(
                $wpdb->prepare(
                    'SHOW TABLES LIKE %s',
                    $table_name_esc
                )
            );
            $table_exists = ($result === $table_name);
            
            // Cache the result
            wp_cache_set($cache_key, $table_exists, 'document-download-manager', HOUR_IN_SECONDS);
        }
        
        return $table_exists;
    }
    
    /**
     * Get download records from database
     * 
     * @param string $table_name The table name to query
     * @param int $limit Maximum number of records to retrieve
     * @return array Array of download records
     */
    private function get_download_records($table_name, $limit = 1000) {
        global $wpdb;
        
        // Generate a cache key that includes the table name and limit parameter
        $cache_key = 'docdownman_records_' . md5($table_name) . '_' . absint($limit);
        
        // Try to get from cache first
        $records = wp_cache_get($cache_key, 'document-download-manager');
        
        // If not in cache, query the database
        if (false === $records) {
            // Ensure limit is a positive integer
            $limit = absint($limit);
            
            // For WordPress coding standards compliance, we need to use a different approach
            // that avoids interpolating variables in SQL strings
            
            // First, safely escape the table name
            // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
            // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
            
            // We have to disable the PreparedSQL.NotPrepared rule because table names cannot be
            // prepared with placeholders in $wpdb->prepare(). This is a known limitation.
            // We're using esc_sql() which is the WordPress-approved way to handle table names.
            $table_name_esc = esc_sql($table_name);
            
            // Build and execute the query
            $sql = "SELECT * FROM {$table_name_esc} ORDER BY time DESC LIMIT %d";
            $prepared_sql = $wpdb->prepare($sql, $limit);
            $records = $wpdb->get_results($prepared_sql, ARRAY_A);
            // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
            // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery
            
            // Cache the results for 5 minutes
            wp_cache_set($cache_key, $records, 'document-download-manager', 5 * MINUTE_IN_SECONDS);
        }
        
        return $records;
    }
    
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
            wp_enqueue_style('docdownman-admin-styles', plugin_dir_url(dirname(__FILE__)) . 'assets/css/admin.css', array(), DOCDOWNMAN_VERSION);
            
            // For backward compatibility
            wp_enqueue_style('docdownman-admin-styles', plugin_dir_url(dirname(__FILE__)) . 'assets/css/admin.css', array(), DOCDOWNMAN_VERSION);
            
            // Add premium notice styles with new prefix
            $premium_notice_css = "
                /* New prefix styles */
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
            wp_add_inline_style('docdownman-admin-styles', $premium_notice_css);
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
            'docdownman_settings', 
            'docdownman_document_files', 
            array($this, 'sanitize_document_files')
        );
        
        // For backward compatibility, register with old prefix too
        register_setting(
            'docdownman_settings', 
            'docdownman_document_files', 
            array($this, 'sanitize_document_files')
        );
        
        // Register Email Marketing settings with more unique prefix
        register_setting('docdownman_email_marketing_settings', 'docdownman_email_api_key', array($this, 'sanitize_text_field'));
        register_setting('docdownman_email_marketing_settings', 'docdownman_email_list_id', array($this, 'sanitize_text_field'));
        register_setting('docdownman_email_marketing_settings', 'docdownman_email_enabled', array($this, 'sanitize_checkbox'));
        
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
     * Sanitize document files
     * 
     * @param array $input The input array to sanitize
     * @return array The sanitized array
     */
    public function sanitize_document_files($input) {
        $sanitized_input = array();
        
        if (!is_array($input)) {
            return $sanitized_input;
        }
        
        foreach ($input as $key => $file) {
            if (!is_array($file)) {
                continue;
            }
            
            // Make sure required fields exist
            if (!isset($file['title']) || !isset($file['url'])) {
                continue;
            }
            
            // Sanitize each field
            $sanitized_input[$key] = array(
                'title' => sanitize_text_field($file['title']),
                'url' => esc_url_raw($file['url']),
                'id' => isset($file['id']) ? sanitize_key($file['id']) : 'document-' . sanitize_key($key)
            );
        }
        
        return $sanitized_input;
    }
    
    /**
     * Check if the current admin screen belongs to this plugin.
     *
     * The block editor runs inside an iframe in WordPress 7.0. This plugin
     * provides no editor canvas integration (it is shortcode-driven), so we
     * scope admin assets tightly to the plugin's own admin screens to avoid
     * any leakage into the editor iframe or unrelated admin pages.
     *
     * @since 1.2.3
     * @param string $hook Current admin page hook.
     * @return bool True if this is one of the plugin's own admin screens.
     */
    private function is_plugin_admin_screen($hook) {
        return is_string($hook) && (false !== strpos($hook, 'docdownman') || false !== strpos($hook, 'document-download'));
    }

    /**
     * Enqueue admin styles
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_styles($hook = '') {
        if (!$this->is_plugin_admin_screen($hook)) {
            return;
        }
        wp_register_style('docdownman-admin-css', DOCDOWNMAN_PLUGIN_URL . 'assets/css/admin.css', array(), DOCDOWNMAN_VERSION);
        wp_enqueue_style('docdownman-admin-css');
    }
    
    /**
     * Safely get and sanitize POST data with nonce verification
     * 
     * @param string $key The POST key to retrieve
     * @param string $nonce_action The nonce action to verify
     * @param string $nonce_name The nonce field name
     * @return array The sanitized data
     */
    private function get_sanitized_post_data($key, $nonce_action = 'docdownman_settings-options', $nonce_name = '_wpnonce') {
        // Check if user has proper permissions
        if (!current_user_can('manage_options')) {
            return array();
        }
        
        // Verify nonce for security
        if (!isset($_POST[$nonce_name])) {
            return array();
        }
        
        $nonce_value = sanitize_text_field(wp_unslash($_POST[$nonce_name]));
        if (!wp_verify_nonce($nonce_value, $nonce_action)) {
            return array();
        }
        
        if (!isset($_POST[$key])) {
            return array();
        }
        
        // Special handling for document files which is a nested array
        if ($key === 'docdownman_document_files') {
            // Get a copy of the POST data
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- We're sanitizing it in the next steps based on type
            $post_value = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : array();
            
            // Check if it's an array
            if (!is_array($post_value)) {
                return array();
            }
            
            $sanitized_data = array();
            $raw_input = wp_unslash($post_value);
            
            foreach ($raw_input as $doc_key => $doc_data) {
                if (!is_array($doc_data)) {
                    continue;
                }
                
                $sanitized_data[$doc_key] = array();
                
                // Sanitize each field in the document data
                if (isset($doc_data['title'])) {
                    $sanitized_data[$doc_key]['title'] = sanitize_text_field($doc_data['title']);
                }
                
                if (isset($doc_data['url'])) {
                    $sanitized_data[$doc_key]['url'] = esc_url_raw($doc_data['url']);
                }
                
                if (isset($doc_data['id'])) {
                    $sanitized_data[$doc_key]['id'] = sanitize_key($doc_data['id']);
                } else {
                    $sanitized_data[$doc_key]['id'] = 'document-' . sanitize_key($doc_key);
                }
            }
            
            return $sanitized_data;
        }
        
        // For other fields, use the standard sanitization
        $sanitized_key = sanitize_key($key);
        
        if (!isset($_POST[$sanitized_key])) {
            return array();
        }
        
        // Get a copy of the POST data
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- We're sanitizing it in the next steps based on type
        $post_value = wp_unslash($_POST[$sanitized_key]);
        
        // Get the raw POST data and sanitize it
        if (is_array($post_value)) {
            // For simple arrays, sanitize each element
            $post_data = wp_unslash($post_value);
            $sanitized_data = array();
            
            foreach ($post_data as $k => $v) {
                $sanitized_data[sanitize_key($k)] = sanitize_text_field($v);
            }
            
            return $sanitized_data;
        } else {
            // For scalar values
            return sanitize_text_field(wp_unslash($post_value));
        }
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook = '') {
        if (!$this->is_plugin_admin_screen($hook)) {
            return;
        }
        wp_register_script('docdownman-admin-js', DOCDOWNMAN_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), DOCDOWNMAN_VERSION, true);
        wp_enqueue_script('docdownman-admin-js');
        
        // Add inline script for API key masking
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is for admin script enqueuing, not form processing
        if (isset($_GET['page']) && 
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe GET parameter check for admin page identification
            (sanitize_text_field(wp_unslash($_GET['page'])) === 'document-download-email-marketing' || 
             // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe GET parameter check for admin page identification
             sanitize_text_field(wp_unslash($_GET['page'])) === 'docdownman-email-marketing') && 
            current_user_can('manage_options')) {
            $api_key_script = "
                jQuery(document).ready(function($) {
                    // Toggle API key visibility with new prefix
                    $('#docdownman_toggle_api_key').on('click', function() {
                        var input = $('#docdownman_email_api_key_display');
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
                    $('#docdownman_edit_api_key').on('click', function() {
                        $('.docdownman-api-key-wrapper').hide();
                        $('.docdownman-api-key-edit').show();
                        $('#docdownman_email_api_key_edit').focus();
                    });
                    
                    // Cancel API key edit with new prefix
                    $('#docdownman_cancel_api_key').on('click', function() {
                        $('.docdownman-api-key-edit').hide();
                        $('.docdownman-api-key-wrapper').show();
                    });
                    
                    // Save API key with new prefix
                    $('#docdownman_save_api_key').on('click', function() {
                        var newKey = $('#docdownman_email_api_key_edit').val();
                        
                        if (newKey) {
                            $('#docdownman_email_api_key').val(newKey);
                            
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
                            $('#docdownman_email_api_key_display').val(displayValue);
                        }
                        
                        // Hide edit form, show display wrapper
                        $('.docdownman-api-key-edit').hide();
                        $('.docdownman-api-key-wrapper').show();
                        $('#docdownman_email_api_key_edit').val('');
                    });
                });
            ";
            
            // Add inline script to both old and new prefixed scripts for backward compatibility
            wp_add_inline_script('docdownman-admin-js', $api_key_script);
            wp_add_inline_script('docdownman-admin-js', $api_key_script);
            
            // Add inline styles for API key masking with new prefix
            $api_key_styles = "
                /* New prefix styles */
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
        // Get existing files with the standard prefix
        $document_files = get_option('docdownman_document_files', array());
        
        // Handle form submission
        if (isset($_POST['submit']) && isset($_POST['_wpnonce'])) {
            // Verify nonce for security
            check_admin_referer('docdownman_settings-options');
            
            // Check if the document files field exists in POST data
            if (isset($_POST['docdownman_document_files']) && is_array($_POST['docdownman_document_files'])) {
                // Get the raw input and sanitize it using our helper method
                $raw_input = $this->get_sanitized_post_data('docdownman_document_files');
                
                // Run it through our sanitization method
                $sanitized_input = $this->sanitize_document_files($raw_input);
                
                // Save the sanitized input to the database
                update_option('docdownman_document_files', $sanitized_input);
                
                // Refresh the data with the newly saved values
                $document_files = get_option('docdownman_document_files', array());
                
                // Add success message
                add_settings_error('docdownman_settings', 'settings_updated', 'Document files saved successfully.', 'updated');
            }
        }
        ?>
        <div class="wrap">
            <h1>Document Download Manager</h1>
            
            <!-- Upgrade and Donate Links -->
            <div class="docdownman-header-actions" style="margin: 20px 0; padding: 15px; background: #f9f9f9; border-radius: 5px;">
                <p style="margin: 0 0 10px 0;"><strong>Support the Development:</strong></p>
                <a href="https://checkout.freemius.com/plugin/19168/plan/31773/" class="button button-primary docdownman-icon-btn" target="_blank" style="margin-right: 10px;">
                    <span class="dashicons dashicons-star-filled"></span>
                    <span>Upgrade to Premium</span>
                </a>
                <a href="https://ko-fi.com/gunjanjaswal" class="button button-secondary docdownman-icon-btn" target="_blank">
                    <span class="dashicons dashicons-heart"></span>
                    <span>Support on Ko-fi</span>
                </a>
            </div>
            <style>
                .docdownman-icon-btn { display: inline-flex; align-items: center; gap: 6px; line-height: 1; }
                .docdownman-icon-btn .dashicons { font-size: 18px; width: 18px; height: 18px; line-height: 1; }
            </style>
            
            <?php 
            // Show both old and new settings errors for backward compatibility
            settings_errors('docdownman_settings'); 
            settings_errors('docdownman_settings'); 
            ?>
            
            <div class="notice notice-info is-dismissible">
                <p><strong>Important:</strong> You can add both Excel (.xlsx, .xls, .csv) and PDF (.pdf) files. The file type will be automatically detected based on the file extension in the URL.</p>
                <p>Make sure your file URL ends with the correct extension (e.g., <code>.pdf</code> for PDF files or <code>.xlsx</code> for Excel files).</p>
            </div>
            
            <form method="post" action="">
                <?php wp_nonce_field('docdownman_settings-options'); ?>
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
                                    <input type="text" name="docdownman_document_files[<?php echo esc_attr($key); ?>][title]" value="<?php echo esc_attr($file['title']); ?>" class="regular-text" required />
                                    <input type="hidden" name="docdownman_document_files[<?php echo esc_attr($key); ?>][id]" value="<?php echo esc_attr(isset($file['id']) ? $file['id'] : sanitize_title($file['title'])); ?>" />
                                </td>
                                <td>
                                    <input type="url" name="docdownman_document_files[<?php echo esc_attr($key); ?>][url]" value="<?php echo esc_url($file['url']); ?>" class="regular-text" required />
                                </td>
                                <td>
                                    <span class="dashicons <?php echo esc_attr($file_icon); ?>"></span> <?php echo esc_html($file_type); ?>
                                </td>
                                <td>
                                    <code>[docdownman_document_download id="<?php echo esc_attr(isset($file['id']) ? $file['id'] : sanitize_title($file['title'])); ?>"]</code>
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
                    <button type="button" class="button button-secondary docdownman-add-document-file">Add Document File</button>
                </p>
                <input type="submit" name="submit" class="button button-primary" value="Save Changes">
            </form>
            
            <div class="docdownman-instructions">
                <h2>How to Use</h2>
                <ol>
                    <li>Add Excel or PDF files using the form above</li>
                    <li>The file type will be automatically detected based on the file extension (.xlsx, .xls, .pdf, etc.)</li>
                    <li>Copy the shortcode for each file</li>
                    <li>Paste the shortcode in any post or page where you want to display the download button</li>
                    <li>You can customize the button text: <code>[docdownman_document_download id="file-id" text="Free Download"]</code></li>
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
     * Display download records page
     */
    public function display_records_page() {
        // Check if user has proper permissions
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Get the table name
        global $wpdb;
        $table_name = $wpdb->prefix . 'docdownman_downloads';
        
        // Get records from the database using our helper method with caching
        $records = $this->get_download_records($table_name);
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Download Records', 'document-download-manager'); ?></h1>
            
            <?php if (empty($records)) : ?>
                <div class="notice notice-info">
                    <p><?php echo esc_html__('No download records found.', 'document-download-manager'); ?></p>
                </div>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('ID', 'document-download-manager'); ?></th>
                            <th><?php echo esc_html__('Name', 'document-download-manager'); ?></th>
                            <th><?php echo esc_html__('Email', 'document-download-manager'); ?></th>
                            <th><?php echo esc_html__('File Name', 'document-download-manager'); ?></th>
                            <th><?php echo esc_html__('File URL', 'document-download-manager'); ?></th>
                            <th><?php echo esc_html__('Date/Time', 'document-download-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $record) : ?>
                            <tr>
                                <td><?php echo esc_html($record['id']); ?></td>
                                <td><?php echo esc_html($record['name']); ?></td>
                                <td><?php echo esc_html($record['email']); ?></td>
                                <td><?php echo esc_html($record['file_name']); ?></td>
                                <td><a href="<?php echo esc_url($record['file_url']); ?>" target="_blank"><?php echo esc_html(basename($record['file_url'])); ?></a></td>
                                <td><?php echo esc_html($record['time']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
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
            
            <div class="docdownman-premium-notice">
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
            
            <div class="docdownman-feature-list">
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
    
    // The sanitize_document_files method is already defined earlier in this class
}
