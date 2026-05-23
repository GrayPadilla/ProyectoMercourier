<div id="history_info" class="col-md-12 mb-4">
	<div  class="card">
		<section class="card-header">
			<?php echo apply_filters( 'wpcfe_history_table_header_label', __('History Records', 'wpcargo-frontend-manager') ); ?>
		</section>
		<section class="card-body">
			<?php
				$shmap_active 	= get_option('shmap_active');
				if( $shmap_active ){
					?>
					<div id="shmap-wrapper" style="margin: 12px 0;">
						<div id="wpcargo-shmap" style="height: 320px;"></div>
					</div>
					<?php
				}
				$shipment_history = wpcargo_history_order( $shipments );
			?>
			<div id="shipment-history-list" class="table-responsive">
				<table id="shipment-history" class="wpc-shipment-history table table-hover table-sm" style="width:100%">
					<thead>
						<tr class="text-center">
							<?php foreach( wpcargo_history_fields() as $history_name => $history_fields ): ?>
								<th class="tbl-sh-<?php echo $history_name; ?>"><strong><?php _e($history_fields['label'], 'wpcargo-frontend-manager'); ?></strong></th>
							<?php endforeach; ?>
							<?php do_action('wpcargo_shipment_history_header'); ?>
							<?php if( $role_intersected ): ?>
								<th>&nbsp;</th>
							<?php endif;?>
						</tr>
					</thead>
					<tbody data-repeater-list="wpcargo_shipments_update">
						<?php
							if( !empty( $shipment_history ) ):
								foreach ( $shipment_history as $history ) :
									?>
									<tr data-repeater-item class="history-data">
										<?php foreach( wpcargo_history_fields() as $history_name => $history_value ): ?>
											<?php
												$value = !empty( $history[$history_name] ) ? $history[$history_name] : '';
												$class = 'form-control';
												if( $history_name == 'date' ){
													$class .= ' wpccf-datepicker';
												}elseif( $history_name == 'time' ){
													$class .= ' wpccf-timepicker';
												}
												if( $history_value['field'] == 'select' ){
													$class .= ' browser-default';
												}
												if( $history_name == 'updated-name' ){
													$class .= ' disabled';
												}
											?>
											<td class="tbl-sh-<?php echo $history_name; ?>">
												<?php if( $role_intersected ): ?>
													<?php echo wpcargo_field_generator( $history_value, $history_name, $value, $class ); ?>
												<?php else: ?>
													<?php echo $value; ?>
												<?php endif; ?>
											</td>
										<?php endforeach; ?>
										<?php do_action('wpcargo_shipment_history_data', $history ); ?>
										<?php if( $role_intersected ): ?>
											<td class="tbl-sh-action">
												<input data-repeater-delete type="button" class="wpc-delete btn btn-danger btn-rounded btn-sm" value="<?php esc_html_e('Delete', 'wpcargo-frontend-manager')?>"/>
											</td>
										<?php endif; ?>
									</tr>
									<?php
								endforeach;
							else :
								?>
								<tr data-repeater-item class="history-data">
									<td colspan="6"><?php _e('No Shipment History Found.', 'wpcargo-frontend-manager'); ?></td>
								</tr>
								<?php
							endif;
						?>
					</tbody>
				</table>
			</div>
		</section>
	</div>
</div>