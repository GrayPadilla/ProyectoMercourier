<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function wpc_import_book_sidebar_menu($menu_items){
    if( function_exists('wpcfe_admin_page') && wpc_can_add_address_book() ){
        $menu_items['adrress-book-ie'] = array(
            'page-id' => wpca_import_export_address(),
            'label' => __('Import Address', 'wpcargo-address-book'),
            'permalink' => get_permalink( wpca_import_export_address() ),
            'icon' => 'fa-book'
        );
    }
    return $menu_items;
}
// add_filter( 'wpcfe_after_sidebar_menu_items', 'wpc_import_book_sidebar_menu', 10 );

function wpca_import_export_address(){

	$wpca_import_export_address = get_option('wpca_import_export_address');
	$shortcode_id = '';

	if( !$wpca_import_export_address ){
		global $wpdb;
		$sql 			= "SELECT `ID` FROM {$wpdb->prefix}posts WHERE `post_content` LIKE '%[wpca_import_export]%' AND `post_status` LIKE 'publish' LIMIT 1";
		$shortcode_id 	= $wpdb->get_var( $sql );
		update_option( 'wpca_import_export_address', $shortcode_id );

		if( ! $shortcode_id ){
			// Create post object
			$address_book = array(
				'post_title'    => __('Import Export Book', 'wpc-import-export'),
				'post_content'  => '[wpca_import_export]',
				'post_status'   => 'publish',
				'post_type'   	=> 'page',
			);	
			// Insert the post into the database
			$shortcode_id = wp_insert_post( $address_book );		
		}
		if( $shortcode_id ){
			update_post_meta( $shortcode_id, '_wp_page_template', 'dashboard.php');
			update_option( 'wpca_import_export_address', $shortcode_id );
		}
	}
	return $wpca_import_export_address;
}

function is_address_admin(){
	$user	= wp_get_current_user();
	$role	= $user->roles;
	$admin	= false;

	if( in_array( 'administrator', $role ) ){
		$admin = true;
	}
	return $admin;
}