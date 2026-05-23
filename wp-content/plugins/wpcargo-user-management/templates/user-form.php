<?php
$_user_id = 0;
$_user_groups = array();
if( isset( $_GET['umpage'] ) && $_GET['umpage'] == 'edit' ) {
	$_user_id     = (int)$user_data->ID;
	$_user_groups = wpcumanage_get_all_user_groups( $_user_id );
	$username	  = $user_data->first_name . ' ' . $user_data->last_name;
}
?>
<form id="umanageUserForm" method="post">
	<?php wp_nonce_field( 'wpcumanage_create_account_action', 'wpcumanage_create_account_field'); ?>
	<?php do_action('wpcumanage_user_form_begin'); ?>	
	<div class="row mb-4">
		<?php if( $is_update ): ?>
			<input type="hidden" name="_email" value="<?php echo $user_data->user_email; ?>">
			<div class="col-sm-12 mb-4">
				<h1 class="h4 py-2 border-bottom text-primary"><?php _e( 'Update User', 'wpcargo-umanagement' ); ?> <?php echo $username; ?></h1>
			</div>
		<?php endif; ?>
		<div class="col-sm-12">
			<h2 class="h6 py-2 border-bottom font-weight-bold"><?php echo apply_filters( 'wpcfe_reg_personal_info', __( 'Personal Information', 'wpcargo-umanagement' ) ); ?></h2>
		</div>
		<?php wpcumanage_generate_template( wpcfe_personal_info_fields(), $user_data, $is_update ); ?>
	</div>
	<div class="row mb-4">
		<div class="col-sm-12">
			<h2 class="h6 py-2 border-bottom font-weight-bold"><?php echo apply_filters( 'wpcfe_reg_billing_info', __( 'Billing Information', 'wpcargo-umanagement' ) ); ?></h2>
		</div>
		<?php wpcumanage_generate_template( wpcfe_billing_address_fields(), $user_data, $is_update ); ?>
	</div>
	<?php do_action('wpcumanage_user_form_middle', $user_data, $is_update ); ?>
	<div class="row mb-4">
		<div class="col-sm-12">
			<h2 class="h6 py-2 border-bottom font-weight-bold"><?php _e( 'User Role and Groups', 'wpcargo-umanagement' ) ?></h2>
		</div>
		<div class="col-sm-12 mb-3">
			<div class="row">
				<label for="_roles" class="col-md-2 col-form-label"><?php _e( 'Roles', 'wpcargo-umanagement' ) ?></label>
				<div class="col-md-10">
					<select name="_roles[]" multiple="multiple" class="form-control browser-default custom-select wpcumanage-select2 _roles" id="_roles" required>
						<?php foreach ( wpcumanage_registered_roles() as $role ): ?>
							<?php if( !array_key_exists( $role, $all_roles ) ) continue; ?>
							<option value="<?php echo $role; ?>" <?php echo in_array( $role, $user_data->roles ) ? 'selected' : '' ; ?>><?php echo $all_roles[$role]['name']; ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
		</div>
		<div class="col-sm-12">
			<div class="row">
				<label for="_roles" class="col-md-2 col-form-label"><?php _e( 'Groups', 'wpcargo-umanagement' ) ?></label>
				<div class="col-md-10">
					<select name="_groups[]" multiple="multiple" class="form-control browser-default custom-select _groups" id="_groups">
						<?php foreach( $user_group_ids as $group_id ):?>
							<option value="<?php echo $group_id;?>" <?php echo ( in_array( $group_id, $_user_groups ) ) ? 'selected' : ''; ?>><?php echo wpcumanage_get_user_group_label( $group_id ); ?></option>
						<?php endforeach;?>
					</select>
				</div>
			</div>
		</div>
	</div>
	<?php include_once( wpcumanage_locate_template( 'user-password' ) ); ?>
	<?php do_action('wpcumanage_user_form_end', $_user_id); ?>	
	<button id="reg-submit" class="btn btn-primary btn-md my-4" style="width: initial;" type="submit" name="reg-submit"><?php echo $btn_label; ?></button>
</form>