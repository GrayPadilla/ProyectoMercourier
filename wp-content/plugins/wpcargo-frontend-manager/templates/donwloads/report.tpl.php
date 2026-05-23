<!DOCTYPE html>
<html lang="en">
    <head>
    <title><?php echo $waybill_title; ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style type="text/css">
    	*{
    		margin: 0;
    		padding: 0;
    		font-family: Arial, Helvetica, sans-serif !important;
    	}
    	body{
    		padding: 18px;
    	}
    	table{
    		border-left: 1px solid #333;
    		border-right: 1px solid #333;	
    	}
        table tr td, table tr th{
        	font-size: 8px;
        	border-top: 1px solid #333;
        	border-bottom: 1px solid #333;
        	padding: 1px 4px;
        	text-align: center;
        	font-family: Arial, Helvetica, sans-serif !important;
        }
        .no-space{
        	white-space: nowrap;
        }
    </style>
    </head>
    <body>
		<p style="text-align:center;margin-bottom: 6px;font-size: 10px;">SHIPMENT REPORTS</p>
		<p style="font-size: 10px;">PRIDE LOGISTICS <span style="display:inline-block;float:right;"><?php echo date('m-d-Y | H:i:s'); ?></span><br/><span style="font-size: 8px;">INDEPENDENT LANDSTAR AGENCY</span></p>
		<table style="width:100%;border-collapse: collapse;margin-top:36px;">
			<thead>
				<tr>
					<th class="no-space">LS Pro #</th>
					<th class="no-space">CUSTOMER<br />REFERENCE</th>
					<th class="no-space">SHIP DATE</th>
					<th class="no-space">REQUESTED<br />DELIVERY DATE</th>
					<th class="no-space" style="width:120px;">ORIGIN</th>	
					<th class="no-space" style="width:120px;">DESTINATION</th>
					<th class="no-space">DELIVERY<br/>ETA</th>
					<th class="no-space" style="width:120px;">CURRENT<br/>LOCATION</th>
					<th class="no-space">LOCATION<br/>AS OF</th>
					<th class="no-space">MILES<br />OUT</th>
                    <th class="no-space">STATUS</th>
                    <th class="no-space">LOADED</th>
					<th class="no-space">POD</th>
					<th class="no-space">POD/<br />DATE TIME</th>
					<th class="no-space">CHARGES</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach( $shipment_ids as $key => $shipment_id){ ?>
					<?php
					$ship_date 			= get_post_meta( $shipment_id, 'origin_delivery_window', true ) ? get_post_meta( $shipment_id, 'origin_delivery_window', true ) : ''; 
					$ship_date_tz 		= $ship_date ? get_post_meta( $shipment_id, 'origin_timezone_window', true ) : ''; 
					$customer 			= get_post_meta( $shipment_id, 'registered_shipper', true );
					$customer 			= ( $customer ) ? $wpcargo->user_fullname( $customer ) : '';
					$customer_reference = get_post_meta( $shipment_id, 'customer_reference', true );
					$requested_delivery = get_post_meta( $shipment_id, 'destination_delivery_window', true );
					$requested_delivery_tz = $requested_delivery ? get_post_meta( $shipment_id, 'destination_timezone_window', true ) : '';
					// Origin
					$origin_address 	= '';
					$origin      		= get_post_meta( $shipment_id, 'origin', true );
					if( $origin ){
						$origin_address .= $origin.'<br/>';
					}
					$origin_state      	= get_post_meta( $shipment_id, 'origin_state', true );
					if( $origin_state ){
						$origin_address .= $origin_state.'<br/>';
					}
					$origin_postcode	= get_post_meta( $shipment_id, 'origin_postcode', true );
					if( $origin_postcode ){
						$origin_address .= $origin_postcode;
					}
					// Destination
					$destination_address 	= '';
					$destination      		= get_post_meta( $shipment_id, 'destination', true );
					if( $destination ){
						$destination_address .= $destination.'<br/>';
					}
					$destination_state      	= get_post_meta( $shipment_id, 'destination_state', true );
					if( $destination_state ){
						$destination_address .= $destination_state.'<br/>';
					}
					$destination_postcode	= get_post_meta( $shipment_id, 'destination_postcode', true );
					if( $destination_postcode ){
						$destination_address .= $destination_postcode;
					}
					$eta_delivery      	= get_post_meta( $shipment_id, 'eta_delivery', true );
					$eta_delivery_tz    = $eta_delivery ? get_post_meta( $shipment_id, 'eta_timezone', true ) : '';

					$date_time_update   = get_post_meta( $shipment_id, 'date_time_update', true );
					$date_time_update_tz   = $date_time_update ? get_post_meta( $shipment_id, 'update_timezone', true ) : '' ;

					$history 			= maybe_unserialize( get_post_meta( $shipment_id, 'wpcargo_shipments_update', true ) );
					$location_update    = '';
                    $mile_out    		= '';
                    
                    $dispatched_datetime  = '';


					if( !empty( $history ) ){

                        $dispatch_history   = $history;

						$latest_update 		= array_pop($history);
						$location_update 	= array_key_exists('location', $latest_update) ? $latest_update['location'] : '' ;
                        $mile_out 			= array_key_exists('miles_out', $latest_update) ? $latest_update['miles_out'] : '' ;

                        foreach( array_reverse( $dispatch_history ) as $dh_value ){
							//  DISPATCHED
                            if( trim( strtolower($dh_value['status']) ) == strtolower('LOADED') || trim( strtolower($dh_value['status']) ) == strtolower('DISPATCHED') ){
                                $dispatched_datetime = $dh_value['date_time_update'];
                                break;
                            }
                        }
                    }
                    
					$pod_signed     	= get_post_meta( $shipment_id, 'pod_signed', true );
					$pod_date_time  	= get_post_meta( $shipment_id, 'date', true );
					$pod_date_time_tz 	= $pod_date_time ? get_post_meta( $shipment_id, 'pod_timezone', true ) : '';
					$estimated_charges	= get_post_meta( $shipment_id, 'total_bill', true );

					$pod_datetime 		= '';

					// wpcargo_status
					$completed_status 	= array(
						trim( strtolower( 'DELIVERED' ) ),
						trim( strtolower( 'COMPLETE' ) ),
						trim( strtolower( 'ARRIVED AT DELIVERY' ) ),
					);

					$shipment_status = trim( strtolower( get_post_meta( $shipment_id, 'wpcargo_status', true ) ) );

					if( in_array( $shipment_status, $completed_status)  ){
						$pod_datetime 		= wpcargo_customizer_formatted_date( $pod_date_time ).$pod_date_time_tz;
					}

					?>
					<tr>
						<td><?php echo get_the_title( $shipment_id ); ?></td>
						<td><?php echo strtoupper($customer); ?><br/><?php echo $customer_reference; ?></td>
						<td><?php echo wpcargo_customizer_formatted_date( $ship_date ).$ship_date_tz; ?></td>	
						<td><?php echo wpcargo_customizer_formatted_date( $requested_delivery ).$requested_delivery_tz; ?></td>
						<td style="text-align: left !important;" ><?php echo strtoupper($origin_address); ?></td>
						<td style="text-align: left !important;" ><?php echo strtoupper($destination_address); ?></td>
						<td><?php echo wpcargo_customizer_formatted_date( $eta_delivery ).$eta_delivery_tz; ?></td>
						<td style="text-align: left !important;"><?php echo strtoupper($location_update); ?></td>
						<td><?php echo wpcargo_customizer_formatted_date( $date_time_update ).$date_time_update_tz; ?></td>
						<td><?php echo $mile_out; ?></td>
						<td><?php echo strtoupper(get_post_meta( $shipment_id, 'wpcargo_status', true )); ?></td>
						<td><?php echo strtoupper($dispatched_datetime); ?></td>
						<td><?php echo strtoupper($pod_signed); ?></td>
						<td><?php echo $pod_datetime; ?></td>
						<td><?php echo $estimated_charges; ?></td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	</body>
</html>