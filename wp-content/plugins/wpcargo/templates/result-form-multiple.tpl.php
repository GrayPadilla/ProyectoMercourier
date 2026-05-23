<div id="wpcargo-result-wrapper">
	<div class="wpcargo-result wpcargo" id="wpcargo-result">
	<?php $shipment_ids = wpcargo_trackform_multiple_shipment_numbers( $shipment_numbers );
		  if ( count( $shipment_ids ) > 1 ) : ?>
		    <?php wpcargo_display_multiple_results_for_multiple_tracking( $shipment_ids ) ?>
		<?php elseif ( count( $shipment_ids ) == 1 ): ?>
			<?php wpcargo_display_single_result_for_multiple_tracking( $shipment_ids ) ?>
		<?php else: ?>
			<h3 style="color: red !important; text-align:center;margin-bottom:0;padding:12px;"><?php echo apply_filters('wpcargo_tn_no_result_text', esc_html__('No results found!','wpcargo') ); ?></h3>
		<?php endif; ?>
	</div>
</div>
