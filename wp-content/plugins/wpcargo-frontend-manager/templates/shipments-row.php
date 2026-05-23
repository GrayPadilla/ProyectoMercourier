<?php
$shipper_meta 	= get_post_meta( $shipment_id, $shipper_data['field_key'], true );
$receiver_meta 	= get_post_meta( $shipment_id, $receiver_data['field_key'], true );
$status 		= get_post_meta( $shipment_id, 'wpcargo_status', true );
// $ship_date      = get_post_meta( $shipment_id, 'ship_date', true ) ? wpcfe_convert_date( get_post_meta( $shipment_id, 'ship_date', true ) ) : '';

$ship_date 		= get_post_meta( $shipment_id, 'origin_delivery_window', true ) ? get_post_meta( $shipment_id, 'origin_delivery_window', true ) : '-'; 
$ship_date_tz 	= get_post_meta( $shipment_id, 'origin_timezone_window', true ) ? get_post_meta( $shipment_id, 'origin_timezone_window', true ) : '-'; 

$customer_reference = get_post_meta( $shipment_id, 'customer_reference', true );
$customer 			= get_post_meta( $shipment_id, 'registered_shipper', true );
// $customer 			= ( $customer ) ? substr($wpcargo->user_fullname( $customer ), 0 ,16 ).'...' : '';
$customer 			= ( $customer ) ? $wpcargo->user_fullname( $customer ) : '-';
$requested_delivery = get_post_meta( $shipment_id, 'requested_delivery', true );

if( get_post_meta( $shipment_id, 'destination_delivery_window', true ) ){
    $requested_delivery = get_post_meta( $shipment_id, 'destination_delivery_window', true ) ? get_post_meta( $shipment_id, 'destination_delivery_window', true ) : '-';
}
$requested_delivery_tz = get_post_meta( $shipment_id, 'destination_delivery_window', true ) ? get_post_meta( $shipment_id, 'destination_timezone_window', true ) : '-';

$date_time_update   = get_post_meta( $shipment_id, 'date_time_update', true ) ? get_post_meta( $shipment_id, 'date_time_update', true ) : '-';
$date_time_update_tz   = get_post_meta( $shipment_id, 'date_time_update', true ) ? get_post_meta( $shipment_id, 'update_timezone', true ) : '-' ;
$origin      		= get_post_meta( $shipment_id, 'origin_state', true ) ? get_post_meta( $shipment_id, 'origin_state', true ) : '-';
$destination      	= get_post_meta( $shipment_id, 'destination_state', true ) ? get_post_meta( $shipment_id, 'destination_state', true ) : '-' ;

$miles_out     		= get_post_meta( $shipment_id, 'miles_out', true ) ? get_post_meta( $shipment_id, 'miles_out', true ) : '-';
$vehicle_type      	= get_post_meta( $shipment_id, 'vehicle_type', true ) ? get_post_meta( $shipment_id, 'vehicle_type', true ) : '-';
$eta_delivery      	= get_post_meta( $shipment_id, 'eta_delivery', true ) ? get_post_meta( $shipment_id, 'eta_delivery', true ) : '-';
$eta_delivery_tz    = get_post_meta( $shipment_id, 'eta_delivery', true ) ? get_post_meta( $shipment_id, 'eta_timezone', true ) : '-';
$pod_signed     	= get_post_meta( $shipment_id, 'pod_signed', true ) ? get_post_meta( $shipment_id, 'pod_signed', true ) : '-' ;
$pod_date_time  	= get_post_meta( $shipment_id, 'pod_date_time', true ) ? get_post_meta( $shipment_id, 'pod_date_time', true ) : '-' ;

$assigned_to 		= get_post_meta( $shipment_id, 'assigned_agent_to', true );
$estimated_charges	= get_post_meta( $shipment_id, 'estimated_charges', true ) ? get_post_meta( $shipment_id, 'estimated_charges', true ) : '-';

