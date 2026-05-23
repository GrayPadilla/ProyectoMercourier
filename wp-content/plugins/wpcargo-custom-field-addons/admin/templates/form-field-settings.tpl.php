<div class="wpcargo-field-seeting postbox" style="clear: both;">
	<div class="inside">
		<h2><?php esc_html_e('Settings', 'wpcargo-custom-field' ); ?></h2>
		<form id="wpc-custom-field-settings" method="POST" action="<?php echo admin_url('admin.php?page=wpc-cf-manage-form-field&action=settings'); ?>">
			<?php wp_nonce_field( 'wpc-custom-field-settings_action', 'wpc-custom-field-settings_nonce_field' ); ?>
			<?php 
			$shipper_fields 	= wpccf_get_custom_fields_by_flag( 'shipper_info' );
			$receiver_fields 	= wpccf_get_custom_fields_by_flag( 'receiver_info' );
			$shipment_fields 	= wpccf_get_custom_fields_by_flag( 'shipment_info' );
			$all_fields			= array_merge( $shipper_fields, $receiver_fields, $shipment_fields );
			$shipper_column 	= get_option('shipper_column');
			$receiver_column 	= get_option('receiver_column');
			?>
			<table class="custom-field-settings-table form-table">
				<tr>
					<th><?php esc_html_e('Select Field to display in Shipper Name Column', 'wpcargo-custom-field' ); ?></th>
					<td>
						<select name="shipper_column" required="required">
							<option value=""><?php esc_html_e('Select Field', 'wpcargo-custom-field'); ?></option>
							<?php 
							if( !empty( $shipper_fields ) ){
								foreach ( $shipper_fields as $field ) {
									?><option value="<?php echo $field['field_key']; ?>" <?php selected( $shipper_column, $field['field_key'] ); ?> ><?php echo stripslashes( $field['label'] ); ?></option><?php
								}
							}
							?>
						</select>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e('Select Field to display in Receiver Name Column', 'wpcargo-custom-field' ); ?></th>
					<td>
						<select name="receiver_column" required="required">
							<option value=""><?php esc_html_e('Select Field', 'wpcargo-custom-field'); ?></option>
							<?php 
							if( !empty( $receiver_fields ) ){
								foreach ( $receiver_fields as $field ) {
									?><option value="<?php echo $field['field_key']; ?>" <?php selected( $receiver_column, $field['field_key'] ); ?> ><?php echo stripslashes( $field['label'] ); ?></option><?php
								}
							}
							?>
						</select>
					</td>
				</tr>
				<tr>
					<th colspan="2"><input type="submit" class="button button-primary" name="submit" value="<?php esc_html_e( 'Save Settings', 'wpcargo-custom-field' ); ?>"></th>
				</tr>
			</table>
		</form>
	</div>
</div>