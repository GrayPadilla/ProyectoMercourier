<div id="wpcargo-container">
	<?php do_action('container_track_form_title'); ?>
    <form id="container-trackform" method="post" action="<?php echo $pageredirect_url; ?>">
    	<p id="trackform-container-id" class="wpc-form-field"><input type="text" name="track-container" value="<?php echo ( isset($_POST['track-container'])) ? $_POST['track-container'] : '' ; ?>" placeholder="<?php echo apply_filters( 'wpc_container_trackform_placeholder', __( 'Enter Container No.', 'wpcargo-shipment-container' ) ); ?>" required /></p> 
        <?php do_action('container_track_form_description'); ?>
    	<p id="trackform-submit" class="wpc-form-field submit"><input class="button wpc-button wpc-button-submit" type="submit" name="wpc-container-track-submit" value="<?php echo apply_filters( 'wpc_container_trackform_submit', __( 'Track Container', 'wpcargo-shipment-container' ) ); ?>" /></p>
    </form>
</div>