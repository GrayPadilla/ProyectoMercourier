<?php ob_start(); ?>
<div class="wpc-email-notification-content" style="padding: 2em 2em 1em 2em; font-size: 18px;">
	<?php do_action( 'wpcfe_before_registration_email_content', $user_id ); ?>
		<p><?php esc_html_e( 'Dear', 'wpcargo-frontend-manager' ); ?> <?php echo $wpcargo->user_fullname( $user_id ); ?>,</p>
		<p><?php esc_html_e( 'You have successfully registered!', 'wpcargo-frontend-manager' ); ?></p>
	<?php do_action( 'wpcfe_after_registration_email_content', $user_id ); ?>
</div>
<?php $client_mail_content .= ob_get_clean();