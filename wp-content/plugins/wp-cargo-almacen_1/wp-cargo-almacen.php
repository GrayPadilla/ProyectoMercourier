<?php
/**
 * Plugin Name: WP Cargo Almacén
 * Plugin URI:  https://dhvcourier.com
 * Description: Gestión de almacén (entradas, salidas, stock) para DHV Courier.
 * Version:     1.0.0
 * Author:      DHV Courier
 * Text Domain: wp-cargo-almacen
 * Requires PHP: 8.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WPCA_VERSION',  '1.0.0' );
define( 'WPCA_PATH',     plugin_dir_path( __FILE__ ) );
define( 'WPCA_URL',      plugin_dir_url( __FILE__ ) );
define( 'WPCA_BASENAME', plugin_basename( __FILE__ ) );

require_once WPCA_PATH . 'includes/functions.php';
require_once WPCA_PATH . 'admin/classes/class-database.php';
require_once WPCA_PATH . 'admin/classes/class-producto.php';
require_once WPCA_PATH . 'admin/classes/class-movimiento.php';
require_once WPCA_PATH . 'admin/classes/class-admin.php';
require_once WPCA_PATH . 'admin/classes/class-frontend.php';

register_activation_hook( __FILE__, 'wpca_activar' );

function wpca_activar(): void {
    WPCA_Database::crear_tablas();
    wpca_get_frontend_page_id();
}
