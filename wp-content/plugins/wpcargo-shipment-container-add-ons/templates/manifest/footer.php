<?php do_action( 'wpcsc_pdf_before_footer_manifest', $container_id ); ?>
<div id="wpcsc-manifest-footer">
    <div class="acknowledgement" style="margin-bottom: 36px;">
        <?php echo $acknowledgement; ?>
    </div>
    <?php echo $footer_data; ?>
</div>