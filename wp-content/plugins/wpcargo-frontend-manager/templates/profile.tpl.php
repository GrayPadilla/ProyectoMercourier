<?php
    $current_user = wp_get_current_user();
?>
<div id="wpcfe-profile-page">
	<?php do_action( 'wpcfe_before_profile_header', get_current_user_id() ); ?>
	<div id="profile-header" class="text-center">
		<div id="wpcfe-avatar-wrapper">
			<div id="user-avatar">
				<a href="#" id="wpcfe-change-avatar"><i class="fa fa-camera text-primary"></i></a>
				<div class="photo-container">
					<?php wpcfe_user_avatar(); ?>
				</div>
			</div>
			<div id="upload-avatar-wrapper" style="display:none;">
				<a href="#" id="close-upload-avatar"><i class="fa fa-close text-danger"></i></a>
				<div id="upload-avatar" ></div>
				<div id="croppie-actions">
					<input type="file" id="upload" class="btn actionUpload btn-primary btn-sm" value="<?php esc_html_e('Upload Avatar', 'wpcargo-frontend-manager' ); ?>" accept="image/*" />
					<a class="button actionSave btn btn-success btn-sm"><?php esc_html_e('Save Avatar', 'wpcargo-frontend-manager' ); ?></a>
				</div>
			</div>
		</div>
	</div>
	<?php do_action( 'wpcfe_after_profile_header', get_current_user_id() ); ?>
	<form class="wpcfe-profile-form" method="post">
		<?php wp_nonce_field( 'wpcfe_save_profile_action', 'wpcfe_save_profile_field' ); ?>
		<?php do_action( 'wpcfe_before_personal_information', get_current_user_id() ); ?>
		<div class="profile-section personal-profile">
			<h4><?php esc_html_e( 'Personal Information', 'wpcargo-frontend-manager' ); ?></h4>
			<div class="row">
				<?php foreach( wpcfe_personal_info_fields() as $user_fields ): ?>
					<div class="form-group col-md-6">
						<?php
							$value = get_user_meta( get_current_user_id(), $user_fields['field_key'], true );
							if( $user_fields['field_key'] == 'email' ){
								$value =  $current_user->user_email;
							}
							$select = $user_fields['field_type'] == 'select' ? 'browser-default' : '';
						?>
						<label for="<?php echo $user_fields['field_key']; ?>"><?php echo $user_fields['label']; ?></label>
                        <?php if( $user_fields['field_key'] == 'billing_email' ): ?>
                            <input class="form-control" value="<?php echo $current_user->user_email; ?>" disabled>
                        <?php else: ?>
						    <?php echo wpcargo_field_generator( $user_fields, $user_fields['field_key'], $value, 'form-control '.$select );?>
                        <?php endif; ?>
					</div>
				<?php endforeach; ?>
				<?php do_action( 'wpcfe_after_personal_details', get_current_user_id() ); ?>
			</div>
		</div>
		<?php do_action( 'wpcfe_after_personal_information', get_current_user_id() ); ?>
		<?php do_action( 'wpcfe_before_billing_information', get_current_user_id() ); ?>
		<div class="profile-section billing-information">
			<h4><?php esc_html_e( 'Billing Information', 'wpcargo-frontend-manager' ); ?></h4>
			<div class="row">
				<?php foreach( wpcfe_billing_address_fields() as $billing_fields ): ?>
					<div class="form-group col-md-6">
						<?php
							$value = get_user_meta( get_current_user_id(), $billing_fields['field_key'], true );
							$select = $billing_fields['field_type'] == 'select' ? 'browser-default' : '';
						?>
						<label for="<?php echo $billing_fields['field_key']; ?>"><?php echo $billing_fields['label']; ?></label>
						<?php
						if( $billing_fields['field_key'] === 'billing_email'){
							?><input class="form-control" value="<?php echo $current_user->user_email; ?>" readonly><?php
						}elseif( $billing_fields['field_key'] == 'billing_country' || $billing_fields['field_key'] == 'billing_state' ){
							if( $billing_fields['field_key'] == 'billing_state' && !class_exists( 'WooCommerce' ) ){
								echo wpcargo_field_generator( $billing_fields, $billing_fields['field_key'], $value, 'form-control '.$select );
								continue;
							}
							?>
							<select id="<?php echo $billing_fields['field_key']; ?>" class="form-control browser-default" name="<?php echo $billing_fields['field_key']; ?>" <?php echo $billing_fields['required'] ? 'required' : ''; ?> >
								<option value=""><?php esc_html_e('-- Select Type --','wpcargo'); ?></option>
								<?php if( !empty( $billing_fields['options'] ) ){ ?>
									<?php foreach ( $billing_fields['options'] as $_key => $_value) { ?>
										<option value="<?php echo trim($_key); ?>" <?php selected( $value, trim($_key) ); ?>><?php echo trim($_value); ?></option>
									<?php } ?>
								<?php } ?>
								</select>
							<?php
						}else{
							echo wpcargo_field_generator( $billing_fields, $billing_fields['field_key'], $value, 'form-control '.$select );
						}
						?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php do_action( 'wpcfe_after_billing_information', get_current_user_id() ); ?>
		<div class="submit-wrapper">
			<input type="submit" class="btn btn-info" value="<?php esc_html_e( 'Update', 'wpcargo-frontend-manager' ); ?>">
		</div>
	</form>
	<?php do_action( 'wpcfe_before_security_profile', get_current_user_id() ); ?>
	<div class="security-profile">
		<h4><?php esc_html_e( 'Change Password', 'wpcargo-frontend-manager' ); ?></h4>
		<form class="wpcfe-password-form" method ="post">
			<?php wp_nonce_field( 'wpcfe_change_password_action', 'wpcfe_change_password_field' ); ?>
			<div class="row">
				<div class="form-group col-md-6">
					<label for="wpcfe-account-password"><?php esc_html_e( 'New Password', 'wpcargo-frontend-manager' ); ?></label>
					<input type="password" id="wpcfe-account-password" name="account-password" class="account-password form-control" autocomplete="off">
					<span toggle="#wpcfe-account-password" class="fa fa-fw fa-eye field-icon toggle-password"></span>
				</div>
				<div class="form-group col-md-6">
					<label for="wpcfe-confirm-password"><?php esc_html_e( 'Confirm Password', 'wpcargo-frontend-manager' ); ?></label>
					<input type="password" id="wpcfe-confirm-password" name="confirm-password" class="confirm-password form-control" autocomplete="off">
					<span toggle="#wpcfe-confirm-password" class="fa fa-fw fa-eye field-icon toggle-password"></span>
				</div>
			</div>
			<div class="submit-wrapper">
				<input type="submit" class="btn btn-info" value="<?php esc_html_e( 'Change Password', 'wpcargo-frontend-manager' ); ?>" disabled>
			</div>
		</form>
	</div>
	<?php do_action( 'wpcfe_after_security_profile', get_current_user_id() ); ?>
</div>