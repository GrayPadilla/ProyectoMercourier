<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
function wpc_admin_address_book_pagination( $pagelink, $numpages, $paged ){
	$pagination_args = array(
        'base' => $pagelink . '%_%',
        'format' => '&wpc-page=%#%',
        'total' => $numpages,
        'current' => $paged,
        'show_all' => false,
        'end_size' => 1,
        'mid_size' => 4,
        'prev_next' => true,
        'prev_text' => '&laquo;',
        'next_text' => '&raquo;',
        'type' => 'plain',
        'add_args' => false,
        'add_fragment' => ''
    );
    $paginate_links  = paginate_links($pagination_args);
    if ($paginate_links) {
        echo "<nav class='wpcargo-custom-pagination'>";
        echo "<span class='page-numbers page-num'>Page " . $paged . " of " . $numpages . "</span> ";
        echo $paginate_links;
        echo "</nav>";
    }
}
function wpc_book_address_pagination( $pagelink, $numpages, $paged ){
	$pagination_args = array(
        'base' => $pagelink . '%_%',
        'format' => 'page/%#%',
        'total' => $numpages,
        'current' => $paged,
        'show_all' => false,
        'end_size' => 1,
        'mid_size' => 4,
        'prev_next' => true,
        'prev_text' => '&laquo;',
        'next_text' => '&raquo;',
        'type' => 'plain',
        'add_args' => false,
        'add_fragment' => ''
    );
    $paginate_links  = paginate_links($pagination_args);
    if ($paginate_links) {
        echo "<nav class='wpcargo-custom-pagination'>";
        echo "<span class='page-numbers page-num'>Page " . $paged . " of " . $numpages . "</span> ";
        echo $paginate_links;
        echo "</nav>";
    }
}
function wpc_address_book_get_user_fullname( $userID ){
	$user_info = get_userdata( $userID );
	$fullname = '';
	if( !empty( $user_info->first_name ) && !empty($user_info->last_name) ){
		$fullname = ucfirst( $user_info->first_name ). ' ' . ucfirst( $user_info->last_name );
	}else{
		$fullname = $user_info->user_email;
	}
	return $fullname;
}
function wpc_address_book_get_user_book( $userID, $book ){
	global $wpdb;
	$sql = "SELECT tbl1.ID AS 'book_id' FROM `$wpdb->posts` as tbl1 JOIN `$wpdb->postmeta` AS tbl2 WHERE tbl1.ID = tbl2.post_id AND tbl1.post_type LIKE 'wpc_address_book' AND tbl1.post_author = ".$userID." AND tbl2.meta_key LIKE 'book' AND tbl2.meta_value LIKE '".$book."'";
	$results = $wpdb->get_results( $sql, OBJECT );
	return $results;
}
function wpc_address_book_get_user_role_name( $userID ){
	global $wp_roles;
	$user_data = get_userdata( $userID );
	if( !empty( $user_data->roles ) ){
		$role = array_shift($user_data->roles);
	}else{
		$role = 'subscriber';
	}
	
	$user_role_name = $wp_roles->roles[$role]['name'];
	return $user_role_name;
}
function wpc_registered_roles(){
	$wpcargo_roles = array( 'administrator', 'delivery_agent', 'wpcargo_client', 'wpcargo_driver', 'cargo_agent' ) ;
	$wpcargo_roles 	= apply_filters( 'wpc_address_book_registered_roles', $wpcargo_roles );
	return $wpcargo_roles;
}
/*
 * $data is a key and value pairs
 * Check if the address exist to avoid duplication
 * Type : shipper | receiver
 */
