<?php
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}
	$current_user = wp_get_current_user();
	$get_wpc_date_format = 'Y-m-d';
	$get_wpc_time_format = get_option( 'time_format' );
	$wpcargo_city_name = '';
	if(is_user_logged_in()) {
		$user_id 				= get_current_user_id();
		$wpcargo_city_name 		= get_the_author_meta( 'wpcargo_city_name', $user_id );
	}
	
	$shipment = new stdClass;
?>
<div class="wpc-receiving">
	<h1><?php esc_html_e('Receiving', 'wpcargo-receiving');?></h1>
	<div class="wpc-receiving-wrap">
		<div class="wpc-receiving-wrap-inner">
			<h3><?php esc_html_e('Connect your scanner device', 'wpcargo-receiving');?></h3>
			<?php do_action('wpcargo_before_recieving_form');?>
			<div class="wpc-receiving-form">
				<form method="post" id="wpc-receiving">
					<table class="wpc-receiving-tbl">
						<tr>
							<td colspan="6">
								<input type="checkbox" id="clear-fields" name="clear-fields" style="width: initial !important; min-height: initial !important;" value="1"> <strong><?php esc_html_e('Clear all fields after scanned.', 'wpcargo-receiving' );?></strong>
							</td>
						</tr>
						<tr>
							<td>
								<input type="checkbox" id="add-not-found" name="add-not-found" value="1" style="width: initial !important; min-height: initial !important;"><strong> <?php esc_html_e('Add when shipment is not found.', 'wpcargo-receiving'); ?></strong>
							</td>
						</tr>
						<?php do_action( 'wpcr_before_admin_receiving_form_fields'  ); ?>
						<tr>
							<?php foreach( wpcargo_history_fields() as $history_name => $history_value ): ?>
								<?php
									$location_class = ( $history_name == 'location' ) ? 'status_location' : '';
									$value = '';
									$picker_class = '';
									if( $history_name == 'date' ){
										$picker_class = 'wpcargo-datepicker';
										$value = date( $wpcargo->date_format );
									}elseif( $history_name == 'time' ){
										$picker_class = 'wpcargo-timepicker';
										$value = $wpcargo->user_time( get_current_user_id() );
									}
								?>
								<td>
									<label for="wpc-receiving-<?php echo $history_name; ?>"><?php echo $history_value['label'];?></label><br />
									<?php if( $history_name != 'updated-name' ): ?>
										<?php echo wpcargo_field_generator( $history_value, $history_name, $value, 'wpc-receiving-'.$history_name.' '.$location_class.' '.$picker_class ); ?>
									<?php else: ?>
										<input readonly="readonly" class="form-control wpc-receiving-current-user-name" type="text" name="updated-name" value="<?php echo $current_user->user_firstname.' '.$current_user->user_lastname;?>" />
										<input class="wpc-receiving-current-user-id" type="hidden" name="updated-by" value="<?php echo get_current_user_id(); ?>"/>
									<?php endif; ?>
								</td>
							<?php endforeach; ?>
						</tr>
						<?php do_action( 'wpcr_after_admin_receiving_form_fields'  ); ?>
						<tr>
						<td colspan="6"><input type="text" placeholder="<?php esc_html_e('Scan your shipment barcode to update or enter the tracking number and press ENTER', 'wpcargo-receiving');?>" id="wpc-tracking-number" name="wpc-tracking-number"></td>
						</tr>
						<?php do_action( 'wpcr_after_admin_receiving_shipment_fields'  ); ?>
					</table>
				</form>
				<script>
					jQuery(document).ready(function ($) {
						$("#wpc-tracking-number").focus();
					});
				</script>
				<?php do_action('before_wpcargo_shipment_history', 0); ?>
			</div>
			<?php do_action('wpcargo_after_receiving_form'); ?>
		</div>
	</div>
</div>
<div class="wpc-receiver-notif"></div>
<div class="wpc-receivier-notes">
	<p><?php esc_html_e('Notes:', 'wpcargo-receiving');?></p>
	<ol>
		<li><?php esc_html_e('If you have connected your barcode scanner please scan directly to the barcode and it will automatic update the Shipment Status', 'wpcargo-receiving');?></li>
		<li><?php esc_html_e('If you don\'t have a barcode scanner please input it to the tracking number field and press <i>Enter</i> on your keyboard', 'wpcargo-receiving');?></li>
	</ol>
</div>