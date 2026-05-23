<div id="container-info">
	<p id="container-header" class="section-header"><?php esc_html_e( 'Shipment Container Information', 'wpcargo-shipment-container' ); ?></p>
    <div id="container-details">
    	<div id="flight-info" class="one-third first">
        	<h1><?php esc_html_e( 'Container Information', 'wpcargo-shipment-container' ); ?></h1>
            <?php foreach ( wpc_container_info_fields() as $info_key => $info_value): ?>
                <p class="label"><?php echo $info_value['label']; ?></p>
                <p class="label-info"><?php echo get_post_meta( $container_id, $info_key, true ); ?><
            <?php endforeach; ?>
        </div><!-- #flight-info -->
        <div id="trip-info" class="one-third">
            <h1><?php esc_html_e( 'Trip Information', 'wpcargo-shipment-container' ); ?></h1>
            <?php foreach ( wpc_trip_info_fields() as $trip_key => $trip_value): ?>
                <p class="label"><?php echo $trip_value['label']; ?></p>
                <p class="label-info"><?php echo get_post_meta( $container_id, $trip_key, true ); ?><
            <?php endforeach; ?>
        </div><!-- #container-info -->
        <div id="time-info" class="one-third">
            <h1><?php esc_html_e( 'Time Information', 'wpcargo-shipment-container' ); ?></h1>
            <?php foreach ( wpc_time_info_fields() as $time_key => $time_value): ?>
                <p class="label"><?php echo $time_value['label']; ?></p>
                <p class="label-info"><?php echo get_post_meta( $container_id, $time_key, true ); ?><
            <?php endforeach; ?>
        </div><!-- #time-info -->
    </div><!-- #container-details -->
</div><!-- #container-info -->