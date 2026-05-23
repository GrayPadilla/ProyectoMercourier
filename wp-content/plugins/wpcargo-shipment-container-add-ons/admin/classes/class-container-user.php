<?php
if (!defined('ABSPATH')){
    exit; // Exit if accessed directly
}
class WPCargo_Container_User{
	public $text_domain = 'wpcargo-shipment-container';
	
	function __construct(){
		register_activation_hook( WPCARGO_SHIPMENT_CONTAINER_FILE , array( $this, 'add_roles_on_plugin_activation' ) );
		register_deactivation_hook( WPCARGO_SHIPMENT_CONTAINER_FILE , array( $this, 'remove_roles_on_plugin_deactivation' ) );
	}
	
	function add_roles_on_plugin_activation() {
		add_role( 
			'delivery_agent', 
			__('Delivery Agent', 'wpcargo-shipment-container' ), 
			array( 'read' => true, 'level_0' => true )
		);
	}
	function remove_roles_on_plugin_deactivation(){
		remove_role( 'delivery_agent' );
	}
	
	// Register Container Post Type	
	
}
new WPCargo_Container_User;