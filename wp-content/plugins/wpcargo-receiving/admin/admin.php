<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/*
 * Language Translation for the Ecncrypted Files
 */
function wpc_receiving_empty_track_number_message(){
	return esc_html__('Empty Tracking Number!', 'wpcargo-receiving');
}
function wpc_receiving_update_success_message(){
	return esc_html__('Shipment Updated Succesfully!', 'wpcargo-receiving');
}
function wpc_receiving_number_not_found_message(){
	return esc_html__('Shipment Not Found!', 'wpcargo-receiving');
}
function wpc_receiving_added_successfully_message(){
	return esc_html__('Shipment Added Succesfully!', 'wpcargo-receiving');
}
function wpc_receiving_activate_license_message(){
	return sprintf(
		'%s <a href="'.admin_url().'admin.php?page=wptaskforce-helper" title="WPCargo license page">%s</a>.',
		__('Please activate your license key', 'wpcargo-receiving'),
		__('here', 'wpcargo-receiving')
	);
}
function wpc_receiving_license_helper_plugin_dependent_message(){
	return sprintf(
		'%s <a href="http://wpcargo.com/" target="_blank">WPTaskForce License Helper</a> %s',
		__('This plugin requires', 'wpcargo-receiving'),
		__('plugin to be active!', 'wpcargo-receiving')
	);
}
function wpc_receiving_frontend_manager_plugin_dependent_message(){
	return sprintf(
		'%s <strong>WPCargo Frontend Manager</strong> %s',
		__('This plugin requires', 'wpcargo-receiving'),
		__('plugin to be active!', 'wpcargo-receiving')
	);
}
function wpc_receiving_frontend_manager_bulk_success_message(){
	return sprintf( 
		'<p><strong>%s</strong> %s</p>',
		__( 'Success!', 'wpcargo-receiving' ),
		__( 'Following shipments were updated successfully.', 'wpcargo-receiving' )
	 );;
}
function wpc_receiving_wpcargo_plugin_dependent_message(){
	return sprintf(
		'%s <a href="https://wordpress.org/plugins/wpcargo/" target="_blank">WPCargo</a> %s',
		__('This plugin requires', 'wpcargo-receiving'),
		__('plugin to be active!', 'wpcargo-receiving')
	);
}
function wpc_receiving_cheating_plugin_dependent_message(){
	return esc_html__('Cheating, uh?', 'wpcargo-receiving');
}
function wpc_receiving_label(){
	return esc_html__('Receiving', 'wpcargo-receiving');
}
// Action Hooks
function wpcr_client_receiving_form_fields(){
	if( !can_wpcfe_assign_client() ){
		return false;
	}
	$wpcargo_client 	= wpcfe_get_users('wpcargo_client');
	if( empty( $wpcargo_client ) ){
		return false;
	}
	?>
	<div class="col-md-6 mb-4 receiving-client">
		<label for="wpsr_registered_shipper"><?php esc_html_e('Client','wpcargo-receiving'); ?></label>
		<select id="wpsr_registered_shipper" name="registered_shipper" class="wpsr-control mdb-select mt-0 form-control browser-default"  >
			<option value=""><?php echo apply_filters( 'wpcsr_select_client_label', __('Select Client', 'wpcargo-receiving' ) ); ?></option>
			<?php foreach( $wpcargo_client as $key => $value ): ?>
				<option value="<?php echo $key; ?>"><?php echo $value; ?></option>
			<?php endforeach; ?>	                
		</select>
	</div>
    <?php
}
function wpcr_agent_receiving_form_fields(){
	if( !can_wpcfe_assign_agent() ){
		return false;
	}
	$wpcargo_agent 		= wpcfe_get_users('cargo_agent');
	if( empty( $wpcargo_agent ) ){
		return false;
	}
	?>
	<div class="col-md-6 mb-4 receiving-agent">
		<label for="wpsr_agent_fields"><?php esc_html_e('Agent','wpcargo-receiving'); ?></label>
		<select id="wpsr_agent_fields" name="agent_fields" class="wpsr-control mdb-select mt-0 form-control browser-default"  >
			<option value=""><?php echo apply_filters( 'wpcsr_select_agent_label', __('Select Agent', 'wpcargo-receiving' ) ); ?></option>
			<?php foreach( $wpcargo_agent as $agentID => $agentName ): ?>
				<option value="<?php echo $agentID; ?>"><?php echo $agentName; ?></option>
			<?php endforeach; ?>		                
		</select>
	</div>
    <?php
}
function wpcr_employee_receiving_form_fields(){
	if( !can_wpcfe_assign_employee() ){
		return false;
	}
	$wpcargo_employee 	= wpcfe_get_users('wpcargo_employee');
	if( empty( $wpcargo_employee ) ){
		return false;
	}
	?>
	<div class="col-md-6 mb-4 receiving-employee">
		<label for="wpsr_employee_fields"><?php esc_html_e('Employee','wpcargo-receiving'); ?></label>
		<select id="wpsr_wpcargo_employee" name="wpcargo_employee" class="wpsr-control mdb-select mt-0 form-control browser-default"  >
			<option value=""><?php echo apply_filters( 'wpcsr_select_employee_label', __('Select employee', 'wpcargo-receiving' ) ); ?></option>
			<?php foreach( $wpcargo_employee as $empID => $empName ): ?>
				<option value="<?php echo $empID; ?>"><?php echo $empName; ?></option>
			<?php endforeach; ?>			                
		</select>
	</div>
    <?php
}
function wpcr_pod_receiving_form_fields(){
	if( !can_wpcfe_assign_driver() ){
		return false;
	}
	if( !class_exists('WPC_POD_Signature_Metabox') ){
		return false;
	}
	$drivers = wpcargo_pod_get_drivers();
	if( empty( $drivers ) ){
		return false;
	}
	?>
	<div class="col-md-6 mb-4 receiving-driver">
		<label for="wpsr_wpcargo_driver"><?php _e('Driver','wpcargo-receiving'); ?></label>
		<select id="wpsr_wpcargo_driver" name="wpcargo_driver" class="form-control browser-default mdb-select wpsr-control" >
			<option value=""><?php echo apply_filters( 'wpcsr_select_driver_label', __('Select Driver', 'wpcargo-receiving' ) ); ?></option>
			<?php foreach( $drivers as $driverID => $driver_name ): ?>
				<option value="<?php echo $driverID; ?>"><?php echo $driver_name; ?></option>
			<?php endforeach; ?>
		</select>
	</div>
	<?php
}
function wpcr_receiving_form_hooks(){
	if( !class_exists('WPCargo_Frontend_Template') ){
		return false;
	}
	add_action( 'wpcr_after_receiving_form_fields', 'wpcr_client_receiving_form_fields' );
	add_action( 'wpcr_after_receiving_form_fields', 'wpcr_agent_receiving_form_fields' );
	add_action( 'wpcr_after_receiving_form_fields', 'wpcr_employee_receiving_form_fields' );
	add_action( 'wpcr_after_receiving_form_fields', 'wpcr_pod_receiving_form_fields' );

}
add_action( 'plugins_loaded', 'wpcr_receiving_form_hooks' );

require_once(WPCARGO_RECEIVING_PATH.'admin/classes/wpc-receiving-scripts.php');
require_once(WPCARGO_RECEIVING_PATH.'admin/classes/wpc-receiving-loader.php');
require_once(WPCARGO_RECEIVING_PATH.'admin/classes/wpc-receiving-settings.php');

/*

if (is_plugin_active('wpcargo-frontend-manager/wpcargo-frontend-manager.php')) {

        

	}

*/