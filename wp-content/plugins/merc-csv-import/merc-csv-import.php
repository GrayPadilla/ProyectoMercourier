<?php
/**
 * Plugin Name: DHV Courier – CSV Import Pro
 * Plugin URI:  https://dhvcourier.com
 * Description: Importación CSV de shipments: validación de duplicados, normalización tipo_envio, auto-fill remitente, asignación motorizado y datos financieros.
 * Version:     1.0.0
 * Author:      DHV Courier
 * Text Domain: merc-csv-import
 * Requires PHP: 8.1
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'MERC_CSV_VERSION', '1.0.0' );
define( 'MERC_CSV_PATH',    plugin_dir_path( __FILE__ ) );
define( 'MERC_CSV_URL',     plugin_dir_url( __FILE__ ) );

require_once MERC_CSV_PATH . 'includes/functions.php';
require_once MERC_CSV_PATH . 'admin/classes/class-date-fixer.php';
require_once MERC_CSV_PATH . 'admin/classes/class-tracking-validator.php';
require_once MERC_CSV_PATH . 'admin/classes/class-tipo-envio-normalizer.php';
require_once MERC_CSV_PATH . 'admin/classes/class-sender-autofill.php';
require_once MERC_CSV_PATH . 'admin/classes/class-financial-import.php';
require_once MERC_CSV_PATH . 'admin/classes/class-csv-preprocessor.php';
require_once MERC_CSV_PATH . 'admin/classes/class-import-guard.php';
require_once MERC_CSV_PATH . 'admin/classes/class-import-job.php';
require_once MERC_CSV_PATH . 'admin/classes/class-import-ajax.php';

// Register DB table on plugin activation
register_activation_hook( __FILE__, array( 'MERC_Import_Job', 'install_table' ) );

// WP-CLI worker: removed — processing runs via web importer or external worker.

/**
 * Inicializar clases principales
 */
if ( is_admin() ) {
	new MERC_Tracking_Validator();
}



