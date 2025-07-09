<?php
/**
 * Main plugin class
 */
class Document_Download_Manager {
    /**
     * The loader that's responsible for maintaining and registering all hooks
     */
    protected $admin;
    protected $public;

    /**
     * Define the core functionality of the plugin.
     */
    public function __construct() {
        $this->admin = new Document_Download_Manager_Admin();
        $this->public = new Document_Download_Manager_Public();
    }

    /**
     * Run the plugin.
     */
    public function run() {
        // Register admin hooks
        add_action('admin_menu', array($this->admin, 'add_admin_menu'));
        add_action('admin_init', array($this->admin, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this->admin, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($this->admin, 'enqueue_scripts'));
        
        // Register public hooks
        add_action('wp_enqueue_scripts', array($this->public, 'enqueue_styles'));
        add_action('wp_enqueue_scripts', array($this->public, 'enqueue_scripts'));
        
        // Register AJAX handlers and shortcodes through the public class
        $this->public->register_ajax_handlers();
        $this->public->register_shortcodes();
    }

    /**
     * Activate the plugin.
     */
    public static function activate() {
        global $wpdb;
        
        // Use a more unique prefix for database tables
        $table_name = $wpdb->prefix . 'ddmanager_downloads';
        $old_table_name = $wpdb->prefix . 'docdownman_downloads';
        
        // Check if either table exists (new or old prefix)
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        $old_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$old_table_name'") === $old_table_name;
        
        // Create the table if it doesn't exist
        if (!$table_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                name varchar(100) NOT NULL,
                email varchar(100) NOT NULL,
                file_name varchar(255) NOT NULL,
                file_url varchar(255) NOT NULL,
                PRIMARY KEY  (id)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            // Cache that we've created the table with new prefix
            wp_cache_set('ddmanager_table_created', true, 'document-download-manager', DAY_IN_SECONDS);
            
            // If old table exists, migrate data to the new table
            if ($old_table_exists) {
                $wpdb->query("INSERT INTO $table_name SELECT * FROM $old_table_name");
                // Don't delete the old table yet, will be handled during uninstall
            }
        } elseif ($old_table_exists && !$table_exists) {
            // If only the old table exists, create the new one and migrate data
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                name varchar(100) NOT NULL,
                email varchar(100) NOT NULL,
                file_name varchar(255) NOT NULL,
                file_url varchar(255) NOT NULL,
                PRIMARY KEY  (id)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            // Migrate data
            $wpdb->query("INSERT INTO $table_name SELECT * FROM $old_table_name");
            
            // Cache that we've created the table with new prefix
            wp_cache_set('ddmanager_table_created', true, 'document-download-manager', DAY_IN_SECONDS);
        }
        
        // For backward compatibility, also set the old cache key
        if (wp_cache_get('ddmanager_table_created', 'document-download-manager')) {
            wp_cache_set('docdownman_table_created', true, 'document-download-manager', DAY_IN_SECONDS);
        }
        
        // Migrate options from old prefixes to new prefix
        $old_document_files = get_option('docdownman_document_files');
        $very_old_document_files = get_option('document_download_files');
        
        // Check if we need to migrate from old prefix
        if (!get_option('ddmanager_document_files') && $old_document_files) {
            update_option('ddmanager_document_files', $old_document_files);
        }
        
        // Check if we need to migrate from very old prefix
        if (!get_option('ddmanager_document_files') && $very_old_document_files) {
            update_option('ddmanager_document_files', $very_old_document_files);
        }
        
        // Migrate email API key if it exists
        $old_email_api_key = get_option('docdownman_email_api_key');
        if (!get_option('ddmanager_email_api_key') && $old_email_api_key) {
            update_option('ddmanager_email_api_key', $old_email_api_key);
        }
    }

    /**
     * Deactivate the plugin.
     */
    public static function deactivate() {
        // Nothing to do here for now
    }

    /**
     * Uninstall the plugin.
     */
    public static function uninstall() {
        global $wpdb;
        
        // Drop both old and new downloads tables
        $new_table_name = $wpdb->prefix . 'ddmanager_downloads';
        $old_table_name = $wpdb->prefix . 'docdownman_downloads';
        
        $wpdb->query("DROP TABLE IF EXISTS $new_table_name");
        $wpdb->query("DROP TABLE IF EXISTS $old_table_name");
        
        // Delete options with both prefixes
        delete_option('ddmanager_document_files');
        delete_option('docdownman_document_files');
        
        // Clear any cached data
        wp_cache_delete('ddmanager_table_created', 'document-download-manager');
        wp_cache_delete('docdownman_table_created', 'document-download-manager');
    }
}
