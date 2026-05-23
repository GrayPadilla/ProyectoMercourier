<?php
    $current_user 	= wp_get_current_user();
	$user_roles 	= $current_user->roles;
?>
<?php if( in_array( 'wpcargo_client', (array)$user_roles ) && !can_wpcfe_client_assign_user() ): ?>
	<input type="hidden" name="registered_shipper" id="registered_shipper" value="<?php echo get_current_user_id(); ?>">
<?php else: ?>
	<div id="wpcfe-misc-assign-user" class="card mb-4">
		<section class="card-header">
			<?php echo apply_filters( 'wpcfe_registered_shipper_label', esc_html__('Assign shipment to','wwpcargo-shipment-container') ); ?>
		</section>
		<section class="card-body">
			<?php if( has_action( 'wpcfe_before_assign_form_content' ) ): ?>
				<?php do_action( 'wpcfe_before_assign_form_content', $container_id ); ?>
			<?php endif; ?>
			<?php do_action( 'wpcfe_assign_form_content', $container_id ); ?>
			<?php if( has_action( 'wpcfe_after_designation_dropdown' ) ): ?>
				<?php do_action( 'wpcfe_after_designation_dropdown', $container_id ); ?>
			<?php endif; ?>
		</section>
	</div>
<?php  endif; ?>	