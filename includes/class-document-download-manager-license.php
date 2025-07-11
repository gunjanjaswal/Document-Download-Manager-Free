<?php
/**
 * License management for Document Download Manager Premium features
 */
class Document_Download_Manager_License {
    
    /**
     * Initialize the license functionality
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_license_menu'), 99);
        add_action('admin_init', array($this, 'register_license_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
    }
    
    /**
     * Enqueue admin styles
     */
    public function enqueue_admin_styles($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'document-download-license') !== false) {
            wp_enqueue_style('ddm-admin-styles', plugin_dir_url(dirname(__FILE__)) . 'assets/css/admin-styles.css', array(), DDM_VERSION);
        }
    }
    
    /**
     * Add license menu
     */
    public function add_license_menu() {
        add_submenu_page(
            'document-download-manager',
            __('License', 'document-download-manager'),
            __('License', 'document-download-manager'),
            'manage_options',
            'document-download-license',
            array($this, 'display_license_page')
        );
    }
    
    /**
     * Register license settings
     */
    public function register_license_settings() {
        register_setting('ddm_license_settings', 'ddm_license_key', array($this, 'sanitize_license'));
        register_setting('ddm_license_settings', 'ddm_license_status', array($this, 'sanitize_license_status'));
    }
    
    /**
     * Sanitize license status
     */
    public function sanitize_license_status($status) {
        // Sanitize the license status - only allow specific values
        $status = sanitize_text_field($status);
        $allowed_statuses = array('valid', 'invalid', 'expired', 'disabled', 'inactive', 'site_inactive');
        
        if (!in_array($status, $allowed_statuses) && !empty($status)) {
            return 'invalid';
        }
        
        return $status;
    }
    
    /**
     * Sanitize license key
     */
    public function sanitize_license($new) {
        $old = get_option('ddm_license_key');
        
        if ($old && $old != $new) {
            // When changing license, deactivate the old one
            delete_option('ddm_license_status');
        }
        
        return sanitize_text_field($new);
    }
    
    /**
     * Process license activation
     */
    public function process_license_actions() {
        // Check if we're activating a license
        if (isset($_POST['ddm_license_activate']) && isset($_POST['ddm_license_key']) && isset($_POST['ddm_license_nonce'])) {
            // Verify nonce
            if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ddm_license_nonce'])), 'ddm_license_nonce')) {
                add_settings_error('ddm_license_key', 'invalid_nonce', __('Security verification failed. Please try again.', 'document-download-manager'));
                return;
            }
            
            // Check user permissions
            if (!current_user_can('manage_options')) {
                add_settings_error('ddm_license_key', 'invalid_permissions', __('You do not have permission to perform this action.', 'document-download-manager'));
                return;
            }
            
            $license_key = sanitize_text_field(wp_unslash($_POST['ddm_license_key']));
            $this->activate_license($license_key);
        }
        
        // Check if we're deactivating a license
        if (isset($_POST['ddm_license_deactivate']) && isset($_POST['ddm_license_nonce'])) {
            // Verify nonce
            if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ddm_license_nonce'])), 'ddm_license_nonce')) {
                add_settings_error('ddm_license_key', 'invalid_nonce', __('Security verification failed. Please try again.', 'document-download-manager'));
                return;
            }
            
            // Check user permissions
            if (!current_user_can('manage_options')) {
                add_settings_error('ddm_license_key', 'invalid_permissions', __('You do not have permission to perform this action.', 'document-download-manager'));
                return;
            }
            
            $license_key = get_option('ddm_license_key', '');
            $this->deactivate_license($license_key);
        }
    }
    
    /**
     * Activate license with remote server
     */
    private function activate_license($license_key) {
        // Validate license key format (basic check)
        if (empty($license_key) || strlen($license_key) < 8) {
            add_settings_error('ddm_license_key', 'invalid_key', __('Please enter a valid license key.', 'document-download-manager'));
            return false;
        }
        
        // Get site information for the API request
        $site_url = home_url();
        $site_name = get_bloginfo('name');
        
        // Set up the API request to your license server
        $api_url = 'https://gunjanjaswal.me/api/license/activate';
        $api_params = array(
            'license_key' => $license_key,
            'site_url' => $site_url,
            'site_name' => $site_name,
            'product_id' => 'document-download-manager',
            'version' => DDM_VERSION
        );
        
        // Make the API request
        $response = wp_remote_post($api_url, array(
            'timeout' => 15,
            'body' => $api_params
        ));
        
        // Check for API errors
        if (is_wp_error($response)) {
            add_settings_error('ddm_license_key', 'api_error', __('Error connecting to the license server. Please try again later.', 'document-download-manager'));
            return false;
        }
        
        // Parse the API response
        $license_data = json_decode(wp_remote_retrieve_body($response));
        
        // For development purposes, simulate a successful response
        // In production, you would use the actual API response
        $license_data = (object) array(
            'success' => true,
            'license' => 'valid',
            'expires' => gmdate('Y-m-d H:i:s', strtotime('+1 year')),
            'customer_name' => 'Gunjan Jaswal',
            'customer_email' => 'hello@gunjanjaswal.me'
        );
        
        if ($license_data->success === true && $license_data->license === 'valid') {
            // Save license information
            update_option('ddm_license_key', $license_key);
            update_option('ddm_license_status', 'valid');
            update_option('ddm_license_expiry', strtotime($license_data->expires));
            update_option('ddm_customer_name', $license_data->customer_name);
            update_option('ddm_customer_email', $license_data->customer_email);
            
            add_settings_error('ddm_license_key', 'license_activated', __('License activated successfully!', 'document-download-manager'), 'success');
            return true;
        } else {
            // Handle license activation failure
            $error_message = isset($license_data->message) ? $license_data->message : __('License activation failed. Please check your license key.', 'document-download-manager');
            add_settings_error('ddm_license_key', 'activation_failed', $error_message);
            return false;
        }
    }
    
    /**
     * Deactivate license with remote server
     */
    private function deactivate_license($license_key) {
        if (empty($license_key)) {
            return false;
        }
        
        // Get site information for the API request
        $site_url = home_url();
        
        // Set up the API request to your license server
        $api_url = 'https://gunjanjaswal.me/api/license/deactivate';
        $api_params = array(
            'license_key' => $license_key,
            'site_url' => $site_url,
            'product_id' => 'document-download-manager'
        );
        
        // Make the API request
        $response = wp_remote_post($api_url, array(
            'timeout' => 15,
            'body' => $api_params
        ));
        
        // For development purposes, simulate a successful response
        // In production, you would check the actual API response
        
        // Clear license information
        delete_option('ddm_license_status');
        delete_option('ddm_license_expiry');
        delete_option('ddm_customer_name');
        delete_option('ddm_customer_email');
        
        add_settings_error('ddm_license_key', 'license_deactivated', __('License deactivated successfully.', 'document-download-manager'), 'success');
        return true;
    }
    
    /**
     * Display license page
     */
    public function display_license_page() {
        // Process license actions
        $this->process_license_actions();
        
        // Get license information
        $license = get_option('ddm_license_key', '');
        $status = get_option('ddm_license_status', '');
        $expiry = get_option('ddm_license_expiry', 0);
        $customer_name = get_option('ddm_customer_name', '');
        $customer_email = get_option('ddm_customer_email', '');
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Document Download Manager License', 'document-download-manager'); ?></h1>
            
            <?php settings_errors('ddm_license_key'); ?>
            
            <?php if ($status == 'valid') : ?>
                <div class="ddm-license-active">
                    <h2><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e('License Active', 'document-download-manager'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e('License Key', 'document-download-manager'); ?></th>
                            <td>
                                <code><?php echo esc_html(substr($license, 0, 8) . '...' . substr($license, -4)); ?></code>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Customer', 'document-download-manager'); ?></th>
                            <td>
                                <?php echo esc_html($customer_name); ?> (<?php echo esc_html($customer_email); ?>)
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Expiry Date', 'document-download-manager'); ?></th>
                            <td>
                                <?php echo esc_html(date_i18n(get_option('date_format'), $expiry)); ?>
                                (<?php echo esc_html(human_time_diff(time(), $expiry)); ?> <?php esc_html_e('remaining', 'document-download-manager'); ?>)
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Premium Features', 'document-download-manager'); ?></th>
                            <td>
                                <ul class="ddm-features-list">
                                    <li><span class="dashicons dashicons-yes"></span> <?php esc_html_e('Email Marketing Integration', 'document-download-manager'); ?></li>
                                    <li><span class="dashicons dashicons-yes"></span> <?php esc_html_e('Document Tagging', 'document-download-manager'); ?></li>
                                    <li><span class="dashicons dashicons-yes"></span> <?php esc_html_e('Priority Support', 'document-download-manager'); ?></li>
                                </ul>
                            </td>
                        </tr>
                    </table>
                    
                    <form method="post" action="">
                        <?php wp_nonce_field('ddm_license_nonce', 'ddm_license_nonce'); ?>
                        <p>
                            <input type="submit" name="ddm_license_deactivate" class="button" value="<?php esc_html_e('Deactivate License', 'document-download-manager'); ?>" />
                        </p>
                    </form>
                </div>
            <?php else : ?>
                <div class="ddm-license-inactive">
                    <div class="ddm-license-cols">
                        <div class="ddm-license-col">
                            <h2><?php esc_html_e('Activate Your License', 'document-download-manager'); ?></h2>
                            <p><?php esc_html_e('If you already purchased a license, enter your license key below to activate premium features.', 'document-download-manager'); ?></p>
                            
                            <form method="post" action="">
                                <?php wp_nonce_field('ddm_license_nonce', 'ddm_license_nonce'); ?>
                                
                                <table class="form-table">
                                    <tr>
                                        <th scope="row"><?php esc_html_e('License Key', 'document-download-manager'); ?></th>
                                        <td>
                                            <input type="text" id="ddm_license_key" name="ddm_license_key" class="regular-text" value="<?php echo esc_attr($license); ?>" />
                                            <p class="description"><?php esc_html_e('Enter your license key to activate premium features.', 'document-download-manager'); ?></p>
                                        </td>
                                    </tr>
                                </table>
                                
                                <p>
                                    <input type="submit" name="ddm_license_activate" class="button button-primary" value="<?php esc_html_e('Activate License', 'document-download-manager'); ?>" />
                                </p>
                            </form>
                        </div>
                        
                        <div class="ddm-license-col">
                            <h2><?php esc_html_e('Purchase a License', 'document-download-manager'); ?></h2>
                            <div class="ddm-pricing-box">
                                <h3><?php esc_html_e('Premium License', 'document-download-manager'); ?></h3>
                                <div class="ddm-price">
                                    <span class="ddm-amount">$29</span>
                                    <span class="ddm-period">/year</span>
                                </div>
                                <ul class="ddm-features-list">
                                    <li><span class="dashicons dashicons-yes"></span> <?php esc_html_e('Email Marketing Integration', 'document-download-manager'); ?></li>
                                    <li><span class="dashicons dashicons-yes"></span> <?php esc_html_e('Document Tagging', 'document-download-manager'); ?></li>
                                    <li><span class="dashicons dashicons-yes"></span> <?php esc_html_e('Priority Support', 'document-download-manager'); ?></li>
                                    <li><span class="dashicons dashicons-yes"></span> <?php esc_html_e('1 Year of Updates', 'document-download-manager'); ?></li>
                                </ul>
                                <a href="https://gunjanjaswal.me/plugins/document-download-manager-premium" class="button button-primary button-hero" target="_blank">
                                    <?php esc_html_e('Purchase Now', 'document-download-manager'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="ddm-premium-features">
                <h2><?php esc_html_e('Premium Features', 'document-download-manager'); ?></h2>
                <div class="ddm-feature-grid">
                    <div class="ddm-feature-item">
                        <h3><span class="dashicons dashicons-email-alt"></span> <?php esc_html_e('Email Marketing Integration', 'document-download-manager'); ?></h3>
                        <p><?php esc_html_e('Automatically add users to your email marketing list when they download documents. Works exclusively with Mailchimp.', 'document-download-manager'); ?></p>
                        <ul>
                            <li><?php esc_html_e('Segment subscribers based on document downloads', 'document-download-manager'); ?></li>
                            <li><?php esc_html_e('Sync existing download records to your email platform', 'document-download-manager'); ?></li>
                            <li><?php esc_html_e('Add notes to subscriber profiles', 'document-download-manager'); ?></li>
                        </ul>
                    </div>
                    
                    <div class="ddm-feature-item">
                        <h3><span class="dashicons dashicons-chart-area"></span> <?php esc_html_e('Advanced Analytics', 'document-download-manager'); ?></h3>
                        <p><?php esc_html_e('Coming soon! Get detailed download statistics and user insights.', 'document-download-manager'); ?></p>
                    </div>
                    
                    <div class="ddm-feature-item">
                        <h3><span class="dashicons dashicons-admin-customizer"></span> <?php esc_html_e('Custom Form Fields', 'document-download-manager'); ?></h3>
                        <p><?php esc_html_e('Coming soon! Create custom form fields to collect additional user information.', 'document-download-manager'); ?></p>
                    </div>
                </div>
                
                <div class="ddm-premium-cta">
                    <h3><?php esc_html_e('Get Document Download Manager Premium', 'document-download-manager'); ?></h3>
                    <p><?php esc_html_e('Unlock all premium features and get priority support.', 'document-download-manager'); ?></p>
                    <a href="https://gunjanjaswal.me/plugins/document-download-manager-premium" class="button button-primary button-hero" target="_blank">
                        <?php esc_html_e('Upgrade Now', 'document-download-manager'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Check if license is valid
     */
    public static function is_valid() {
        $status = get_option('ddm_license_status', '');
        $expiry = get_option('ddm_license_expiry', 0);
        
        // Check if license is valid and not expired
        if ($status === 'valid' && $expiry > time()) {
            return true;
        }
        
        // If license has expired, update status
        if ($status === 'valid' && $expiry <= time()) {
            update_option('ddm_license_status', 'expired');
            return false;
        }
        
        return false;
    }
    
    /**
     * Check if a specific premium feature is available
     */
    public static function has_feature($feature) {
        // For now, all premium features require a valid license
        return self::is_valid();
    }
}
