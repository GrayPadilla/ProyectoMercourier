<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; 
} ?>
<div class="postbox">
    <section class="inside">
        <form method="post" action="options.php" class="wpc-pod-setting-admin">
            <?php settings_fields( 'wpcargo_podapp_settings_group' ); ?>
            <?php do_settings_sections( 'wpcargo_podapp_settings_group' ); ?>
            <p class="description"><?php esc_html_e( 'Note: This settings are only use for the WPCargo Driver APP mobile Application.', 'wpcargo-pod' ) ; ?></p>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Select Shipment status to display in Delivered Tab', 'wpcargo-pod' ) ; ?></th>	
                    <td>
                        <select name="wpcargo_podapp_status[delivered]" id="wpcargo_podapp_status-delivered" required>
                            <option value=""><?php esc_html_e( 'Choose One', 'wpcargo-pod' ) ; ?></option>
                            <?php foreach( $api_status as $key => $value ): ?>
                                <option value="<?php echo $key; ?>" <?php selected( $api_delivered, $key); ?> ><?php echo $value; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>	
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Select Shipment status to display in Cancelled Tab', 'wpcargo-pod' ) ; ?></th>	
                    <td>
                        <select name="wpcargo_podapp_status[cancelled]" id="wpcargo_podapp_status-cancelled" required>
                            <option value=""><?php esc_html_e( 'Choose One', 'wpcargo-pod' ) ; ?></option>
                            <?php foreach( $api_status as $key => $value ): ?>
                                <option value="<?php echo $key; ?>" <?php selected( $api_cancelled, $key); ?>><?php echo $value; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>	
                </tr>
                <tr>
                    <th colspan="2">
                        <?php _e('Remove required attributes on the Following Fields', 'wpcargo-pod' ); ?>
                        <ul>
                            <li>
                                <input id="pod-signature" type="checkbox" name="wpcargo_podapp_unrequired_fields[]" value="signature" <?php echo in_array( 'signature', $unrequired_fields ) ? 'checked' : '' ; ?>>
                                <label for="pod-signature"><?php _e('Signature', 'wpcargo-pod' ); ?></label>
                            </li>
                            <li>
                                <input id="pod-photo" type="checkbox" name="wpcargo_podapp_unrequired_fields[]" value="photo" <?php echo in_array( 'photo', $unrequired_fields ) ? 'checked' : '' ; ?>>
                                <label for="pod-photo"><?php _e('Photo', 'wpcargo-pod' ); ?></label>
                            </li>
                            <li>
                                <input id="pod-location" type="checkbox" name="wpcargo_podapp_unrequired_fields[]" value="location" <?php echo in_array( 'location', $unrequired_fields ) ? 'checked' : '' ; ?>>
                                <label for="pod-location"><?php _e('Location', 'wpcargo-pod' ); ?></label>
                            </li>
                            <li>
                                <input id="pod-remarks" type="checkbox" name="wpcargo_podapp_unrequired_fields[]" value="remarks" <?php echo in_array( 'remarks', $unrequired_fields ) ? 'checked' : '' ; ?>>
                                <label for="pod-remarks"><?php _e('Remarks', 'wpcargo-pod' ); ?></label>
                            </li>
                        </ul>
                    </th>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </section>
</div>