// Additional Data 
$__vehicle_eta 		= get_post_meta( $shipment_id, '__vehicle_eta', true );	
$__vehicle_eta  	= $__vehicle_eta ? str_replace( ' ', '<br/>', $__vehicle_eta ) : '';	

if( get_post_meta( $shipment_id, 'total_bill', true ) ){
    $estimated_charges	= get_post_meta( $shipment_id, 'total_bill', true ) ? get_post_meta( $shipment_id, 'total_bill', true ) : 0 ;
}

$assigned_to 		= ( $assigned_to ) ? $wpcargo->user_fullname( $assigned_to ) : '-';

$shipment_request  	= get_post_meta( $shipment_id, 'shipment_request', true );

$requested 			= $shipment_request ? 'requested' : '' ;
$disabled 			= $shipment_request ? 'disabled' : '' ;

$history 			= maybe_unserialize( get_post_meta( $shipment_id, 'wpcargo_shipments_update', true ) );
$location_update    = '-';
if( !empty( $history ) && is_array( $history ) ){
    $latest_update 		= array_pop($history);
    $location_update 	= array_key_exists('location', $latest_update) ? $latest_update['location'] : '-' ;
}

$userID 		= get_post_meta( $shipment_id, 'user_post', true);
$icon 			= (!empty($userID) && $userID != $current_userid) ? '<br><i class="fa fa-lock text-info"></i>' : '';
$g_origin = str_replace(' ', '+', $origin);
$g_destination = str_replace(' ', '+', $destination);

$g_map_distance = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=$g_origin&destinations=$g_destination&mode=driving&sensor=false&key=AIzaSyC2J7XrcnGVQGLvRS4_GFNtzkFw4whfwsg";
$g_map_json = file_get_contents($g_map_distance);
$g_map_distance = json_decode($g_map_json, TRUE);

//$g_map_km = $g_map_distance['rows'][0]['elements'][0]['distance']['value'];								
//$g_map_time = $g_map_distance['rows'][0]['elements'][0]['duration']['text'];
$g_map_km = 0;								
$g_map_time = 0;
$g_map_km = !empty($g_map_km)? floatval($g_map_km) * 0.0006213712 : '';
$g_map_time = !empty($g_map_time)? $g_map_time : '';
$req_sp_instr = '';
if(get_post_meta( $shipment_id, 'requires_special_instruction', true) == 'Yes'){
    $req_sp_instr = 'special_instruction';
}

$is_child = get_post_meta( $shipment_id, '_parent_shipment_id', true )

