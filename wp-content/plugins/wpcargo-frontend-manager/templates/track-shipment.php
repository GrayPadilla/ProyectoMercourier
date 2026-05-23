<div class="card">
	<div class="card-body">
	    
		<div id="wpcargo-result-wrapper" class="wpcargo-wrap-details wpcargo-container mb-5">
		    <?php
		    do_action('wpcargo_before_track_details', $shipment_detail );
		    do_action('wpcargo_track_header_details', $shipment_detail );
		    do_action('wpcargo_track_after_header_details', $shipment_detail );
		    do_action('wpcargo_track_shipper_details', $shipment_detail );
		    do_action('wpcargo_before_shipment_details', $shipment_detail );
		    do_action('wpcargo_track_shipment_details', $shipment_detail );
		    do_action('wpcargo_after_package_details', $shipment_detail );
		    do_action('wpcargo_after_package_totals', $shipment_detail );
			do_action('wpcargo_after_track_details', $shipment_detail );
		    ?>
		</div>
	</div>
</div>
