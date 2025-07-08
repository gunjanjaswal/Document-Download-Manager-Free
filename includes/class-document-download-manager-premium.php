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



// The upgrade URL function is defined in the main plugin file



/**
 * Check if a specific premium feature is available
 * Always returns false in the WordPress.org version
 */
function docdownman_has_feature($feature) {
    return false;
}


