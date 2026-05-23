<div id="container-history-wrapper" class="table-responsive">
    <table id="container-history" class="wpc-shipment-history table table-hover table-sm" style="width:100%">
        <thead>
            <tr class="text-center">
                <?php foreach( wpcargo_history_fields() as $history_name => $history_fields ): ?>
                    <th class="tbl-sh-<?php echo $history_name; ?>"><strong><?php _e($history_fields['label'], 'wpcargo-shipment-container'); ?></strong></th>
                <?php endforeach; ?>
                <th>&nbsp;</th>
            </tr>
        </thead>
        <tbody data-repeater-list="container_history">
            <?php
                if( !empty( $history ) ):
                    foreach ( $history as $_history ) :
                        ?>
                        <tr data-repeater-item class="history-data">

                            <?php foreach( wpcargo_history_fields() as $history_metakey => $history_value ): ?>
                                <?php
                                    $custom_classes = array( 'form-control' );
                                    $value          = !empty( $_history[$history_metakey] ) ? $_history[$history_metakey] : '';
                                    if( $history_metakey == 'date' ){
                                        $custom_classes[] = 'wpccf-datepicker';
                                    }elseif( $history_metakey == 'time' ){
                                        $custom_classes[] = 'wpccf-timepicker';
                                    }
                                    if( $history_value['field'] == 'select'){
                                        $custom_classes[] = 'browser-default';
                                    }
                                    if( in_array( $history_metakey, wpcfe_autocomplete_address_fields() ) ){
                                        $custom_classes[] = 'wpcfe_autocomplete_address';
                                    }
                                    $custom_classes = implode(" ", $custom_classes );

                                ?>
                                <td class="tbl-sh-<?php echo $history_metakey; ?>">
                                    <?php echo wpcargo_field_generator( $history_value, $history_metakey, $value, $custom_classes ); ?>
                                </td>
                            <?php endforeach; ?>
                            <td class="tbl-sh-action">
                                <input data-repeater-delete type="button" class="wpc-delete btn btn-danger btn-rounded btn-sm" value="<?php esc_html_e('Delete', 'wpcargo-shipment-container')?>"/>
                            </td>
                        </tr>
                        <?php
                    endforeach;
                else :
                    ?>
                    <tr data-repeater-item class="history-data">
                        <td colspan="6"><?php _e('No Shipment History Found.', 'wpcargo-shipment-container'); ?></td>
                    </tr>
                    <?php
                endif;
            ?>
        </tbody>
    </table>
</div>