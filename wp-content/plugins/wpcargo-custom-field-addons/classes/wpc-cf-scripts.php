<?php
if ( ! defined( 'ABSPATH' ) ) {
 exit; 
}
class WPCargo_CF_Scripts{
	function __construct(){
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_style' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_front_end' ), 55 );
	}
	function enqueue_admin_style() {
		global $wpcargo;
		$current_screen = get_current_screen();
		
		// wp_register_script( 'wpcargo-cf-sortable-ui', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.3/jquery-ui.min.js', array(), WPCARGO_CUSTOM_FIELD_VERSION );
		wp_register_script( 'wpcargo-cf-sortable-ui', WPCARGO_CUSTOM_FIELD_URL.'admin/assets/js/jquery-ui.min.js', array(), WPCARGO_CUSTOM_FIELD_VERSION );
		wp_register_script( 'wpcargo-cf-sortable-js', WPCARGO_CUSTOM_FIELD_URL.'admin/assets/js/admin-sortable.js', array(), WPCARGO_CUSTOM_FIELD_VERSION );
		wp_register_script( 'wpccf-repeater-js', WPCARGO_CUSTOM_FIELD_URL.'admin/assets/js/repeater.min.js', array('jquery'), WPCARGO_CUSTOM_FIELD_VERSION, true);
		wp_register_script( 'wpcargo-cf-ajax-js', WPCARGO_CUSTOM_FIELD_URL.'admin/assets/js/admin-ajax.js', array('wpccf-repeater-js'), WPCARGO_CUSTOM_FIELD_VERSION );
		wp_register_script( 'wpccf-datetimepicker-script', WPCARGO_CUSTOM_FIELD_URL . 'assets/js/jquery.datetimepicker.full.min.js', array( 'jquery' ), WPCARGO_CUSTOM_FIELD_VERSION,  false );
		wp_register_script( 'wpcargo-cf-admin-script', WPCARGO_CUSTOM_FIELD_URL.'admin/assets/js/wpc-cf-admin-script.js', array(), WPCARGO_CUSTOM_FIELD_VERSION );	
		wp_register_script( 'wpccf-script', WPCARGO_CUSTOM_FIELD_URL . 'assets/js/wpccf-scripts.js', array( 'jquery' ), WPCARGO_CUSTOM_FIELD_VERSION,  false );
		

		
		// Admin style
		wp_register_style( 'wpcargo-bootstrap-css', WPCARGO_CUSTOM_FIELD_URL . 'admin/assets/css/bootstrap.min.css', array(), WPCARGO_CUSTOM_FIELD_VERSION );
		wp_register_style( 'wpcargo-cf-css', WPCARGO_CUSTOM_FIELD_URL . 'admin/assets/css/wpc-cf-styles.css', array(), WPCARGO_CUSTOM_FIELD_VERSION );
		// wp_register_style( 'wpcargo-cf-sortable-css', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.3/themes/smoothness/jquery-ui.css', array(), WPCARGO_CUSTOM_FIELD_VERSION );
		wp_register_style( 'wpcargo-cf-sortable-css', WPCARGO_CUSTOM_FIELD_URL . 'admin/assets/css/jquery-ui.min.css', array(), WPCARGO_CUSTOM_FIELD_VERSION );
		
		if( $current_screen && $current_screen->post_type == 'wpcargo_shipment'){
			wp_enqueue_style( 'wpcargo-bootstrap-css');
			wp_enqueue_script( 'wpcargo-cf-admin-script');
			wp_enqueue_script( 'wpccf-datetimepicker-script');
			wp_enqueue_script( 'wpccf-script');
			$translation   = array(
				'dateFormat'           => $wpcargo->date_format,
				'timeFormat'           => $wpcargo->time_format,
				'dateTimeFormat'       => $wpcargo->datetime_format
			);
			wp_localize_script( 'wpccf-script', 'wpccfAjaxhandler', $translation );
			wp_enqueue_style( 'wpcargo-cf-sortable-css');
		    wp_enqueue_style( 'wpcargo-cf-css');
		}
		
		if( isset( $_GET['page'] ) && $_GET['page'] == 'wpccf-print-label-settings' ){
		
		wp_enqueue_style( 'wpcargo-cf-css');
		}
		
		if( isset( $_GET['page'] ) && $_GET['page'] == 'wpc-cf-manage-form-field' ){
		    wp_enqueue_style( 'wpcargo-cf-sortable-css');
		    wp_enqueue_style( 'wpcargo-cf-css');
			wp_enqueue_script( 'jquery');
			wp_enqueue_script( 'wpcargo-cf-sortable-ui');
			wp_enqueue_script( 'wpcargo-cf-sortable-js');
			wp_enqueue_script( 'wpccf-repeater-js');
			wp_enqueue_script( 'wpcargo-cf-ajax-js');
			wp_localize_script( 'wpcargo-cf-ajax-js', 'deleteCFhandler', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		}	
		wp_enqueue_script( 'jquery-migrate' );
	}
	function enqueue_front_end() {
		global $wpcargo;
		$is_wpcfe_add_shipment_page = false;
		$is_wpcfe_update_shipment_page = false;
		if(function_exists('wpcfe_admin_page')) {
			$wpcfe = ($_GET['wpcfe'] ?? '') ?: '';
			$id = ($_GET['id'] ?? '') ?: '';
			if(is_page(wpcfe_admin_page())) {
				if($wpcfe === 'add') {
					$is_wpcfe_add_shipment_page = true;
				} else {
					if($id) {
						$is_wpcfe_update_shipment_page = true;
					}
				}
			}
		}
		// Styles
		wp_register_style( 'wpccf-datetimepicker-css', WPCARGO_CUSTOM_FIELD_URL . 'assets/css/jquery.datetimepicker.min.css', array(), WPCARGO_CUSTOM_FIELD_VERSION );
		wp_register_style( 'wpccf-style', WPCARGO_CUSTOM_FIELD_URL.'assets/css/wpcargo-custom-fields.css', array(), WPCARGO_CUSTOM_FIELD_VERSION );		
		wp_register_style( 'wpccf-media-style', WPCARGO_CUSTOM_FIELD_URL.'assets/css/wp-media.css', array(), WPCARGO_CUSTOM_FIELD_VERSION );
		wp_enqueue_style( 'wpccf-datetimepicker-css');
		wp_enqueue_style( 'wpccf-media-style');
		wp_enqueue_style( 'wpccf-style');
		// Scripts
		wp_register_script( 'wpccf-datetimepicker-script', WPCARGO_CUSTOM_FIELD_URL . 'assets/js/jquery.datetimepicker.full.min.js', array( 'jquery' ), WPCARGO_CUSTOM_FIELD_VERSION,  false );
		wp_register_script( 'wpccf-script', WPCARGO_CUSTOM_FIELD_URL . 'assets/js/wpccf-scripts.js', array( 'jquery' ), WPCARGO_CUSTOM_FIELD_VERSION,  false );
		wp_enqueue_script( 'jquery');
		wp_enqueue_media();
		wp_enqueue_script( 'wpccf-datetimepicker-script');
		wp_enqueue_script( 'wpccf-script');
		// Auto Calculate Scripts
		wp_enqueue_script('wpccf-jautocalc-js', WPCARGO_CUSTOM_FIELD_URL.'assets/js/jautocalc.min.js', array('jquery'), WPCARGO_CUSTOM_FIELD_VERSION, true);
		wp_enqueue_script('wpccf-jautocalc-scripts', WPCARGO_CUSTOM_FIELD_URL.'assets/js/jautocalc-scripts.js', array('jquery'), WPCARGO_CUSTOM_FIELD_VERSION, true);
		// Conditional Scripts
		wp_enqueue_script('wpccf-conditionize-js', WPCARGO_CUSTOM_FIELD_URL.'assets/js/conditionize2.min.js', array('jquery'), WPCARGO_CUSTOM_FIELD_VERSION, true);
		wp_enqueue_script('wpccf-conditional-scripts', WPCARGO_CUSTOM_FIELD_URL.'assets/js/wpccf-conditional-scripts.js', array('jquery'), WPCARGO_CUSTOM_FIELD_VERSION, true);
		$translation   = array(
			'dateFormat'           => $wpcargo->date_format,
			'timeFormat'           => $wpcargo->time_format,
			'dateTimeFormat'       => $wpcargo->datetime_format
		);
		wp_localize_script( 'wpccf-script', 'wpccfAjaxhandler', $translation );
		$conditional_localization = array(
			'is_wpcfe_add_shipment_page' => $is_wpcfe_add_shipment_page,
			'is_wpcfe_update_shipment_page' => $is_wpcfe_update_shipment_page,
		);
		wp_localize_script( 'wpccf-conditional-scripts', 'wpccfConditionalLocalize', $conditional_localization );
	}
}
new WPCargo_CF_Scripts;