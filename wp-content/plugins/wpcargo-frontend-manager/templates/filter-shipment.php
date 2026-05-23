<?php
global $wpcargo;
$shipper_data   = wpcfe_table_header('shipper');
$receiver_data  = wpcfe_table_header('receiver');
$s_shipper     	= isset( $_GET['shipper'] ) ? sanitize_text_field( $_GET['shipper'] ) : '' ;
$s_receiver     = isset( $_GET['receiver'] ) ? sanitize_text_field( $_GET['receiver'] ) : '' ;
$s_shipment     = isset( $_GET['wpcfes'] ) ? sanitize_text_field( $_GET['wpcfes'] ) : '' ;
$s_status 		= isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '' ;

?>
<div class="col-lg-9 col-md-8 mt-0">
	<form id="wpcfe-filters" action="<?php echo $page_url; ?>" class="form-inline" style="width: 100%">
		<div class="row">
			<?php do_action( 'wpcfe_before_shipment_filters' ); ?>
			<?php if( !empty( $wpcargo->status ) ): ?>
				<div class="form-group wpcfe-filter status-filter p-0 mx-1">
					<label class="sr-only" for="status"><?php esc_html_e('Status', 'wpcargo-frontend-manager' ); ?></label>
					<select id="status" name="status" class="form-control md-form wpcfe-select">
						<option value=""><?php echo esc_html__('All Status', 'wpcargo-frontend-manager' ); ?></option>
						<?php 
							foreach ( $wpcargo->status as $status ) {
								?><option value="<?php echo $status; ?>" <?php selected( $s_status, $status, true ); ?>><?php echo $status; ?></option><?php
							}
						?>
					</select>
				</div>
			<?php endif; ?>
			<div class="form-group wpcfe-filter shipper-filter p-0 mx-1">
				<label class="sr-only" for="shipper"><?php echo sanitize_text_field( $shipper_data['label'] ); ?></label>
				<select id="shipper" name="shipper" class="form-control md-form wpcfe-select-ajax" data-filter="shipper">
					<?php if(!$s_shipper): ?>
						<option value=""><?php echo esc_html__('All', 'wpcargo-frontend-manager' ).' '.sanitize_text_field( $shipper_data['label'] ); ?></option>
					<?php else: ?>
						<option value="<?php echo $s_shipper; ?>"><?php echo $s_shipper; ?></option>
					<?php endif; ?>
				</select>
			</div>
			<div class="form-group wpcfe-filter receiver-filter p-0 mx-1">
				<label class="sr-only" for="receiver"><?php echo sanitize_text_field( $receiver_data['label'] ); ?></label>
				<select id="receiver" name="receiver" class="form-control md-form wpcfe-select-ajax" data-filter="receiver">
					<?php if( !$s_receiver ): ?>
						<option value=""><?php echo esc_html__('All', 'wpcargo-frontend-manager' ).' '.sanitize_text_field( $receiver_data['label'] ); ?></option>
					<?php else: ?>
						<option value="<?php echo $s_receiver; ?>"><?php echo $s_receiver; ?></option>
					<?php endif; ?>
				</select>
			</div>
			<?php do_action( 'wpcfe_after_shipment_filters' ); ?>
			<div class="form-group submit-filter p-0 mx-1">
				<button id="wpcfe-submit-filter" type="submit" class="btn btn-primary btn-fill btn-sm"><?php esc_html_e('Filter', 'wpcargo-frontend-manager' ); ?></button>
				<?php if(isset( $s_status )): ?>
					<a href="<?php echo esc_url( $page_url ); ?>" class="btn btn-secondary btn-fill btn-sm"><?php esc_html_e('Reset', 'wpcargo-frontend-manager' ); ?></a>
				<?php endif; ?>
			</div>
		</div>
	</form>
</div>
<div class="col-lg-3 col-md-4 mt-0 p-0">
	<form id="wpcfe-search" class="float-md-none float-lg-right" action="<?php echo esc_url( $page_url ); ?>" method="get">
		<div class="form-sm">
			<label for="search-shipment" class="sr-only"><?php echo apply_filters('wpcfe_shipment_number_label', __('Shipment Number', 'wpcargo-frontend-manager' ) ); ?></label>
			<input type="text" class="form-control form-control-sm" name="wpcfes" id="search-shipment" placeholder="<?php echo apply_filters('wpcfe_shipment_number_label', __('Shipment Number', 'wpcargo-frontend-manager' ) ); ?>" value="<?php echo $s_shipment; ?>">
			<button type="submit" class="btn btn-primary btn-sm mx-md-0 ml-2"><?php esc_html_e('Search', 'wpcargo-frontend-manager' ); ?></button>
		</div>
	</form>
</div>