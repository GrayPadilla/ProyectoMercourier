<?php
/**
 * Plugin Name: Merc Finance
 * Plugin URI: https://mercourier.com
 * Description: Módulo de finanzas, liquidaciones, pagos y penalidades
 * Version: 1.0.0
 * Author: Mercourier
 * License: Proprietary
 * Text Domain: merc-finance
 */

if (!defined('ABSPATH')) exit;

define('MERC_FINANCE_FILE', __FILE__);
define('MERC_FINANCE_DIR', plugin_dir_path(__FILE__));
define('MERC_FINANCE_URL', plugin_dir_url(__FILE__));

// Cargar archivos
require_once MERC_FINANCE_DIR . 'includes/post-types.php';
require_once MERC_FINANCE_DIR . 'includes/payments.php';
require_once MERC_FINANCE_DIR . 'includes/penalties.php';
require_once MERC_FINANCE_DIR . 'includes/ajax.php';
require_once MERC_FINANCE_DIR . 'includes/hooks.php';
require_once MERC_FINANCE_DIR . 'includes/frontend.php';

register_activation_hook(__FILE__, function() {
    error_log('[MERC FINANCE] Plugin activado');
    // Registrar post types
    do_action('merc_finance_activate');
});

register_deactivation_hook(__FILE__, function() {
    error_log('[MERC FINANCE] Plugin desactivado');
});

