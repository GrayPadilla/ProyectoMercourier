<div class="postbox" style="clear: both;">
	<div id="print-label-settings-wrapper" class="inside">
		<style type="text/css">
			div.copy-section {
				border: 2px solid #000;
				margin-bottom: 18px;
			}

			.copy-section table {
				border-collapse: collapse;
			}

			.copy-section table td.align-center {
				text-align: center;
			}

			.copy-section table td {
				border: 1px solid #000;
			}

			h4.text-danger {
				color: red;
			}

			table tr td {
				padding: 6px;
			}
		</style>
		<?php
		$shipper_fields 	= wpccf_get_custom_fields_by_flag('shipper_info');
		$receiver_fields 	= wpccf_get_custom_fields_by_flag('receiver_info');
		$shipment_fields 	= wpccf_get_custom_fields_by_flag('shipment_info');
		$shipper_fields 	= apply_filters('wpcc_shipper_meta_shortcode', $shipper_fields);
		$receiver_fields 	= apply_filters('wpcc_receiver_meta_shortcode', $receiver_fields);
		$shipment_fields 	= apply_filters('wpcc_shipment_meta_shortcode', $shipment_fields);
		/* 
			** Print Label Settings Value
			*/
		$barcode			= WPCARGO_CUSTOM_FIELD_URL . '/assets/images/wpcargo-123-barcode.png';
		$logo 				= '';
		if (!empty($setting_options['settings_shipment_ship_logo'])) {
			$logo 		= '<img style="width: 180px;" src="' . $setting_options['settings_shipment_ship_logo'] . '">';
		} else {
			$logo 		= '<img style="width: 180px;" src="' . WPCARGO_CUSTOM_FIELD_URL . '/assets/images/wpcargo-logo.png"  alt="WPCargo Track & Trace"/>';
		}
		if (get_option('wpcargo_label_header')) {
			$siteInfo = get_option('wpcargo_label_header');
		} else {
			$siteInfo  = $logo;
			$siteInfo .= '<h2 style="margin:0;padding:0;">' . get_bloginfo('name') . '</h2>';
			$siteInfo .= '<p style="margin:0;padding:0;font-size: 14px;">' . get_bloginfo('description') . '</p>';
			$siteInfo .= '<p style="margin:0;padding:0;font-size: 10px;">' . get_bloginfo('wpurl') . '</p>';
		}
		$shipmentDetails 	= array(
			'barcode'		=> $barcode,
			//	'packageSettings'	=> $mp_settings,
			//	'cargoSettings'	=> $setting_options,
			'logo'			=> $logo,
			'siteInfo'		=> $siteInfo
		);
		?>
		<h3><?php esc_html_e('Print Label Settings', 'wpcargo-custom-field'); ?></h3>
		<h4 class="text-danger"><?php esc_html_e('Click "Save Label" button again if Waybill is still blank after printing.', 'wpcargo-custom-field'); ?></h4>
		<div id="label-template-wrapper">
			<form id="wpccf-print-label-settings" method="POST" action="options.php">
				<?php settings_fields('wpccf_print_label_settings_group'); ?>
				<div id="account-copy" class="copy-section">
					<table class="shipment-header-table" cellpadding="0" cellspacing="0" style="border: 1px solid #000;width: 100%;margin:0;padding:0;">
						<tr>
							<td rowspan="3" class="align-center">
								<?php echo $shipmentDetails['logo']; ?>
							</td>
							<td rowspan="3" class="align-center">
								<img style="float: none !important; margin: 0 !important; width: 180px;height: 50px;" src="<?php echo $shipmentDetails['barcode']; ?>" alt="WPC123456" />
								<p style="margin:0;padding:0;font-weight: bold;">WPC123456</p>
								<span class="copy-label"><?php esc_html_e('Accounts Copy', 'wpcargo-custom-field'); ?></span>
							</td>
							<td>
								<input type="text" name="wpccf_print_label_settings[header_cell1]" style="width: 100%" value="<?php echo $header_cell1 ?: 'Pickup Date: {wpcargo_pickup_date_picker}'; ?>" placeholder="<?php esc_html_e('Pickup Date', 'wpcargo-custom-field'); ?>: {pickup_date_metakey}">
							</td>
							<td>
								<input type="text" name="wpccf_print_label_settings[header_cell2]" style="width: 100%" value="<?php echo $header_cell2 ?: 'Pickup Time: {wpcargo_pickup_time_picker}'; ?>" placeholder="<?php esc_html_e('Pickup Time', 'wpcargo-custom-field'); ?>: {pickup_time_metakey}">
							</td>
							<td>
								<input type="text" name="wpccf_print_label_settings[header_cell3]" style="width: 100%" value="<?php echo $header_cell3 ?: 'Expected Delivery Date: {wpcargo_expected_delivery_date_picker}'; ?>" placeholder="<?php esc_html_e('Delivery Date', 'wpcargo-custom-field'); ?>: {delivery_date_metakey}">
							</td>
						</tr>
						<tr>
							<td>
								<input type="text" name="wpccf_print_label_settings[header_cell4]" style="width: 100%" value="<?php echo $header_cell4 ?: 'Origin: {wpcargo_origin_field}'; ?>" placeholder="<?php esc_html_e('Origin', 'wpcargo-custom-field'); ?>: {origin_metakey}">
							</td>
							<td>
								<input type="text" name="wpccf_print_label_settings[header_cell5]" style="width: 100%" value="<?php echo $header_cell5 ?: 'Destination: {wpcargo_destination}'; ?>" placeholder="<?php esc_html_e('Destination', 'wpcargo-custom-field'); ?>: {destination_metakey}">
							</td>
							<td>
								<input type="text" name="wpccf_print_label_settings[header_cell6]" style="width: 100%" value="<?php echo $header_cell6 ?: 'Courier: {wpcargo_courier}'; ?>" placeholder="<?php esc_html_e('Courier', 'wpcargo-custom-field'); ?>: {courrier_metakey}">
							</td>
						</tr>
						<tr>
							<td>
								<input type="text" name="wpccf_print_label_settings[header_cell7]" style="width: 100%" value="<?php echo $header_cell7 ?: 'Carrier: {wpcargo_carrier_field}'; ?>" placeholder="<?php esc_html_e('Carrier', 'wpcargo-custom-field'); ?>: {carrier_metakey}">
							</td>
							<td>
								<input type="text" name="wpccf_print_label_settings[header_cell8]" style="width: 100%" value="<?php echo $header_cell8 ?: 'Carrier Reference No.: {wpcargo_carrier_ref_number}'; ?>" placeholder="<?php esc_html_e('Carrier Reference No.', 'wpcargo-custom-field'); ?> {carrier_no_metakey}">
							</td>
							<td>
								<input type="text" name="wpccf_print_label_settings[header_cell9]" style="width: 100%" value="<?php echo $header_cell9 ?: 'Departure Time: {wpcargo_departure_time_picker}'; ?>" placeholder="<?php esc_html_e('Departure Time', 'wpcargo-custom-field'); ?>: {departure_metakey}">
							</td>
						</tr>
						<tr>
						</tr>
					</table>
					<table class="shipment-info-table" cellpadding="0" cellspacing="0" style="border: 1px solid #000;width: 100%;margin:0;padding:0;">
						<tr>
							<td><?php esc_html_e('Shipper', 'wpcargo-custom-field'); ?></td>
							<td><input type="text" name="wpccf_print_label_settings[content_cell1]" style="width: 100%" value="<?php echo $content_cell1 ?: '{wpcargo_shipper_name}'; ?>" placeholder="{shipper_name_metakey}"></td>
							<td><?php esc_html_e('Consignee', 'wpcargo-custom-field'); ?></td>
							<td><input type="text" name="wpccf_print_label_settings[content_cell2]" style="width: 100%" value="<?php echo $content_cell2 ?: '{wpcargo_receiver_name}'; ?>" placeholder="{receiver_name_metakey}"></td>
							<td colspan="2"><input type="text" name="wpccf_print_label_settings[content_cell3]" style="width: 100%" value="<?php echo $content_cell3 ?: 'Status: {wpcargo_status}'; ?>" placeholder="<?php esc_html_e('Status: Pending', 'wpcargo-custom-field'); ?>"></td>
						</tr>
						<tr>
							<td colspan="2">
								<textarea name="wpccf_print_label_settings[content_cell4]" rows="4" style="width: 100%; height:100%" placeholder="<?php esc_html_e('Address', 'wpcargo-custom-field'); ?> {address_metakey}</br><?php esc_html_e('Phone', 'wpcargo-custom-field'); ?>: {phone_metakey}</br> <?php esc_html_e('Email', 'wpcargo-custom-field'); ?>: {email_meta_key}"><?php echo $content_cell4 ?: 'Address: {wpcargo_shipper_address}</br>
