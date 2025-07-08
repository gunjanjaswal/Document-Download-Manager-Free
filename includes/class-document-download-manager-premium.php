<?php
/**
 * Compatibility functions for the WordPress.org version
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * These functions provide compatibility for code that might check for premium features
 * They always return false since this is the free version
 */

/**
 * Check if premium features are available
 * Always returns false in the WordPress.org version
 */
function docdownman_is_premium() {
    return false;
}



/**
 * Get upgrade URL
 * Returns the URL to the premium version checkout page
 */
function docdownman_get_upgrade_url() {
    return 'https://checkout.freemius.com/plugin/19168/plan/31773/';
}



/**
 * Check if a specific premium feature is available
 * Always returns false in the WordPress.org version
 */
function docdownman_has_feature($feature) {
    return false;
}


