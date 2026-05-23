<?php
function wpcr_scripts_to_wpcfe( $scripts ){
	$scripts[] = 'ajax-receiving-script';
	return $scripts;
}
function wpcr_shipment_bulk_scanner(){
	?>
	<button class="bulk-barcode-scan btn btn-secondary btn-sm" data-toggle="modal" data-target="#wpcr-bulk-barcode-modal"><?php esc_html_e('Bulk Update', 'wpcargo-receiving'); ?></button>
	<?php
}
function wpcr_bulk_barcode_modal() {
	$current_user = wp_get_current_user();
	global $wpcargo;
	require_once( wpcr_include_template( 'bulk-barcode.tpl' ) );
}
function wpcr_receiver_roles(){
	$receiver_roles = array( 'wpcargo_employee', 'administrator', 'wpcargo_driver' );
	return apply_filters( 'wpcr_receiver_roles', $receiver_roles );
}
function can_receive_wpcr(){
	$current_user 	= wp_get_current_user();
	$user_roles 	= $current_user->roles;
	if( !empty( array_intersect( $user_roles, wpcr_receiver_roles() ) ) ){
		return true;
	}
	return false;
}
function wpcr_restriction_message(){
	return esc_html__("Sorry you don't have enough permission to access this page.", 'wpcargo-receiving');
}
function wpcr_license_message(){
	return esc_html__("Please activate you license to use this functionality.", 'wpcargo-receiving');
}
function wpcr_get_frontend_page(){
$wpcr_page = get_option('wpcr_page');

	if( !$wpcr_page){
	global $wpdb;
	$sql 			= "SELECT `ID` FROM {$wpdb->prefix}posts WHERE `post_content` LIKE '%[wpcr-update]%' AND `post_status` LIKE 'publish' LIMIT 1";
	$shortcode_id 	= $wpdb->get_var( $sql );
	if( ! $shortcode_id ){
		// Create post object
		$receiving = array(
			'post_title'    => __('Receiving', 'wpcargo-receiving'),
			'post_content'  => '[wpcr-update]',
			'post_status'   => 'publish',
			'post_type'   	=> 'page',
		);	
		// Insert the post into the database
		$shortcode_id = wp_insert_post( $receiving );	

	

	}
	update_option( 'wpcr_page', $shortcode_id, 'yes' );
	$wpcr_page =$shortcode_id;
	if( $shortcode_id ){
		update_post_meta( $shortcode_id, '_wp_page_template', 'dashboard.php');
	}
	}
	return $wpcr_page;

}
function wpcr_include_template( $file_name ){
    $file_slug              = strtolower( preg_replace('/\s+/', '_', trim( str_replace( '.tpl', '', $file_name ) ) ) );
    $file_slug              = preg_replace('/[^A-Za-z0-9_]/', '_', $file_slug );
    $custom_template_path   = get_stylesheet_directory().'/wpcargo/wpcargo-receiving/'.$file_name.'.php';
    if( file_exists( $custom_template_path ) ){
        $template_path = $custom_template_path;
    }else{
        $template_path  = WPCARGO_RECEIVING_PATH.'templates/'.$file_name.'.php';
        $template_path  = apply_filters( "wpcr_locate_template_{$file_slug}", $template_path );
    }
	return $template_path;
}
function wpcr_admin_include_template( $file_name ){
    $file_slug              = strtolower( preg_replace('/\s+/', '_', trim( str_replace( '.tpl', '', $file_name ) ) ) );
    $file_slug              = preg_replace('/[^A-Za-z0-9_]/', '_', $file_slug );
    $custom_template_path   = get_stylesheet_directory().'/wpcargo/wpcargo-receiving/admin/'.$file_name.'.php';
    if( file_exists( $custom_template_path ) ){
        $template_path = $custom_template_path;
    }else{
        $template_path  = WPCARGO_RECEIVING_PATH.'admin/templates/'.$file_name.'.php';
        $template_path  = apply_filters( "wpcr_locate_template_{$file_slug}", $template_path );
    }
	return $template_path;
}

function wpcr_registered_scripts( $scripts ){
	// custom-scripts - is your enqueued script handle
	$scripts[] = 'script';
	return $scripts;
}

add_filter( 'wpcfe_registered_scripts', 'wpcr_registered_scripts', 99 );