function wpc_has_address_book( $data, $type ){
	global $wpdb;
	$user_id 	= get_current_user_id(  );

	$main_metakey = get_option('wpc_address_'.$type.'_search');

	if( $main_metakey && array_key_exists($main_metakey, $data) ){
		$data = array(
			$main_metakey => $data[$main_metakey]
		);
	}
	$sql 		= "SELECT COUNT(*) FROM {$wpdb->prefix}posts as main";
	$sql 		.= " LEFT JOIN {$wpdb->prefix}postmeta as btype ON btype.post_id = main.ID";
	foreach ($data  as $key => $value ) {
		$sql 	.= " LEFT JOIN {$wpdb->prefix}postmeta as {$key} ON {$key}.post_id = main.ID";
	}
	$sql 		.= " WHERE main.post_author = {$user_id} AND main.post_status LIKE 'publish' AND main.post_type LIKE 'wpc_address_book'";
	$sql 		.= " AND btype.meta_key LIKE 'book' AND btype.meta_value LIKE '{$type}'";
	foreach ($data  as $key => $value ) {
		$value = is_array($value) ? maybe_serialize($value) : $value ;
		$sql 	.= " AND {$key}.meta_key LIKE '{$key}' AND {$key}.meta_value LIKE '{$value}'";
	}
	return $wpdb->get_var( $sql );
}
function wpc_address_book_get_custom_fields( $flag = '' ){
	global $wpdb;
	$table_prefix = $wpdb->prefix;
	$result_fields = $wpdb->get_results( 'SELECT * FROM `'.$table_prefix.'wpcargo_custom_fields` WHERE `section` LIKE "'.$flag.'" ORDER BY ABS(weight)', ARRAY_A );
	return $result_fields;
}
function wpc_address_book_get_field_key( $key = '' ){
	global $wpdb;
	$table_prefix = $wpdb->prefix;
	$result = '';
	if( !empty($key) || $key != '' ){
		$result= $wpdb->get_results( 'SELECT * FROM `'.$table_prefix.'wpcargo_custom_fields` WHERE `section` LIKE "'.$key.'" ORDER BY ABS(weight)', ARRAY_A );
	}
	return $result;
}
function wpc_address_book_metakeys( $key = '' ){
	global $wpdb;
	$table_prefix = $wpdb->prefix;
	$result = '';
	if( !empty($key) || $key != '' ){
		$result= $wpdb->get_col( 'SELECT `field_key` FROM `'.$table_prefix.'wpcargo_custom_fields` WHERE `section` LIKE "'.$key.'" ORDER BY ABS(weight)' );
	}
	return $result;
}
function wpcabook_address_types( ){
	global $wpdb;
	$table_prefix = $wpdb->prefix;
	return $wpdb->get_col( $wpdb->prepare(  'SELECT `field_key` FROM `'.$table_prefix.'wpcargo_custom_fields` WHERE `field_type` LIKE %s', 'address' ) );
}
function wpc_address_book_get_field_key_list(  ){
	global $wpdb;
	$table_prefix = $wpdb->prefix;
	$field_keys = $wpdb->get_results( 'SELECT `field_key` FROM `'.$table_prefix.'wpcargo_custom_fields` ORDER BY ABS(weight)', ARRAY_A );
	return $field_keys;
}
function wpcabook_get_field_type( $metakey ){
	global $wpdb;
	$table_prefix = $wpdb->prefix;
	return  $wpdb->get_var( $wpdb->prepare( 'SELECT `field_type` FROM `'.$table_prefix.'wpcargo_custom_fields` WHERE `field_key` LIKE %s', $metakey ) );
}
function wpc_address_book_get_address_list( $bookType ){
	global $wpdb;
	$table_prefix = $wpdb->prefix;
	$result = $wpdb->get_results( "SELECT `post_id` FROM `".$table_prefix."postmeta` WHERE `meta_key` LIKE 'book' AND `meta_value` LIKE '".$bookType."'", OBJECT );
	$bookIds = array();
	if( !empty( $result ) ){
		foreach( $result as $data ){
			$bookIds[] = $data->post_id;
		}
	}
	return $bookIds;
}
function wpc_address_book_get_address_list_by_user( $bookType, $userID = '' ){
	global $wpdb;
	if( empty( $userID ) ){
		$current_user = wp_get_current_user();
		$current_user = $current_user->ID;
	}else{
		$current_user = $userID;
	}
	$table_prefix = $wpdb->prefix;
	$result = $wpdb->get_results("SELECT * FROM `".$table_prefix."posts` AS tbl1, `".$table_prefix."postmeta` AS tbl2 WHERE tbl1.ID = tbl2.post_id AND tbl1.post_author = ".$current_user." AND tbl1.post_type LIKE 'wpc_address_book' AND tbl2.meta_key LIKE 'book' AND tbl2.meta_value LIKE '".$bookType."'", OBJECT );
	$bookIds = array();
	if( !empty( $result ) ){
		foreach( $result as $data ){
			$bookIds[] = $data->post_id;
		}
	}
	return $bookIds;
}
function wpc_address_book_get_field_key_label( $fieldKey ){
	global $wpdb;
	$table_prefix = $wpdb->prefix;
	$label = $wpdb->get_var( "SELECT `label` FROM `{$table_prefix}wpcargo_custom_fields` WHERE `field_key` LIKE '".$fieldKey."'" );
	return $label;
}
function wpc_address_book_get_all_data( $bookType = '' ){
	global $wpdb;
	$query = "SELECT tbl1.ID, tbl1.post_author FROM `$wpdb->posts` AS tbl1 INNER JOIN `$wpdb->postmeta` as tbl2 ON tbl1.ID = tbl2.post_id WHERE tbl1.post_type LIKE 'wpc_address_book' AND tbl1.post_status LIKE 'publish' AND tbl2.meta_key LIKE 'book' AND tbl2.meta_value LIKE '".$bookType."' ORDER BY tbl1.post_author, tbl2.meta_value ASC";
	$results = $wpdb->get_results( $query );
	return $results;
}
function wpc_abook_addresses( $bookType = '', $user_id = false ){
	global $wpdb;
    $search_data    = wpcaddress_search_data( $bookType );
    $search_option  = $search_data['meta_key'];
	$sql = "SELECT tbl1.ID FROM `{$wpdb->prefix}posts` AS tbl1";
	$sql .= " INNER JOIN `{$wpdb->prefix}postmeta` as tbl2 ON tbl1.ID = tbl2.post_id";
	$sql .= " INNER JOIN `{$wpdb->prefix}postmeta` as tbl3 ON tbl1.ID = tbl3.post_id";
	$sql .= " INNER JOIN `{$wpdb->prefix}postmeta` as tbl4 ON tbl1.ID = tbl4.post_id";
	$sql .= " WHERE tbl1.post_type LIKE 'wpc_address_book' AND tbl1.post_status LIKE 'publish'";
	if( $user_id != false ){
		$user_id		= (int)$user_id;
		$sql .= " AND ((tbl1.post_author = {$user_id}) OR (tbl4.meta_key LIKE 'public_{$bookType}' AND tbl4.meta_value = 1) )";
	}
	$sql .= " AND tbl2.meta_key LIKE 'book' AND tbl2.meta_value LIKE '{$bookType}'";
	if( !empty( $search_option  ) ){
		$sql .= " AND (tbl3.meta_key LIKE '{$search_option}' AND tbl3.meta_value <> '')";
	}
	$sql .= " GROUP BY tbl1.ID";
	$sql .= " ORDER BY tbl3.meta_value ASC";
	
	return $wpdb->get_col( $sql );
}

