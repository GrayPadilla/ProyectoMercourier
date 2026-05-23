<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}
function wpcpod_export_file_format_list(){
	$extension = array(
		'xls' => "\t", 
		'xlt' => "\t", 
		'xla' => "\t", 
		'xlw' => "\t",
		'csv' => ","
	);
	return apply_filters( 'wpcpod_export_file_format_list', $extension );
}
function get_field_section( $key = '' ){
	global $wpdb;
	$table_prefix = $wpdb->prefix;
	$result = '';
	if( !empty($key) || $key != '' ){
		$result= $wpdb->get_results( 'SELECT * FROM `'.$table_prefix.'wpcargo_custom_fields` WHERE `section` LIKE "%'.$key.'%" ORDER BY `weight`', ARRAY_A );
	}
	return $result;
}
function get_field_label( $id ){
	global $wpdb;
	$table_prefix = $wpdb->prefix;
	$result = $id;
	if( !empty($id) || $id != '' ){
		$result= $wpdb->get_var( 'SELECT `label` FROM `'.$table_prefix.'wpcargo_custom_fields` WHERE `id` ='.$id );
	}
	return $result;
}
function get_field_key( $id ){
	global $wpdb;
	$table_prefix = $wpdb->prefix;
	$result = $id;
	if( !empty($id) || $id != '' ){
		$result= $wpdb->get_var( 'SELECT `field_key` FROM `'.$table_prefix.'wpcargo_custom_fields` WHERE `id` ='.$id );
	}
	return $result;
}
function wpcpod_route_origin(){
	$options = get_option( 'wpcpod_route_origin' );
	if( empty( $options ) ){
		return array(
			'latitude' => '',
			'longitude' => '',
			'address' => ''
		);
	}
	return $options;
}
function wpcpod_route_allowed_user( ){
	$current_user 	= wp_get_current_user();
	$user_roles 	= $current_user->roles;
	$allowed_user 	=  apply_filters( 'wpcpod_route_allowed_user', array( 'wpcargo_driver' ) );
	if( array_intersect( $user_roles, $allowed_user ) ){
		return true;
	}
	return false;
}
function wpcpod_route_shipments( ){
	global $wpdb;
	if( !wpcpod_route_allowed_user() || empty( wpcpod_route_status() ) ){
		return array();
	}
	// SQL Query
	$user_id = get_current_user_id();
	$status  = implode("','", wpcpod_route_status());
	$sql = "SELECT tbl1.ID as id, tbl1.post_title as number FROM `{$wpdb->prefix}posts` as tbl1";
	$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta AS tbl2 ON tbl2.post_id = tbl1.ID";
	$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta AS tbl3 ON tbl3.post_id = tbl1.ID";
	$sql .= " WHERE tbl1.post_status LIKE 'publish'";
	$sql .= " AND tbl1.post_type LIKE 'wpcargo_shipment'";
	$sql .= " AND tbl2.meta_key LIKE 'wpcargo_driver'";
	$sql .= " AND tbl2.meta_value = {$user_id}";
	$sql .= " AND tbl3.meta_key LIKE 'wpcargo_status'";
	$sql .= " AND tbl3.meta_value IN ('{$status}')";
	$sql .= " GROUP BY tbl1.ID DESC";
	$sql = apply_filters( 'wpcpod_route_shipments_query', $sql );
	$shipments 	= $wpdb->get_results( $sql );
	return $shipments;
}
function wpcpod_route_addresses( ){
	global $wpdb;
	$addresses 		= array();
	$shipments 		= wpcpod_route_shipments();
	$route_fields 	= wpcpod_route_fields();
	if( !empty( $shipments ) && !empty( $route_fields ) ){	
		foreach ($shipments as $shipment ) {
			$_address = '';
			foreach ( $route_fields as $key ) {
				$value = maybe_unserialize( get_post_meta( $shipment->id, $key, true ) );
				if( is_array( $value ) ){
					$value = implode(" ", wpcpod_route_status());
				}
				if( empty( trim($value) ) ){
					continue;
				}
				$_address .= $value.' ';
			}
			$_address = apply_filters( 'wpcpod_route_shipment_address', $_address );
			if( empty( trim($_address) ) ){
				continue;
			}
			$addresses[$shipment->id] = array(
				'number'  => $shipment->number,
				'address' => $_address
			);
		}
	}
	return $addresses;
}
function wpcpod_route_status(){
	return !empty( get_option( 'wpcpod_route_status' ) ) ? get_option( 'wpcpod_route_status' ) : array() ;
}
function wpcpod_route_fields(){
	return !empty( get_option( 'wpcpod_route_field' ) ) ? get_option( 'wpcpod_route_field' ) : array() ;
}
function wpcpod_report_headers(){
	global $wpdb;
	$headers = array(
		'shipment_number' => esc_html__( 'Shipment Number', 'wpcargo-pod' ),
		'registered_shipper' => esc_html__( 'Registered Shipper', 'wpcargo-pod' ),
	);
	$results = $wpdb->get_results( "SELECT `label`, `field_key` as 'key' FROM `{$wpdb->prefix}wpcargo_custom_fields` ORDER BY `weight` ASC" );
	if( !empty( $results ) ){
		foreach ( $results as $result ) {
			$headers[$result->key] = $result->label;
		}
	}
	$headers['shipment_status'] = esc_html__( 'Shipment Status', 'wpcargo-pod' );
	return apply_filters( 'wpcpod_report_headers', $headers );
}
function wpcargo_pod_is_assigned( $shipment_id ){
	$assigned 		= false;
	$user_id 		= get_current_user_id();
	$wpcargo_driver = get_post_meta( $shipment_id, 'wpcargo_driver', true );
	if( $user_id == $wpcargo_driver && is_user_logged_in() ){
		$assigned = true;
	}
	return $assigned;
}
function wpcargo_pod_status(){
	global $wpcargo;
	$wpcargo_status 		= $wpcargo->status;
	$wpcargo_pod_status 	= get_option('wpcargo_pod_status');
	$wpcargo_pod_status 	= !empty( $wpcargo_pod_status) && is_array( $wpcargo_pod_status ) ? $wpcargo_pod_status : array() ;
	$pod_status 			= array();
	if( !empty( $wpcargo_status ) ){
		foreach ( $wpcargo_status as $status ) {
			if( in_array($status, $wpcargo_pod_status) ){
				continue;
			}
			$pod_status[] = $status;
		}
	}
    return apply_filters( 'wpcargo_pod_status', $pod_status );
}
function wpcargo_pod_get_delivered_status(){
	$status = '';
	$pod_option_settings = get_option('wpcargo_pod_option_settings');
	if( !empty($pod_option_settings) && array_key_exists( 'pod_driver_signed', $pod_option_settings ) ){
		$status = $pod_option_settings['pod_driver_signed'];
	}
	return $status;
}
function wpcargo_pod_get_cancelled_status(){
	$status = '';
	$pod_option_settings = get_option('wpcargo_pod_option_settings');
	if( !empty($pod_option_settings) && array_key_exists( 'pod_driver_cancelled', $pod_option_settings ) ){
		$status = $pod_option_settings['pod_driver_cancelled'];
	}
	return $status;
}
function wpcargo_pod_get_drivers(){
	global $wpcargo;
	if( !$wpcargo ){
		return false;
	}
	$drivers_list = array();
	$args = array(
		'role__in'     => array( 'wpcargo_driver' ),
	);	
	$args 	 = apply_filters( 'wpcargo_pod_get_drivers_arguments', $args );
	$drivers = get_users( $args );
	if( !empty( $drivers ) ){
		foreach ( $drivers  as $driver ) {
			$drivers_list[$driver->ID] = $wpcargo->user_fullname( $driver->ID );
		}
	}
	return apply_filters( 'wpcargo_pod_get_drivers_lists', $drivers_list );
}
function wpcargo_pod_current_user_role(){
    $current_user   = wp_get_current_user();
    $user_roles     = $current_user->roles;
    return $user_roles;
}
function wpcargo_pod_is_driver(){
	if( in_array( 'wpcargo_driver', wpcargo_pod_current_user_role() ) ){
		return true;
	}
	return false;
}
function can_export_wpcpod_report(){
	$can_access = apply_filters( 'can_export_wpcpod_report', array( 'administrator' ) );
	if( array_intersect( $can_access, wpcargo_pod_current_user_role() ) ){
		return true;
	}
	return false;
}
function wpcpod_to_slug( $string = '' ){
    $_string = strtolower( preg_replace('/\s+/', '_', trim( $string ) ) );
    $slug   =  substr( preg_replace('/[^A-Za-z0-9_\-]/', '', $_string ), 0, 60 );
    return apply_filters( 'wpcpod_to_slug', $slug, $string );
}
function wpcpod_api_shipment_status( ){
    global $wpcargo;
	$status 	= array();
	$exd_status = !empty( get_option( 'wpcargo_pod_status' ) ) ? get_option( 'wpcargo_pod_status' ) : array();
    if( !empty( $wpcargo->status ) ){
        foreach( $wpcargo->status as $_status ){
			if( in_array( $_status, $exd_status) ){
				continue;
			}
            $slug = wpcpod_to_slug( $_status );
            $status[$slug] = $_status;
        }
    }
    return $status;
}
function wpcpod_api_delican_status( ){
	global $wpcargo;
	$podapp_status 	= get_option('wpcargo_podapp_status') ? get_option('wpcargo_podapp_status') : array();	
	$api_status		= wpcpod_api_shipment_status( );
	$status 		= array();
    if( !empty( $api_status ) ){
        foreach( $api_status as $key => $value ){
			if( in_array( $key, $podapp_status) ){
				$status[$key] = $value;
				continue;
			}
        }
    }
    return $status;
}

