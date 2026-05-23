<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}
?>
<tr>
	<th><?php esc_html_e('Do you want to display it on vehicles page?', 'wpcargo-pod' ); ?></th>
	<td><input name="display_flags[]" value="vehicle_page" type="checkbox" <?php echo is_array($flags) && in_array( 'vehicle_page', $flags) ? 'checked' : ''; ?> /></td>
</tr>
