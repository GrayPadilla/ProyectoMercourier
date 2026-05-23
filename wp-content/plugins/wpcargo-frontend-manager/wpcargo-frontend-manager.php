<?php
/*
 * Plugin Name: WPCargo Frontend Manager
 * Plugin URI: https://www.wpcargo.com/product/wpcargo-frontend-manager/
 * Description: WPCargo Frontend Manager provides user dashboard in the frontend of your site and mobile responsive ready. Available shortcode [wpcfe_registration]
 * Author: <a href="http://www.wptaskforce.com/">WPTaskForce</a>
 * Text Domain: wpcargo-frontend-manager
 * Domain Path: /languages
 * Version: 5.8.8
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
//* Defined constant
define( 'WPCFE_FILE', __FILE__ );
define( 'WPCFE_URL', plugin_dir_url( WPCFE_FILE ) );
define( 'WPCFE_PATH', plugin_dir_path( WPCFE_FILE ) );
define( 'WPCFE_TEXTDOMAIN', 'wpcargo-frontend-manager' );
define( 'WPCFE_VERSION', '5.8.8' );
define( 'WPCFE_DB_VERSION', '1.0.1' );
define( 'WPCFE_HOME_URL', home_url() );
define( 'WPCFE_BASENAME', plugin_basename( WPCFE_FILE ) );
define( 'WPCFE_DB_REPORTS',  'wpcfe_reports' );


$phpversion =floatval(phpversion());
$phpversion =floor($phpversion);
if($phpversion ==8){
define( 'WPCFE_UPDATE_REMOTE', 'updates-8.1'  );
}else{
define( 'WPCFE_UPDATE_REMOTE', 'updates-7.2'  );
}

require_once( WPCFE_PATH.'admin/includes/functions.php');
require_once( WPCFE_PATH.'admin/includes/widgets.php');
require_once( WPCFE_PATH.'admin/includes/language.php');
require_once( WPCFE_PATH.'admin/includes/pdf-helper.php');
require_once( WPCFE_PATH.'admin/includes/country-list.php');
require_once( WPCFE_PATH.'admin/includes/map.php');
require_once( WPCFE_PATH.'admin/includes/hooks.php');
require_once( WPCFE_PATH.'admin/includes/hooks-shipment.php');
require_once( WPCFE_PATH.'admin/includes/print-hooks.php');
require_once( WPCFE_PATH.'admin/includes/ajax.php');
require_once( WPCFE_PATH.'admin/includes/settings.php');
require_once( WPCFE_PATH.'admin/classes/class-core.php');
require_once( WPCFE_PATH.'admin/classes/class-menus.php');
require_once( WPCFE_PATH.'admin/classes/class-scripts.php');
require_once( WPCFE_PATH.'admin/classes/class-pages.php' );
require_once( WPCFE_PATH.'admin/classes/class-print.php' );
require_once( WPCFE_PATH.'admin/classes/database.php' );
//** Load Plugin text domain
add_action( 'init', 'wpc_frontend_manager_load_textdomain' );
function wpc_frontend_manager_load_textdomain() {
	load_plugin_textdomain( 'wpcargo-frontend-manager', false, '/wpcargo-frontend-manager/languages' );
}
add_action( 'plugins_loaded', 'wpcfe_generate_report_dbtable' );
function wpcfe_generate_report_dbtable(){
    $WPCFE_DATABASE = new WPCFE_DATABASE;
    $WPCFE_DATABASE->create_report(); 
}
//** Create track page
register_activation_hook( WPCFE_FILE, array( 'WPCFE_Admin', 'add_wpcfe_custom_pages' ) );
register_activation_hook(WPCFE_FILE, array( 'WPCFE_DATABASE', 'create_report' ) );
register_activation_hook( __FILE__, array( 'WPC_Custom_ForgotPassword', 'plugin_activated' ) );
add_action( 'upgrader_process_complete', function( $upgrader_object, $options ) {
    if ($options['action'] == 'update' && $options['type'] == 'plugin' ) {
        if( !array_key_exists( 'plugins', $options ) ){
            return false;
        }
        foreach($options['plugins'] as $each_plugin) {
            if ( $each_plugin == WPCFE_BASENAME ) {
                wpcfe_generate_report_dbtable();
                break;
            }
        }
    }
}, 10, 2 );
// Create new custom Role
register_activation_hook( __FILE__, 'wpcfe_regroles_on_plugin_activation' );
register_deactivation_hook( __FILE__, 'wpcfe_regroles_on_plugin_deactivation' );
add_action( 'plugins_loaded', 'wpcfe_regroles_on_plugin_update' );