<div class="card">
	<div class="card-body">
		<div id="wpcargo-result-wrapper" class="wpcargo-wrap-details wpcargo-container mb-5">
		    <?php
		    
		    do_action('wpcargo_before_track_details', $shipmentDetails );
		    do_action('wpcargo_track_header_details', $shipmentDetails );
		    do_action('wpcargo_track_after_header_details', $shipmentDetails );
		    do_action('wpcargo_track_shipper_details', $shipmentDetails );
		    do_action('wpcargo_before_shipment_details', $shipmentDetails );
		    do_action('wpcargo_track_shipment_details', $shipmentDetails );
		    do_action('wpcargo_after_package_details', $shipmentDetails );
		    do_action('wpcargo_after_package_totals', $shipmentDetails );
			do_action('wpcargo_after_track_details', $shipmentDetails );
		   ?>
		</div>
	</div>
</div>