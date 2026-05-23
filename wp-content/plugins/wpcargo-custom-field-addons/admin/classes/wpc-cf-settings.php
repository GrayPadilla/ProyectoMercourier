<?php
if (!defined('ABSPATH')){
    exit; // Exit if accessed directly
}
class WPC_CF_Settings{
	public $text_domain = 'wpcargo-custom-field';
	public function __construct(){
		add_action('admin_menu', array( $this, 'register_custom_field_submenu_page' ), 21 );
		add_action( 'admin_init', array( $this, 'register_custom_field_settings') );
	}
	public function register_custom_field_submenu_page() {
		add_submenu_page(
			'options.php',
			__( 'Custom Field Setting', 'wpcargo-custom-field' ),
			__( 'Custom Field Setting', 'wpcargo-custom-field' ),
			'manage_options',
			'wpc-custom-field-settings',
			array( $this, 'register_custom_field_submenu_page_callback' )
		);
		add_submenu_page( 
			'wpcargo-settings',
			__( 'CF Setting', 'wpcargo-custom-field' ),
			__( 'CF Setting', 'wpcargo-custom-field' ),
			'manage_options',
			'admin.php?page=wpc-custom-field-settings'
		);
	}
	public function register_custom_field_submenu_page_callback(){
		$options = get_option('wpcargo_cf_option_settings');
		$additional_sections    = '';
        if( !empty( $options ) ){
            if( array_key_exists( 'wpc_cf_additional_options', $options )){
                $additional_sections    = $options['wpc_cf_additional_options'];
            }
        }
		ob_start();
		?>
        <div class="wrap">
        	<h1><?php esc_html_e('Custom Field Settings', 'wpcargo-custom-field' ); ?></h1>
            <?php require_once( WPCARGO_CUSTOM_FIELD_PATH.'../wpcargo/admin/templates/admin-navigation.tpl.php'); ?>
            <?php require_once( WPCARGO_CUSTOM_FIELD_PATH.'admin/templates/wpc-cf-settings.tpl.php'); ?>
        </div>
        <?php
		echo ob_get_clean();
	}
	function register_custom_field_settings() {
		//register our settings
		register_setting( 'wpcargo_custom_field_settings_group', 'wpcargo_cf_option_settings' );
		register_setting( 'wpcargo_custom_field_settings_group', 'wpcargo_cf_label_settings' );
	}
}
new WPC_CF_Settings;

//#########################################################//
//## LICENSE CHECKER CODES GOES HERE #####################//
//########################################################//

function wpcargo_cf_reset_u2nBYFt5AQEJPPnUN6qUrYN( $plugin = '', $basename = '' ){
	//# Check if WPCargo Custom Fields license is active or expired
	define('WPCARGO_SERVER_URL_REFRESH', 'http://www.wpcargo.com/');
	define('WPCARGO_ITEM_REFERENCE_REFRESH', 'WPCargo');
	define('WPCARGO_SECRET_KEY_REFRESH', '55935b98777223.709891899');
	
	$wpcargo_plugin_options = get_option( $basename );
	$domain = $_SERVER['SERVER_NAME'];

	$api_params = array(
		'slm_action' => 'slm_check',
		'secret_key' => WPCARGO_SECRET_KEY_REFRESH,
		'license_key' => $wpcargo_plugin_options,
		'product' => $plugin,
		'registered_domain' => $domain, 
		'item_reference' => urlencode( WPCARGO_ITEM_REFERENCE_REFRESH )
	); 

	$response   = wp_remote_get(add_query_arg($api_params, WPCARGO_SERVER_URL_REFRESH), array(
		'timeout' => 5,
		'sslverify' => false,
	));
	
	if (is_wp_error($response)){
		esc_html_e("Unexpected Error! The query returned with an error.", 'wptaskforce-license-helper');
	}
	
	$license_data = json_decode(wp_remote_retrieve_body($response));

	return $license_data;

}

function is_domain( $domain_list ){
	$domain = $_SERVER['SERVER_NAME'];
	$rcd_domains = array();

	foreach( $domain_list as $count => $list ){
		$rcd_domains[] = $list->registered_domain;
	}

	if( in_array($domain, $rcd_domains) ){
		return true;
	}else{
		return false;
	}
}


function check_license(){
	// ==========================================
	// BYPASS: License verification disabled
	// Modified on 2025-10-20 to allow usage without license restrictions
	// Original code preserved in BACKUP_ORIGINAL_2025-10-20/
	// ==========================================
	return true;

	/*
	// ORIGINAL CODE - DISABLED
	$response = wpcargo_cf_reset_u2nBYFt5AQEJPPnUN6qUrYN( 'WPCargo Custom Field Add-ons', WPCARGO_CUSTOM_FIELD_BASENAME );
	$domain_list = $response->registered_domains;
	$is_domain = is_domain( $domain_list);

	// # Check if license is active
	if( !empty( $response->result && $response->result == 'success' ) ){
		// check if domain is equal
		if( !$is_domain ){
			// remove license
			delete_option( WPCARGO_CUSTOM_FIELD_BASENAME );

			//deactivate plugin
			deactivate_plugins( WPCARGO_CUSTOM_FIELD_BASENAME );
		}

		//# checked if license is active, pending, blocked or expired
		//#Add Admin notice if expired
		if( $response->status == 'expired'){
			add_action( 'admin_notices', 'wptf_expired_license' );

		}elseif( $response->status == 'pending' || $response->status == 'blocked' ){
			// remove license
			delete_option( WPCARGO_CUSTOM_FIELD_BASENAME );

			//deactivate plugin
			deactivate_plugins( WPCARGO_CUSTOM_FIELD_BASENAME );
		}
	}
	*/
}


function wptf_expired_license() {
	$class = 'notice notice-error';
	$message = __( 'Your License Key has expired. Please contact support or login to your WPCargo Account and ', WPCARGO_CUSTOM_FIELD_TEXTDOMAIN );
	$renew = __( 'RENEW', WPCARGO_CUSTOM_FIELD_TEXTDOMAIN );

	printf( '<div class="%1$s"><p>%2$s <a href = "https://www.wpcargo.com/my-account" target = __"blank">%3$s</a></p></div>', esc_attr( $class ), esc_html( $message ), $renew ); 
}


function run_checker(){
	// ==========================================
	// BYPASS: License checker disabled
	// Modified on 2025-10-20 to allow usage without license restrictions
	// ==========================================
	return true;

	/*
	// ORIGINAL CODE - DISABLED
	$license_author  	= 'wptaskforce';
	$active_plugins  	= get_option('active_plugins');
	$all_plugin 		= get_plugins();
	foreach( $all_plugin as $plugin => $plugin_details ) {
		if( in_array( $plugin, $active_plugins  ) &&  $plugin == WPCARGO_CUSTOM_FIELD_BASENAME && get_option(WPCARGO_CUSTOM_FIELD_BASENAME) ) {
			check_license();
		}
	}
	*/
}

// ==========================================
// BYPASS: Admin init hook disabled to prevent license checks
// Modified on 2025-10-20
// ==========================================
// add_action('admin_init', 'run_checker'); // DISABLED - License check bypassed