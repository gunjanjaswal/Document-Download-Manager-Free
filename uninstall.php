<?php
/**
 * Uninstall Document Download Manager
 *
 * @package    Document_Download_Manager
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('docdownman_document_files');

// Clear any cached data
wp_cache_delete('docdownman_all_records', 'document-download-manager');
wp_cache_delete('docdownman_table_created', 'document-download-manager');

// Use dbDelta for proper database schema changes
global $wpdb;
$table_name = $wpdb->prefix . 'docdownman_downloads';

// Check if the table exists before attempting to drop it
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

// Drop the table if it exists
if ($table_exists) {
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}
