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

// Delete any other plugin options
delete_option('docdownman_email_api_key');

// Clear any cached data
wp_cache_delete('docdownman_all_records', 'document-download-manager');
wp_cache_delete('docdownman_table_created', 'document-download-manager');

// Use dbDelta for proper database schema changes
global $wpdb;

// Handle table
$docdownman_table_name = $wpdb->prefix . 'docdownman_downloads';

// First check the cache for table existence
$docdownman_cache_key = 'docdownman_table_exists_' . md5($docdownman_table_name);
$docdownman_table_exists = wp_cache_get($docdownman_cache_key, 'document-download-manager');

// If not in cache, check database (with proper caching)
if (false === $docdownman_table_exists) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using caching to minimize DB calls
    $docdownman_table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $docdownman_table_name)) === $docdownman_table_name;
    // Cache the result
    wp_cache_set($docdownman_cache_key, $docdownman_table_exists, 'document-download-manager', HOUR_IN_SECONDS);
}

// Only proceed with table deletion if it exists
if ($docdownman_table_exists) {
    // Use WordPress schema API instead of direct query when possible
    if (function_exists('wp_drop_table')) {
        // WordPress 6.1+ has this function
        wp_drop_table($docdownman_table_name);
    } else {
        // Fallback for older WordPress versions
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.NoCaching -- Necessary for plugin uninstall and we're invalidating cache after
        $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %s", $docdownman_table_name));
    }
    
    // Clear any cached data related to this table
    wp_cache_delete($docdownman_cache_key, 'document-download-manager');
    wp_cache_delete('docdownman_all_records', 'document-download-manager');
}
