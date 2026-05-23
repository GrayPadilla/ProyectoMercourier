<?php $wpcfe_print_options = wpcfe_print_options(); ?>
<?php do_action('wpcfe_before_shipment_table_wrapper'); ?>
<div id="shipment-filters" class="filters-card mb-4">
	<div class="filters-body row wpcfe-filter">
		<?php require_once( wpcfe_include_template( 'filter-shipment' ) ); ?>
	</div>
</div>
<div class="shipments-wrapper mb-4" style="visibility: visible; animation-name: fadeIn;">
    <div class="shipments-body">
		<div id="shipments-table-list" class="content">
			<?php if ( $wpc_shipments->have_posts() ) : ?>
			<div class="table-top form-group">
				<div class="float-md-none float-lg-right">
					<form action="<?php echo $page_url; ?>" method="get">
						<select id="wpcfesort" name="wpcfesort" class="form-control browser-default">
							<option ><?php echo __('Show entries', 'wpcargo-frontend-manager' ); ?></option>
							<?php foreach( $wpcfesort_list as $list ): ?>
							<option value="<?php echo $list ?>" <?php echo $list == $wpcfesort ? 'selected' : '' ;?>><?php echo $list ?> <?php echo __('entries', 'wpcargo-frontend-manager' ); ?></option>
							<?php endforeach; ?>
						</select>
					</form>
				</div>
				<?php if( !empty( $wpcfe_print_options ) ): ?>
				<div class="wpcfe-bulkprint-wrapper dropdown" style="display:inline-block !important;">
				<!--Trigger-->
					<button class="btn btn-default btn-lg dropdown-toggle m-0 py-1 px-2" type="button" data-toggle="dropdown"
						aria-haspopup="true" aria-expanded="false"><i class="fa fa-print"></i><span class="mx-2"><?php esc_html_e('Print', 'wpcargo-frontend-manager'); ?></span></button>
					<!--Menu-->
					<div class="dropdown-menu dropdown-primary">
						<?php foreach( $wpcfe_print_options as $print_key => $print_label ): ?>
							<a class="wpcfe-bulk-print dropdown-item print-<?php echo $print_key; ?> py-1" data-type="<?php echo $print_key; ?>" href="#"><?php echo $print_label; ?></a>
						<?php endforeach; ?>
					</div>
				</div>
				<?php endif; ?>
				<?php if( can_wpcfe_delete_shipment() ): ?>
					<button class="remove-shipments btn btn-danger btn-sm"><i class="fa fa-trash text-white"></i> <?php _e('Delete', 'wpcargo-frontend-manager'); ?></button>
				<?php endif; ?>
				<?php do_action( 'wpcfe_before_after_shipment_table' ); ?>
			</div>
			<?php do_action('wpcfe_after_shipment_table_actions'); ?>
			<div class="card m-0 mb-2">
				<div class="card-body table-responsive">
					<?php do_action( 'wpcfe_before_shipment_table' ); ?>
					<table id="shipment-list" class="table table-hover table-sm">
						<thead>
							<tr>
								<th class="form-check">
								<input class="form-check-input" id="wpcfe-select-all" type="checkbox"/>
								<label class="form-check-label" for="wpcfe-select-all"></label>
								</th>
								<?php do_action( 'wpcfe_shipment_before_tracking_number_header' ); ?>
								<?php do_action( 'wpcfe_shipment_after_tracking_number_header' ); ?>
								<?php do_action( 'wpcfe_shipment_table_header' ); ?>
								<?php do_action( 'wpcfe_shipment_table_header_action' ); ?>
							</tr>
						</thead>
						<tbody>
							<?php	
							do_action( 'wpcfe_before_shipment_table_row', $wpc_shipments, $args ); 				
							while ( $wpc_shipments->have_posts() ) {
								$wpc_shipments->the_post();
								$status  		= get_post_meta( get_the_ID(), 'wpcargo_status', true );
								?>
								<tr id="shipment-<?php echo get_the_ID(); ?>" class="shipment-row <?php echo wpcfe_to_slug( $status ); ?>">
									<td class="form-check">
								  <input class="wpcfe-shipments form-check-input" id="shipment-checkbox-<?php echo get_the_ID(); ?>" type="checkbox" name="wpcfe-shipments[]" value="<?php echo get_the_ID(); ?>" data-number="<?php echo get_the_title(); ?>">
								  <label class="form-check-label" for="shipment-checkbox-<?php echo get_the_ID(); ?>"></label>
									</td>
									<?php do_action( 'wpcfe_shipment_before_tracking_number_data', get_the_ID() ); ?>
									<?php do_action( 'wpcfe_shipment_after_tracking_number_data', get_the_ID() ); ?>
									<?php do_action( 'wpcfe_shipment_table_data', get_the_ID() ); ?>
									<?php do_action( 'wpcfe_shipment_table_data_action', get_the_ID() ); ?>				
								</tr>
								<?php
							} // end while
							do_action( 'wpcfe_after_shipment_table_row', $wpc_shipments, $args );
							?>
						</tbody>
					</table>
				</div>
			</div>
			<?php if( !empty( $wpcfe_print_options ) ): ?>
				<div class="wpcfe-bulkprint-wrapper dropdown" style="display:inline-block !important;">
				<!--Trigger-->
					<button class="btn btn-default btn-lg dropdown-toggle m-0 py-1 px-2" type="button" data-toggle="dropdown"
						aria-haspopup="true" aria-expanded="false"><i class="fa fa-print"></i><span class="mx-2"><?php esc_html_e('Print', 'wpcargo-frontend-manager'); ?></span></button>
					<!--Menu-->
					<div class="dropdown-menu dropdown-primary">
						<?php foreach( $wpcfe_print_options as $print_key => $print_label ): ?>
							<a class="wpcfe-bulk-print dropdown-item print-<?php echo $print_key; ?> py-1" data-type="<?php echo $print_key; ?>" href="#"><?php echo $print_label; ?></a>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>
			<?php if( can_wpcfe_delete_shipment() ): ?>
				<button class="remove-shipments btn btn-danger btn-sm"><i class="fa fa-trash text-white"></i> <?php esc_html_e('Delete', 'wpcargo-frontend-manager'); ?></button>
			<?php endif; ?>
			<?php do_action( 'wpcfe_before_after_shipment_table' ); ?>
			<div class="row my-2">
				<section class="col-md-5">
					<p class="note note-primary">
						<?php _e('Mostrando', 'wpcargo-fm'); echo ' '.$record_start.' '; _e('de', 'wpcargo-fm'); echo ' '.$record_end.' '; _e('de', 'wpcargo-fm'); echo ' '.number_format($number_records).' '; _e('entradas', 'wpcargo-fm'); ?>
					</p>
				</section>
				<section class="col-md-7"><?php wpcfe_bootstrap_pagination( array( 'custom_query' => $wpc_shipments ) ); ?></section>
			</div>
			<?php else: ?>
				<i class="fa fa-inbox d-block p-2 text-center text-danger" style="font-size: 4rem;"></i>
				<h3 class="text-center text-danger"><?php _e('No shipment found.', 'wpcargo-frontend-manager'); ?></h3>
				<?php if( array_key_exists( 's', $args ) && !empty( $args['s'] ) ): ?>
					<p class="text-center text-danger"><?php printf( __('Searched:  "%s"', 'wpcargo-frontend-manager'), $args['s'] ); ?></p>
				<?php else: ?>
					<?php $shipment_date_range_notification = sprintf( __('%s to %s', 'wpcargo-frontend-manager'), wpcfe_formatted_date( $date_start ), wpcfe_formatted_date( $date_end ) ); ?>
					<p class="text-center text-danger"><?php echo apply_filters( 'shipment_date_range_notification', $shipment_date_range_notification, $date_start, $date_end  ); ?></p>
				<?php endif; ?>
			<?php endif; ?>			
		</div>
	</div>
</div>
<?php do_action('wpcfe_after_shipment_data'); ?>