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

// Delete plugin options (both old and new prefixes for backward compatibility)
delete_option('ddmanager_document_files');
delete_option('docdownman_document_files');
delete_option('document_download_files');

// Delete any other plugin options
delete_option('ddmanager_email_api_key');
delete_option('docdownman_email_api_key');

// Clear any cached data (both old and new prefixes for backward compatibility)
wp_cache_delete('ddmanager_all_records', 'document-download-manager');
wp_cache_delete('docdownman_all_records', 'document-download-manager');
wp_cache_delete('ddmanager_table_created', 'document-download-manager');
wp_cache_delete('docdownman_table_created', 'document-download-manager');

// Use dbDelta for proper database schema changes
global $wpdb;

// Handle new prefix table
$new_table_name = $wpdb->prefix . 'ddmanager_downloads';
$new_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$new_table_name'") === $new_table_name;
if ($new_table_exists) {
    $wpdb->query("DROP TABLE IF EXISTS $new_table_name");
}

// Handle old prefix table for backward compatibility
$old_table_name = $wpdb->prefix . 'docdownman_downloads';
$old_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$old_table_name'") === $old_table_name;
if ($old_table_exists) {
    $wpdb->query("DROP TABLE IF EXISTS $old_table_name");
}
