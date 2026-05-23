<div class="modal fade top" id="shipmentBulkContainerModal" tabindex="-1" role="dialog" aria-labelledby="shipmentBulkContainerLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<form id="shipmentBulkAssignContainer-form">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="shipmentBulkContainerLabel"><?php esc_html_e('Bulk Assign Container', 'wpcargo-shipment-container'); ?></h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<div class="shipment-list-wrapper mb-4 pb-4 border-bottom">
						<h6 class="font-weight-bold"><?php esc_html_e('Shipment List', 'wpcargo-shipment-container'); ?></h6>
						<ul class="shipment-list list-group d-flex flex-row flex-wrap"></ul>
					</div>
					<?php if( has_action( 'before_wpcsc_bulk_assign_container_form' ) ): ?>
						<?php do_action( 'before_wpcsc_bulk_assign_container_form' ); ?>
					<?php endif; ?>
					<div class="form-group">
						<div class="select-no-margin">
							<label><?php esc_html_e('Container List','wpcargo-shipment-container'); ?></label>
							<select name="assign_container" class="mdb-select mt-0 form-control browser-default" id="assign_container" >
								<option value=""><?php esc_html_e('-- Select Container --','wpcargo-shipment-container'); ?></option>
								<?php if( $containers ): ?>
									<?php foreach( $containers as $container ): ?>
										<option value="<?php echo $container->ID; ?>"><?php echo $container->post_title; ?></option>
									<?php endforeach; ?>
								<?php endif; ?>
							</select>
						</div>
					</div>
					<?php if( has_action( 'after_wpcsc_bulk_assign_container_form' ) ): ?>
						<?php do_action( 'after_wpcsc_bulk_assign_container_form' ); ?>
					<?php endif; ?>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal"><?php esc_html_e('Close','wpcargo-shipment-container'); ?></button>
					<button type="submit" class="btn btn-sm btn-primary"><?php esc_html_e('Assign','wpcargo-shipment-container'); ?></button>
				</div>
			</div>
		</form>
	</div>
</div>