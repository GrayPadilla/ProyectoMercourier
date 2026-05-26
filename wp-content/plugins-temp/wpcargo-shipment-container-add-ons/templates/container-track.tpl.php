<div id="wpcsc-track-header" class="text-center mb-4" >
    <?php if( $wpcargo->logo ): ?>
        <img src="<?php echo $wpcargo->logo; ?>" alt="<?php echo bloginfo( 'name' ); ?>" class="mx-auto mt-0 mb-4 d-block">
    <?php endif; ?>
    <img src="<?php echo $wpcargo->barcode_url( $container_id ); ?>" alt="<?php echo get_the_title( $container_id ); ?>" class="mx-auto my-0 d-block">
    <p><?php echo get_the_title( $container_id ); ?></p>
</div>
<div id="container-details" class="row">
    <?php do_action('container_track_result_before_details', $container_id ); ?>
    <div class="col-md-4">
        <p class="section-header h5-responsive font-weight-normal pb-2 border-bottom" ><?php echo apply_filters( 'wpc_container_track_result_details_header', __( 'Container Information', 'wpcargo-shipment-container' ) ); ?></p>
        <?php
        if( !empty( wpc_container_info_fields() ) ){
            foreach ( wpc_container_info_fields() as $key => $value) {
                $_value = maybe_unserialize( get_post_meta( $container_id, $key, true ) );
                if( is_array( $_value ) ){
                    $value = implode(",", $_value);
                }
                ?><p><strong><?php echo $value['label'] ?></strong>: <?php echo $_value; ?></p><?php
            }
        }
        ?>
    </div>
    <div class="col-md-4">
        <p class="section-header h5-responsive font-weight-normal pb-2 border-bottom"><?php echo apply_filters( 'wpc_container_track_result_trip_header', __( 'Trip Information', 'wpcargo-shipment-container' ) ); ?></p>
        <?php
        if( !empty( wpc_trip_info_fields() ) ){
            foreach ( wpc_trip_info_fields() as $key => $value) {
                $_value = maybe_unserialize( get_post_meta( $container_id, $key, true ) );
                if( is_array( $_value ) ){
                    $value = implode(",", $_value);
                }
                ?><p><strong><?php echo $value['label'] ?></strong>: <?php echo $_value; ?></p><?php
            }
        }
        ?>
    </div>
    <div class="col-md-4">
        <p class="section-header h5-responsive font-weight-normal pb-2 border-bottom"><?php echo apply_filters( 'wpc_container_track_result_time_header', __( 'Time Information', 'wpcargo-shipment-container' ) ); ?></p>
        <?php
        if( !empty( wpc_time_info_fields() ) ){
            foreach ( wpc_time_info_fields() as $key => $value) {
                $_value = maybe_unserialize( get_post_meta( $container_id, $key, true ) );
                if( is_array( $_value ) ){
                    $value = implode(",", $_value);
                }
                ?><p><strong><?php echo $value['label'] ?></strong>: <?php echo $_value; ?></p><?php
            }
        }
        ?>
    </div>
    <?php do_action('container_track_result_after_details', $container_id ); ?>
</div>