<div class="pod-tracking-header wpcargo-container">
    <div class="wpcargo-row"> 
        <div id="wpcargo-barcode-header" class="wpcargo-col text-center">
            <img src="<?php echo $wpcargo->barcode_url( $get_sid ); ?>" alt="<?php echo get_the_title($get_sid); ?>">
            <h2 class="wpcargo-title"><?php echo get_the_title($get_sid); ?></h2>
            <h3><?php echo $wpcargo_get_status; ?></h3>
        </div>          
    </div>
    <div class="wpcargo-row">
        <div class="pod-shipper wpcargo-md-6">
            <p><span><?php esc_html_e('Shipper Name:', 'wpcargo-pod' ); ?></span>  <?php echo get_post_meta($get_sid, 'wpcargo_shipper_name', true); ?></p>
            <p><span><?php esc_html_e('Phone:', 'wpcargo-pod' ); ?></span>  <?php echo get_post_meta($get_sid, 'wpcargo_shipper_phone', true); ?></p>
            <p><span><?php esc_html_e('Email:', 'wpcargo-pod' ); ?></span>  <?php echo get_post_meta($get_sid, 'wpcargo_shipper_email', true); ?></p>
    		<p><span><?php esc_html_e('Shipper Address:', 'wpcargo-pod' ); ?></span>  <?php echo ''.get_post_meta($get_sid, 'wpcargo_shipper_address', true); ?></p>
        </div>
        <div class="pod-receiver wpcargo-md-6">
            <p><span><?php esc_html_e('Receiver Name:', 'wpcargo-pod' ); ?></span>  <?php echo get_post_meta($get_sid, 'wpcargo_receiver_name', true); ?></p>	
            <p><span><?php esc_html_e('Phone:', 'wpcargo-pod' ); ?></span>  <?php echo get_post_meta($get_sid, 'wpcargo_receiver_phone', true); ?></p>
            <p><span><?php esc_html_e('Email:', 'wpcargo-pod' ); ?></span>  <?php echo get_post_meta($get_sid, 'wpcargo_receiver_email', true); ?></p>
            <p><span><?php esc_html_e('Receiver Address:', 'wpcargo-pod' ); ?></span>  <?php echo ''.get_post_meta($get_sid, 'wpcargo_receiver_address', true); ?></p>	
        </div>	
    </div>   			
</div>