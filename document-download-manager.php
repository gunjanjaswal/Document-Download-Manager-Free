<?php
/**
 * Plugin Name: Document Download Manager With Mailchimp (Free)
 * Plugin URI: https://gunjanjaswal.me/plugins/document-download-manager-with-mailchimp
 * Description: Manage Excel and PDF document downloads with user information collection via popup form. Pro version includes Mailchimp integration for email marketing.
 * Version: 1.0.0
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * Author: Gunjan Jaswaal
 * Author URI: https://gunjanjaswal.me
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

// Define plugin constants
define('DDM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DDM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DDM_VERSION', '1.0.0');

// WordPress.org version - no premium features
define('DDM_IS_PREMIUM', false);

// Include required files
require_once DDM_PLUGIN_DIR . 'includes/class-document-download-manager-premium.php';
require_once DDM_PLUGIN_DIR . 'includes/class-document-download-manager.php';
require_once DDM_PLUGIN_DIR . 'includes/class-document-download-manager-admin.php';
require_once DDM_PLUGIN_DIR . 'includes/class-document-download-manager-public.php';

// Register activation, deactivation, and uninstall hooks
register_activation_hook(__FILE__, array('Document_Download_Manager', 'activate'));
register_deactivation_hook(__FILE__, array('Document_Download_Manager', 'deactivate'));
register_uninstall_hook(__FILE__, array('Document_Download_Manager', 'uninstall'));

// Initialize the plugin
function run_document_download_manager() {
    $plugin = new Document_Download_Manager();
    $plugin->run();
}
run_document_download_manager();
