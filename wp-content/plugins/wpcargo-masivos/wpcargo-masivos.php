<?php
/**
 * Plugin Name: WPCargo Envíos Masivos
 * Plugin URI:  https://mercourier.com
 * Description: Carga masiva de envíos con grilla editable tipo Excel, borradores, historial y asignación de usuario.
 * Version:     2.3.5
 * Author:      Mercourier
 * Text Domain: wpcargo-masivos
 * Requires PHP: 8.1
 */
if ( ! defined('ABSPATH') ) exit;

define('WCMAS_VERSION',  '2.3.5');
define('WCMAS_PATH',     plugin_dir_path(__FILE__));
define('WCMAS_URL',      plugin_dir_url(__FILE__));
define('WCMAS_BASENAME', plugin_basename(__FILE__));

require_once WCMAS_PATH . 'includes/functions.php';
require_once WCMAS_PATH . 'includes/class-columnas.php';
require_once WCMAS_PATH . 'includes/class-procesador.php';
require_once WCMAS_PATH . 'includes/class-historial.php';
require_once WCMAS_PATH . 'admin/classes/class-admin.php';
require_once WCMAS_PATH . 'admin/classes/class-frontend.php';

register_activation_hook(__FILE__, 'wcmas_activar');

function wcmas_activar(): void {
    WCMAS_Columnas::instalar_defaults();
    WCMAS_Historial::crear_tabla();
    wcmas_get_frontend_page_id();
    wcmas_instalar_tarifas_default();
    wcmas_instalar_mapa_contenedores(); // Instalar mapa distrito → contenedor
}
