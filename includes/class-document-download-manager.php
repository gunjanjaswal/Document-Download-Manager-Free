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
        
        // Check if the table already exists
        $table_name = $wpdb->prefix . 'docdownman_downloads';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        // Only create the table if it doesn't exist
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
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
        
        // Delete plugin options
        delete_option('docdownman_document_files');
    }
}
