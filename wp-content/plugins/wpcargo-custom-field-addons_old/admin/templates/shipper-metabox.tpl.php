<div id="shipper-details" class="wpcargo-field-section one-half first">
    <h1 class="section-title"><?php echo apply_filters('wpc_shipper_details_label',__('Shipper Details', 'wpcargo-custom-field' ) ); ?></h1>
    <?php do_action('wpc_before_shipper_details_table'); ?>
    <?php wpc_cf_show_fields( 'shipper_info' ); ?>
    <?php do_action('wpc_after_shipper_details_table'); ?>
</div> <!-- shipper-details -->