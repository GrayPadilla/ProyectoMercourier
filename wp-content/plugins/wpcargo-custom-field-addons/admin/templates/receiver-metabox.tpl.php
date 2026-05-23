<div id="receiver-details" class="wpcargo-field-section one-half">
    <h1 class="section-title"><?php echo apply_filters('wpc_receiver_details_label',__('Receiver Details', 'wpcargo-custom-field' ) ); ?></h1>
    <?php do_action('wpc_before_receiver_details_table'); ?>
    <?php wpc_cf_show_fields( 'receiver_info' ); ?>
    <?php do_action('wpc_after_receiver_details_table'); ?>
</div>