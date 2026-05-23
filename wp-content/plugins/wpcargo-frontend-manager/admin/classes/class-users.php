<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
class WPCFE_Users{
	public static function add_user_role() {
		// Add User Role
		add_role(
			'wpcargo_employee', 
			apply_filters( 'wpcfe_eployee_role_name', __('WPCargo Employee', 'wpcargo-frontend-manager') ), 
			array()
		);
     }
	public static function remove_user_role() {
		// Remove User Role
		remove_role( 'wpcargo_employee' );
     }
}