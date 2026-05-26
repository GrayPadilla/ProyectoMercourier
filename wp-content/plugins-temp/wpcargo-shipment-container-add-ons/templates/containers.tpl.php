<?php do_action('wpcsc_before_container_dashboard'); ?>

<div class="shipment-container-wrapper table-responsive">
    <table id="container-list" class="table table-hover table-sm">
        <thead>
            <tr>
                <th class="form-check">
                    <input class="form-check-input " id="wpcsc-select-all" type="checkbox"/>
                    <label class="form-check-label" for="materialChecked2"></label>
                </th>
                <th><?php echo apply_filters( 'wpcsc_container_number_label', __( 'Container Number', 'wpcargo-shipment-container' ) ); ?></th>
                <?php do_action( 'wpcsc_table_header_value' ); ?>	
            </tr>
        </thead>
        <tbody>
            <?php					
            while ( $wpc_container->have_posts() ) {
                $wpc_container->the_post();
                $action_rows        = wpcsc_shipment_action_rows( get_the_ID() );
                if( !wpcsc_is_user_container( get_the_ID() ) ){
                    $record_start = $record_start-1;
                    $record_end = $record_end-1;
                    $number_records = $number_records-1;
                    continue;
                }
                ?>
                <tr id="container-<?php echo get_the_ID(); ?>" class="container-row">
                    <td class="form-check">
                        <input class="wpcsc-container form-check-input " type="checkbox" name="wpcsc-containers[]" value="<?php echo get_the_ID(); ?>" data-number="<?php echo get_the_title(); ?>">
                        <label class="form-check-label" for="materialChecked2"></label>
                    </td>
                    <td>
                        <a href="<?php echo $page_url.'?wpcsc=edit&id='.get_the_ID(); ?>" class="text-primary font-weight-bold" title="<?php esc_html_e('Edit', 'wpcargo-shipment-container'); ?>"><?php echo get_the_title(); ?></a>
                        <?php if( $action_rows ): ?>
                            <div class="wpcsc-action-row">
                                <?php echo implode(" | ",$action_rows); ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <?php do_action( 'wpcsc_table_data_value', get_the_ID() ); ?>	
                    <?php ?>
                </tr>
                <?php
            } // end while
            ?>
        </tbody>
    </table>
</div>
<div class="row">
    <section class="col-md-5">
        <?php
        if( $number_records != 0){
            printf(
                '<p class="note note-primary">%s %s %s %s %s %s %s.</p>',
                __('Showing', 'wpcargo-shipment-container'),
                $record_start,
                __('to', 'wpcargo-shipment-container'),
                $record_end,
                __('of', 'wpcargo-shipment-container'),
                number_format($number_records),
                __('entries', 'wpcargo-shipment-container')
            );
        }else{ ?>
            <p class = "note note-primary"> <?php echo _e('There is '.$number_records.' to show', 'wpcargo-shipment-container'); ?> </p>
        <?php
        }
            
        ?>
    </section>
    <?php if( function_exists( 'wpcfe_bootstrap_pagination' ) ): ?>
    <section class="col-md-7"><?php wpcfe_bootstrap_pagination( array( 'custom_query' => $wpc_container ) ); ?></section>
    <?php endif; ?>
</div>
<?php do_action('wpcsc_after_container_dashboard'); ?>