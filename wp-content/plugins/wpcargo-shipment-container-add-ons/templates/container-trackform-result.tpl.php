<style>
@media  only screen and (max-width: 760px), (min-device-width: 768px) and (max-device-width: 1024px)  {
		/* Force table to not be like tables anymore */
		table#container-history-table, 
		table#container-history-table thead, 
		table#container-history-table tbody, 
		table#container-history-table th, 
		table#container-history-table td, 
		table#container-history-table tr { 
			display: block; 
		}
		/* Hide table headers (but not display: none;, for accessibility) */
		table#container-history-table thead tr { 
			position: absolute;
			top: -9999px;
			left: -9999px;
		}
		table#container-history-table tr { border: 1px solid #ccc; }
		table#container-history-table td { 
			/* Behave  like a "row" */
			border: none;
			border-bottom: 1px solid #eee; 
			position: relative;
			padding-left: 50%; 
		}
		table#container-history-table td:before { 
			/* Now like a table header */
			position: absolute;
			/* Top/left values mimic padding */
			top: 6px;
			left: 6px;
			width: 45%; 
			padding-right: 10px; 
			white-space: nowrap;
		}
		/*
		Label the data
		*/
		table#container-history-table td:nth-of-type(1):before { content: "<?php esc_html_e( 'Date', 'wpcargo-shipment-container' ); ?> : "; }
		table#container-history-table td:nth-of-type(2):before { content: "<?php esc_html_e( 'Location', 'wpcargo-shipment-container' ); ?> : "; }
		table#container-history-table td:nth-of-type(3):before { content: "<?php esc_html_e( 'Status', 'wpcargo-shipment-container' ); ?> : "; }
		table#container-history-table td:nth-of-type(4):before { content: "<?php esc_html_e( 'Remarks', 'wpcargo-shipment-container' ); ?> : "; }
}
</style>
<script>
	function wpcargo_print(wpcargo_class) {
		var printContents = document.getElementById(wpcargo_class).innerHTML;
		var originalContents = document.body.innerHTML;
		document.body.innerHTML = printContents;
		window.print();
		document.body.innerHTML = originalContents;
		location.reload(true);
	}
</script>
<div class="wpcargo-print-btn">
    <a class="wpcargo-print" type="button" onclick="wpcargo_print('wpcargo-container-track-result')"><img width="24" src="<?php echo WPCARGO_SHIPMENT_CONTAINER_URL; ?>assets/images/print.png" alt="Print" /></a>
