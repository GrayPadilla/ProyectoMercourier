<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}
add_action('pre_get_posts', 'wpc_pod_filter_media_files');
function wpc_pod_filter_media_files($get_query){
	global $current_user;
	$wp_query = $get_query;
	
	if(is_user_logged_in()) {
		$user = wp_get_current_user();
		
		if( isset($wp_query->query['post_type']) && ($wp_query->query['post_type'] === 'attachment') && in_array( 'wpcargo_driver', $user->roles ) ){		
			$wp_query->set('author', $current_user->ID);		
		}
	}
	
}
