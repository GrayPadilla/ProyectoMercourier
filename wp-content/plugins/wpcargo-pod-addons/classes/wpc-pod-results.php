<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}
class WPC_POD_Results{
	public function __construct(){
		add_action('wpcargo_after_track_details', array( $this, 'wpc_proof_of_delivery_results') );
		add_action('wpcargo_before_pod_signature', array( $this, 'wpcargo_pod_images') );
	}	
	public function wpc_proof_of_delivery_results($shipment_detail){
		require_once( wpcpod_include_template( 'wpc-pod-results.tpl' ) );
	}
}
$wpc_pod_results = new WPC_POD_Results;
