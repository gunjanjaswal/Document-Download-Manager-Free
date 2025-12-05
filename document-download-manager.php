<?php
/**
 * Plugin Name: Document Download Manager
 * Plugin URI: https://github.com/gunjanjaswal/Document-Download-Manager-Free
 * Description: A plugin to manage and track document downloads. Collect user information before allowing downloads.
 * Version: 1.2.1
 * Author: Gunjan Jaswaal
 * Author URI: https://www.gunjanjaswal.me
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: document-download-manager
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Tested up to: 6.9
 *
 * Document Download Manager is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Document Download Manager is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Document Download Manager. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants with a unique prefix
define('DOCDOWNMAN_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DOCDOWNMAN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DOCDOWNMAN_VERSION', '1.2.1');

// Using only the unique prefix docdownman

// This is the WordPress.org version with no premium features

// Include required files
require_once DOCDOWNMAN_PLUGIN_DIR . 'includes/class-document-download-manager.php';
require_once DOCDOWNMAN_PLUGIN_DIR . 'includes/class-document-download-manager-admin.php';
require_once DOCDOWNMAN_PLUGIN_DIR . 'includes/class-document-download-manager-public.php';
// Premium class is not included in the free version for WordPress.org compliance

/**
 * Helper function to get the upgrade URL
 * This ensures the function is available even if the premium class is not loaded
 * 
 * @return string The URL to upgrade to the premium version
 */
function docdownman_get_upgrade_url() {
    return 'https://checkout.freemius.com/plugin/19168/plan/31773/';
}

// Using only the unique prefix docdownman

// Register activation and deactivation hooks
register_activation_hook(__FILE__, array('Document_Download_Manager', 'activate'));
register_deactivation_hook(__FILE__, array('Document_Download_Manager', 'deactivate'));
// Note: Uninstall is handled by uninstall.php

/**
 * Add plugin action links on plugins page
 * 
 * @param array $links Existing plugin action links
 * @return array Modified plugin action links
 */
function docdownman_add_plugin_action_links($links) {
    $upgrade_link = '<a href="https://checkout.freemius.com/plugin/19168/plan/31773/" target="_blank" style="color: #d54e21; font-weight: bold;">' . __('Upgrade to Pro', 'document-download-manager') . '</a>';
    $donate_link = '<a href="https://www.buymeacoffee.com/gunjanjaswal" target="_blank" style="color: #0073aa; font-weight: bold;">' . __('Buy Me Coffee', 'document-download-manager') . '</a>';
    
    // Add links at the beginning of the array
    array_unshift($links, $upgrade_link, $donate_link);
    
    return $links;
}

// Add the action links filter
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'docdownman_add_plugin_action_links');

// Initialize the plugin with more unique prefix
function docdownman_initialize() {
    $plugin = new Document_Download_Manager();
    $plugin->run();
}

// Using only the unique prefix docdownman

// Run the plugin
docdownman_initialize();
