<div class="modal fade top" id="wpcumanageAssingmentModal" role="dialog" aria-labelledby="wpcumanageAssingmentModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<form id="wpcumanageAssingmentModal-form">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="wpcumanageAssingmentModalLabel"><?php _e('Assign Default Form', 'wpcargo-umanagement'); ?></h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<?php do_action( 'wpcumanage_before_assign_default_modal_form'); ?>
                    <?php
                        foreach ( wpcumanage_assignment_fields() as $meta_key => $field_data ) {
                            wpcumanage_field_generator( $meta_key, $field_data );
                        }
                    ?>
					<?php do_action( 'wpcumanage_after_assign_default_modal_form'); ?>
				</div>
				<div class="modal-footer">
                    <input type="hidden" id="_userid" name="_userid">
					<button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal"><?php esc_html_e('Close','wpcargo-umanagement'); ?></button>
					<button type="submit" class="btn btn-sm btn-primary"><?php esc_html_e('Assign','wpcargo-umanagement'); ?></button>
				</div>
			</div>
		</form>
	</div>
</div>