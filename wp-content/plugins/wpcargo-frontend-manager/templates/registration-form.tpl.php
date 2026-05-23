<?php do_action( 'wpcfe_before_registration_form' ); ?>
<!-- Form -->
<form id="wpcfeRegistrationForm" method="post">
	<?php wp_nonce_field( 'wpcfe_create_account_action', 'wpcfe_create_account_field' ); ?>
	<div class="row">
		<div class="col-sm-12">
			<h2 class="h5 py-2 border-bottom"><?php echo apply_filters( 'wpcfe_reg_personal_info', __( 'Personal Information', 'wpcargo-frontend-manager' ) ); ?></h5>
		</div>
		<?php foreach( wpcfe_personal_info_fields() as $user_fields ): ?>
			<div class="form-group col-md-6 <?php echo $user_fields['field_key']; ?>-form-group">
				<?php $select = $user_fields['field_type'] == 'select' ? 'browser-default' : ''; ?>
				<label for="<?php echo $user_fields['field_key']; ?>"><?php echo $user_fields['label']; ?></label>
				<?php echo wpcargo_field_generator( $user_fields, $user_fields['field_key'], '', 'form-control '.$select );?>
			</div>
		<?php endforeach; ?>
	</div>
	<div class="row">
		<div class="col-sm-12">
			<h2 class="h5 py-2 border-bottom"><?php echo apply_filters( 'wpcfe_reg_billing_info', __( 'Billing Information', 'wpcargo-frontend-manager' ) ); ?></h5>
		</div>
		<?php foreach( wpcfe_billing_address_fields() as $billing_fields ): ?>
			<div class="form-group col-md-6 <?php echo $billing_fields['field_key']; ?>-form-group">
				<?php $select = $billing_fields['field_type'] == 'select' ? 'browser-default' : ''; ?>
				<label for="<?php echo $billing_fields['field_key']; ?>"><?php echo $billing_fields['label']; ?></label>
				<?php echo wpcargo_field_generator( $billing_fields, $billing_fields['field_key'], '', 'form-control '.$select );?>
			</div>
		<?php endforeach; ?>
	</div>
	<?php do_action('wpcfe_registration_address'); ?>
	<!-- Password -->
	<div class="row">
		<div class="form-group col-md-6">
			<label class="form-check-label" for="reg_pass"><?php echo apply_filters( 'wpcfe_reg_password', __( 'Password', 'wpcargo-frontend-manager' ) ); ?></label>
			<input id="reg_pass" class="form-control" type="password" size="20" value="" name="pwd" required="required">
			<span toggle="#reg_pass" class="fa fa-fw fa-eye field-icon toggle-password"></span>
		</div>
		<div class="form-group col-md-6">
			<label class="form-check-label" for="confirm_pass"><?php echo apply_filters( 'wpcfe_reg_cpassword', __( 'Confirm Password', 'wpcargo-frontend-manager' ) ); ?></label>
			<input id="confirm_pass" class="form-control" type="password" size="20" value="" required="required">
			<span toggle="#confirm_pass" class="fa fa-fw fa-eye field-icon toggle-password"></span>
		</div>
	</div>
	<div class="registration-message"></div>
	<button id="reg-submit" class="btn btn-outline-primary btn-rounded btn-block my-4 waves-effect z-depth-0" type="submit" name="reg-submit"><?php echo apply_filters( 'wpcfe_reg_register', __( 'Register', 'wpcargo-frontend-manager' ) ); ?></button>
</form>
<!-- Form -->
<?php do_action( 'wpcfe_after_registration_form' ); ?>