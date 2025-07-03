<?php
/**
 * Plugin Name: Debug Helper
 * Description: Minimal debug plugin to test WordPress functionality
 * Version: 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Enable WordPress debug logging
if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', true);
}
if (!defined('WP_DEBUG_LOG')) {
    define('WP_DEBUG_LOG', true);
}

// Simple test function
add_action('admin_notices', function() {
    echo '<div class="notice notice-info"><p>Debug Plugin Active - WordPress is working</p></div>';
});

// Log any PHP errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && $error['type'] === E_ERROR) {
        error_log('FATAL ERROR: ' . $error['message'] . ' in ' . $error['file'] . ' on line ' . $error['line']);
    }
});