function wpcpod_api_fields_status( ){
	$options 			= get_option('wpcargo_pod_option_settings') ? get_option('wpcargo_pod_option_settings') : array();	
	$shipper_fields 	= get_field_section('shipper_info');
	$receiver_fields 	= get_field_section('receiver_info');
	$fields = array(
		'shipper' =>array(),
		'receiver' => array()
	);
	if( !empty( $options ) ){
		if( !empty( $shipper_fields ) ){
			foreach( $shipper_fields as $shipper ){
				if( array_key_exists( 'shipper_fields', $options ) ){
					if( !in_array( $shipper['id'], $options['shipper_fields'] ) ){
						continue;
					}
					$fields['shipper'][$shipper['field_key']] = $shipper['label'];
					continue;
				}
				$fields['shipper'][$shipper['field_key']] = $shipper['label'];
			}
		}
		if( !empty( $receiver_fields ) ){
			foreach( $receiver_fields as $receiver ){
				if( array_key_exists( 'receiver_fields', $options ) ){
					if( !in_array( $receiver['id'], $options['receiver_fields'] ) ){
						continue;
					}
					$fields['receiver'][$receiver['field_key']] = $receiver['label'];
					continue;
				}
				$fields['receiver'][$receiver['field_key']] = $receiver['label'];
			}
		}
	}
	return $fields;
}
function wpcpod_clean_dir( $directory ){
	$files = glob( $directory.'*');
	foreach($files as $file){ // iterate files
		if(is_file($file)){
			$basename = basename( $file );
			preg_match ( '/([0-9]+)/', $basename, $matches );
			if( empty( $matches ) ){
				unlink($file);
				continue;
			}
			$timelapse = strtotime("now") - $matches[0];
			if( $timelapse >= 300 ){
				unlink($file);
				continue;
			}
		}
	}
}
// AJAX - Hook
function wpcpod_generate_report(){
	global $wpdb, $wpcargo;
	$driverID 	= (int)$_POST['driverID'];
	if( wpcargo_pod_is_driver() ){
		$driverID 	= get_current_user_id( );
	}
	$status  	= sanitize_text_field( $_POST['status'] );
	$dateFrom 	= $_POST['dateFrom'];
	$dateTo  	= $_POST['dateTo'];
	$parameter 	= array( $driverID );
	// SQL Query
	$sql = "SELECT tbl1.ID FROM `{$wpdb->prefix}posts` as tbl1";
	$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta AS tbl2 ON tbl2.post_id = tbl1.ID";
	$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta AS tbl3 ON tbl3.post_id = tbl1.ID";
	$sql .= " WHERE tbl1.post_status LIKE 'publish'";
	$sql .= " AND tbl1.post_type LIKE 'wpcargo_shipment'";
	$sql .= " AND tbl2.meta_key LIKE 'wpcargo_driver'";
	$sql .= " AND tbl2.meta_value = %d";
	if( !empty( $status ) ){
		$parameter[] = $status;
		$sql .= " AND tbl3.meta_key LIKE 'wpcargo_status'";
		$sql .= " AND tbl3.meta_value = %s";
	}
	if( !empty( $dateFrom ) && !empty( $dateTo ) ){
		if( strtotime($dateFrom) > strtotime($dateTo) ){
			$parameter[] = $dateTo.' 00:00:00';
			$parameter[] = $dateFrom.' 11:59:59';
		}else{
			$parameter[] = $dateFrom.' 00:00:00';
			$parameter[] = $dateTo.' 11:59:59';
		}
		$sql .= " AND tbl1.post_date BETWEEN %s AND %s";
	}elseif( !empty( $dateFrom ) && empty( $dateTo )){
		$parameter[] = $dateFrom.' 00:00:00';
		$parameter[] = $dateFrom.' 11:59:59';
		$sql .= " AND tbl1.post_date BETWEEN %s AND %s";
	}elseif( empty( $dateFrom ) && !empty( $dateTo ) ){
		$parameter[] = $dateTo.' 00:00:00';
		$parameter[] = $dateTo.' 11:59:59';
		$sql .= " AND tbl1.post_date BETWEEN %s AND %s";
	}
	$sql .= " GROUP BY tbl1.ID DESC";
	$sql 		= $wpdb->prepare( $sql, $parameter );
	$shipments 	= $wpdb->get_col( $sql );
	$file_url   = '';

	if( !empty( $shipments ) ){
		$headers 			= wpcpod_report_headers();
		$file_label 		= array_values( $headers );
		$file_key 			= array_keys( $headers );
		// Import variables
		$file_directory 	= WPCARGO_POD_PATH."export-storage".DIRECTORY_SEPARATOR;
		$file_url 			= WPCARGO_POD_URL."export-storage".DIRECTORY_SEPARATOR;
		// Remove all Existing Files
		wpcpod_clean_dir( $file_directory );
		$format_list 		= wpcpod_export_file_format_list();
		$file_format  		= apply_filters( 'wpcpod_export_file_format', "csv" );
		$delimiter 			= $format_list[ $file_format ];
		if( !array_key_exists( trim($file_format), $format_list ) ){
			$file_format 	= 'csv';
			$delimiter 		= ',';
		}
		$file_format 		= str_replace('.', '', $file_format);
		$filename_unique 	= "report-".time().'.'.trim($file_format);
		$csv_file 			= fopen($file_directory.$filename_unique, "w");
		fprintf($csv_file, chr(0xEF).chr(0xBB).chr(0xBF));
		fputcsv( $csv_file, $file_label, $delimiter );
		foreach ( $shipments as $shipment_id ) {
			$shipment_value = array();
			foreach ($file_key as $metakey ) {
				$value = maybe_unserialize ( get_post_meta( $shipment_id, $metakey, TRUE) );
				$value = apply_filters( 'wpcpod_generate_report_data', $value, $shipment_id, $metakey );
				if( $metakey == 'shipment_number' ){
					$shipment_value[] 	= get_the_title( $shipment_id );
					continue;
				}
				if( $metakey == 'registered_shipper' ){
					$reg_shipper 		= (int)get_post_meta( $shipment_id, 'registered_shipper', TRUE);
					$value 				= '';
					if( $reg_shipper ){
						$value 			= $wpcargo->user_fullname( $reg_shipper );
					}
					$shipment_value[] 	= $value;
					continue;
				}
				if( $metakey == 'shipment_status' ){
					$value 				= get_post_meta( $shipment_id, 'shipment_status', TRUE);
					$shipment_value[] 	= $value;
					continue;
				}
				if( is_array( $value ) ){
					$value = implode(",", $value);
				}
				$shipment_value[] = $value;
			}
			fputcsv( $csv_file, $shipment_value, $delimiter );
		}
		fclose($csv_file);
	}
	$message 	= esc_html__( 'No shipment found to generate report', 'wpcargo-pod' );
	$shipcount 	= count( $shipments );
	if( $shipcount > 0 ){
		$message = esc_html__( 'Please wait while generating file...', 'wpcargo-pod' );;
	}
	wp_send_json(
		array(
			'rows' => $shipcount,
			'file_url' => $file_url.$filename_unique,
			'file_name' => $filename_unique,
			'message'  => $message
		)
	);
	wp_die();
}
add_action( 'wp_ajax_wpcpod_generate_report', 'wpcpod_generate_report' );
add_action( 'wp_ajax_nopriv_wpcpod_generate_report', 'wpcpod_generate_report' );

