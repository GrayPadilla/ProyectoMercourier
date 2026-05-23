<?php
	$wpcargo_employees 	= wpcfe_get_users('wpcargo_employee');
    $wpcargo_agents 	= wpcfe_get_users('cargo_agent');
    $wpcargo_clients 	= wpcfe_get_users('wpcargo_client');
?>

<div class="modal fade top" id="shipmentBulkUpdateModal" tabindex="-1" role="dialog" aria-labelledby="shipmentBulkUpdateLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<form id="shipmentBulkUpdate-form">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="shipmentBulkUpdateLabel"><?php esc_html_e('Bulk Shipment Update', 'wpcargo-frontend-manager'); ?></h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<div class="shipment-list-wrapper mb-4 pb-4 border-bottom">
						<h6 class="font-weight-bold"><?php esc_html_e('Shipment List', 'wpcargo-frontend-manager'); ?></h6>
						<ul class="shipment-list list-group d-flex flex-row flex-wrap"></ul>
					</div>
					<?php if( has_action( 'wpcfe_before_bulk_assign_form_content' ) ): ?>
						<?php do_action( 'wpcfe_before_bulk_assign_form_content', 0 ); ?>
					<?php endif; ?>
					<?php do_action( 'wpcfe_bulk_assign_form_content', 0 ); ?>
					<?php if( has_action( 'after_wpcfe_bulk_update_form_fields' ) ): ?>
						<?php do_action( 'after_wpcfe_bulk_update_form_fields', 0 ); ?>
					<?php endif; ?>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal"><?php esc_html_e('Close','wpcargo-frontend-manager'); ?></button>
					<button type="submit" class="btn btn-sm btn-primary"><?php esc_html_e('Update','wpcargo-frontend-manager'); ?></button>
				</div>
			</div>
		</form>
	</div>
</div>