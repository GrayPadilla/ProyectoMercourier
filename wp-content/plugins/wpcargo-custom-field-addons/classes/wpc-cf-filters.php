<?php
if ( ! defined( 'ABSPATH' ) ) {
 exit;
}
class Track_Form_Custom_Fields {
	public function __construct(){
		add_action( 'wpcargo_add_form_fields', array( $this, 'wpcargo_trackform_result_query_custom_field' ) );
		add_action( 'wpcargo_add_fields', array( $this, 'wpcargo_add_fields_template' ) );
		add_filter(	'wpcargo_trackform_shipment_number_query', array( $this, 'wpcargo_trackform_shipment_number_query_callback' ), 10, 2 );
		add_action( 'admin_init', array( $this, 'add_role_caps' ) );
		add_action(	'pre_get_posts', array( $this, 'users_own_attachments' ) );
		add_filter( 'wpcfe_registered_styles', array( $this, 'media_wpcfe_registered_styles' ),10, 1 );
	}
	function media_wpcfe_registered_styles( $styles ){
		$styles[] = 'wpccf-media-style';
		return $styles;
	}
	public function add_role_caps(){
		$role_employee 	= get_role( 'wpcargo_employee' );
		$role_client 	= get_role( 'wpcargo_client' );
		if( $role_employee !== null ){
			$role_employee->add_cap( 'upload_files' );
		}
		if( $role_client !== null ){
			$role_client->add_cap( 'upload_files' );
		}
	}
	
	public function users_own_attachments( $wp_query_obj ) {
		global $current_user, $pagenow;
		$is_attachment_request = ($wp_query_obj->get('post_type')=='attachment');
		if( !$is_attachment_request ){
			return;
		}
		if( !is_a( $current_user, 'WP_User') ){
			return;
		}
			
		if( !in_array( $pagenow, array( 'upload.php', 'admin-ajax.php' ) ) ){
			return;
		}
		if( in_array( 'wpcargo_client', $current_user->roles ) || in_array( 'wpcargo_employee', $current_user->roles ) ){
			$wp_query_obj->set('author', $current_user->ID );
			$wp_query_obj->set('second', 15 );
		}
		return;
	}

	public function wpcargo_trackform_result_query_custom_field() {
		global $wpdb;
		do_action('wpcargo_add_fields');
	}
	public function wpcargo_add_fields_template() {
		global $wpdb;
		require_once( wpccf_include_template( 'wpc-cf-add-track-fields.tpl' ) );
	}
	function wpcargo_trackform_shipment_number_query_callback( $sql, $shipment_number ){
		global $wpdb;
		$shipment_values		= array();
		$shipment_table 		= array();
		$shipment_value 		= array();
		$registered_metakeys 	= wpccf_registered_metakeys();
		if( !empty( $_REQUEST ) ){
			foreach($_REQUEST as $req_key => $req_val ) {
				if( !in_array( $req_key, $registered_metakeys )){
					continue;
				}
				$shipment_values[ $req_key ] = sanitize_text_field( $req_val );
			}
			$shipment_values = apply_filters( 'wpccf_multiple_value_query', $shipment_values );
			if(!empty($shipment_values)){
				$sql = "SELECT m.ID FROM {$wpdb->prefix}posts m ";
				$meta_counter = 1;
				foreach ( $shipment_values as $_key => $_value ) {
					$shipment_table[] = "INNER JOIN {$wpdb->prefix}postmeta m{$meta_counter} ON ( m.ID = m{$meta_counter}.post_id )";
					$shipment_value[] = "AND ( m{$meta_counter}.meta_key LIKE '{$_key}' AND m{$meta_counter}.meta_value LIKE '{$_value}' )";
					$meta_counter++;
				}
				$sql .= implode(' ', $shipment_table );
				$sql .= " WHERE m.post_type = 'wpcargo_shipment' AND m.post_status = 'publish' AND m.post_title LIKE '{$shipment_number}' ";
				$sql .= implode(' ', $shipment_value );
				$sql .= " LIMIT 1";
			}
		}
		return $sql;	
	}
}
$track_form_custom_fields = new Track_Form_Custom_Fields;