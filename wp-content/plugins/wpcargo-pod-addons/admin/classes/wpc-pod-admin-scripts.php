<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}
class WPC_Admin_POD_Scripts {
	public function __construct(){
		add_action( 'admin_enqueue_scripts', array( $this, 'wpc_pod_back_end' ) );
	}
	
	function wpc_pod_back_end() {

		$current_screen = get_current_screen();

		wp_enqueue_media();
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui-datepicker');
		wp_register_script( 'wpcargo-pod-signature', WPCARGO_POD_URL.'admin/assets/js/pod-signature.js', array('jquery'), WPCARGO_POD_VERSION );
		wp_register_script( 'wpcargo-pod-seelct2-js', WPCARGO_POD_URL.'admin/assets/js/select2.min.js', array('jquery'), WPCARGO_POD_VERSION );
		wp_register_script( 'wpcargo-pod-settings', WPCARGO_POD_URL.'admin/assets/js/pod-settings.js', array('jquery', 'jquery-ui-datepicker'), WPCARGO_POD_VERSION );
		
		if( ( isset($_GET['post_type']) && $_GET['post_type'] == 'wpcargo_shipment' ) ){
		wp_enqueue_script('wpcargo-pod-signature');

		}
		if( $current_screen->id == 'admin_page_wpc-pod-settings' || $current_screen->id == 'wpcargo_shipment_page_wpcpod-export' ){
		    
			wp_enqueue_script('wpcargo-pod-seelct2-js');
			wp_enqueue_script('wpcargo-pod-settings');
			wp_localize_script( 'wpcargo-pod-settings', 'wpcargoPODAJAXHandler',
		        array( 
		            'ajaxurl' => admin_url( 'admin-ajax.php' )
		        )
		    );
			wp_enqueue_style('wpcargo-pod-admin-select2-style', WPCARGO_POD_URL . 'admin/assets/css/select2.min.css', array(), WPCARGO_POD_VERSION);
			wp_enqueue_style('wpcargo-pod-admin-style', WPCARGO_POD_URL . 'admin/assets/css/pod-admin-style.css', array(), WPCARGO_POD_VERSION);
		}
		
		

	}
}
$wpc_admin_pod_scripts = new WPC_Admin_POD_Scripts;
