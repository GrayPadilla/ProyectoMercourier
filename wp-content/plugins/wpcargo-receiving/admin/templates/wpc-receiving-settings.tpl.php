<form action="options.php" method="post">
<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
settings_fields( 'wpcargo_receiving_settings' );
do_settings_sections( 'wpcargo_receiving_settings' );
?>
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e('Enable beep sound when scanning?','wpcargo-receiving'); ?></th>
			<td><input type="checkbox" name="wpcargo_receiving_settings[beeb_sound]" <?php checked(isset($options['beeb_sound']), 1); ?> value="1">
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e('Would you like to auto update the user?','wpcargo-receiving'); ?></th>
			<td><input type="checkbox" name="wpcargo_receiving_settings[wpcargo_auto_update_user]" <?php checked(isset($options['wpcargo_auto_update_user']), 1); ?> value="1">
			<p style="font-size: 10px;"><?php esc_html_e('Please check if you want to auto update the user after it is updated successfully on receiving page', 'wpcargo-receiving');?></p></td>
		</tr>			
		<?php do_action( 'wpcr_settings_row', $options );?>
	</table>
<?php submit_button(); ?>
</form>