</div>
<div id="wpcargo-container-track-result">
	<div id="container-track-result">
    	<div id="container-header">
        	<?php do_action('container_track_result_before_header', $container_id ); ?>
            <div id="site-info" class="header-section one-half first">
                <?php echo $site_info; ?>
            </div><!-- #site-info -->
            <div id="container-info" class="header-section one-half">
            	<?php if(!empty($barcode)): ?>
					<div class="wpc-barcode-code"><?php echo $barcode_image; ?></div><!-- b_code -->
				<?php endif; ?>
            	<p><?php echo apply_filters( 'wpc_container_trackform_number_result_label', __('Container No: ', 'wpcargo-shipment-container') ) . $container_number; ?></p>
            </div><!-- #container-info --> 
            <?php do_action('container_track_result_after_header', $container_id ); ?>
            <div class="clear-line"></div>          
        </div>
        <div id="container-details">
        	 <?php do_action('container_track_result_before_details', $container_id ); ?>
        	<div id="details" class="detail-section one-third first">
            	<p class="section-header"><strong><?php echo wpc_scpt_container_information_header(); ?></strong></p>
            	<p class="label"><?php esc_html_e( 'Flight/Container No.', 'wpcargo-shipment-container' ); ?></p>
                <p class="label-info"><?php echo ( get_post_meta( $container_id, 'container_no', true ) ) ? get_post_meta( $container_id, 'container_no', true ) : '--' ; ?></p>
                <p class="label"><?php esc_html_e( 'Agent name', 'wpcargo-shipment-container' ); ?></p>
                <p class="label-info"><?php echo ( get_post_meta( $container_id, 'container_agent', true ) ) ?  get_post_meta( $container_id, 'container_agent', true ) : '--' ;  ?></p>
                <p class="label"><?php esc_html_e( 'Telephone', 'wpcargo-shipment-container' ); ?></p>
                <p class="label-info"><?php echo ( get_post_meta( $container_id, 'container_tel', true ) ) ? get_post_meta( $container_id, 'container_tel', true ) : '--'; ?></p>
                <p class="label"><?php esc_html_e( 'Passport', 'wpcargo-shipment-container' ); ?></p>
                <p class="label-info"><?php echo get_post_meta( $container_id, 'passport', true ) ? get_post_meta( $container_id, 'passport', true ) : '--' ; ?></p>
            </div><!-- #details -->
            <div id="trip" class="detail-section one-third">
                <p class="section-header"><strong><?php echo wpc_scpt_trip_information_header(); ?></strong></p>
            	<p class="label"><?php esc_html_e( 'Origin port', 'wpcargo-shipment-container' ); ?></p>
                <p class="label-info"><?php echo get_post_meta( $container_id, 'origin', true ) ? get_post_meta( $container_id, 'origin', true ) : '--' ; ?></p>
                <p class="label"><?php esc_html_e( 'Destination port', 'wpcargo-shipment-container' ); ?></p>
                <p class="label-info"><?php echo get_post_meta( $container_id, 'destination', true ) ? get_post_meta( $container_id, 'destination', true ) : '--' ; ?></p>
                <p class="label"><?php esc_html_e( 'Delivery Agent', 'wpcargo-shipment-container' ); ?></p>
                <p class="label-info"><?php echo ( get_post_meta( $container_id, 'delivery_agent', true ) ) ? get_post_meta( $container_id, 'delivery_agent', true ) : '--' ; ?></p>
                <p class="label"><?php esc_html_e( 'Telephone', 'wpcargo-shipment-container' ); ?></p>
                <p class="label-info"><?php echo get_post_meta( $container_id, 'delivery_tel', true ) ? get_post_meta( $container_id, 'delivery_tel', true ) : '--' ; ?></p>
            </div><!-- #shipments -->
            <div id="time" class="detail-section one-third">
            	<p class="section-header"><strong><?php echo wpc_scpt_time_information_header(); ?></strong></p>
            	<p class="label"><?php esc_html_e( 'Date', 'wpcargo-shipment-container' ); ?></p>
                <p class="label-info"><?php echo get_post_meta( $container_id, 'date', true ) ? get_post_meta( $container_id, 'date', true ) : '--' ; ?></p>
                <p class="label"><?php esc_html_e( 'Time', 'wpcargo-shipment-container' ); ?></p>
                <p class="label-info"><?php echo get_post_meta( $container_id, 'time', true ) ? get_post_meta( $container_id, 'time', true ) : '--' ; ?></p>
                <p class="label"><?php esc_html_e( 'Expected Date', 'wpcargo-shipment-container' ); ?></p>
                <p class="label-info"><?php echo get_post_meta( $container_id, 'expected_date', true ) ? get_post_meta( $container_id, 'expected_date', true ) : '--' ; ?></p>
                <p class="label"><?php esc_html_e( 'Travel Mode', 'wpcargo-shipment-container' ); ?></p>
                <p class="label-info"><?php echo get_post_meta( $container_id, 'travel_mode', true ) ? get_post_meta( $container_id, 'travel_mode', true ) : '--' ; ?></p>
            </div><!-- #history -->
            <?php do_action('container_track_result_after_details', $container_id ); ?>
            <div class="clear-line"></div>
        </div><!-- container-details -->
        <div id="shipments">
        	<p class="section-header"><strong><?php echo apply_filters( 'wpc_container_track_result_shipment_header', __( 'Assigned Shipments', 'wpcargo-shipment-container' ) ); ?></strong></p>
            <?php do_action('container_track_result_before_shipments', $container_id ); ?>
        	<?php
				if( !empty($shipments) ):
					foreach( $shipments as $shipment_id ):
						$shipment_title = get_the_title( $shipment_id );
						?>
                        <div class="shipment-section">
                            <?php do_action( 'wpcsc_before_shipment_section_result' ); ?>
                        	<img src="<?php echo $code_url.$shipment_title; ?>" alt="<?php echo $shipment_title; ?>" />
                            <p class="shipment-id"><?php echo $shipment_title; ?></p>
                            <?php do_action( 'wpcsc_after_shipment_section_result' ); ?>
                        </div>
                        <?php
					endforeach;
				endif;
			?>
            <?php do_action('container_track_result_after_shipments', $container_id ); ?>
        </div><!-- #shipments -->
        <div id="history">
        	<p class="section-header"><strong><?php echo apply_filters( 'wpc_container_track_result_history_header', __( 'Container History', 'wpcargo-shipment-container' ) ); ?></strong></p>
            <?php do_action('container_track_result_before_history', $container_id ); ?>
            <table id="container-history-table">
            	<thead>
                    <tr>
                        <th><?php esc_html_e( 'Date', 'wpcargo-shipment-container' ); ?></th>
                        <th><?php esc_html_e( 'Location', 'wpcargo-shipment-container' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'wpcargo-shipment-container' ); ?></th>
                        <th><?php esc_html_e( 'Remarks', 'wpcargo-shipment-container' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                  <?php if( !empty( $history  ) ): foreach( $history as $details ): ?>
                  <tr>
                    <td ><?php echo $details['date']; ?></td>
                    <td ><?php echo $details['location']; ?></td>
                    <td ><?php echo $details['status']; ?></td>
                    <td ><?php echo $details['remarks']; ?></td>
                  </tr>
                  <?php endforeach; else: ?>
                  <tr>
                    <td colspan="4" data-th="<?php esc_html_e( 'Result', 'wpcargo-shipment-container' ); ?>"><?php esc_html_e( 'No registered history found.', 'wpcargo-shipment-container' ); ?></td>
                  </tr>
                  <?php endif; ?>
                </tbody>
            </table>
            <?php do_action('container_track_result_before_history', $container_id ); ?>
        </div><!-- #history -->
    </div>
</div>