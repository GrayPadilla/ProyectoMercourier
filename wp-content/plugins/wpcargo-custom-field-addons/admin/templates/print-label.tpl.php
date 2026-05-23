<?php 
	$_pfields 		= get_option( 'wpccf_print_label_settings' );
	$packages 		= $shipmentDetails['packages'];
	$shipment_id 	= $shipmentDetails['shipmentID'];
	$mp_settings 	= $shipmentDetails['packageSettings'];
	$header_cell1 	= str_replace( wpccf_search_metakey_code(), wpccf_replace_metakey_code( $shipment_id ), $_pfields['header_cell1'] );
	$header_cell2 	= str_replace( wpccf_search_metakey_code(), wpccf_replace_metakey_code( $shipment_id ), $_pfields['header_cell2'] );
	$header_cell3 	= str_replace( wpccf_search_metakey_code(), wpccf_replace_metakey_code( $shipment_id ), $_pfields['header_cell3'] );
	$header_cell4 	= str_replace( wpccf_search_metakey_code(), wpccf_replace_metakey_code( $shipment_id ), $_pfields['header_cell4'] );
	$header_cell5 	= str_replace( wpccf_search_metakey_code(), wpccf_replace_metakey_code( $shipment_id ), $_pfields['header_cell5'] );
	$header_cell6 	= str_replace( wpccf_search_metakey_code(), wpccf_replace_metakey_code( $shipment_id ), $_pfields['header_cell6'] );
	$header_cell7 	= str_replace( wpccf_search_metakey_code(), wpccf_replace_metakey_code( $shipment_id ), $_pfields['header_cell7'] );
	$header_cell8 	= str_replace( wpccf_search_metakey_code(), wpccf_replace_metakey_code( $shipment_id ), $_pfields['header_cell8'] );
	$header_cell9 	= str_replace( wpccf_search_metakey_code(), wpccf_replace_metakey_code( $shipment_id ), $_pfields['header_cell9'] );
	$content_cell1 	= str_replace( wpccf_search_metakey_code(), wpccf_replace_metakey_code( $shipment_id ), $_pfields['content_cell1'] );
	$content_cell2 	= str_replace( wpccf_search_metakey_code(), wpccf_replace_metakey_code( $shipment_id ), $_pfields['content_cell2'] );
	$content_cell3 	= str_replace( wpccf_search_metakey_code(), wpccf_replace_metakey_code( $shipment_id ), $_pfields['content_cell3'] );
	$content_cell4 	= str_replace( wpccf_search_metakey_code(), wpccf_replace_metakey_code( $shipment_id ), $_pfields['content_cell4'] );
	$content_cell5 	= str_replace( wpccf_search_metakey_code(), wpccf_replace_metakey_code( $shipment_id ), $_pfields['content_cell5'] );
	$content_cell6 	= str_replace( wpccf_search_metakey_code(), wpccf_replace_metakey_code( $shipment_id ), $_pfields['content_cell6'] );
	$content_cell7 	= str_replace( wpccf_search_metakey_code(), wpccf_replace_metakey_code( $shipment_id ), $_pfields['content_cell7'] );
	$content_cell8 	= str_replace( wpccf_search_metakey_code(), wpccf_replace_metakey_code( $shipment_id ), $_pfields['content_cell8'] );
	$copies = array(
		'account-copy' 		=> esc_html__('Accounts Copy', 'wpcargo' ),
		'consignee-copy' 	=> esc_html__('Consignee Copy', 'wpcargo' ),
		'shippers-copy' 	=> esc_html__('Shippers Copy', 'wpcargo' ),
	);
	$copies = apply_filters( 'wpcargo_print_label_template_copies', $copies );
	if( empty( $copies ) ){
		return false;
	}
?>
<style type="text/css">
	.copy-section {
		border: 2px solid #000;
		margin-bottom: 18px;
	}
	.copy-section table {
		border-collapse: collapse;
	}
	.copy-section table td.align-center{
		text-align: center;
	}
	.copy-section table td {
		border: 1px solid #000;
		padding:6px;
	}
