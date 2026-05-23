<?php
/**
 * Plugin Name:       WPTaskForce License Helper
 * Plugin URI:        http://www.wpcargo.com/
 * Description:       This is to help you activate and deactivate your WPCargo addons license key  in your wordpress website.
 * Version:           5.1.0
 * Author:            WPCargo
 * Author URI:        http://www.wptaskforce.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wptaskforce-license-helper
 * Domain Path:       /languages
 */
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
define( 'WPCARGO_LICENSING_FILE', __FILE__ );
define( 'WPCARGO_LICENSING_BASENAME', plugin_basename( WPCARGO_LICENSING_FILE ) );
define( 'WPCARGO_LICENSING_URL', plugin_dir_url( WPCARGO_LICENSING_FILE ) );
define( 'WPCARGO_LICENSING_PATH', plugin_dir_path( WPCARGO_LICENSING_FILE ) );
define( 'WPCARGO_LICENSING_INC_PATH', plugin_dir_path( WPCARGO_LICENSING_FILE ) . 'includes' );
define( 'WPCARGO_LICENSING_ADMIN_URL', plugin_dir_url( WPCARGO_LICENSING_FILE ).'admin' );
define( 'WPCARGO_LICENSING_ADMIN_PATH', plugin_dir_path( WPCARGO_LICENSING_FILE ).'admin' );

$phpversion =floatval(phpversion());
$phpversion =floor($phpversion);

if($phpversion ==8){
define( 'WPC_LICENSING_UPDATE_REMOTE', 'updates-8.1'  );
}else{
define( 'WPC_LICENSING_UPDATE_REMOTE', 'updates-7.2'  );
}

    
function wptaskforce_license_helper_load_textdomain() {
   load_plugin_textdomain( 'wptaskforce-license-helper', false, dirname( WPCARGO_LICENSING_BASENAME ) . '/languages' ); 
}
add_action( 'plugins_loaded', 'wptaskforce_license_helper_load_textdomain' );
// Respository Update
function wptaskforce_license_helper_activate_au(){
    $disable_update = apply_filters( 'disable_update_l', true );
    if($disable_update==false){

        if( !function_exists('get_plugin_data') ){
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }
        $data = get_plugin_data( WPCARGO_LICENSING_FILE );
        require_once( WPCARGO_LICENSING_PATH.'admin/wp_autoupdate.php');
        $plugin_remote_path = 'https://wpcargo.com/repository/wptaskforce-license-helper/'.WPC_LICENSING_UPDATE_REMOTE.'.php';
        new WPCargo_License_Helper_AutoUpdate ( $data['Version'], $plugin_remote_path, WPCARGO_LICENSING_BASENAME );
    }
}
add_action( 'admin_init', 'wptaskforce_license_helper_activate_au' );

require_once( WPCARGO_LICENSING_ADMIN_PATH. '/functions.php' );
function register_wpcargo_license_helper_page(){
	add_submenu_page( 'wpcargo-settings', 'WPCargo License Helper', 'License Helper', 'manage_options', 'wptaskforce-helper', 'wpcargo_license_helper_page'); 
}
add_action('admin_menu','register_wpcargo_license_helper_page', 999);