function wpcabook_search_addresses( $search = false, $bookType = '', $user_id = false ){
	global $wpdb;

	if( !$search ){
		return false;
	}
	
    $search_data    = wpcaddress_search_data( $bookType );
    $search_option  = $search_data['meta_key'];
    $param          = array();

	$sql = "SELECT tbl1.ID FROM `{$wpdb->prefix}posts` AS tbl1";
	$sql .= " INNER JOIN `{$wpdb->prefix}postmeta` as tbl2 ON tbl1.ID = tbl2.post_id";
	$sql .= " INNER JOIN `{$wpdb->prefix}postmeta` as tbl3 ON tbl1.ID = tbl3.post_id";
	$sql .= " INNER JOIN `{$wpdb->prefix}postmeta` as tbl4 ON tbl1.ID = tbl4.post_id";
	$sql .= " WHERE tbl1.post_type LIKE 'wpc_address_book' AND tbl1.post_status LIKE 'publish'";

	if( $user_id != false ){
		$sql .= " AND tbl1.post_author = %d";
		$param[] = (int)$user_id;
	}

	$sql .= " AND tbl2.meta_key LIKE 'book' AND tbl2.meta_value LIKE %s";
	$sql .= " AND tbl3.meta_key LIKE %s AND tbl3.meta_value LIKE %s";
	$sql .= " GROUP BY tbl1.ID";
	$sql .= " ORDER BY tbl3.meta_value ASC";

	$param[] = $bookType;
	$param[] = $search_option;
	$param[] = '%'.$search.'%';
	$sql = $wpdb->prepare( $sql, $param );
	return $wpdb->get_col( $sql );
}

function wpcabook_get_default( $type = "shipper"){
	$address_id = get_user_default_shipper( get_current_user_id(  ), $type );
	if( !$address_id ){
		return false;
	}
	$address = array();
	$fields = wpc_address_book_get_field_key( $type.'_info');
	if( $fields ){
		foreach ( $fields as $field ) {
			$address[$field['field_key']] = get_post_meta( $address_id, $field['field_key'], true );
		}
	}
	return $address;
}

