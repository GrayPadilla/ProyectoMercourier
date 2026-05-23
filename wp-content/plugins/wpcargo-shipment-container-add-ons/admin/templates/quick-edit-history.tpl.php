<fieldset class="inline-edit-col-left" style="border: 1px solid #ddd;margin: 18px 0 12px; position:relative !important;">
    <div class="inline-edit-col">	
        <div class="inline-edit-group wp-clearfix">
            <legend class="inline-edit-legend"><?php esc_html_e( 'Container Status', 'wpcargo-shipment-container' ) ?></legend>
            <?php foreach( wpcargo_history_fields() as $history_name => $history_value ): ?>
                <p>
                    <?php
                        $picker_class = '';
                        $value = '';
                        if( $history_name == 'date' ){
                            $picker_class = 'wpcargo-datepicker';
                            $value = current_time( $wpcargo->date_format );
                        }elseif( $history_name == 'time' ){
                            $picker_class = 'wpcargo-timepicker';
                            $value = current_time( $wpcargo->time_format );
                        }
                        if( $history_name != 'updated-name' ){
                            echo '<label for="'.$history_name.'">'.$history_value['label'].'</label>';
                            echo wpcargo_field_generator( $history_value, '_wpcsh_'.$history_name, $value, 'history-update '.$picker_class.' status_'.$history_name );
                        }
                    ?>
                </p>
            <?php endforeach; ?>
            <label><input id="wpcsh-ashipment" type="checkbox" name="apply_shipment" value="1"/><span for="wpcsh-ashipment" class="description"><?php esc_html_e( 'Apply this update for all shipments in the container.', 'wpcargo-shipment-container' ); ?></span></label>
        </div>
    </div>
</fieldset>