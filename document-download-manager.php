<?php
/**
 * Plugin Name: Document Download Manager
 * Plugin URI: https://wordpress.org/plugins/document-download-manager/
 * Description: A plugin to manage and track document downloads. Collect user information before allowing downloads.
 * Version: 1.0.0
 * Author: Gunjan Jaswaal
 * Author URI: https://profiles.wordpress.org/gunjanjaswal/
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: document-download-manager
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
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

// Define plugin constants with a more unique prefix
define('DDMANAGER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DDMANAGER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DDMANAGER_VERSION', '1.0.0');

// For backward compatibility
define('DOCDOWNMAN_PLUGIN_DIR', DDMANAGER_PLUGIN_DIR);
define('DOCDOWNMAN_PLUGIN_URL', DDMANAGER_PLUGIN_URL);
define('DOCDOWNMAN_VERSION', DDMANAGER_VERSION);

// This is the WordPress.org version with no premium features

// Include required files
require_once DDMANAGER_PLUGIN_DIR . 'includes/class-document-download-manager.php';
require_once DDMANAGER_PLUGIN_DIR . 'includes/class-document-download-manager-admin.php';
require_once DDMANAGER_PLUGIN_DIR . 'includes/class-document-download-manager-public.php';
// Premium class is not included in the free version for WordPress.org compliance

/**
 * Helper function to get the upgrade URL
 * This ensures the function is available even if the premium class is not loaded
 *
 * @return string The URL to upgrade to the premium version
 */
function ddmanager_get_upgrade_url() {
    return 'https://checkout.freemius.com/plugin/19168/plan/31773/';
}

// Backward compatibility function
if (!function_exists('docdownman_get_upgrade_url')) {
    function docdownman_get_upgrade_url() {
        return ddmanager_get_upgrade_url();
    }
}

// Register activation and deactivation hooks
register_activation_hook(__FILE__, array('Document_Download_Manager', 'activate'));
register_deactivation_hook(__FILE__, array('Document_Download_Manager', 'deactivate'));
// Note: Uninstall is handled by uninstall.php

// Initialize the plugin with more unique prefix
function ddmanager_initialize() {
    $plugin = new Document_Download_Manager();
    $plugin->run();
}

// Backward compatibility function
if (!function_exists('document_download_manager_initialize')) {
    function document_download_manager_initialize() {
        ddmanager_initialize();
    }
}

// Run the plugin
ddmanager_initialize();