function wpc_address_book_get_data_by_user( $userID, $bookType = '' ){
	global $wpdb;
	$query = "SELECT tbl1.ID, tbl2.meta_value, tbl1.post_author FROM `$wpdb->posts` AS tbl1 INNER JOIN `$wpdb->postmeta` as tbl2 ON tbl1.ID = tbl2.post_id WHERE tbl1.post_type LIKE 'wpc_address_book' AND tbl1.post_status LIKE 'publish' AND tbl1.post_author = %d AND tbl2.meta_key LIKE 'book' AND tbl2.meta_value LIKE '%s' ORDER BY tbl2.meta_value ASC";
	$results = $wpdb->get_results( $wpdb->prepare( $query, $userID, $bookType ) );
	return $results;
}
function wpc_address_book_get_list( $bookType ){
	$current_user 		= wp_get_current_user();
	$default_ID   		= get_user_meta( $current_user->ID, 'default_'.$bookType, true );
	$user_roles  		= $current_user->roles;
	$address_list 		= array();
	$field_list 		= wpc_address_book_get_field_key( $bookType.'_info'); 
	$access         	= array('administrator' );
	if( get_option( 'wpcfe_employee_all_access' ) ){
		$access[] = 'wpcargo_employee';
	}
	$access 			= apply_filters( 'can_wpc_get_all_address_roles', $access );
    if( array_intersect( $access, $user_roles ) ){
		$address = wpc_abook_addresses( $bookType );
	}else{
		$address = wpc_abook_addresses( $bookType, $current_user->ID );
	}	

	if( !empty( $address ) && !empty( $field_list ) ){
		foreach( $address as $address_id ){
			$field_container 	= array();
			foreach ( $field_list as $field_info ) {
				$value = maybe_unserialize( get_post_meta( $address_id, $field_info['field_key'], true ) );
				if( !wpcabook_get_field_type( $field_info['field_key'] ) == 'address' && is_array( $value ) ){
					$value = implode(",", $value);
				}
				$field_container[$field_info['field_key']] = $value;
			}
			$field_container['_assigned_to'] = get_post_field( 'post_author', $address_id );
			// Add default status
			$default = get_post_meta( $address_id, 'default_'.$bookType, true );
			$field_container['default_'.$bookType] = $default_ID === $address_id ? 1 : 0 ;
			$address_list[$address_id] = $field_container;
		}
	}
	return $address_list;
}
function wpc_address_book_get_all_list(){
	return wpc_address_book_get_list('shipper') + wpc_address_book_get_list('receiver');
}
function wpc_address_book_upload_errors(){
	$phpFileUploadErrors = array(
		0 => __('There is no error, the file uploaded with success.', 'wpcargo-address-book' ),
		1 => __('The uploaded file exceeds the upload_max_filesize directive in php.ini.', 'wpcargo-address-book' ),
		2 => __('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.', 'wpcargo-address-book' ),
		3 => __('The uploaded file was only partially uploaded.', 'wpcargo-address-book' ),
		4 => __('No file was uploaded.', 'wpcargo-address-book' ),
		6 => __('Missing a temporary folder.', 'wpcargo-address-book' ),
		7 => __('Failed to write file to disk.', 'wpcargo-address-book' ),
		8 => __('A PHP extension stopped the file upload.', 'wpcargo-address-book' ),
	);
	return $phpFileUploadErrors;
}
function wpc_address_book_convert_to_form_field( $fields = array(), $ffid = '' ){
	global $WPCCF_Fields;
	$WPCCF_Fields->convert_to_form_fields( $fields, '', '', $ffid.'-address-book-' );
}
function get_user_default_shipper( $user_id, $type = "shipper" ){
	$data = get_user_meta( $user_id, 'default_'.$type, true );
	return $data;
}
function wpc_address_book_get_user_meta_keys($bookType="receiver", $array_fields = array()  ){
	$default_id = get_user_meta( get_current_user_id(), 'default_'.$bookType, true );
	$meta_keys = array();
	if( !empty( $array_fields ) ){
		foreach ($array_fields as $key) {
			$meta_keys[$key['field_key']] = get_post_meta( $default_id, $key['field_key'], true );
		}
	}
	return $meta_keys;
}
function wpcaddress_search_data( $book = "shipper" ){
	$search_meta	= get_option('wpc_address_'.$book.'_search');
	if( $search_meta ){
		$search_label 	= wpc_address_book_get_field_key_label($search_meta);
		return array( 
			'meta_key' => $search_meta,
			'meta_label' => $search_label
		);
	}
	global $WPCCF_Fields;
	$field_list = $WPCCF_Fields->get_field_key( $book );
	$default_field = array_shift( $field_list );
	return array( 
		'meta_key' => $default_field['field_key'],
		'meta_label' => $default_field['label']
	);
}
function wpc_save_ajax_address_book( $data, $type ){
	$field_list 	= wpc_address_book_metakeys( $type.'_info' );
	if( empty($field_list) ){
		return false;
	}
	$formData 		= $data;
	$formatted_data = array();
	foreach( $formData as $data ){
		$formatted_data[$data['name']] = $data['value'];
	}
	$postID 	= wp_insert_post( array( 'post_status'  => 'publish', 'post_type'=>'wpc_address_book', 'post_author' => get_current_user_id( ) ) );
	if ( is_wp_error( $postID ) ) {
		return false;
	}
	$reg_metakeys 	= wpc_address_book_metakeys( $type.'_info' );
	update_post_meta( $postID, 'book', $type );
	foreach( $formatted_data as $metakey => $metavalue ){
		if( !in_array( $metakey, $reg_metakeys  ) ){
			continue;
		}
		$value = is_array( $metavalue ) ? $metavalue : sanitize_text_field( $metavalue );
		update_post_meta( $postID, $metakey, $value );
	}
	// Save Address
	$address_data = wpcab_get_address_serialize_array($formatted_data);
	if( $address_data ){
		foreach ($address_data as $add_metakey => $add_metavalue ) {
			update_post_meta( $postID, $add_metakey, $add_metavalue );
		}
	}
	do_action( 'wpc_after_add_address_book_ajax_save', $postID, $formData, $formatted_data, $type );
}
function wpc_save_address_book( $data, $type ){
	$field_list 	= wpc_address_book_metakeys( $type.'_info' );
	if( empty($field_list) ){
		return false;
	}
	$field_value  	= array();
	if( !empty( $field_list ) ){
		foreach ($field_list as $meta_key ) {
			if( !array_key_exists( $meta_key, $data ) ){
				continue;
			}
			$value 	= is_array( $data[$meta_key ] ) ? $data[$meta_key ] : sanitize_text_field( $data[$meta_key ] );
			$field_value[ $meta_key ] = $value;
		}
	}

	$has_address_book =  wpc_has_address_book( $field_value, $type );

	if( $has_address_book || empty( $field_value ) ){
		return false;
	}
	// Create post object
	$book_args = array(
		'post_type'     => 'wpc_address_book',
		'post_status'   => 'publish',
		'post_author'   => get_current_user_id(),
	);
	// Insert the post into the database
	$book_id = wp_insert_post( $book_args );
	// Check if Books successfull created
	if( $book_id ){
		update_post_meta( $book_id, 'book', $type );
		foreach ( $field_value as $key => $value) {
			update_post_meta( $book_id, $key, $value );
		}
	}
	do_action( 'wpc_after_add_address_book_save', $book_id, $data, $type );
}
function wpc_address_book_get_frontend_page(){

	$wpc_address_book_get_frontend_page = get_option('wpc_address_book_get_frontend_page');
	$shortcode_id = '';

	if( !$wpc_address_book_get_frontend_page ){
		global $wpdb;
		$sql 			= "SELECT `ID` FROM {$wpdb->prefix}posts WHERE `post_content` LIKE '%[wpc_address_book]%' AND `post_status` LIKE 'publish' LIMIT 1";
		$shortcode_id 	= $wpdb->get_var( $sql );
		update_option( 'wpc_address_book_get_frontend_page', $shortcode_id );

		if( ! $shortcode_id ){
			// Create post object
			$address_book = array(
				'post_title'    => __('Address Book', 'wpc-import-export'),
				'post_content'  => '[wpc_address_book]',
				'post_status'   => 'publish',
				'post_type'   	=> 'page',
			);	
			// Insert the post into the database
			$shortcode_id = wp_insert_post( $address_book );		
		}
		if( $shortcode_id ){
			update_post_meta( $shortcode_id, '_wp_page_template', 'dashboard.php');
			update_option( 'wpc_address_book_get_frontend_page', $shortcode_id );
		}
	}
	return $wpc_address_book_get_frontend_page;
}
function wpc_can_add_address_book(){
	return apply_filters( 'wpc_can_access_address_book', true );
}
function wpcab_include_template( $file_name ){
	$file_slug              = strtolower( preg_replace('/\s+/', '_', trim( str_replace( '.tpl', '', $file_name ) ) ) );
    $file_slug              = preg_replace('/[^A-Za-z0-9_]/', '_', $file_slug );
    $custom_template_path   = get_stylesheet_directory().'/wpcargo/wpcargo-address-book-add-ons/'.$file_name.'.php';
    if( file_exists( $custom_template_path ) ){
        $template_path = $custom_template_path;
    }else{
        $template_path  = WPCARGO_ADDRESS_BOOK_PATH.'templates/'.$file_name.'.php';
		$template_path  = apply_filters( "wpcab_locate_template_{$file_slug}", $template_path );
    }
	return $template_path;
}
function wpcab_admin_include_template( $file_name ){
	$file_slug              = strtolower( preg_replace('/\s+/', '_', trim( str_replace( '.tpl', '', $file_name ) ) ) );
    $file_slug              = preg_replace('/[^A-Za-z0-9_]/', '_', $file_slug );
	$custom_template_path   = get_stylesheet_directory().'/wpcargo/wpcargo-address-book-add-ons/admin/'.$file_name.'.php';
	if( file_exists( $custom_template_path ) ){
		$template_path = $custom_template_path;
	}else{
		$template_path  = WPCARGO_ADDRESS_BOOK_PATH.'admin/templates/'.$file_name.'.php';
		$template_path  = apply_filters( "wpcab_locate_admin_template_{$file_slug}", $template_path );
	}
	return $template_path;
}
function wpcab_get_address_serialize_array( $data_array ){
	$address_keys		= wpcabook_address_types();
	if( !$address_keys ){
		return false;
	}
	$address_data 		= array();
	foreach ($data_array as $metakey => $metavalue) {
		foreach ($address_keys as $addkey ) {
			if( strpos($metakey, $addkey.'[') !== 0 ){
				continue;
			}
			$sub_meta	= str_replace( array($addkey.'[', ']'), '', $metakey );
			$address_data[$addkey][$sub_meta] = $metavalue;
		}
	}
	return $address_data;
}