?>
<tr id="shipment-<?php echo $shipment_id; ?>" class="<?php echo preg_replace('/[^a-zA-Z0-9]/','_', strtolower($status));?> <?php echo $requested; ?> <?php echo $req_sp_instr; ?> <?php echo $is_child ? 'wpcc_child_shipment ' : '' ; ?>">
    <td style="width:40px;"><input class="wpcfe-shipments" type="checkbox" name="wpcfe-shipments[]" value="<?php echo $shipment_id; ?>"></td>
    <td class="no-space">
        <a href="/dashboard/?wpcfe-edit=<?php echo $shipment_id; ?>"><?php echo get_the_title( $shipment_id ); ?><?php echo $icon; ?>
        <br/><?php echo get_post_meta( $shipment_id, '__shipment_type', true ) ?>
    </td>
    <td class="no-space ship_date requested_delivery">
        <?php  echo wpcargo_customizer_formatted_date( $ship_date ).$ship_date_tz; ?>
    </td>
    <?php 
        if($roles[0]!='wpcargo_client'){
    ?>
    <td class="customer_reference" style="width:90px;word-break: break-all;"><?php echo $customer; ?></td>
    <?php 
        }
    ?>
    <td class="customer_reference" style="width:86px;word-break: break-all;"><?php echo $customer_reference; ?></td>
    <td class="no-space requested_delivery"><?php echo wpcargo_customizer_formatted_date( $requested_delivery ).$requested_delivery_tz; ?></td>
    <td class="no-space __vehicle_eta"><?php echo $__vehicle_eta; ?></td>
    <td class="no-space location_update"><?php echo $location_update; ?></td>
    <td class="date_time_update" style="width:80px;word-break: break-all;"><?php echo wpcargo_customizer_formatted_date( $date_time_update ).$date_time_update_tz; ?></td>
    <td class="no-space origin"><?php echo $origin; ?></td>
    <td class="no-space destination"><?php echo $destination; ?></td>
    <td class="no-space miles_out"><?php echo $miles_out; ?></td>
    <td class="no-space vehicle_type"><?php echo $vehicle_type; ?></td>
    <td class="no-space eta_delivery"><?php echo wpcargo_customizer_formatted_date( $eta_delivery ).$eta_delivery_tz; ?></td>
    <!-- <td>
        <?php //if( strtoupper($status) == 'NEW' || strtoupper($status) == 'DISPATCHED' ): ?>
        <?php //echo number_format(floatval($g_map_km), 0).' miles'.'<br>'.$g_map_time; ?>
        <?php //endif; ?>
    </td> -->
    <?php if( !is_wpcargo_client() ): ?>
    <td class="no-space assigned_to"><?php echo $assigned_to; ?></td>
    <?php endif; ?>
    <?php if( is_wpcargo_client() ): ?>
    <td class="no-space"><?php echo $estimated_charges; ?></td>
    <?php endif; ?>
    <td class="no-space status"><?php echo $status; ?></td>
    <?php if( is_wpcargo_client() ): ?>
    <td class="no-space">
        <a class="send-request btn btn-info btn-sm btn-rounded <?php echo $disabled; ?>" data-id="<?php echo $shipment_id; ?>">Send Request</button>
    </td>
    <?php endif; ?>
    <?php do_action( 'wpcfe_shipment_table_data', $shipment_id ); ?>
    <?php do_action( 'wpcfe_shipment_table_action', $shipment_id ); ?>
    <td class="shipment_note text-center" style="width:50px; !important;">
        <a href="#" class="view-shipment_note" data-id="<?php echo $shipment_id; ?>" data-toggle="modal" data-target="#shipmentNoteComment" title="<?php _e('Comment', 'wpcargo-frontend-manager'); ?>">
        <?php if( get_shipment_notes_mark($shipment_id) != 1 ): ?>
            <span class="_makr_new">new</span><span class="fa fa-2x fa-comment text-info"></span>
        <?php else: ?>
            <span class="fa fa-2x fa-comments text-secondary"></span>
        <?php endif; ?>
        </a>
    </td>
    <?php if( !in_array( 'wpcargo_employee', $roles )  ): ?>
        <td class="text-center wpcfe-action">
                <form method="post" name="wpcargo-track-form" action="/track/" target="_blank">
                <?php wp_nonce_field( 'wpcargo_track_shipment_action', 'track_shipment_nonce' ); ?>
                                                                <input type="hidden" name="_wp_http_referer" value="/track/">
                <input class="input_track_num" type="hidden" name="wpcargo_tracking_number" value="<?php echo get_the_title( $shipment_id ); ?>" autocomplete="off" required>
                    <input id="submit_wpcargo" class="wpcargo-btn wpcargo-btn-primary" name="wpcargo-submit" type="submit" value="<?php echo apply_filters('wpcargo_tn_submit_val', __( 'TRACK', 'wpcargo' ) ); ?>">
            </form>
        </td>
    <?php endif; ?>
    <?php if( can_wpcfe_delete_shipment() ): ?>
        <td class="text-center">
            <a href="#" class="wpcfe-copy-shipment" data-id="<?php echo $shipment_id; ?>" title="<?php _e('Copy', 'wpcargo-frontend-manager'); ?>"><i class="fa fa-copy text-success"></i></a>
        </td>	
        <td class="text-center">
            <a href="#" class="wpcfe-delete-shipment" data-id="<?php echo $shipment_id; ?>" title="<?php _e('Delete', 'wpcargo-frontend-manager'); ?>"><i class="fa fa-trash-alt text-danger"></i></a>
        </td>	
    <?php endif; ?>						
</tr>