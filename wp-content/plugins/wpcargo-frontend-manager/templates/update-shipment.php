<?php 
	global $wpcargo;
	$shipment = new stdClass();
	$user_roles = wpcfe_current_user_role();
?>
<form method="post" action="" enctype="multipart/form-data" class="update-shipment">
	<?php wp_nonce_field( 'wpcfe_edit_action', 'wpcfe_form_fields' ); ?>
	<input type="hidden" name="shipment_id" value="<?php echo $shipment_id; ?>">
	<div class="row">
		<div class="col-md-9 mb-3">
            <section class="row">        
                <?php if( has_action( 'before_wpcfe_shipment_form_fields' ) ): ?>
                    <?php do_action( 'before_wpcfe_shipment_form_fields', $shipment_id ); ?>
                <?php endif; ?>
                <?php
                    $counter = 1;
                    // if( !empty( wpcfe_get_shipment_sections() ) ){
                        foreach ( wpcfe_get_shipment_sections() as $section => $section_header ) {
                            if( empty( $section ) ){
                                continue;
                            }
                            $column = 12;
                            // if( ( $section == 'shipper_info' || $section == 'receiver_info' ) && $counter <= 2 && count(wpcfe_get_shipment_sections() ) > 1 ){
                            if( ( $section == 'shipper_info' || $section == 'receiver_info' ) && $counter <= 2 ){
                                $column = 6;
                            }
                            $column = apply_filters( 'wpcfe_shipment_form_column', $column, $section ); 
                            ?>
                            <div id="<?php echo $section; ?>" class="col-md-<?php echo $column; ?> mb-4">
                                <div class="card">
                                    <section class="card-header">
                                        <?php echo $section_header; ?>
                                    </section>
                                    <section class="card-body">
                                        <div class="row">
                                            <?php if( has_action( 'before_wpcfe_'.$section.'_form_fields' ) ): ?>
                                                <?php do_action( 'before_wpcfe_'.$section.'_form_fields', $shipment_id ); ?>
                                            <?php endif; ?>
                                            <?php $section_fields = $WPCCF_Fields->get_custom_fields( $section ); ?>
                                            <?php $WPCCF_Fields->convert_to_form_fields( $section_fields, $shipment_id ); ?>
                                            <?php if( has_action( 'after_wpcfe_'.$section.'_form_fields' ) ): ?>
                                                <?php do_action( 'after_wpcfe_'.$section.'_form_fields', $shipment_id ); ?>
                                            <?php endif; ?>
                                        </div>
                                    </section>
                                </div>
                            </div>
                            <?php
                            $counter++;
                        }
                    // }
                ?>
                <?php if( has_action( 'after_wpcfe_shipment_form_fields' ) ): ?>
                    <?php do_action( 'after_wpcfe_shipment_form_fields', $shipment_id ); ?>
                <?php endif; ?>
                <div class="clearfix"></div>
			</section>
		</div>
		<div class="col-md-3 mb-3">
            <section class="row"> 
                <?php if( has_action( 'before_wpcfe_shipment_form_submit' ) ): ?>
                    <div class="after-shipments-info col-md-12 mb-4">
                        <?php do_action( 'before_wpcfe_shipment_form_submit', $shipment_id ); ?>
                    </div>
                <?php endif; ?>
                <div class="col-md-12 mb-5 text-right">
                    <button type="submit" class="btn btn-small btn-info btn-fill btn-wd btn-block"><?php esc_html_e('Update Shipment', 'wpcargo-frontend-manager'); ?></button>
                </div>
            </section>
		</div>
	</div>
</form>
<?php do_action( 'before_wpcargo_shipment_history', $shipment_id); ?>