//#Added functionalities - User Roles
function can_wpcfe_delete_address(  ){
    $user_roles     = wpcfe_current_user_role();
    $result         = false;
    if( array_intersect( wpcfe_delete_address_role(), $user_roles ) ){
        $result = true;
    }
    return apply_filters( 'can_wpcfe_delete_address', $result );
}

function wpcfe_delete_address_role(){
    $delete_shipment_role = array( 'administrator', 'wpcargo_employee' ) ;
    return apply_filters( 'wpcfe_delete_address_role', $delete_shipment_role );
}


/*
 * Language translation for the encrypted file
 */
function wpc_address_book_activate_license_message(){
	return __( 'Please activate your license key <a href="'.admin_url().'admin.php?page=wptaskforce-helper" title="WPCargo license page">here</a>.', 'wpcargo-address-book' );
}
function wpc_address_book_role_access_label(){
	return __('This user is not allowed to have Address Book, Please update user role with WPCargo user role.', 'wpcargo-address-book' );
}
function wpc_address_book_page_access_label(){
	return __( 'Sorry, You are not allowed to Access this page.', 'wpcargo-address-book' );
}
function wpc_address_book_page_template_error_label(){
	return __( 'Page access restricted, This page need to setup template to WPCargo Dashboard to make it work.', 'wpcargo-address-book' );
}
function wpc_address_book_list_label(){
	return __('Address Book List', 'wpcargo-address-book' );
}
function wpc_address_book_enable_shipper_receiver_search_label(){
	return __('Shipper and Receiver Address Book is Disabled. Please Enable', 'wpcargo-address-book');
}
function wpc_address_book_enable_receiver_search_label(){
	return __('Receiver Address Book is Disabled. Please Enable', 'wpcargo-address-book');
}
function wpc_address_book_enable_shipper_search_label(){
	return __('Shipper Address Book is Disabled. Please Enable', 'wpcargo-address-book');
}
function wpc_address_book_enable_search_link_label(){
	return __('here', 'wpcargo-address-book');
}
function wpc_address_book_set_up_settings_label(){
	return __( 'Please setup Address Book settings', 'wpcargo-address-book' );
}
function wpc_address_book_label(){
	return __( 'Address Book', 'wpcargo-address-book' );
}
function wpc_address_book_description_label(){
	return __( 'Address Book Description', 'wpcargo-address-book' );
}
function wpc_address_book_archive_label(){
	return __( 'Book Archives', 'wpcargo-address-book' );
}
function wpc_address_book_parent_label(){
	return __( 'Parent Book:', 'wpcargo-address-book' );
}
function wpc_address_book_all_label(){
	return __( 'All Books', 'wpcargo-address-book' );
}
function wpc_address_book_add_new_address_label(){
	return __( 'Add New Address', 'wpcargo-address-book' );
}
function wpc_address_book_add_address_label(){
	return __( 'Add Address', 'wpcargo-address-book' );
}
function wpc_address_book_new_address_label(){
	return __( 'New Address', 'wpcargo-address-book' );
}
function wpc_address_book_my_address_label(){
	return __( 'My Address Book', 'wpcargo-address-book' );
}
function wpc_address_book_edit_address_label(){
	return __( 'Edit Address', 'wpcargo-address-book' );
}
function wpc_address_book_update_address_label(){
	return __( 'Update Address', 'wpcargo-address-book' );
}
function wpc_address_book_view_address_label(){
	return __( 'View Address', 'wpcargo-address-book' );
}
function wpc_address_book_search_address_label(){
	return __( 'Search Address', 'wpcargo-address-book' );
}
function wpc_address_book_not_found_label(){
	return __( 'Not found', 'wpcargo-address-book' );
}
function wpc_address_book_not_found_trash_label(){
	return __( 'Not found in Trash', 'wpcargo-address-book' );
}
function wpc_address_book_featured_image_label(){
	return __( 'Featured Image', 'wpcargo-address-book' );
}
function wpc_address_book_set_featured_image_label(){
	return __( 'Set featured image', 'wpcargo-address-book' );
}
function wpc_address_book_removed_featured_image_label(){
	return __( 'Remove featured image', 'wpcargo-address-book' );
}
function wpc_address_book_use_featured_image_label(){
	return __( 'Use as featured image', 'wpcargo-address-book' );
}
function wpc_address_book_shipper_empty_label(){
	return __( 'Shipper Book Address Empty', 'wpcargo-address-book' );
}
function wpc_address_book_insert_address_label(){
	return __( 'Insert into Address', 'wpcargo-address-book' );
}
function wpc_address_book_upload_address_label(){
	return __( 'Uploaded to this Address', 'wpcargo-address-book' );
}
function wpc_address_book_list_address_label(){
	return __( 'Address list', 'wpcargo-address-book' );
}
function wpc_address_book_list_address_nav_label(){
	return __( 'Address list navigation', 'wpcargo-address-book' );
}
function wpc_address_book_filter_book_label(){
	return __( 'Filter Books list', 'wpcargo-address-book' );
}
function wpc_address_book_receiver_empty_label(){
	return __( 'Receiver Book Address Empty', 'wpcargo-address-book' );
}
function wpc_address_book_settings_label(){
	return __( 'Address Book Settings', 'wpcargo-address-book' );
}
function wpc_address_book_plugin_helper_dependent_label(){
	return __('This plugin requires <a href="http://wpcargo.com/" target="_blank">WPTaskForce License Helper</a> plugin to be active!', 'wpcargo-address-book' );
}
function wpc_address_book_plugin_wpcargo_dependent_label(){
	return __( 'This plugin requires <a href="https://wordpress.org/plugins/wpcargo/" target="_blank">WPCargo</a> plugin to be active!', 'wpcargo-address-book' );
}
function wpc_address_book_plugin_custom_field_dependent_label(){
	return __( 'This plugin requires <strong>WPCargo Custom Field Add-ons</strong> plugin to be active!', 'wpcargo-address-book' );
}
function wpc_address_book_frontend_manager_plugin_dependent_message(){
	return __( 'This plugin requires <strong>WPCargo Frontend Manager</strong> plugin to be active!', 'wpcargo-address-book' );
}
function wpc_address_book_plugin_cheating_label(){
	return __( 'Cheating, uh?', 'wpcargo-address-book' );
}