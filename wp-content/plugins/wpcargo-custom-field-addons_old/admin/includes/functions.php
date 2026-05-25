<?php
function wpccf_get_all_custom_fields(){
	global $wpdb;
	$table_prefix = $wpdb->prefix;
	//** Flag value Parameter
	//* @shipper_info
	//* @receiver_info
	//* @shipment_info
	$result_fields = $wpdb->get_results( 'SELECT * FROM `'.$table_prefix.'wpcargo_custom_fields` ORDER BY ABS(weight)', ARRAY_A );
	return $result_fields;
}
function wpccf_get_field_type($meta_key){
	global $wpdb;
	$sql = $wpdb->prepare( "SELECT `field_type` FROM `{$wpdb->prefix}wpcargo_custom_fields` WHERE `field_key` LIKE %s LIMIT 1", $meta_key );
	$field_type = $wpdb->get_var( $sql );
	return $field_type;
}
function wpccf_reserve_metakeys(){
    $metakeys = array(
        'wpcargo_tracking_number',
        'wpcargo_shipments_update',
        'wpc-multiple-package'
    );
    return apply_filters( 'wpccf_reserve_metakeys', $metakeys );
}
function wpccf_registered_metakeys(){
	$meta_keys = array();
	$custom_fields = wpccf_get_all_custom_fields();
	if( !empty( $custom_fields  ) ){
		$meta_keys = array_map( function( $value ){
			return $value = $value['field_key'];
		}, $custom_fields );
    }
    $meta_keys = apply_filters( 'wpcargo_registered_custom_metakeys', $meta_keys );
    foreach( wpccf_reserve_metakeys() as $key ){
        if ( ($key = array_search($key, $meta_keys) ) !== false) {
            unset($meta_keys[$key]);
        }
	}
	$meta_keys[] = 'wpcargo_status';
	return $meta_keys;
}
function wpccf_get_custom_fields_by_flag( $flag = '' ){
	global $wpdb;
	$table_prefix = $wpdb->prefix;
	//** Flag value Parameter
	//* @shipper_info
	//* @receiver_info
	//* @shipment_info
	$result_fields = $wpdb->get_results( 'SELECT * FROM `'.$table_prefix.'wpcargo_custom_fields` WHERE `section` LIKE "%'.$flag.'%" ORDER BY ABS(weight)', ARRAY_A );
	return $result_fields;
}
function wpccf_get_field_key( $key = '' ){
	global $wpdb;
	$table_prefix = $wpdb->prefix;
	$result = '';
	if( !empty($key) || $key != '' ){
		$result= $wpdb->get_results( 'SELECT * FROM `'.$table_prefix.'wpcargo_custom_fields` WHERE `section` LIKE "%'.$key.'%"', ARRAY_A );
	}
	return $result;
}
function wpccf_get_field_by_metakey( $metakey = '' ){
	global $wpdb;
	$table_prefix = $wpdb->prefix;
	$result = '';
	if( !empty($metakey) || $metakey != '' ){
		$result= $wpdb->get_row( 'SELECT * FROM `'.$table_prefix.'wpcargo_custom_fields` WHERE `field_key` LIKE "%'.$metakey.'%" LIMIT 1', ARRAY_A );
	}
	return $result;
}
function wpccf_get_field_key_list(  ){
	global $wpdb;
	$table_prefix = $wpdb->prefix;
	$field_keys = $wpdb->get_results( 'SELECT `field_key` FROM `'.$table_prefix.'wpcargo_custom_fields`', ARRAY_A );
	return $field_keys;
}
function wpccf_get_all_fields(){
	global $wpdb;
	$table_prefix = $wpdb->prefix;
	$field_keys = $wpdb->get_results( 'SELECT * FROM `'.$table_prefix.'wpcargo_custom_fields`', ARRAY_A );
	return $field_keys;
}
function wpccf_user_displayname( $userID ){
	$displayname = '';
	if( $userID && is_numeric( $userID ) ){
		$user_info = get_userdata( $userID  );
		if( !empty( $user_info->last_name ) && !empty( $user_info->first_name  ) ){
			$displayname = $user_info->last_name .  ", " . $user_info->first_name;
		}else{
			$displayname = $user_info->display_name;
		}
	}	
	return $displayname;
}
function wpccf_additional_sections(){
	$custom_sections = array();
	$options = get_option('wpcargo_cf_option_settings');
	if( !empty( $options ) && array_key_exists( 'wpc_cf_additional_options', $options ) ){
		$additional_sections = trim( $options['wpc_cf_additional_options'] );
		if( !empty( $additional_sections ) ){
			$additional_sections = array_filter( array_map( 'trim', explode(",", $additional_sections) ) );
			if( !empty( $additional_sections ) ){
				foreach( $additional_sections as $section ){
					$key = wpccf_get_text_slug($section);
					$custom_sections[$key] = $section;
				}
			}
		}
	}
	$custom_sections =  apply_filters( 'wpccf_additional_sections', $custom_sections );
	if( empty( $options ) || !array_key_exists( 'wpc_cf_disable_shipment', $options ) ):
		$shipment_info = array( 
			'shipment_info' => apply_filters( 'result_shipment_information', __('Shipment Information', 'wpcargo-custom-field')) 
		);	
		$custom_sections = $shipment_info + $custom_sections;
	endif;
	return $custom_sections;
}
function wpccf_search_metakey_code(){
	$metacode = array();
	$all_fields			= wpccf_registered_metakeys();
	if( !empty( $all_fields ) ){
		foreach ($all_fields as $value ) {
			$metacode[] = '{'.$value.'}';
		}
	}
	return $metacode;
}
function wpccf_get_text_slug( $string ){
	return preg_replace('!\s+!', '_', strtolower( trim($string) ) );
}
function wpccf_replace_metakey_code( $shipmentID ){
	$metakey = array();
	$all_fields			= wpccf_registered_metakeys();
	if( !empty( $all_fields ) ){
		foreach ($all_fields as $value ) {
			$field_type = wpccf_get_field_type( $value );
			$metavalue = maybe_unserialize( get_post_meta( $shipmentID, $value, true ) );
			if( is_array( $metavalue  ) ){
				$metavalue = implode(', ', $metavalue);
			}else{
				if( $field_type == 'agent'  ){
					$metavalue = wpccf_user_displayname( $metavalue );
				}elseif( $field_type == 'date' ){
					$metavalue = wpcargo_get_postmeta(  $shipmentID, $value, 'date' );
				}	
			}
			$metakey[] = $metavalue;
		}
	}
	return $metakey;
}
// This function use only for the address metakey
// This will extract serialize data
function wpccf_extract_address( $post_id, $address_metakey ){
    $address = array(
        'street'     => '',
        'city'       => '',
        'state' 	 => '',
        'postcode'   => '',
        'country'    => '',
    );	
    $data_selection = get_post_meta( $post_id, $address_metakey, true ) ? maybe_unserialize( get_post_meta( $post_id, $address_metakey, true) ) : array();	
    $data_selection = is_array( $data_selection ) ? $data_selection : array();
    if( !empty( $data_selection ) ){
        $address = $data_selection;
    }
    return $address;
}
function wpccf_get_address( $post_id, $address_metakey ){
	$extract_address = wpccf_extract_address( $post_id, $address_metakey );
	$address	= '';
	if( !empty($extract_address['street']) ){
		$address .= $extract_address['street'].', ';
	}
	if( !empty($extract_address['city']) ){
		$address .= $extract_address['city'].', ';
	}
	if( !empty($extract_address['state']) ){
		$address .= $extract_address['state'].' ';
	}
	if( !empty($extract_address['postcode']) ){
		$address .= $extract_address['postcode'].' ';
	}
	if( !empty($extract_address['country']) ){
		$address .= $extract_address['country'];
	}
	return apply_filters( 'wpccf_get_address', $address, $extract_address, $post_id, $address_metakey );
}

