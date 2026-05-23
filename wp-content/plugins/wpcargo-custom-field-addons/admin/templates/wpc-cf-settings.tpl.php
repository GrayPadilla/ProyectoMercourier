<form method="post" action="options.php" class="wpc-cf-setting-admin">    
	<?php settings_fields( 'wpcargo_custom_field_settings_group' ); ?>    
	<?php do_settings_sections( 'wpcargo_custom_field_settings_group' ); ?>    
	<table class="form-table">
		<tr>        
			<th scope="row"><?php esc_html_e( 'Disable Shipper Details Section?', 'wpcargo-custom-field' ) ; ?></th>            
			<td>                
				<input type="checkbox" name="wpcargo_cf_option_settings[wpc_cf_disable_shipper]" <?php  echo ( !empty( $options['wpc_cf_disable_shipper'] ) && $options['wpc_cf_disable_shipper'] != NULL  ) ? 'checked' : '' ; ?> />                
				<p style="font-size: 10px;">( <?php esc_html_e( 'This settings will remove Shipper Details Section', 'wpcargo-custom-field' ) ; ?> )</p>            
			</td>        
		</tr> 
		<tr>       
			<th scope="row"><?php esc_html_e( 'Disable Receiver Details Section?', 'wpcargo-custom-field' ) ; ?></th>            
			<td>                
				<input type="checkbox" name="wpcargo_cf_option_settings[wpc_cf_disable_receiver]" <?php  echo ( !empty( $options['wpc_cf_disable_receiver'] ) && $options['wpc_cf_disable_receiver'] != NULL  ) ? 'checked' : '' ; ?> />                
				<p style="font-size: 10px;">( <?php esc_html_e( 'This settings will remove Receiver Details Section', 'wpcargo-custom-field' ) ; ?> )</p>            
			</td>        
		</tr> 
		<tr>       
			<th scope="row"><?php esc_html_e( 'Disable Shipment Details Section?', 'wpcargo-custom-field' ) ; ?></th>            
			<td>                
				<input type="checkbox" name="wpcargo_cf_option_settings[wpc_cf_disable_shipment]" <?php  echo ( !empty( $options['wpc_cf_disable_shipment'] ) && $options['wpc_cf_disable_shipment'] != NULL  ) ? 'checked' : '' ; ?> />                
				<p style="font-size: 10px;">( <?php esc_html_e( 'This settings will remove Shipment Details Section', 'wpcargo-custom-field' ) ; ?> )</p>            
			</td>        
		</tr>        
		<tr>            
			<th scope="row"><?php esc_html_e( 'Add Field display options', 'wpcargo-custom-field' ) ; ?></th>            
			<td>                
				<textarea placeholder="<?php esc_html_e( 'Ex. Option 1, Option 2, Option 3', 'wpcargo-custom-field' ) ; ?>" cols="40" rows="5" name="wpcargo_cf_option_settings[wpc_cf_additional_options]"><?php echo $additional_sections; ?></textarea>                
				<p style="font-size: 10px;">( <?php esc_html_e( 'This settings will add additional section for "Field display options" in "Add Form Field" page. Must be comma separated', 'wpcargo-custom-field' ) ; ?> )</p>            
			</td>        
		</tr>    
	</table>    
	<?php submit_button(); ?>
</form>