</style>
<?php do_action('wpc_label_before_header_information', $shipment_id ); ?>
<?php foreach( $copies as $key => $label ): ?>
	<div id="<?php echo $key; ?>" class="copy-section">
		<table class="shipment-header-table" cellpadding="0" cellspacing="0" style="border: 1px solid #000;width: 100%;margin:0;padding:0;">
			<tr>
				<td rowspan="3" class="align-center">
					<?php echo $shipmentDetails['logo']; ?>
				</td>
				<td rowspan="3" class="align-center">
					<img style="float: none !important; margin: 0 !important; width: 180px;height: 50px;" src="<?php echo $shipmentDetails['barcode']; ?>" alt="<?php echo get_the_title( $shipment_id ); ?>" />
					<p style="margin:0;padding:0;font-weight: bold;"><?php echo get_the_title( $shipment_id ); ?></p>
					<?php do_action('wpc_label_header_barcode_information', $shipment_id ); ?>
					<span class="copy-label"><?php echo $label; ?></span>
				</td>		
				<td><?php echo $header_cell1; ?></td>
				<td><?php echo $header_cell2; ?></td>
				<td><?php echo $header_cell3; ?></td>
			</tr>
			<tr>
				<td><?php echo $header_cell4; ?></td>
				<td><?php echo $header_cell5; ?></td>
				<td><?php echo $header_cell6; ?></td>
			</tr>
			<tr>
				<td><?php echo $header_cell7; ?></td>
				<td><?php echo $header_cell8; ?></td>
				<td><?php echo $header_cell9; ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e('Shipper', 'wpcargo-custom-field' ); ?></td>
				<td><?php echo $content_cell1; ?></td>
				<td><?php esc_html_e('Consignee', 'wpcargo-custom-field' ); ?></td>
				<td><?php echo $content_cell2; ?></td>
				<td><?php echo $content_cell3; ?></td>
			</tr>
			<tr>
				<td colspan="2"><?php echo $content_cell4; ?></td>
				<td colspan="2"><?php echo $content_cell5; ?></td>
				<td rowspan="2" style="vertical-align: baseline;"><?php echo $content_cell6; ?></td>
			</tr>
			<tr>
				<td colspan="2"><?php echo $content_cell7; ?></td>
				<td colspan="2"><?php echo $content_cell8; ?></td>
			</tr>
		</table>
		<?php
		if( $mp_settings ){ 
			//** Checked in multiple setting has value
			if( array_key_exists('wpc_mp_enable_admin', $mp_settings)){
				//** Check if the multiple package is Enable
				if( !empty( $packages ) ){			
					?>
					<p><strong><?php esc_html_e('Package Information', 'wpcargo-custom-field' ); ?></strong></p>
					<table id="shipment-packages" cellpadding="0" cellspacing="0" style="width: 100%;border: none;margin:0;padding:0;">
						<thead>
							<tr>
								<td class="package-description"><?php esc_html_e('Description', 'wpcargo-custom-field' ); ?></td>	
								<td><?php esc_html_e('Qty.', 'wpcargo-custom-field' ); ?></td>
								<td><?php esc_html_e('Piece Type', 'wpcargo-custom-field' ); ?></td>
								<?php if( array_key_exists('wpc_mp_enable_dimension_unit', $mp_settings)): ?>
									<td><?php esc_html_e('Length', 'wpcargo-custom-field' ); ?> (<?php echo $mp_settings['wpc_mp_dimension_unit']; ?>)</td>
									<td><?php esc_html_e('Width', 'wpcargo-custom-field' ); ?> (<?php echo $mp_settings['wpc_mp_dimension_unit']; ?>)</td>
									<td><?php esc_html_e('Height', 'wpcargo-custom-field' ); ?> (<?php echo $mp_settings['wpc_mp_dimension_unit']; ?>)</td>
								<?php endif; ?>
								<td><?php esc_html_e('Weight', 'wpcargo-custom-field' ); ?> (<?php echo $mp_settings['wpc_mp_weight_unit']; ?>)</td>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach ( $packages as $package ) {
								?>
								<tr>
									<td class="package-description"><?php echo $package['wpc-pm-description']; ?></td>
									<td><?php echo $package['wpc-pm-qty']; ?></td>
									<td><?php echo $package['wpc-pm-piece-type']; ?></td>
									<?php if( array_key_exists('wpc_mp_enable_dimension_unit', $mp_settings)): ?>
										<td><?php echo $package['wpc-pm-length']; ?></td>
										<td><?php echo $package['wpc-pm-width']; ?></td>
										<td><?php echo $package['wpc-pm-height']; ?></td>										
									<?php endif; ?>
									<td><?php echo $package['wpc-pm-weight']; ?></td>
								</tr>
								<?php
							}
							?>
						</tbody>
					</table>
					<?php
				}
			}
		}
		?>
	</div><!-- account copy -->
	<?php echo !empty( $packages ) && $key != 'shippers-copy' ? '<div style="page-break-before: always;"></div>' : '' ; ?>
<?php endforeach; ?>
<?php do_action('wpc_label_footer_information', $shipment_id ); ?>