<form method="POST" id="container-form" class="row"
      action="<?php echo admin_url('admin-post.php'); ?>">
    <?php $submit_label = isset( $_GET['wpcsc'] ) && $_GET['wpcsc'] == 'edit' ? wpc_scpt_update_item_label() : wpc_scpt_add_container_label(); ?>
    <?php wp_nonce_field( 'wpcsc_form_action', 'wpcsc_nonce_field_value' ); ?>
    <input type="hidden" name="action" value="wpcsc_assign_partial_shipments">
    
    <div class="col-md-9">
        <div class="row">
            <div class="col-md-12 mb-4">
                <!-- Default input -->
                <label class="sr-only" for="wpcsc_number"><?php echo wpc_scpt_container_num_label(); ?></label>
                <div class="input-group">
                    <div class="input-group-prepend">
                    <div class="input-group-text"><i class="fa fa-barcode mr-3"></i><?php echo wpc_scpt_container_num_label(); ?></div>
                    </div>
                    <input type="text" class="form-control py-0" id="wpcsc_number" name="wpcsc_number" value="<?php echo $container_number; ?>">
                </div>
            </div>
            <?php include_once( WPCARGO_SHIPMENT_CONTAINER_PATH.'templates/container-form-shipments.tpl.php' ); ?>
			<!-- #container-info -->
        </div> <!-- End Row -->
    </div> <!-- End col-md-9 -->
    <div class="col-md-3" >
        <?php do_action( 'wpcsc_before_sidebar_form_section', $container_id ); ?>
        <input type="hidden" id="container_id" name="container_id" value="<?php echo $container_id; ?>">
        <!-- Botón eliminado: ahora se asigna directamente desde la tabla de pedidos -->
    </div>
</form>
<?php include_once( WPCARGO_SHIPMENT_CONTAINER_PATH.'templates/container-form-modal.tpl.php' ); ?>