Phone: {wpcargo_shipper_phone}</br>
Email: {wpcargo_shipper_email}'; ?></textarea>
							</td>
							<td colspan="2">
								<textarea name="wpccf_print_label_settings[content_cell5]" rows="4" style="width: 100%; height:100%" placeholder="Address {address_metakey}</br><?php esc_html_e('Phone', 'wpcargo-custom-field'); ?>: {phone_metakey}</br> <?php esc_html_e('Email', 'wpcargo-custom-field'); ?>: {email_meta_key}"><?php echo $content_cell5 ?: 'Address: {wpcargo_receiver_address}</br>
Phone: {wpcargo_receiver_phone}</br>
Email: {wpcargo_receiver_email}'; ?></textarea>
							</td>
							<td colspan="2" rowspan="2" style="vertical-align: baseline;">
								<textarea name="wpccf_print_label_settings[content_cell6]" rows="6" style="width: 100%; height:100%" placeholder="<?php esc_html_e('Comment', 'wpcargo-custom-field'); ?>: {comment_metakey}"><?php echo $content_cell6 ?: 'Comments: {wpcargo_comments}'; ?></textarea>
							</td>
						</tr>
						<tr>
							<td colspan="2">
								<textarea name="wpccf_print_label_settings[content_cell7]" rows="4" style="width: 100%; height:100%" placeholder="<?php esc_html_e('Type of Shipment', 'wpcargo-custom-field'); ?>: {shipment_type_metakey}"><?php echo $content_cell7 ?: 'Type of Shipment: {wpcargo_type_of_shipment}'; ?></textarea>
							</td>
							<td colspan="2">
								<textarea name="wpccf_print_label_settings[content_cell8]" rows="4" style="width: 100%; height:100%" placeholder="<?php esc_html_e('Product', 'wpcargo-custom-field'); ?>: {product_metakey}"><?php echo $content_cell8 ?: ''; ?></textarea>
							</td>
						</tr>
					</table>
				</div><!-- #account-copy -->
				<input type="submit" class="button button-primary button-large" name="submit" value="<?php esc_html_e('Save Label', 'wpcargo-custom-field'); ?>">
			</form>
		</div><!-- #label-template-wrapper -->
		<div id="metacode-list">
			<h3><?php esc_html_e('Custom Field Code List', 'wpcargo-custom-field'); ?></h3>
			<p class="description"><?php esc_html_e('Note: User Metakey Code to display dynamic data in print label.', 'wpcargo-custom-field'); ?></p>
			<table id="shipper-meta">
				<tr>
					<th colspan="2"><?php esc_html_e('Available Shipper Fields', 'wpcargo-custom-field'); ?></th>
				</tr>
				<tr>
					<th><?php esc_html_e('Field Label', 'wpcargo-custom-field'); ?></th>
					<th><?php esc_html_e('Metakey Code', 'wpcargo-custom-field'); ?></th>
				</tr>
				<?php
				if (!empty($shipper_fields)) {
					foreach ($shipper_fields as $shpr_field) {
				?>
						<tr>
							<td><?php echo stripslashes($shpr_field['label']); ?></td>
							<td><?php echo '{' . $shpr_field['field_key'] . '}'; ?></td>
						</tr>
					<?php
					}
				} else {
					?><tr>
						<td colspan="2"><?php esc_html_e('No Field found for Shipper', 'wpcargo-custom-field'); ?></td>
					</tr><?php
							}
								?>
			</table>
			<table id="receiver-meta">
				<tr>
					<th colspan="2"><?php esc_html_e('Available Receiver Fields', 'wpcargo-custom-field'); ?></th>
				</tr>
				<tr>
					<th><?php esc_html_e('Field Label', 'wpcargo-custom-field'); ?></th>
					<th><?php esc_html_e('Metakey Code', 'wpcargo-custom-field'); ?></th>
				</tr>
				<?php
				if (!empty($receiver_fields)) {
					foreach ($receiver_fields as $recr_field) {
				?>
						<tr>
							<td><?php echo stripslashes($recr_field['label']); ?></td>
							<td><?php echo '{' . $recr_field['field_key'] . '}'; ?></td>
						</tr>
					<?php
					}
				} else {
					?><tr>
						<td colspan="2"><?php esc_html_e('No Field found for Receiver', 'wpcargo-custom-field'); ?></td>
					</tr><?php
							}
								?>
			</table>
			<table id="shipment-meta">
				<tr>
					<th colspan="2"><?php esc_html_e('Available Shipment Information Fields', 'wpcargo-custom-field'); ?></th>
				</tr>
				<tr>
					<th><?php esc_html_e('Field Label', 'wpcargo-custom-field'); ?></th>
					<th><?php esc_html_e('Metakey Code', 'wpcargo-custom-field'); ?></th>
				</tr>
				<?php
				if (!empty($shipment_fields)) {
					foreach ($shipment_fields as $shmt_field) {
				?>
						<tr>
							<td><?php echo stripslashes($shmt_field['label']); ?></td>
							<td><?php echo '{' . $shmt_field['field_key'] . '}'; ?></td>
						</tr>
					<?php
					}
				} else {
					?><tr>
						<td colspan="2"><?php esc_html_e('No Field found for Shipment Information', 'wpcargo-custom-field'); ?></td>
					</tr><?php
							}
								?>
			</table>
			<?php do_action('wpccf_after_code_list_information'); ?>
		</div>
	</div>
</div>
