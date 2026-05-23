<div id="signaturepad-header-wrapper" class="container">
<input type="hidden" id="shipment-id" value="<?php echo $get_sid; ?>">
    <div class="row"> 
    	<div class="col-md-6 mb-4">
    		<?php if( $wpcargo->logo ): ?>
        		<img src="<?php echo $wpcargo->logo ; ?>">
        	<?php else: ?>
        		<p class="h3"><?php echo get_bloginfo( 'name' ); ?></p>
        	<?php endif; ?>
    	</div>
    	<div class="col-md-6 mb-4 text-center">
    		<img src="<?php echo $wpcargo->barcode_url( $get_sid ); ?>" alt="<?php echo get_the_title($get_sid); ?>">
            <h3 class="wpcargo-title"><?php echo get_the_title( $get_sid ); ?></h3>
    	</div>       
        <div id="pod-shipper" class="col-md-6" >
            <h4 class="header-title"><?php _e( 'Shipper', 'wpcargo-pod' ); ?></h4>
            <?php
                if( !empty( $shipper_selected_option ) ){
                    foreach ( $shipper_selected_option as $option ) {
                        ?><p style="margin: 0;padding:0;"><span class="pod-label"><?php echo get_field_label($option); ?></span>: <span class="pod-value"><?php echo wpcargo_get_postmeta( $get_sid,get_field_key($option) ); ?></span></p><?php
                    }
                }
            ?>
        </div>
        <div id="pod-receiver" class="col-md-6">
            <h4 class="header-title"><?php _e( 'Receiver', 'wpcargo-pod' ); ?></h4>
            <?php
                if( !empty( $receiver_selected_option ) ){
                    foreach ( $receiver_selected_option as $option ) {
                        ?><p style="margin: 0;padding:0;"><span class="pod-label"><?php echo get_field_label($option); ?></span>: <span class="pod-value"><?php echo wpcargo_get_postmeta( $get_sid,get_field_key($option) ); ?></span></p><?php
                    }
                }
            ?>
        </div>  
        <div id="shipment-status" class="col-md-12 my-4" style="text-align:center;">
            <p id="result-status-header"><?php echo apply_filters( 'wpcargo_track_shipment_status_result_title', __( 'Estado del envío', 'wpcargo-pod' ) ); ?> : <?php echo $wpcargo_get_status; ?></p>
		</div> 
    </div>         
</div>