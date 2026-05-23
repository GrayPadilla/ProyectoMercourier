<h1><?php esc_html_e('Address Book Settings', 'wpcargo-address-book' ); ?></h1>
<form method="POST" action="options.php" enctype="multipart/form-data">
    <?php settings_fields( 'wpc-address-book-settings-group' ); ?>
    <?php do_settings_sections( 'wpc-address-book-settings-group' ); ?>
    <table class="form-table">
        <tbody>
        	<tr valign="top">
                <th scope="row" colspan="2" class="titledesc">
                    <input id="wpc_disable_address_shipper_search" type="checkbox" name="wpc_disable_address_shipper_search" value="1" <?php checked( get_option('wpc_disable_address_shipper_search'), 1 ); ?> /> <label for="wpc_disable_address_shipper_search"><?php esc_html_e('Disable Search Shipper Address?', 'wpcargo-address-book' ); ?></label>
                </th>
            </tr>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <?php esc_html_e('Select Shipper Address Field Search Value', 'wpcargo-address-book' ); ?>
                </th>
                <td>
                	<select name="wpc_address_shipper_search" required>
                    	<option value=""><?php esc_html_e('-- Select Shipper Search Value --', 'wpcargo-address-book' ); ?></option>
                        <?php
						$wpc_address_shipper_search = get_option('wpc_address_shipper_search');
						if( !empty($shipper_fields) ){
							foreach( $shipper_fields as $field ){
								?><option value="<?php echo $field['field_key']; ?>" <?php selected( $wpc_address_shipper_search, $field['field_key'] ); ?>> <?php echo $field['label']; ?></option><?php
							}
						}
						?>
                    </select>
                    <p class="description"><?php esc_html_e('The selected options will be the key value for the search Shipper Book Address list in the form.', 'wpcargo-address-book' ); ?></p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <?php esc_html_e('Select Fields to be display in Shipper Book Address List', 'wpcargo-address-book' ); ?>
                </th>
                <td>
                	<input style="display:none !important;" type="checkbox" name="wpc_shipper_address_book[]" value="" checked />
					<?php
					$wpc_shipper_address_book = get_option('wpc_shipper_address_book');
					if( empty( $wpc_shipper_address_book ) ){
						$wpc_shipper_address_book = array();
					}
                    if( !empty($shipper_fields) ){
                        foreach( $shipper_fields as $field ){
                            ?><input type="checkbox" name="wpc_shipper_address_book[]" value="<?php echo $field['id']; ?>" <?php echo ( in_array( $field['id'], $wpc_shipper_address_book) ) ? 'checked' : '' ; ?> /> <?php echo $field['label']; ?><br/><?php
                        }
                    }
                    ?>
                    <p class="description"><?php esc_html_e('The selected options will display in the Shipper Book Address template list table.', 'wpcargo-address-book' ); ?></p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row" colspan="2" class="titledesc">
                    <input id="wpc_disable_address_receiver_search" type="checkbox" name="wpc_disable_address_receiver_search" value="1" <?php checked( get_option('wpc_disable_address_receiver_search'), 1 ); ?> /> <label for="wpc_disable_address_receiver_search"><?php esc_html_e('Disable Search Receiver Address?', 'wpcargo-address-book' ); ?></label>
                </th>
            </tr>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <?php esc_html_e('Select Receiver Address Field Search Value', 'wpcargo-address-book' ); ?>
                </th>
                <td>
                    <select name="wpc_address_receiver_search" required>
                    	<option value=""><?php esc_html_e('-- Select Receiver Search Value --', 'wpcargo-address-book' ); ?></option>
                        <?php
						$wpc_address_receiver_search = get_option('wpc_address_receiver_search');
						if( !empty($receiver_fields) ){
							foreach( $receiver_fields as $field ){
								?><option value="<?php echo $field['field_key']; ?>" <?php selected( $wpc_address_receiver_search, $field['field_key'] ); ?>> <?php echo $field['label']; ?></option><?php
							}
						}
						?>
                    </select>
                    <p class="description"><?php esc_html_e('The selected options will be the key value for the search Receiver Book Address list in the form.', 'wpcargo-address-book' ); ?></p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <?php esc_html_e('Select Fields to be display in Receiver Book Address List', 'wpcargo-address-book' ); ?>
                </th>
                <td>
                	<input style="display:none !important;" type="checkbox" name="wpc_receiver_address_book[]" value="" checked />
					<?php
					$wpc_receiver_address_book = get_option('wpc_receiver_address_book');
					if( empty( $wpc_receiver_address_book ) ){
						$wpc_receiver_address_book = array();
					}
                    if( !empty($receiver_fields) ){
                        foreach( $receiver_fields as $field ){
                            ?><input type="checkbox" name="wpc_receiver_address_book[]" value="<?php echo $field['id']; ?>" <?php echo ( in_array( $field['id'], $wpc_receiver_address_book) ) ? 'checked' : '' ; ?> /> <?php echo $field['label']; ?><br/><?php
                        }
                    }
                    ?>
                    <p class="description"><?php esc_html_e('The selected options will display in the Receiver Book Address template list table.', 'wpcargo-address-book' ); ?></p>
                </td>
            </tr>
        </tbody>
    </table>
    <input class="primary button-primary" type="submit" name="submit" value="<?php esc_html_e('Save Address Book Settings', 'wpcargo-address-book' ); ?>" />
</form>