function wpccf_address_fields_data(){
	$fields = array(
		'street' 	=> apply_filters( 'wpccf_address_fields_street', __('Street Address', 'wpcargo-custom-field') ),
		'city' 		=> apply_filters( 'wpccf_address_fields_city', __('City', 'wpcargo-custom-field') ),
		'state' 	=> apply_filters( 'wpccf_address_fields_state', __('State/Province/Region', 'wpcargo-custom-field') ),
		'postcode' 	=> apply_filters( 'wpccf_address_fields_postcode', __('Zip/PostCode', 'wpcargo-custom-field') ),
		'country' 	=> apply_filters( 'wpccf_address_fields_country', __('Country', 'wpcargo-custom-field') ),
	);
	return $fields;
}
function wpccf_field_type_list(){
	$fields = array(
		'text' 		=> __('Text', 'wpcargo-custom-field'),
		'textarea' 	=> __('Textarea', 'wpcargo-custom-field'),
		'email' 	=> __('Email', 'wpcargo-custom-field'),
		'address' 	=> __('Address', 'wpcargo-custom-field'),
		'number' 	=> __('Number', 'wpcargo-custom-field'),
		'select' 	=> __('Select', 'wpcargo-custom-field'),
		'multiselect' 	=> __('Multiselect', 'wpcargo-custom-field'),
		'radio' 	=> __('Radio', 'wpcargo-custom-field'),
		'checkbox' 	=> __('Checkbox', 'wpcargo-custom-field'),
		'file' 		=> __('File', 'wpcargo-custom-field'),
		'date' 		=> __('Date', 'wpcargo-custom-field'),
		'time' 		=> __('Time', 'wpcargo-custom-field'),
		'datetime' 	=> __('Date Time', 'wpcargo-custom-field'),
		'url' 		=> __('URL', 'wpcargo-custom-field'),
		'html' 		=> __('HTML', 'wpcargo-custom-field'),

	);
	return apply_filters( 'wpccf_field_type_list', $fields );
}
function wpccf_get_shipment_sections(){
    global $wpdb;
    $sections           = $wpdb->get_col( "SELECT `section` FROM `{$wpdb->prefix}wpcargo_custom_fields` GROUP BY `section` ORDER BY `weight`" );
    $formatted_section  = array();
    $cfsections_opt     = !empty( get_option( 'wpcargo_cf_option_settings' ) ) ? get_option( 'wpcargo_cf_option_settings' ) : array();
    if( !empty( $sections ) ){
        foreach ( $sections as $section ) { 
            
            if( array_key_exists('wpc_cf_disable_shipper', $cfsections_opt) && $section == 'shipper_info' ){
                continue;
            }
            if( array_key_exists('wpc_cf_disable_receiver', $cfsections_opt) && $section == 'receiver_info' ){
                continue;
            }
            if( array_key_exists('wpc_cf_disable_shipment', $cfsections_opt) && $section == 'shipment_info' ){
                continue;
            }
            
            $header = '';
            if( $section == 'shipper_info' ){
                $header = apply_filters( 'wpcfe_shipper_label', __('Shipper Information', 'wpcargo-custom-field' ) ); 
            }elseif( $section == 'receiver_info' ){
                $header = apply_filters( 'wpcfe_receiver_label', __('Receiver Information', 'wpcargo-custom-field' ) );
            }elseif( $section == 'shipment_info' ){
                $header = apply_filters( 'wpcfe_shipment_label', __('Shipment Information', 'wpcargo-custom-field' ) );
            }else{
                $header = ucwords( str_replace('_', ' ', $section ) );
            } 
            $formatted_section[$section] = $header;
        }
    }
    return apply_filters( 'wpccf_shipment_sections', $formatted_section );
}
function wpccf_get_table_column_names(){
	global $wpdb;
	// wpcargo_custom_fields - Plugin table name
	return $wpdb->get_col("DESC `".$wpdb->prefix."wpcargo_custom_fields`", 0);
}
/*
* 	WPCAGRO FILTERS 
*/
add_filter('wpcargo_shipper_label_filter', function( $string){
	if( get_option('shipper_column') ){
		$string = get_option('shipper_column');
		$value 	= wpccf_get_field_by_metakey( $string );
		if( $value ){
			$string = $value['label'];
		}
	}
	return $string;
}, 10, 1);
add_filter('wpcargo_receiver_label_filter', function( $string){
	if( get_option('receiver_column') ){
		$string = get_option('receiver_column');
		$value 	= wpccf_get_field_by_metakey( $string );
		if( $value ){
			$string = $value['label'];
		}
	}
	return $string;
}, 10, 1);
add_filter('wpcargo_shipper_meta_filter', function( $string ){
	if( get_option('shipper_column') ){
		$string = get_option('shipper_column');
	}
	return $string;
}, 10, 1);
add_filter('wpcargo_receiver_meta_filter', function( $string ){
	if( get_option('receiver_column') ){
		$string = get_option('receiver_column');
	}
	return $string;
}, 10, 1);
add_filter('wpc_email_meta_tags', function( $tags ){
	$new_tags	= array( 
		'{wpcargo_tracking_number}' => __('Shipment Number', 'wpcargo-custom-field' ), 
		'{status}' 					=> __('Status', 'wpcargo-custom-field' ),
		'{location}' 				=> __('Location', 'wpcargo-custom-field' ),
		'{admin_email}'             => __('Admin Email','wpcargo-custom-field'),
		'{site_name}'               => __('Website Name','wpcargo-custom-field'),
		'{site_url}'                => __('Website URL','wpcargo-custom-field'),
		'{wpcreg_client_email}'     => __('Registered Client Email','wpcargo-custom-field'),
	);
	$all_fields = wpccf_get_all_fields();
	if( !empty( $all_fields ) ){
		foreach ($all_fields as $value) {
			if( $value['field_key'] == 'wpcargo_status' ){
				continue;
			}
			$new_tags[ '{'.$value['field_key'].'}' ] = $value['label'];
		}
		$tags = $new_tags;
	}
	return $tags;
}, 10, 1);
add_filter('wpc_email_notification_find_hook', function( $str_find ){
	$all_fields = wpccf_get_all_fields();	
	if( !empty( $all_fields ) ){
		$str_find = array('{wpcargo_tracking_number}', '{status}');
		foreach ($all_fields as $value) {
			$str_find[] =  '{'.$value['field_key'].'}';
		}
	}
	return $str_find;
}, 10, 1);
add_filter('wpc_email_notification_replace_hook', function( $str_replce, $shipmentID ){
	$all_fields 	= wpccf_get_all_fields();	
	$status 		= $str_replce[8]; // Shipment new Status
	if( !empty( $all_fields ) ){
		$str_replce = array( get_the_title( $shipmentID ) );
		$str_replce[] = $status;
		foreach ($all_fields as $field ) {
			$value 	= maybe_unserialize( get_post_meta($shipmentID, $field['field_key'], true) );
			if( is_array( $value  ) ){
				$value = implode( ',', $value );
			}
			$str_replce[] =  $value;
		}
	}
	return $str_replce;
}, 10, 2 );
add_filter('wpc_shipper_name_table_data', function( $string ){
	if( get_option('shipper_column') ){
		$string = get_option('shipper_column');
	}
	return $string;
}, 10, 1);
add_filter('wpc_receiver_name_table_data', function( $string ){
	if( get_option('receiver_column') ){
		$string = get_option('receiver_column');
	}
	return $string;
}, 10, 1);
add_filter('wpc_report_search_shipper_name_metakey', function( $string ){
	if( get_option('shipper_column') ){
		$string = get_option('shipper_column');
	}
	return $string;
}, 10, 1);
add_filter('wpcargo_status_option', function( $status ){
	$wpcargo_status 	=  wpccf_get_field_by_metakey( 'wpcargo_status' );
	if( !empty( $wpcargo_status ) ){
		if( !empty( $wpcargo_status['field_data'] ) ){
			$cf_status 	= maybe_unserialize( $wpcargo_status['field_data'] );
			$cf_status 	= array_map('trim',$cf_status);
			$status 	= array_map('trim',$status);
			$status 	= array_unique( array_merge($status, $cf_status) );
		}
	}
	return $status;
}, 10, 1);
//** Hook Branch to Export Form Field
add_filter('ie_registered_fields', 'wpccf_filter_ie_registered_fields' ,10, 1 );
function wpccf_filter_ie_registered_fields( $fields ){
	if( !empty( wpccf_get_all_fields() ) ){
		$fields = array();
		foreach ( wpccf_get_all_fields() as $field_data ) {
			$fields[] = array(
				'meta_key' 	=> $field_data['field_key'],
				'label' 	=> $field_data['label'],
				'fields' 	=> array()
			);
		}
	}
	return $fields;
}
function wpccf_include_template( $file_name ){
    $custom_template_path   = get_stylesheet_directory().'/wpcargo/wpcargo-custom-field-addons/'.$file_name.'.php';
    if( file_exists( $custom_template_path ) ){
        $template_path = $custom_template_path;
    }else{
        $template_path  = WPCARGO_CUSTOM_FIELD_PATH.'templates/'.$file_name.'.php';
    }
	return $template_path;
}
add_filter( 'wpcfe_registered_scripts', 'wpcf_scripts_to_wpcfe' );
function wpcf_scripts_to_wpcfe( $scripts ){
	$scripts[] = 'wpcargo-datetimepicker';
	$scripts[] = 'wpcargo-js';
	return $scripts;
}
add_filter( 'wpcfe_registered_styles', 'wpcf_style_to_wpcfe' );
function wpcf_style_to_wpcfe( $styles ){
	$styles[] = 'wpcargo-datetimepicker';
	$styles[] = 'wpccf-style';
	return $styles;
}
/*
 * Language Translation for the Ecncrypted Files
 */
