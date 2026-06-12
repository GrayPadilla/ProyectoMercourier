<?php
/**
 * Plugin Name: Merc Returns
 * Plugin URI: https://mercourier.com
 * Description: Módulo de gestión de devoluciones, cambios de producto y estado de entregas
 * Version: 1.0.0
 * Author: Mercourier
 * Author URI: https://mercourier.com
 * License: Proprietary
 * Text Domain: merc-returns
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('MERC_RETURNS_FILE', __FILE__);
define('MERC_RETURNS_DIR', plugin_dir_path(__FILE__));
define('MERC_RETURNS_URL', plugin_dir_url(__FILE__));
define('MERC_RETURNS_BASENAME', plugin_basename(__FILE__));

// Cargar archivos requeridos
require_once MERC_RETURNS_DIR . 'includes/helpers.php';
require_once MERC_RETURNS_DIR . 'includes/shortcodes.php';
require_once MERC_RETURNS_DIR . 'includes/ajax.php';
require_once MERC_RETURNS_DIR . 'includes/hooks.php';
require_once MERC_RETURNS_DIR . 'includes/reprogramacion.php';

/**
 * Enqueuear scripts y estilos
 */
add_action('wp_enqueue_scripts', 'merc_returns_enqueue_scripts');
add_action('admin_enqueue_scripts', 'merc_returns_enqueue_scripts');
function merc_returns_enqueue_scripts() {
    // Cargar CSS de reprogramación
    wp_enqueue_style(
        'merc-returns-reprogramacion',
        MERC_RETURNS_URL . 'assets/reprogramacion.css',
        array(),
        filemtime(MERC_RETURNS_DIR . 'assets/reprogramacion.css')
    );
    
    // Inyectar la variable ANTES del script para que esté disponible cuando se ejecute
    wp_add_inline_script('jquery', "window.mercReturnsConfig = " . wp_json_encode(array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
    )) . ";", 'before');
    
    // Cargar script en footer
    wp_enqueue_script(
        'merc-returns',
        MERC_RETURNS_URL . 'assets/scripts.js',
        array('jquery'),
        filemtime(MERC_RETURNS_DIR . 'assets/scripts.js'),
        true // ← En footer, después de que jQuery (con la variable) esté cargado
    );
    
    // Script de reprogramación
    wp_enqueue_script(
        'merc-reprogramacion-js',
        MERC_RETURNS_URL . 'assets/reprogramacion.js',
        array('jquery'),
        filemtime(MERC_RETURNS_DIR . 'assets/reprogramacion.js'),
        true
    );
    
    // Cargar estilos de reprogramación
    wp_enqueue_style(
        'merc-reprogramacion',
        MERC_RETURNS_URL . 'assets/reprogramacion.css',
        array(),
        filemtime(MERC_RETURNS_DIR . 'assets/reprogramacion.css')
    );
}

/**
 * Hook de activación
 */
register_activation_hook(__FILE__, function() {
    error_log('[MERC RETURNS] Plugin activado');
});

/**
 * Hook de desactivación
 */
register_deactivation_hook(__FILE__, function() {
    error_log('[MERC RETURNS] Plugin desactivado');
});

