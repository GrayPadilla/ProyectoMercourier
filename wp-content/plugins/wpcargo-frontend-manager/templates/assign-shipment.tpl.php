<?php
	global $wpcargo;
	$user_roles 		= wpcfe_current_user_role();
?>
<?php if( in_array( 'wpcargo_client', (array)$user_roles ) && !can_wpcfe_client_assign_user() ): ?>
	<input type="hidden" name="registered_shipper" id="registered_shipper" value="<?php echo get_current_user_id(); ?>">
<?php else: ?>
	<div id="wpcfe-misc-assign-user" class="card mb-4">
		<section class="card-header">
			<?php echo apply_filters( 'wpcfe_registered_shipper_label', esc_html__('Assign shipment to','wpcargo-frontend-manager') ); ?>
		</section>
		<section class="card-body">
			<?php if( has_action( 'wpcfe_before_assign_form_content' ) ): ?>
				<?php do_action( 'wpcfe_before_assign_form_content', $shipment->ID ); ?>
			<?php endif; ?>
			<?php do_action( 'wpcfe_assign_form_content', $shipment->ID ); ?>
			<?php if( has_action( 'wpcfe_after_designation_dropdown' ) ): ?>
				<?php do_action( 'wpcfe_after_designation_dropdown', $shipment->ID ); ?>
			<?php endif; ?>
		</section>
	</div>
<?php  endif; ?>	