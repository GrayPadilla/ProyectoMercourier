<?php
/**
 * Shared date and bypass helpers.
 *
 * @package WPCargo_Access_Control
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Check if access bypass is enabled for today
 * 
 * @return bool True if bypass is enabled
 */
function wpcac_is_bypass_enabled_today() {
    // Backwards-compatible: if a specific mode is set, respect it
    $mode = get_option('merc_skip_blocks_mode', '');

    // If mode is 'mon-sat', enable bypass on all days except Sunday
    if ($mode === 'mon-sat') {
        $ts = current_time('timestamp');
        $weekday = intval(date('w', $ts)); // 0 = Sunday
        return $weekday !== 0;
    }

    $bypass_date = get_option('merc_skip_blocks_today');
    return $bypass_date === wpcac_get_today();
}

/**
 * Get current date in Y-m-d format
 * 
 * @return string Current date
 */
function wpcac_get_today() {
    return current_time('Y-m-d');
}

