<!-- Full Height Modal Left Info Demo-->
<div class="modal fade" id="wpcr-bulk-barcode-modal" tabindex="-1" role="dialog" aria-labelledby="<?php esc_html_e( 'Receiver Bulk Update', 'wpcargo-receiving' ); ?>" aria-hidden="true" data-backdrop="false">
    <div class="modal-dialog modal-notify modal-info" role="document">
        <!--Content-->
        <div class="modal-content">
            <!--Header-->
            <div class="modal-header primary-color-dark darken-2">
                <p class="heading lead"><?php esc_html_e( 'Receiver Bulk Update', 'wpcargo-receiving' ); ?></p>
                <button type="button" class="close" data-dismiss="modal" aria-label="<?php esc_html_e( 'Close', 'wpcargo-receiving' ); ?>">
                    <span aria-hidden="true" class="white-text">&times;</span>
                </button>
            </div>
            <!--Body-->
            <div id="bulk-receiver" class="modal-body">
				<div class="message"></div>
				<form method="post" id="wpc-receiving">
					<?php do_action( 'wpcr_before_bulkupdate_form_fields' ); ?>
					<?php foreach( wpcargo_history_fields() as $history_name => $history_value ): ?>
						<?php
							$picker_class = '';
							$value = '';
							if( $history_name == 'date' ){
								$picker_class = 'wpccf-datepicker';
								$value = current_time( $wpcargo->date_format );
							}elseif( $history_name == 'time' ){
								$picker_class = 'wpccf-timepicker';
								$value = current_time( $wpcargo->time_format );
							}
							$select_class = ( $history_value['field'] == 'select' ) ? 'browser-default' : '';
							$location_class = ( $history_name == 'location' ) ? 'status_location' : '';
							
							// Filtrar estados para motorizado
							if ($history_name == 'status') {
								$is_driver = in_array('wpcargo_driver', $current_user->roles) && !in_array('administrator', $current_user->roles);
								if ($is_driver && isset($history_value['options'])) {
									$estados_permitidos = array('RECOGIDO', 'NO CONTESTA', 'EN RUTA', 'ENTREGADO', 'REPROGRAMADO', 'ANULADO');
									$opciones_filtradas = array();
									foreach ($history_value['options'] as $key => $option) {
										$option_upper = strtoupper($option);
										foreach ($estados_permitidos as $permitido) {
											if (strpos($option_upper, $permitido) !== false || strpos($permitido, $option_upper) !== false) {
												$opciones_filtradas[$key] = $option;
												break;
											}
										}
									}
									$history_value['options'] = $opciones_filtradas;
								}
							}
						?>
						<div class="col-md-12 mb-4 receiving-input">
							<label for="wpc-receiving-<?php echo $history_name; ?>"><strong><?php echo $history_value['label'];?></strong></label><br />
							<?php if( $history_name != 'updated-name' ): ?>
								<?php echo wpcargo_field_generator( $history_value, $history_name, $value, 'form-control wpc-receiving-'.$history_name.' '.$select_class.' '.$picker_class.' '.$location_class ); ?>
							<?php else: ?>
								<input readonly="readonly" class="form-control wpc-receiving-current-user-name" type="text" name="updated-name" value="<?php echo $current_user->user_firstname.' '.$current_user->user_lastname;?>" />
								<input class="wpc-receiving-current-user-id" type="hidden" name="updated-by" value="<?php echo get_current_user_id(); ?>"/>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
					<?php do_action( 'wpcr_after_bulkupdate_form_fields' ); ?>
					<input type="hidden" class="shipments-to-scan" value="" />
					<button class="btn btn-small btn-info btn-fill btn-wd btn-block submit-shipment-scan"><?php esc_html_e( 'Update', 'wpcargo-receiving' ); ?></button>
				</form>
			</div> <!-- modal body -->
            <!--Footer-->
            <div class="modal-footer justify-content-center"></div>
        </div>
        <!--/.Content-->
    </div>
</div>
<!-- Full Height Modal Right Info Demo-->
<script>
	/*
	** Google map Script Auto Complete address
	*/
	var placeSearch, autocomplete, map, geocoder;
	function wpcSHinitMap() {
		geocoder = new google.maps.Geocoder();
		getPlace_dynamic();
	}
	function getPlace_dynamic() {
		 var defaultBounds = new google.maps.LatLngBounds(
			 new google.maps.LatLng(-33.8902, 151.1759),
			 new google.maps.LatLng(-33.8474, 151.2631)
		 );
		 var input = document.getElementsByClassName('status_location');
		 var options = {
			 bounds: defaultBounds,
			 types: ['geocode']
		 };
		 for (i = 0; i < input.length; i++) {
			 autocomplete = new google.maps.places.Autocomplete(input[i], options);
		 }
	}
</script>
<?php
echo wpcargo_map_script( 'wpcSHinitMap' );