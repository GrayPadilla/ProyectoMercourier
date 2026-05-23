<?php ob_start(); ?>
<div class="wpc-email-notification-content" style="padding: 2em 2em 1em 2em; font-size: 18px;">
	<?php do_action( 'wpcfe_before_registration_admin_email_content', $user_id ); ?>
	<p><?php echo esc_html__( 'A new user has registered!', 'wpcargo-frontend-manager' ); ?></p>
	<p><?php echo esc_html__( 'Name: '.$wpcargo->user_fullname( $user_id ), 'wpcargo-frontend-manager' ); ?></p>
	<?php do_action( 'wpcfe_after_registration_admin_email_content', $user_id ); ?>
</div>
<?php $admin_mail_content .= ob_get_clean();