<?php
/**
 * Premium feature compatibility for WordPress.org version
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Premium feature compatibility class
 * This class provides compatibility functions for the WordPress.org version
 * to replace Freemius functionality
 */
class Document_Download_Manager_Premium {
    
    /**
     * Check if premium features are available
     * Always returns false in the WordPress.org version
     */
    public static function is_premium() {
        return false;
    }
    
    /**
     * Get upgrade URL
     * Returns the URL to the Freemius checkout page for the premium version
     */
    public static function get_upgrade_url() {
        // Use the general plugin URL which will show all available plans
        return 'https://checkout.freemius.com/mode/dialog/plugin/19168/';
    }
    
    /**
     * Check if a specific premium feature is available
     * Always returns false in the WordPress.org version
     */
    public static function has_feature($feature) {
        return false;
    }
}

/**
 * Compatibility function for ddm_fs()->is_paying()
 */
function ddm_is_premium() {
    return Document_Download_Manager_Premium::is_premium();
}

/**
 * Compatibility function for ddm_fs()->get_upgrade_url()
 */
function ddm_get_upgrade_url() {
    return Document_Download_Manager_Premium::get_upgrade_url();
}
