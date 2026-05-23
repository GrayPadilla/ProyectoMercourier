<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
/*
 * Language Translation for the Ecncrypted Files
 */
function wpc_frontend_manager_activate_license_message(){
	return __( 'Please activate your license key for WPCargo Frontend Manager', 'wpcargo-frontend-manager').' <a href="'.admin_url().'admin.php?page=wptaskforce-helper" title="WPCargo Frontend Manager">here</a>.';
}
function wpc_frontend_manager_no_dashbaord_message(){
	return sprintf( '<p><strong>%s</strong></p> <p><a href="'.admin_url().'admin.php?page=wpcfe-settings" title="%s" class="button button-secondary button-small">%s</a></p>', __('Warning: Frontend Manager Dashboard found, please set up.', 'wpcargo-frontend-manager' ), __( 'Frontend Manager Dashboard', 'wpcargo-frontend-manager'), __( 'Set up Dashboard', 'wpcargo-frontend-manager')  );
}
function wpc_frontend_manager_license_helper_plugin_dependent_message(){
	return __('This plugin requires <a href="http://wpcargo.com/" target="_blank">WPTaskForce License Helper</a> plugin to be active!', 'wpcargo-frontend-manager' );
}
function wpc_frontend_manager_wpcargo_plugin_dependent_message(){
	return __( 'This plugin requires <a href="https://wordpress.org/plugins/wpcargo/" target="_blank">WPCargo</a> plugin to be active!', 'wpcargo-frontend-manager' );
}
function wpc_frontend_manager_custom_field_plugin_dependent_message(){
	return __( 'This plugin requires <strong>WPCargo Custom Field Add-ons</strong> plugin to be active!', 'wpcargo-frontend-manager' );
}
function wpc_frontend_manager_cheating_plugin_dependent_message(){
	return __( 'Cheating, uh?', 'wpcargo-frontend-manager' );
}
function wpc_frontend_dashboard_settings_label(){
    return __('Frontend Dashboard Settings', 'wpcargo-frontend-manager' );
}
function wpc_frontend_dashboard_label(){
    return __( 'Frontend Dashboard', 'wpcargo-frontend-manager' );
}
function wpc_frontend_dashboard_side_menu_label(){
    return __( 'WPCargo Dashboard Sidebar Menu', 'wpcargo-frontend-manager' );
}
function wpc_frontend_dashboard_top_menu_label(){
    return __( 'WPCargo Dashboard Top Menu', 'wpcargo-frontend-manager' );
}
function wpcfe_no_order_message(){
    return __( 'No order has been made.', 'wpcargo-frontend-manager' );
}
function wpcfe_registraion_success_message(){
    return __('You have been successfully registered.', 'wpcargo-frontend-manager' );
}
function wpcfe_registraion_success_message_approval(){
    return __("You have been successfully registered. We'll check your account for approval and send you notification in your registered email with registration status.", 'wpcargo-frontend-manager' );
}
function wpcfe_registraion_error_message(){
    return __('Something went wrong in your registration. Please reload and try again', 'wpcargo-frontend-manager' );
}
function wpcfe_permission_error_message(){
    return __( 'You do not have permissions to view this data.', 'wpcargo-frontend-manager' );
}
function wpcfe_remove_license_error_message(){
    return sprintf( '%s : '.get_bloginfo('name'), __( 'Removing License failed, License not registered in domain', 'wpcargo-frontend-manager' ) );
}
function wpcfe_remove_license_success_message(){
    return sprintf( '%s : '.get_bloginfo('name'), __( 'License has been successfully removed from', 'wpcargo-frontend-manager' ) );
}
function wpcfe_has_wpml(){
	$return = false;
	if ( in_array( 'sitepress-multilingual-cms/sitepress.php', get_option( 'active_plugins' ) ) ) {
		$return = true;
	}
	return $return;
}
function wpcfe_wpml_languages(){
	$languages = array();
	if( wpcfe_has_wpml() ){
		$languages = icl_get_languages('skip_missing=0&orderby=KEY&order=DIR&link_empty_to=str');
	}
	return $languages;
}