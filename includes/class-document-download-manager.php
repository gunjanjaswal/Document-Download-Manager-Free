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
        
        // Use a unique prefix for database tables
        $table_name = $wpdb->prefix . 'docdownman_downloads';
        
        // First check if we have a cached result
        $cache_key = 'docdownman_table_exists_' . md5($table_name);
        $table_exists = wp_cache_get($cache_key, 'document-download-manager');
        
        // If not in cache, check if table exists using prepare to avoid SQL injection
        if (false === $table_exists) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Using caching to minimize DB calls
            $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name;
            // Cache check result for better performance
            wp_cache_set($cache_key, $table_exists, 'document-download-manager', HOUR_IN_SECONDS);
        }
        
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
            
            // Cache that we've created the table
            wp_cache_set('docdownman_table_created', true, 'document-download-manager', DAY_IN_SECONDS);
        }
        
        // Migrate options
        
        // No migration needed as we're using only the new prefix
        
        // No migration needed as we're using only the new prefix
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
        
        // Drop the downloads table
        $table_name = $wpdb->prefix . 'docdownman_downloads';
        
        // Use WordPress schema API if available (WordPress 3.9+)
        if (function_exists('dbDelta')) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery -- Necessary for plugin uninstall
            $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %s", $table_name));
        } else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery -- Necessary for plugin uninstall
            $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %s", $table_name));
        }
        
        // Clear any cached table existence checks
        $cache_key = 'docdownman_table_exists_' . md5($table_name);
        wp_cache_delete($cache_key, 'document-download-manager');
        
        // Delete options
        delete_option('docdownman_document_files');
        
        // Clear any cached data
        wp_cache_delete('docdownman_table_created', 'document-download-manager');
    }
}
