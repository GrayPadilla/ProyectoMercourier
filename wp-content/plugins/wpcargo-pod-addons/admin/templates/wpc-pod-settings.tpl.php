<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}
?>
<form method="post" action="options.php" class="wpc-pod-setting-admin">
    <?php settings_fields( 'wpcargo_pod_settings_group' ); ?>
    <?php do_settings_sections( 'wpcargo_pod_settings_group' ); ?>
	
    <table class="form-table">
		<tr>
			<th><?php _e('Exclude Status for Driver', 'wpcargo-pod' ); ?></th>
			<td>
				<select class="wpcpod-select2" name="wpcargo_pod_status[]" multiple="multiple" style="width:360px;">
					<?php
					if( !empty( $roles ) ){
						foreach ($roles as $_key => $_value) {
							?><option value="<?php echo $_key; ?>" <?php echo in_array( $_key, $access_dashboard_role ) ? 'selected' : '' ; ?>><?php echo $_value; ?></option><?php
						}
					}
					if( !empty( $wpcargo->status ) ){
						$wpcargo_pod_status 	= get_option('wpcargo_pod_status');
						$wpcargo_pod_status 	= !empty( $wpcargo_pod_status) && is_array( $wpcargo_pod_status ) ? $wpcargo_pod_status : array() ;
						foreach ( $wpcargo->status as $opt_status) {
							?><option value="<?php echo $opt_status; ?>" <?php echo in_array( $opt_status, $wpcargo_pod_status ) ? 'selected' : '' ; ?>><?php echo $opt_status; ?></option><?php
						}
					}
					?>
				</select>
				<p class="description"><?php _e('Note: This options applicable only in front end dashboard. It will exclude the shipment status in updating the shipment.', 'wpcargo-frontend-manager'); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php _e( 'Select shipper fields to display when driver update shipment', 'wpcargo-pod' ) ; ?></th>	
			<td>
			<?php
				if( !empty( $shipper_fields ) ){
					?><ul id="shipper-fields"><?php
					foreach ($shipper_fields as $field ) {
						?><li><input type="checkbox" name="wpcargo_pod_option_settings[shipper_fields][]" value="<?php echo $field['id']; ?>" <?php echo ( in_array( $field['id'], $shipper_selected_option) ) ? 'checked' : '' ; ?>><?php echo $field['label'] ?></li><?php
					}
					?></ul><?php
				}
			?>
			</td>	
		</tr>
		<tr>
			<th scope="row"><?php _e( 'Select receiver fields to display when driver update shipment', 'wpcargo-pod' ) ; ?></th>	
			<td>
			<?php
				if( !empty( $receiver_fields ) ){
					?><ul id="shipper-fields"><?php
					foreach ($receiver_fields as $field ) {
						?><li><input type="checkbox" name="wpcargo_pod_option_settings[receiver_fields][]" value="<?php echo $field['id']; ?>" <?php echo ( in_array( $field['id'], $receiver_selected_option) ) ? 'checked' : '' ; ?>><?php echo $field['label']; ?></li><?php
					}
					?></ul><?php
				}
			?>
			</td>	
		</tr>
    </table>
	<?php if( !empty( get_option('shmap_api') ) ): $route_origin = wpcpod_route_origin(); $pickup_route_origin = wpcpod_pickup_route_origin(); ?>
		<h2><?php _e('Delivery Driver Route', 'wpcargo-pod'); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php _e( 'Point of Orgin / Warehouse', 'wpcargo-pod' ); ?></th>
				<td>
					<input type="hidden" name="wpcpod_route_origin[latitude]" id="wpcpod_route_origin-lat" value="<?php echo $route_origin['latitude']; ?>">
					<input type="hidden" name="wpcpod_route_origin[longitude]" id="wpcpod_route_origin-long" value="<?php echo $route_origin['longitude']; ?>">
					<input style="min-width: 400px;" type="text" name="wpcpod_route_origin[address]" id="wpcpod_route_origin-address" value="<?php echo $route_origin['address']; ?>" required>
					<div id="wpcpod-route-origin">
						<p class="description"><?php esc_html_e( 'Note: Dag the map marker to generate Point of Orgin / Warehouse Address', 'wpcargo-pod' ) ; ?></p>
						<div id="wpcpod-ro-map" style="width: 600px;height: 300px;margin: 24px 0 0 0; border: 1px solid #7e8993;"></div>
					</div>
				</td>
			</tr>
			<tr>
				<th><?php _e('Display status to Map Route', 'wpcargo-pod' ); ?></th>
				<td>
					<select class="wpcpod-select2" name="wpcpod_route_status[]" multiple="multiple" style="width:360px;" required>
						<?php
						if( !empty( $roles ) ){
							foreach ($roles as $_key => $_value) {
								?><option value="<?php echo $_key; ?>" <?php echo in_array( $_key, $access_dashboard_role ) ? 'selected' : '' ; ?>><?php echo $_value; ?></option><?php
							}
						}
						if( !empty( $wpcargo->status ) ){
							$wpcpod_route_status 	= get_option('wpcpod_route_status');
							$wpcpod_route_status 	= !empty( $wpcpod_route_status) && is_array( $wpcpod_route_status ) ? $wpcpod_route_status : array() ;
							foreach ( $wpcargo->status as $opt_status) {
								?><option value="<?php echo $opt_status; ?>" <?php echo in_array( $opt_status, $wpcpod_route_status ) ? 'selected' : '' ; ?>><?php echo $opt_status; ?></option><?php
							}
						}
						?>
					</select>
					<p class="description"><?php _e('Note: This display the shipments in the Map Route.', 'wpcargo-frontend-manager'); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php _e('Receiver Address Field', 'wpcargo-pod' ); ?></th>
				<td>
					<select class="wpcpod-select2 wpcpod-order-select2" name="wpcpod_route_field[]" multiple="multiple" style="width:360px;" required>
						<?php
						if( !empty( $receiver_fields ) ){
							$wpcpod_route_field 	= get_option('wpcpod_route_field');
							$wpcpod_route_field 	= !empty( $wpcpod_route_field) && is_array( $wpcpod_route_field ) ? $wpcpod_route_field : array() ;
							foreach ( $receiver_fields as $rfield) {
								?><option value="<?php echo $rfield['field_key']; ?>" <?php echo in_array( $rfield['field_key'], $wpcpod_route_field ) ? 'selected' : '' ; ?>><?php echo $rfield['label']; ?></option><?php
							}
						}
						?>
					</select>
					<p class="description"><?php _e('Note: This field will serve as address for the map.', 'wpcargo-frontend-manager'); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php _e('Additional Route Segment Information', 'wpcargo-pod' ); ?></th>
				<td>
					<select class="wpcpod-select2 wpcpod-order-select2" name="wpcpod_route_segment_info[]" multiple="multiple" style="width:360px;" required>
						<?php
						if( !empty( $receiver_fields ) ){
							$route_info 	= get_option('wpcpod_route_segment_info');
							$route_info 	= !empty( $route_info) && is_array( $route_info ) ? $route_info : array() ;
							foreach ( $receiver_fields as $rfield) {
								?><option value="<?php echo $rfield['field_key']; ?>" <?php echo in_array( $rfield['field_key'], $route_info ) ? 'selected' : '' ; ?>><?php echo $rfield['label']; ?></option><?php
							}
						}
						?>
						<option value="wpcargo_status" <?php echo in_array( 'wpcargo_status', $route_info ) ? 'selected' : '' ; ?>><?php _e('Status', 'wpcargo-pod' ); ?></option>
					</select>
					<p class="description"><?php _e('Note: Additional information for the route segment.', 'wpcargo-frontend-manager'); ?></p>
				</td>
			</tr>
		</table>
		<!-- pickup driver route -->
		<hr />
		<h2><?php _e('Pickup Driver Route', 'wpcargo-pod'); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php _e( 'Point of Orgin / Warehouse', 'wpcargo-pod' ); ?></th>
				<td>
					<input type="hidden" name="wpcpod_pickup_route_origin[latitude]" id="wpcpod_pickup_route_origin-lat" value="<?php echo $pickup_route_origin['latitude']; ?>">
					<input type="hidden" name="wpcpod_pickup_route_origin[longitude]" id="wpcpod_pickup_route_origin-long" value="<?php echo $pickup_route_origin['longitude']; ?>">
					<input style="min-width: 400px;" type="text" name="wpcpod_pickup_route_origin[address]" id="wpcpod_pickup_route_origin-address" value="<?php echo $pickup_route_origin['address']; ?>" required>
					<div id="wpcpod-route-origin">
						<p class="description"><?php esc_html_e( 'Note: Dag the map marker to generate Point of Orgin / Warehouse Address', 'wpcargo-pod' ) ; ?></p>
						<div id="wpcpod-pickup-ro-map" style="width: 600px;height: 300px;margin: 24px 0 0 0; border: 1px solid #7e8993;"></div>
					</div>
				</td>
			</tr>
			<tr>
				<th><?php _e('Display status to Map Route', 'wpcargo-pod' ); ?></th>
				<td>
					<select class="wpcpod-select2" name="wpcpod_pickup_route_status[]" multiple="multiple" style="width:360px;" required>
						<?php
						if( !empty( $roles ) ){
							foreach ($roles as $_key => $_value) {
								?><option value="<?php echo $_key; ?>" <?php echo in_array( $_key, $access_dashboard_role ) ? 'selected' : '' ; ?>><?php echo $_value; ?></option><?php
							}
						}
						if( !empty( $wpcargo->status ) ){
							$wpcpod_pickup_route_status 	= get_option('wpcpod_pickup_route_status');
							$wpcpod_pickup_route_status 	= !empty( $wpcpod_pickup_route_status) && is_array( $wpcpod_pickup_route_status ) ? $wpcpod_pickup_route_status : array() ;
							foreach ( $wpcargo->status as $opt_status) {
								?><option value="<?php echo $opt_status; ?>" <?php echo in_array( $opt_status, $wpcpod_pickup_route_status ) ? 'selected' : '' ; ?>><?php echo $opt_status; ?></option><?php
							}
						}
						?>
					</select>
					<p class="description"><?php _e('Note: This display the shipments in the Map Route.', 'wpcargo-frontend-manager'); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php _e('Shipper Address Field', 'wpcargo-pod' ); ?></th>
				<td>
					<select class="wpcpod-select2 wpcpod-order-select2" name="wpcpod_pickup_route_field[]" multiple="multiple" style="width:360px;" required>
						<?php
						if( !empty( $shipper_fields ) ){
							$wpcpod_pickup_route_field 	= get_option('wpcpod_pickup_route_field');
							$wpcpod_pickup_route_field 	= !empty( $wpcpod_pickup_route_field) && is_array( $wpcpod_pickup_route_field ) ? $wpcpod_pickup_route_field : array() ;
							foreach ( $shipper_fields as $rfield) {
								?><option value="<?php echo $rfield['field_key']; ?>" <?php echo in_array( $rfield['field_key'], $wpcpod_pickup_route_field ) ? 'selected' : '' ; ?>><?php echo $rfield['label']; ?></option><?php
							}
						}
						?>
					</select>
					<p class="description"><?php _e('Note: This field will serve as address for the map.', 'wpcargo-frontend-manager'); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php _e('Additional Route Segment Information', 'wpcargo-pod' ); ?></th>
				<td>
					<select class="wpcpod-select2 wpcpod-order-select2" name="wpcpod_pickup_route_segment_info[]" multiple="multiple" style="width:360px;" required>
						<?php
						if( !empty( $shipper_fields ) ){
							$pickup_route_info 	= get_option('wpcpod_pickup_route_segment_info');
							$pickup_route_info 	= !empty( $pickup_route_info) && is_array( $pickup_route_info ) ? $pickup_route_info : array() ;
							foreach ( $shipper_fields as $rfield) {
								?><option value="<?php echo $rfield['field_key']; ?>" <?php echo in_array( $rfield['field_key'], $pickup_route_info ) ? 'selected' : '' ; ?>><?php echo $rfield['label']; ?></option><?php
							}
						}
						?>
						<option value="wpcargo_status" <?php echo in_array( 'wpcargo_status', $pickup_route_info ) ? 'selected' : '' ; ?>><?php _e('Status', 'wpcargo-pod' ); ?></option>
					</select>
					<p class="description"><?php _e('Note: Additional information for the route segment.', 'wpcargo-frontend-manager'); ?></p>
				</td>
			</tr>
		</table>
		<script
			src="https://maps.googleapis.com/maps/api/js?key=<?php echo get_option('shmap_api'); ?>&callback=initialize&libraries=&v=weekly"
			defer
		></script>
		<script>

			// -------------------------------------------------- Delivery Driver Route --------------------------------------------------------- //

			// In the following example, markers appear when the user clicks on the map.
			// Each marker is labeled with a single alphabetical character.
			<?php
			$latitude 	= !empty( $route_origin['latitude'] ) ? $route_origin['latitude'] : 10.7119282 ;
			$longitude 	= !empty( $route_origin['longitude'] ) ? $route_origin['longitude'] : 122.54032955 ;
			?>
			var geocoder;
			function initialize() {
				geocoder = new google.maps.Geocoder();
				var geoCooridinates = { lat: <?php echo $latitude; ?>, lng: <?php echo $longitude; ?> };
				var map = new google.maps.Map(document.getElementById('wpcpod-ro-map'), { 
					zoom: 13, 
					center: geoCooridinates,
				} );
				// Add a marker at the center of the map.
				addMarker( geoCooridinates, map );

				// call the pickup route main funtion inside "initialize" function to avoid using google map's api script multiple times
				pickup_initialize();
			}
	
			// Adds a marker to the map.
			function addMarker( location, map ) {
				// Add the marker at the clicked location, and add the next-available label
				// from the array of alphabetical characters.
				var marker = new google.maps.Marker({
					position: location,
					map: map,
					draggable:true,
					icon: "<?php echo WPCARGO_POD_URL.'assets/img/origin.png';  ?>",
					animation: google.maps.Animation.DROP,
				});
				marker.addListener('dragend', function( event ){
					var latitude = event.latLng.lat();
					var longitude =  event.latLng.lng();
					document.getElementById( 'wpcpod_route_origin-lat' ).value = latitude;
					document.getElementById( 'wpcpod_route_origin-long' ).value = longitude;

					geocodePosition(marker.getPosition());
						
				});
			}
			function geocodePosition(pos) {
				geocoder.geocode({
					latLng: pos
				}, function(responses) {
					if (responses && responses.length > 0) {
						document.getElementById( 'wpcpod_route_origin-address' ).value = responses[0].formatted_address;
					} else {
						alert('Cannot determine address at this location.');
					}
				});
			}

			// -------------------------------------------------- Pickup Driver Route --------------------------------------------------------- //

			<?php
			$pickup_latitude 	= !empty( $pickup_route_origin['latitude'] ) ? $pickup_route_origin['latitude'] : 10.7119282 ;
			$pickup_longitude = !empty( $pickup_route_origin['longitude'] ) ? $pickup_route_origin['longitude'] : 122.54032955 ;
			?>
			var pickup_geocoder;
			function pickup_initialize() {
				pickup_geocoder = new google.maps.Geocoder();
				var geoCooridinates = { lat: <?php echo $pickup_latitude; ?>, lng: <?php echo $pickup_longitude; ?> };
				var map = new google.maps.Map(document.getElementById('wpcpod-pickup-ro-map'), { 
					zoom: 13, 
					center: geoCooridinates,
				} );
				// Add a marker at the center of the map.
				pickup_addMarker( geoCooridinates, map );
			}
	
			// Adds a marker to the map.
			function pickup_addMarker( location, map ) {
				// Add the marker at the clicked location, and add the next-available label
				// from the array of alphabetical characters.
				var marker = new google.maps.Marker({
					position: location,
					map: map,
					draggable:true,
					icon: "<?php echo WPCARGO_POD_URL.'assets/img/origin.png';  ?>",
					animation: google.maps.Animation.DROP,
				});
				marker.addListener('dragend', function( event ){
					var latitude = event.latLng.lat();
					var longitude =  event.latLng.lng();
					document.getElementById( 'wpcpod_pickup_route_origin-lat' ).value = latitude;
					document.getElementById( 'wpcpod_pickup_route_origin-long' ).value = longitude;

					pickup_geocodePosition(marker.getPosition());
						
				});
			}
			function pickup_geocodePosition(pos) {
				pickup_geocoder.geocode({
					latLng: pos
				}, function(responses) {
					if (responses && responses.length > 0) {
						document.getElementById( 'wpcpod_pickup_route_origin-address' ).value = responses[0].formatted_address;
					} else {
						alert('Cannot determine address at this location.');
					}
				});
			}

		</script>
	<?php endif; ?>
	<?php submit_button(); ?>
</form>