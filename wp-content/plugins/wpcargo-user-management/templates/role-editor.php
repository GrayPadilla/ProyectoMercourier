<div id="wpcumanage-role-editor-wrapper">
    <h1 class="h4 mb-4"><?php _e('Role Editor', 'wpcargo-umanagement'); ?></h1>
    <section class="row">
        <?php if( !empty( wpcumanage_access_list() ) ) :  ?>
            <?php foreach( wpcumanage_access_list() as $key => $label ): ?>
                <div id="wpcumanage-access-<?php echo $key; ?>" class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title text-primary mb-2 pb-2 border-bottom"><?php echo $label; ?> <span data-role="<?php echo $key; ?>" class="fa fa-edit text-info float-right" data-toggle="modal" data-target="#wpcumanageAccessForm"></span></h5>
                            <p class="card-text text-muted">
                                Some quick example text to build on the card title and make up the bulk of the
                                card's content.
                            </p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</div>
<div class="modal fade top" id="wpcumanageAccessForm" tabindex="-1" role="dialog" aria-labelledby="wpcumanageAccessFormLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<form id="wpcumanageAccessForm-form">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="wpcumanageAccessFormLabel"><?php _e('Access Form', 'wpcargo-umanagement'); ?></h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
                    <label for="_roles" class="col-md-2 col-form-label"><?php _e( 'Assign Roles', 'wpcargo-umanagement' ) ?></label>
                    <div class="col-md-4">
                        <select name="_roles[]" multiple="multiple" class="form-control browser-default custom-select wpcumanage-select2 _roles" id="_roles" required>
                            <?php foreach ( wpcumanage_registered_roles() as $role ): ?>
                                <?php if( !array_key_exists( $role, $all_roles ) ) continue; ?>
                                <option value="<?php echo $role; ?>"><?php echo $all_roles[$role]['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal"><?php esc_html_e('Close','wpcargo-umanagement'); ?></button>
					<button type="submit" class="btn btn-sm btn-primary"><?php esc_html_e('Save','wpcargo-umanagement'); ?></button>
				</div>
			</div>
		</form>
	</div>
</div>