function wpcpod_get_route_address_order(){
	$poo 			= true;
	$address_list 	= wpcpod_route_addresses();
	$route_origin 	= wpcpod_route_origin();
	$waypoints 		= array();
	$shipments 		= array();
	if( empty( get_option('shmap_api') ) ){
		return array(
			'status' 	=> 'error',
			'message' 	=> printf( __('Google API key required to run the Driver Route Planner. Add API here <a href="%s" class="btn btn-primary btn-sm">Here</a>', admin_url( 'admin.php?page=wpc-pod-settings' ) ), 'wpcargo-pod')
		);
	}
	if( empty( $address_list ) ){
		return array(
			'status' 	=> 'error',
			'message' 	=> __('No Delivery for route found.', 'wpcargo-pod')
		);
	}
	if( !empty( $route_origin['address'] ) ){
		$origin = array(
			'id' 		=> null,
			'number' 	=> __('Point of Orgin ', 'wpcargo-pod'),
			'address' 	=> $route_origin['address']
		);
	}else{
		$poo 	  = false;
		$key      = key($address_list);
		$origin   = $address_list[$key];
		$origin = array(
			'id' 		=> $key,
			'number' 	=> $address_list[$key]['number'],
			'address' 	=> $address_list[$key]['address']
		);
		//unset($address_list[$key]);
	}
	$counter = 1;
	foreach ($address_list as $shipmentID => $shipment ) {
		$shipmentNumber = $shipment['number'];
		$destination 	= urlencode( $shipment['address'] );
		$distance_data 	= file_get_contents('https://maps.googleapis.com/maps/api/distancematrix/json?&origins='.urlencode( $origin['address'] ).'&destinations='.$destination.'&key='.get_option('shmap_api') );
		$distance_arr 	= json_decode($distance_data);
		if( $distance_arr->status=='OK' && $distance_arr->rows[0]->elements[0]->status == 'OK' ){
			$distance = $distance_arr->rows[0]->elements[0]->distance->value;
			$data = array(
				'id' 		=> $shipmentID,
				'number' 	=> $shipmentNumber,
				'address' 	=> urldecode( $destination ),
			);

			$waypoints[$distance] 	= $data;
			$shipments[$distance] 	= $data;
		}else{
			$data = array(
				'id' 		=> $shipmentID,
				'number' 	=> $shipmentNumber,
				'address' 	=> urldecode( $destination ),
			);
			$waypoints[$counter] 	= $data;
			$shipments[$counter] 	= $data;
		}
		$counter++;
	}
	ksort($waypoints);
	ksort($shipments);
	$shipments  	= array_values( $shipments );
	$waypoints  	= array_values( $waypoints );
	$pointcount 	= count( $waypoints );
	if( count( $waypoints ) == 0  ){
		$destination = $origin;
	}else{
		$destination 	= array_pop($waypoints);
	}
	return array(
			'status' => 'success',
			'waypoints' => $waypoints,
			'origin' => $origin,
			'destination' => $destination,
			'shipments' => $shipments,
			'poo' => $poo
		);
}

