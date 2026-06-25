<?php
/**
 * Plugin Name: WPCargo Access Control
 * Plugin URI: https://mercourier.com/
 * Description: Gestión centralizada de permisos y acceso por rol/email para WPCargo
 * Version: 1.0.0
 * Author: Mercourier
 * Author URI: https://mercourier.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wpcargo-access-control
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 *
 * @package WPCargo_Access_Control
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WPCAC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPCAC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPCAC_VERSION', '1.0.0');

/**
 * Load plugin files
 */
function wpcac_load_plugin() {
    require_once WPCAC_PLUGIN_DIR . 'includes/role-filters.php';
    require_once WPCAC_PLUGIN_DIR . 'includes/unlock-system.php';
    require_once WPCAC_PLUGIN_DIR . 'admin/unlock-page.php';
}

// Load on plugins_loaded hook
add_action('plugins_loaded', 'wpcac_load_plugin');

/**
 * Activation hook
 */
function wpcac_activate() {
    // Flush rewrite rules
    flush_rewrite_rules();
    
    error_log('✅ WPCargo Access Control activated');
}

register_activation_hook(__FILE__, 'wpcac_activate');

/**
 * Deactivation hook
 */
function wpcac_deactivate() {
    // Clean up
    flush_rewrite_rules();
    error_log('❌ WPCargo Access Control deactivated');
}

register_deactivation_hook(__FILE__, 'wpcac_deactivate');

/**
 * Uninstall hook
 */
function wpcac_uninstall() {
    // Delete all plugin options
    delete_option('merc_skip_blocks_today');
    delete_option('merc_skip_blocks_mode');
    
    // Clean user meta
    $users = get_users(array('fields' => 'ID', 'number' => -1));
    foreach ($users as $user_id) {
        delete_user_meta($user_id, 'merc_desbloqueado_manualmente_fecha');
        delete_user_meta($user_id, 'merc_desbloqueo_manual_envios_permitidos');
    }
    
    error_log('🗑️ WPCargo Access Control uninstalled and cleaned');
}

register_uninstall_hook(__FILE__, 'wpcac_uninstall');

