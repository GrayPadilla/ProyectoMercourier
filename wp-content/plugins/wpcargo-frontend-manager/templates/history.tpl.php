<?php
	global $wpcargo , $WPCCF_Fields;
	$user_role = wpcfe_current_user_role();
?>
<div id="wpcfe-misc-history" class="card mb-4">
	<section class="card-header">
		<?php echo apply_filters( 'wpcfe_history_header_label', __('History','wpcargo-frontend-manager') ); ?> <span class="float-right font-weight-bold text-uppercase"><?php echo $shipment->ID ? wpcfe_get_shipment_status( $shipment->ID ) : ''; ?></span>
	</section>
	<section class="card-body">
		<div class="form-row">
			<?php foreach( wpcargo_history_fields() as $history_metakey => $history_value ): ?>
				<?php 
					if( $history_metakey == 'updated-name' ){
						continue;
					}
					$custom_classes = array( 'form-control' );
					$value 			= '';
					if( $history_metakey == 'date' ){
						$custom_classes[] = 'wpccf-datepicker';
						$value = current_time( $wpcargo->date_format );
					}elseif( $history_metakey == 'time' ){
						$custom_classes[] = 'wpccf-timepicker';
						$value = current_time( $wpcargo->time_format );
					}
					if( $history_value['field'] == 'select'){
						$custom_classes[] = 'browser-default';
					}
					if( in_array( $history_metakey, wpcfe_autocomplete_address_fields() ) ){
						$custom_classes[] = 'wpcfe_autocomplete_address';
					}
					$custom_classes = implode(" ", $custom_classes );
				?>
				<div class="form-group col-md-12">
					<label for="status-<?php echo $history_metakey; ?>"><?php echo $history_value['label'];?></label>
					<?php echo wpcargo_field_generator( $history_value, $history_metakey, $value, 'status_'.$history_metakey.' '.$custom_classes ); ?>
				</div>
			<?php endforeach; ?>
		</div>
	</section>
</div>