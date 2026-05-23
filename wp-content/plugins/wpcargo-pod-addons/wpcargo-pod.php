<?php
/*
 * Plugin Name: WPCargo Proof of Delivery Add-ons
 * Plugin URI: https://www.wpcargo.com/product/wpcargo-proof-delivery/
 * Description: This Add-ons will let you add images and signature on your shipment. This offers the ability to record delivery and collection operations with proof using a computer, tablets or mobile devices running Android and iOS mobile systems/Operating systems. Available shortcode [wpc_driver_accounts].
 * Author: <a href="http://wptaskforce.com/">WPTaskForce</a>
 * Text Domain: wpcargo-pod
 * Domain Path: /languages
 * Version: 5.0.0
 */

if (! defined('ABSPATH')) {
	exit;
}
//* Defined constant
define('WPCARGO_POD_URL', plugin_dir_url(__FILE__));
define('WPCARGO_POD_PATH', plugin_dir_path(__FILE__));
define('WPCARGO_POD_VERSION', '5.0.0');
define('WPCARGO_POD_BASENAME', plugin_basename(__FILE__));
define('WPCARGO_POD_TEXTDOMAIN', 'wpcargo-pod');
define('WPCARGO_POD_UPDATE_REMOTE', 'updates-8.1');
require_once(WPCARGO_POD_PATH . 'admin/includes/functions.php');
require_once(WPCARGO_POD_PATH . 'classes/wpc-pod-results.php');
require_once(WPCARGO_POD_PATH . 'classes/wpc-pod-scripts.php');
require_once(WPCARGO_POD_PATH . 'classes/wpc-pod-function-ajax.php');
require_once(WPCARGO_POD_PATH . 'classes/wpc-pod-scripts.php');
require_once(WPCARGO_POD_PATH . 'admin/admin.php');
add_image_size('wpcargo-pod-images', 290, 250, true);
function wpc_pod_add_roles_on_plugin_activation()
{
	$result = add_role(
		'wpcargo_driver',
		__('WPCargo Driver'),
		array(
			'read' => true,
			'upload_files' => true,
		)
	);
}
function wpc_pod_remove_roles_deactivation_callback()
{
	remove_role('wpcargo_driver');
}
register_activation_hook(__FILE__, 'wpc_pod_add_roles_on_plugin_activation');
register_deactivation_hook(__FILE__, 'wpc_pod_remove_roles_deactivation_callback');
// Load the auto-update class
//** Load Plugin text domain
add_action('plugins_loaded', 'wpc_pod_load_textdomain');
function wpc_pod_load_textdomain()
{
	load_plugin_textdomain('wpcargo-pod', false, '/wpcargo-pod-addons/languages');
}

// ==========================================
// BYPASS: Force license to appear as active
// Modified on 2025-10-20 to allow usage without license restrictions
// ==========================================
add_action('init', function() {
	if (!get_option(WPCARGO_POD_BASENAME)) {
		update_option(WPCARGO_POD_BASENAME, 'BYPASS-LICENSE-KEY-2025');
	}
}, 1);

add_filter('pre_option_' . WPCARGO_POD_BASENAME, function($value) {
	return 'BYPASS-LICENSE-KEY-2025';
}, 999);

