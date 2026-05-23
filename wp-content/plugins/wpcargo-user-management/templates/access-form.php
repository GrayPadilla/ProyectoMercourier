<div class="modal fade top" id="wpcumanageAccessModal" role="dialog" aria-labelledby="wpcumanageAccessModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<form id="wpcumanageAccessModal-form">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="wpcumanageAccessModalLabel"><?php _e('Access Form', 'wpcargo-umanagement'); ?></h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<?php do_action( 'wpcumanage_before_access_modal_form'); ?>
                    <label for="_access" class="col-md-2 col-form-label align-top font-weight-bold px-0 mb-4"><?php _e( 'Assign Access', 'wpcargo-umanagement' ) ?></label>
					<div id="_access-opt-wrapper" class="col-md-10 select2-list-full">
						<div class="form-check mb-2">
							<input type="checkbox" id="wpcumanageCheckboxAll" class="form-check-input" >
							<label class="form-check-label" for="wpcumanageCheckboxAll"><?php _e( 'Select All', 'wpcargo-umanagement' ) ?></label>
						</div>
						<select name="_access" multiple="multiple" class="form-control browser-default custom-select wpcumanage-select2 wpcumanage-select2-access _access" id="_access" required>
							<?php foreach ( wpcumanage_access_list() as $access_key => $access_label ): ?>
								<option value="<?php echo $access_key; ?>"><?php echo $access_label; ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<?php do_action( 'wpcumanage_after_access_modal_form'); ?>
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