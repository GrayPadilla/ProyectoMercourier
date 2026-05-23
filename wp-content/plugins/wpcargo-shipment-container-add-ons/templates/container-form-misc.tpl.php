<section id="container-history" class="mb-4">
    <div class="card">
        <section class="card-header">
            <?php echo apply_filters( 'wpcfe_publish_header_label', __('Publish', 'wpcargo-shipment-container') ); ?>
        </section>
        <section class="card-body">
            <div class="form-row">
                <?php do_action( 'wpcsc_before_misc_fields', $container_id ); ?>
                <?php foreach( wpcargo_history_fields() as $history_metakey => $history_value ): ?>
                    <?php 
                        if( $history_metakey == 'updated-name' ){
                            continue;
                        }
                        $custom_classes = array( 'form-control' );
                        $value          = '';
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
                        <label for="<?php echo '_wpcsh_'.$history_metakey; ?>"><?php echo $history_value['label'];?></label>
                        <?php echo wpcargo_field_generator( $history_value, '_wpcsh_'.$history_metakey, $value, '_wpcsh_'.$history_metakey.' '.$custom_classes ); ?>
                    </div>
                <?php endforeach; ?>
                <?php do_action( 'wpcsc_after_misc_fields', $container_id ); ?>
            </div>
            <div class="form-check">
                <input id="wpcscapply-shipment" type="checkbox" class="form-check-input" name="apply_shipment" value="1"/>
                <label for="wpcscapply-shipment"><?php echo wpc_scpt_apply_to_shipments_message(); ?></label>
            </div>
        </section>
    </div>
</section>