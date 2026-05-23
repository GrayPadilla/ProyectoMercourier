<div class="pod-tracking-header wpcargo-container">
    <div class="wpcargo-row"> 
        <div id="wpcargo-barcode-header" class="wpcargo-col text-center">
            <img src="<?php echo $wpcargo->barcode_url( $get_sid ); ?>" alt="<?php echo get_the_title($get_sid); ?>">
            <h2 class="wpcargo-title"><?php echo get_the_title($get_sid); ?></h2>
            <h3><?php echo $wpcargo_get_status; ?></h3>
        </div>          
    </div>
    <div class="wpcargo-row">
        <div id="pod-shipper" class="pod-section wpcargo-md-6" >
            <h4 class="header-title"><?php esc_html_e( 'Shipper', 'wpcargo-pod' ); ?></h4>
            <?php
                if( !empty( $shipper_selected_option ) ){
                    foreach ( $shipper_selected_option as $option ) {
                        ?><p style="margin: 0;padding:0;"><span class="pod-label"><?php echo get_field_label($option); ?></span>: <span class="pod-value"><?php echo wpcargo_get_postmeta( $get_sid, get_field_key($option) ); ?></span></p><?php
                    }
                }
            ?>
        </div>
        <div id="pod-receiver" class="pod-section wpcargo-md-6">
            <h4 class="header-title"><?php esc_html_e( 'Receiver', 'wpcargo-pod' ); ?></h4>
            <?php
                if( !empty( $receiver_selected_option ) ){
                    foreach ( $receiver_selected_option as $option ) {
                        ?><p style="margin: 0;padding:0;"><span class="pod-label"><?php echo get_field_label($option); ?></span>: <span class="pod-value"><?php echo wpcargo_get_postmeta( $get_sid, get_field_key($option) ); ?></span></p><?php
                    }
                }
            ?>
        </div>   
    </div>         
</div>