function wpccf_activate_license_message(){
	// ==========================================
	// BYPASS: License activation message disabled
	// Modified on 2025-10-20 to allow usage without license restrictions
	// ==========================================
	return ''; // Return empty string to hide license message

	/*
	// ORIGINAL CODE - DISABLED
	return __( 'Please activate your license key', 'wpcargo-custom-field' ).' <a href="'.admin_url().'admin.php?page=wptaskforce-helper" title="WPCargo license page">'.__('here', 'wpcargo-custom-field' ).'</a>.';
	*/
}
function wpccf_manage_form_fields_label(){
	return __( 'Manage Form Fields', 'wpcargo-custom-field' );
}
function wpccf_print_label(){
	return __( 'Print Waybill', 'wpcargo-custom-field' );
}
function wpccf_cfprint_label(){
	return __( 'CF Print Waybill', 'wpcargo-custom-field' );
}
function wpccf_success_message(){
	return __( 'Field Successfully Added!', 'wpcargo-custom-field' );
}
function wpccf_error_message(){
	return __( 'Something went wrong in adding field!', 'wpcargo-custom-field' );
}
function wpccf_error_metakey_message( $meta_key ){
	return sprintf( __( "Can't process your request, metakey '%s' is a reserved metakey.", 'wpcargo-custom-field' ), $meta_key  );
}
function wpccf_license_helper_plugin_dependent_message(){
	return __('This plugin requires <a href="http://wpcargo.com/" target="_blank">WPTaskForce License Helper</a> plugin to be active!', 'wpcargo-custom-field');
}
function wpccf_wpcargo_plugin_dependent_message(){
	return __('This plugin requires <a href="https://wordpress.org/plugins/wpcargo/" target="_blank">WPCargo</a> plugin to be active!', 'wpcargo-custom-field');
}
function wpccf_cheating_plugin_dependent_message(){
	return __('Cheating, uh?', 'wpcargo-custom-field');
}
/*
 * Conditional Logics
 */
function wpccf_show_hide_options(){
	$opts = array(
		'show' => __('Show', WPCARGO_CUSTOM_FIELD_TEXTDOMAIN),
		'hide' => __('Hide', WPCARGO_CUSTOM_FIELD_TEXTDOMAIN),
	);
	return apply_filters('wpccf_show_hide_options', $opts);
}
function wpccf_condition_options(){
	$opts = array(
		'all' => __('All', WPCARGO_CUSTOM_FIELD_TEXTDOMAIN),
		'any' => __('Any', WPCARGO_CUSTOM_FIELD_TEXTDOMAIN),
	);
	return apply_filters('wpccf_condition_options', $opts);
}
function wpccf_condition_checker_options(){
	$opts = array(
		'is' => __('is', WPCARGO_CUSTOM_FIELD_TEXTDOMAIN),
		'is-not' => __('is not', WPCARGO_CUSTOM_FIELD_TEXTDOMAIN),
		'empty' => __('empty', WPCARGO_CUSTOM_FIELD_TEXTDOMAIN),
		'not-empty' => __('not empty', WPCARGO_CUSTOM_FIELD_TEXTDOMAIN),
	);
	return apply_filters('wpccf_condition_checker_options', $opts);
}