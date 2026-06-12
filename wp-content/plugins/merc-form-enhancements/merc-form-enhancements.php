<?php
/**
 * Plugin Name: DHV Courier – Form Enhancements
 * Plugin URI:  https://dhvcourier.com
 * Description: Mejoras al formulario de shipment: guardar tipo_envio, auto-completar remitente, filtrar estados por tipo, ocultar campos por rol y validación de bloqueo.
 * Version:     1.5.3
 * Author:      DHV Courier
 * Text Domain: merc-form-enhancements
 * Requires PHP: 8.1
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'MERC_FORM_VERSION', '1.5.3' );
define( 'MERC_FORM_PATH',    plugin_dir_path( __FILE__ ) );
define( 'MERC_FORM_URL',     plugin_dir_url( __FILE__ ) );

require_once MERC_FORM_PATH . 'includes/functions.php';
require_once MERC_FORM_PATH . 'admin/classes/class-shipment-form-fields.php';
require_once MERC_FORM_PATH . 'admin/classes/class-tipo-envio-saver.php';
require_once MERC_FORM_PATH . 'admin/classes/class-form-autofill.php';
require_once MERC_FORM_PATH . 'admin/classes/class-status-filter.php';
require_once MERC_FORM_PATH . 'admin/classes/class-container-assign.php';
require_once MERC_FORM_PATH . 'admin/classes/class-client-autofill.php';

