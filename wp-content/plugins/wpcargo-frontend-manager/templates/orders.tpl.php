<?php $dashboard_url = get_the_permalink( wpcfe_admin_page() ); ?>
<table id="wpcshcon-all-shipment" class="table table-hover table-sm">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Order ID', 'wpcargo-frontend-manager' ); ?></th>
			<th><?php esc_html_e( 'Date', 'wpcargo-frontend-manager' ); ?></th>
			<th><?php esc_html_e( 'Shipment Number', 'wpcargo-frontend-manager' ); ?></th>
			<th><?php esc_html_e( 'Shipment Type', 'wpcargo-frontend-manager' ); ?></th>
			<th><?php esc_html_e( 'Order Status', 'wpcargo-frontend-manager' ); ?></th>
			<th><?php esc_html_e( 'Shipment Status', 'wpcargo-frontend-manager' ); ?></th>
			<th><?php esc_html_e( 'Amount', 'wpcargo-frontend-manager' ); ?></th>
			<th>&nbsp;</th>
		</tr>
	</thead>
	<?php if ( $shipment_orders->have_posts() ) : ?>
		<?php while ( $shipment_orders->have_posts() ) : $shipment_orders->the_post(); ?>
		<?php			
		$order 				= wc_get_order( get_the_ID() );
		$order_items 		= $order->get_items();
		$shipment_title 	= get_wpcfe_order_shipment_number( get_the_ID() );
		$shipment_id 		= wpcfe_shipment_id( $shipment_title );
		$shipment_type 		= wpcfe_get_shipment_type( $shipment_id  );
		$status 			= wpcfe_get_shipment_status( $shipment_id);
		?>
		<tr>
			<td><a href="<?php echo get_permalink( get_option('woocommerce_myaccount_page_id') ); ?>view-order/<?php echo get_the_ID(); ?>/" title="<?php esc_html_e('Orders Account','wpcargo-frontend-manager'); ?>">#<?php echo get_the_ID(); ?></a></td>
			<td><?php echo get_the_date( 'Y-m-d H:i:s'); ?></td>
			<td><?php echo $shipment_title; ?></td>
			<td><?php echo $shipment_type; ?></td>
			<td><?php echo $order->get_status(); ?></td>
			<td><?php echo $status; ?></td>
			<td><?php echo get_woocommerce_currency_symbol( $order->get_currency() ).$order->get_total(); ?></td>
			<td><a href="<?php echo $dashboard_url; ?>?wpcfe=track&num=<?php echo $shipment_title; ?>"><?php esc_html_e( 'View', 'wpcargo-frontend-manager' ); ?></a></td>
		</tr>
		<?php endwhile; ?>
	<?php endif; ?>
</table>
<div class="row">
	<section class="col-md-5">
		<p class="note note-primary">
			<?php _e('Mostrando', 'wpcargo-fm'); echo ' '.$record_start.' '; _e('de', 'wpcargo-fm'); echo ' '.$record_end.' '; _e('de', 'wpcargo-fm'); echo ' '.number_format($number_records).' '; _e('entradas', 'wpcargo-fm'); ?>
		</p>
	</section>
	<section class="col-md-7"><?php wpcfe_bootstrap_pagination( array( 'custom_query' => $shipment_orders ) ); ?></section>
</div>