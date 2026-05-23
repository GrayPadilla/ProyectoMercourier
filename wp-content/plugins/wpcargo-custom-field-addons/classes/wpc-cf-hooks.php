<?php
class WPC_CF_Hooks{
	public function __construct() {
		add_action( 'wpcargo_track_shipper_details', array($this, 'wpc_cf_track_result_shipper_template'), 10 );
		add_action( 'wpcargo_track_shipment_details', array($this, 'wpc_cf_track_result_shipment_template'), 10 );
		add_action( 'admin_print_shipper', array($this, 'wpc_cf_print_admin_shipper_template'), 10 );
		add_action( 'admin_print_shipment', array($this, 'wpc_cf_print_admin_shipment_template'), 10 );
		add_filter( 'wpcfe_registered_scripts', array($this, 'wpc_cf_wpcfe_registered_scripts_cb'), 99, 1);
		add_action('wp_footer', array($this, 'wpccf_set_default_radio_btn_values_cb'), 9999);
	}
	function wpc_cf_track_result_shipper_template( $shipment_detail ){
		$options 				= get_option('wpcargo_cf_option_settings');
		require( wpccf_include_template( 'wpc-cf-track-shipper.tpl' ) );
	}
	function wpc_cf_track_result_shipment_template( $shipment_detail ){
		$sections 		= wpccf_additional_sections();
		if( !empty( $sections ) ){
			$shipment_id 	= $shipment_detail->ID;	
			require( wpccf_include_template( 'track-custom-section.tpl' ) );
		}
	}
	function wpc_cf_print_admin_shipper_template( $shipment_detail ){
		$options 				= get_option('wpcargo_cf_option_settings');
		require( WPCARGO_CUSTOM_FIELD_PATH.'admin/templates/print-cf-shipper.tpl.php' );
	}
	function wpc_cf_print_admin_shipment_template( $shipment_detail ){
		$options 				= get_option('wpcargo_cf_option_settings');
		require( WPCARGO_CUSTOM_FIELD_PATH.'admin/templates/print-cf-shipment.tpl.php' );
	}
	function wpc_cf_wpcfe_registered_scripts_cb( $scripts ){
		$_scripts = (array)$scripts;
		// if(function_exists('wpcfe_admin_page')){
			// if(is_page(wpcfe_admin_page()) && isset($_GET['wpcfe']) && ($_GET['wpcfe'] === 'add' || $_GET['wpcfe'] === 'update')){
				$_scripts[] = 'wpccf-jautocalc-js';
				$_scripts[] = 'wpccf-jautocalc-scripts';
				$_scripts[] = 'wpccf-conditionize-js';
				$_scripts[] = 'wpccf-conditional-scripts';
			// }
		// }
		return $_scripts;
	}
	function wpccf_set_default_radio_btn_values_cb() {
		?>
		<script>
			jQuery(document).ready(function($){
				setTimeout(() => {
					let radioInputs = $('input[type="radio"]');
					if(radioInputs.length > 0) {
						radioInputs.each(function(){
							let dataDefault = $(this).data('default');
							if(dataDefault) {
								$(this).val(dataDefault);
							}
						});
					}
				}, 1000);
			});
		</script>
		<?php
	}
}
$wpc_cf_hooks = new WPC_CF_Hooks;