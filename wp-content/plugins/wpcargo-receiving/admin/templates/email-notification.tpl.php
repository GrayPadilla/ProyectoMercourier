<?php
$options 			= get_option('wpcargo_mail_settings');
$options2 			= get_option('wpcargo_option_settings');
if (!empty($options['wpcargo_active_mail'])) {
	$current_status	= isset($_REQUEST['wpc_status']) ? $_REQUEST['wpc_status'] : '';
	$wpcargo_tn		= get_the_title($get_data_id);
	$shipper_email  = get_post_meta($get_data_id, 'wpcargo_shipper_email', true);
	$receiver_email = get_post_meta($get_data_id, 'wpcargo_receiver_email', true);
	$shipper_phone  = get_post_meta($get_data_id, 'wpcargo_shipper_phone', true);
	$receiver_phone = get_post_meta($get_data_id, 'wpcargo_receiver_phone', true);
	$admin_email    = get_option('admin_email');
	$shipper_name   = get_post_meta($get_data_id, 'wpcargo_shipper_name', true);
	$receiver_name  = get_post_meta($get_data_id, 'wpcargo_receiver_name', true);
	$site_name      = get_bloginfo('name');
	$site_url      	= get_bloginfo('url');
	$str_find       = array(
		'{wpcargo_tracking_number}',
		'{shipper_email}',
		'{receiver_email}',
		'{shipper_phone}',
		'{receiver_phone}',
		'{admin_email}',
		'{shipper_name}',
		'{receiver_name}',
		'{status}',
		'{site_name}',
		'{site_url}'
	);
	$str_find = apply_filters('wpc_email_notification_find_hook', $str_find );
	$str_replce     = array(
		$wpcargo_tn,
		$shipper_email,
		$receiver_email,
		$shipper_phone,
		$receiver_phone,
		$admin_email,
		$shipper_name,
		$receiver_name,
		$current_status,
		$site_name,
		$site_url
	);
	$str_replce = apply_filters( 'wpc_email_notification_replace_hook', $str_replce, $get_data_id );
	$get_default_logo = WPCARGO_RECEIVING_URL.'admin/assets/images/wpcargo-logo-email.png';
	$get_default_footer = WPCARGO_RECEIVING_URL.'admin/assets/images/wpc-email-footer.png';
	$get_general_logo = $options2['settings_shipment_ship_logo'];
	$get_the_logo = !empty($get_general_logo) ? $get_general_logo : $get_default_logo;
	$get_the_mail_content = !empty($options['wpcargo_mail_message']) ? $options['wpcargo_mail_message'] : '<p>Dear {shipper_name},</p>
	<p>We are pleased to inform you that your shipment has now cleared customs and is now {status}.</p>
	<br />
	<h4 style="font-size: 25px; color: #00a924;">Tracking Information</h4>
	<p>Tracking Number - {wpcargo_tracking_number}</p>
	<p>Latest International Scan: Customs status updated</p>
	<p>We hope this meets with your approval. Please do not hesitate to get in touch if we can be of any further assistance.</p>
	<br />
	<p>Yours sincerely</p>
	<p><a href="{site_url}">{site_name}</a></p>';
	$get_the_mail_footer = !empty($options['wpcargo_mail_footer']) ? $options['wpcargo_mail_footer'] : '<div class="wpc-contact-info" style="margin-top: 10px;">
		<p>Your Address Here...</p>
		<p>Email: <a href="mailto:{admin_email}">{admin_email}</a> - Web: <a href="{site_url}">{site_name}</a></p>
		<p>Phone: <a href="tel:">Your Phone Number Here</a>, <a href="tel:">Your Phone Number Here</a></p>
	</div>
	<div class="wpc-contact-bottom" style="margin-top: 20px; padding: 5px; border-top: 1px solid #000;">
		<p>This message is intended solely for the use of the individual or organisation to whom it is addressed. It may contain privileged or confidential information. If you have received this message in error, please notify the originator immediately. If you are not the intended recipient, you should not use, copy, alter or disclose the contents of this message. All information or opinions expressed in this message and/or any attachments are those of the author and are not necessarily those of {site_name} or its affiliates. {site_name} accepts no responsibility for loss or damage arising from its use, including damage from virus.</p>
	</div>';
	$headers 		= array();
	$headers[]      = 'From: ' . $options['wpcargo_mail_header'];
	if( $wpcargo->mail_cc ){
		$headers[]      = 'cc: '.str_replace($str_find, $str_replce, $wpcargo->mail_cc )."\r\n";
	}
	if( $wpcargo->mail_bcc ){
		$headers[]      = 'Bcc: '.str_replace($str_find, $str_replce, $wpcargo->mail_bcc )."\r\n";
	}
	$subject        = str_replace($str_find, $str_replce, $options['wpcargo_mail_subject']);
	$send_to        = str_replace($str_find, $str_replce, $options['wpcargo_mail_to']);
	$message        = str_replace($str_find, $str_replce,
	'<div class="wpc-email-notification-wrap" style="width: 100%; font-family: sans-serif;">
		<div class="wpc-email-notification" style="padding: 80px; background: #efefef;">
			<div class="wpc-email-template" style="background: #fff; width: 680px; margin: 0 auto;">
				<div class="wpc-email-notification-logo" style="padding: 50px 50px 0px 50px;">
					<img src="'.$get_the_logo.'" />
				</div>
				<div class="wpc-email-notification-content" style="padding: 20px 50px 20px 50px; font-size: 18px;">
					'.$get_the_mail_content.'
				</div>
				<div class="wpc-email-notification-footer" style="font-size: 10px; text-align: center; margin: 0 auto;">
					<div class="wpc-footer-devider">
					<img src="'.$get_default_footer.'" />
				</div>
					'.$get_the_mail_footer.'
				</div>
			</div>
		</div>
	</div>');
	if( $current_status != $old_status ) {
		wp_mail( $send_to, $subject, $message, $headers );
	}
}