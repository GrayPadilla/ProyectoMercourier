<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}
class WPC_POD_Function_Ajax{
	public function __construct(){
		add_action( 'wp_ajax_wpc_pod_func_data', array( $this, 'wpc_pod_func_data' ) );
		add_action( 'wp_ajax_nopriv_wpc_pod_func_data', array( $this, 'wpc_pod_func_data' ) );	
	}
	
	function tester123(){
		global $wp;
	}
	function wpc_pod_func_data(){
	
		global $wpdb;
		$wpc_tracking_number = isset($_REQUEST['wpc_tracking_number']) ? $_REQUEST['wpc_tracking_number'] : '';	
		
		$wpc_get_query = $wpdb->get_results("SELECT ID FROM ".$wpdb->prefix."posts WHERE post_title = ".$wpc_tracking_number." ");
		
		$get_arr_query = array();
		if(!empty($wpc_get_query)) {
		foreach($wpc_get_query as $get_query){
			$get_arr_query = array(
				'wpc_get_url'	=> "//".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']."?sid=".$get_query->ID."",
				'wpc_get_data'	=> "1"
			);
		}
			
		}else{
			$get_arr_query = array(
				'wpc_get_data'	=> "0"
			);
		}
		echo json_encode($get_arr_query);
		die();	
	
	}
}
$wpc_pod_function_ajax = new WPC_POD_Function_Ajax;