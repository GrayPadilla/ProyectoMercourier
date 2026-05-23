<fieldset class="inline-edit-col-right">
    <div class="inline-edit-col">	
        <div class="inline-edit-group wp-clearfix">
            <label class="inline-edit-status">
                <span class="title"><?php esc_html_e( 'Select Driver', 'wpcargo-shipment-container' ); ?></span>
                <select name="wpcsc_delivery_agent">
                    <option value=""><?php esc_html_e( '— No Change —', 'wpcargo-shipment-container' ); ?></option>
                    <?php
                    if( !empty( wpc_shipment_container_get_all_user( 'delivery_agent' ) ) ){
                        $delivery_agents = wpc_shipment_container_get_all_user( 'delivery_agent' );
                        foreach ( $delivery_agents as $agent ) {
                            ?><option value="<?php echo $agent->ID; ?>"><?php echo $wpcargo->user_fullname( $agent->ID ); ?></option><?php
                        }
                    }
                    ?>
                </select>
            </label>
            <label><input id="wpcsh-adriver" type="checkbox" name="apply_driver" value="1"> <span for="wpcsh-adriver"><?php esc_html_e('Apply this Driver to all the shipments under this container.', 'wpcargo-shipment-container'); ?><span></label>
        </div>
    </div>
</fieldset>