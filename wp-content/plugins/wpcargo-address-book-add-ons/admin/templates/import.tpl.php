<div id="export-section" class="postbox">
	<div class="inside">
		<h2><?php esc_html_e('Export Address Book', 'wpcargo-address-book' ); ?></h2>
		<?php 
		$shipper_fields 	= wpc_address_book_get_custom_fields( 'shipper_info' );
		$receiver_fields 	= wpc_address_book_get_custom_fields( 'receiver_info' );
		$user_book 			= wpc_address_book_get_all_data( 'shipper' );
		$address_book_user = get_users( array( 'role__in' => wpc_registered_roles() )  );
		?>
		<form id="export-address-book">
			<table class="form-table">
				<tr>
					<th><?php esc_html_e('Select User Book to Export', 'wpcargo-address-book' ); ?></th>
					<td>
						<?php 		
						if( !empty( $address_book_user ) ){
							?>
							<select name="user">
								<option value=""><?php esc_html_e('All User', 'wpcargo-address-book' ); ?></option>
								<?php 
									foreach ( $address_book_user as $key => $value) {
										?><option value="<?php echo $value->ID; ?>"><?php echo wpc_address_book_get_user_fullname( $value->ID ); ?></option><?php
									}
								?>
							</select>
							<?php
						}
						?>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e('Select Book Type', 'wpcargo-address-book' ); ?></th>
					<td>
						<select name="book_type" required="required">
							<option value=""><?php esc_html_e('Select Book', 'wpcargo-address-book' ); ?></option>
							<option value="receiver"><?php esc_html_e('Receiver', 'wpcargo-address-book' ); ?></option>
							<option value="shipper"><?php esc_html_e('Shipper', 'wpcargo-address-book' ); ?></option>
						</select>
					</td>
				</tr>
			</table>	
			<input class="button button-primary button-large" type="submit" name="export-address" value="<?php esc_html_e('Export Address Book', 'wpcargo-address-book' ); ?>">
		</form>
	</div>
</div>
<div id="import-section" class="postbox">
	<div class="inside">
		<h2><?php esc_html_e('Import Address Book', 'wpcargo-address-book' ); ?></h2>
		<p class="description"><?php esc_html_e('Note: Please download .csv format in importing address book based on Book Type.', 'wpcargo-address-book' ); ?></p>
		<ul id="wpcab-csv-template">
			<li><a data-id="shipper" href="#"><?php esc_html_e('Download Shipper CSV template', 'wpcargo-address-book' ); ?></a></li>
			<li><a data-id="receiver" href="#"><?php esc_html_e('Download Receiver CSV template', 'wpcargo-address-book' ); ?></a></li>
		</ul>
		<form id="import-address-book" enctype="multipart/form-data" method="post" action="<?php echo admin_url('admin.php?page=ei-address-book'); ?>">
			<?php wp_nonce_field( 'import_address_book_action', 'import_address_field' ); ?>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e('Upload Book', 'wpcargo-address-book' ); ?></th>
					<td><input id="address_file" type="file" name="address_file"></td>
				</tr>
				<tr>
					<th><?php esc_html_e('Select Book Type to Import', 'wpcargo-address-book' ); ?></th>
					<td>
						<select name="book_type" required="required">
							<option value=""><?php esc_html_e('Select Book', 'wpcargo-address-book' ); ?></option>
							<option value="receiver"><?php esc_html_e('Receiver', 'wpcargo-address-book' ); ?></option>
							<option value="shipper"><?php esc_html_e('Shipper', 'wpcargo-address-book' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e('Mac / International language settings compatibility', 'wpcargo-address-book' ); ?></th>
					<td>
						<input type="checkbox" name="compatibility" value="1">
						<p class="description"><?php esc_html_e( 'Please this checkbox if you\'re using Mac OS or you\'re having issue importing due to international language settings setup.' , 'wpcargo-address-book'); ?></p>
					</td>
				</tr>
			</table>	
			<input class="button button-primary button-large" type="submit" name="export-address" value="<?php esc_html_e('Import Address Book', 'wpcargo-address-book' ); ?>">
		</form>
		<div id="import-result"></div>
	</div>
</div>