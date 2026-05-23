<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
function is_wpcumanage_page_exist( $shortcode ){
    global $wpdb;
    $sql = "SELECT `ID` FROM `{$wpdb->prefix}posts` WHERE `post_status` LIKE 'publish' AND `post_type` LIKE 'page' AND `post_content` LIKE %s LIMIT 1";
    return $wpdb->get_var( $wpdb->prepare( $sql, '%'.$shortcode.'%') );
}

function wpcumanage_users_page(){
	$wpcumanage_users_page = get_option('wpcumanage_users_page');
	if( !$wpcumanage_users_page ){
		$page_id = is_wpcumanage_page_exist( '[wpcargo_users]' );
		update_option( 'wpcumanage_users_page', $page_id, 'yes' );

		if( $page_id ){
			return $page_id;
		}

		$post_title 	= __('Users', 'wpcargo-merchant' );
		$post_name 		= 'wpcumanage-users';
		$post_content 	= '[wpcargo_users]';
		$page_id		= wpcumanage_generate_page( $post_title, $post_name, $post_content );
		update_option( 'wpcumanage_users_page', $page_id, 'yes' );
		}
	
	return $wpcumanage_users_page;
}

function wpcumanage_generate_page( $post_title, $post_name, $post_content ){
	$page_args    = array(
		'comment_status' => 'closed',
		'ping_status' 	=> 'closed',
		'post_author' 	=> 1,
		'post_date' 	=> date('Y-m-d H:i:s'),
		'post_content' 	=> $post_content,
		'post_name' 	=> $post_name,
		'post_status' 	=> 'publish',
		'post_title' 	=> $post_title,
		'post_type' 	=> 'page',
	);
	$page_id = wp_insert_post( $page_args, false );
	update_post_meta( $page_id, '_wp_page_template', 'dashboard.php' );
	return $page_id;
}
function wpcumanage_create_default_pages(){
	wpcumanage_users_page();
}