function wpcpod_route_address_order( ){
	wp_send_json( wpcpod_get_route_address_order() );
	wp_die();
}
add_action( 'wp_ajax_wpcpod_generate_route_address', 'wpcpod_route_address_order' );

/*
 * Language translation for encrypted file
 */
function wpcargo_pod_report_label(){
	return esc_html__('Driver Report', 'wpcargo-pod');
}
function wpcargo_pod_add_metabox_label(){
	return esc_html__('WPCargo Proof of Delivery', 'wpcargo-pod' );
}
function wpcargo_pod_activate_license_message(){
	return esc_html_e( 'Please activate your license key <a href="'.admin_url().'admin.php?page=wptaskforce-helper" title="WPCargo license page">here</a>.', 'wpcargo-pod' );
}
function wpcargo_pod_activate_api_message(){
	return esc_html_e( 'Google Map API Key is not activated.', 'wpcargo-pod' ); 
}
function wpcargo_pod_route_access_message(){
	return esc_html_e( 'Sorry you are not allowed to access this page, This page are only for WPCargo Driver users.', 'wpcargo-pod' ); 
}
function wpcargo_pod_current_signature_label(){
	return esc_html__('Your current signature', 'wpcargo-pod' );
}
function wpcargo_pod_signature_save_label(){
	return esc_html__( 'Signature Successfully Saved!', 'wpcargo-pod' );
}
function wpcargo_pod_signature_error_label(){
	return esc_html__( 'Error on saving!', 'wpcargo-pod' );
}
function wpcargo_pod_permission_error_message(){
	return esc_html_e("Sorry you don't have enough permission to access this page.", 'wpcargo-pod' );
}
function wpcargo_pod_delivered_label(){
	return esc_html__( 'Delivered', 'wpcargo-pod' );
}
function wpcargo_pod_cancelled_label(){
	return esc_html__( 'Cancelled', 'wpcargo-pod' );
}
function wpcargo_pod_back_dashboard_label(){
	return esc_html__( 'Back to Dashboard','wpcargo-pod' );
}
function wpcargo_pod_error_cheating_label(){
	return esc_html__('Cheating, uh?', 'wpcargo-pod' );
}
function wpcargo_pod_error_wpcargo_label(){
	return esc_html__('This plugin requires <a href="https://wordpress.org/plugins/wpcargo/" target="_blank">WPCargo</a> plugin to be active!', 'wpcargo-pod' );
}
function wpcargo_pod_error_wptaskforce_license_label(){
	return esc_html__('This plugin requires <a href="http://wpcargo.com/" target="_blank">WPTaskForce License Helper</a> plugin to be active!', 'wpcargo-pod');
}
function wpcargo_pod_activate_wpcfe_message(){
	return esc_html__( 'This plugin requires <strong>WPCargo Frontend Manager</strong> plugin to be active!', 'wpcargo-pod' );
}