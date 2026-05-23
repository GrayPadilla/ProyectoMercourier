<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}
?>
<div id="wpc_pod" style="margin: 50px 0; ">
	<form class="pod-form" style="float: right;"> 
		<input type="text" name="podsch" placeholder="Shipper Number">
		<button type="submit" class="wpcargo-btn wpcargo-btn-success"><?php echo apply_filters('wpcargo_pod_search_shipment', __('Search', 'wpcargo-pod' ) ); ?></button>
	</form>
	<a href="<?php echo $page_url; ?>" class="wpcargo-btn wpcargo-btn-primary"><?php echo apply_filters('wpcargo_pod_pending_tab', __('Pending', 'wpcargo-pod' ) ); ?></a>	
	<?php if( wpcargo_pod_get_delivered_status() ): ?>
		<a href="<?php echo $page_url; ?>?podq=<?php echo  wpcargo_pod_get_delivered_status(); ?>" class="wpcargo-btn wpcargo-btn-primary"> <?php echo  wpcargo_pod_get_delivered_status(); ?></a> 
	<?php endif; ?>
	<?php if( wpcargo_pod_get_cancelled_status() ): ?>
	<a href="<?php echo $page_url; ?>?podq=<?php echo wpcargo_pod_get_cancelled_status(); ?>" class="wpcargo-btn wpcargo-btn-primary"><?php echo wpcargo_pod_get_cancelled_status(); ?></a>
	<?php endif; ?>
</div>
<table class="table wpcargo-table wpcargo-mb-4">
	<thead>
		<tr>
			<th><?php echo apply_filters('wpcargo_pod_tracking_number_label', __('Tracking Number', 'wpcargo-pod' ) ); ?></th>
			<?php
			if( !empty( $fields ) ){
				foreach($fields as $field) {
					?><th><?php echo $field->label; ?></th><?php
				}
			}	
			?>
			<th><?php echo apply_filters('wpcargo_pod_action_label', __('Action', 'wpcargo-pod' ) ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php
		// The Loop
		if ( $shipment_query->have_posts() ) {
			while ( $shipment_query->have_posts() ) {
		      $shipment_query->the_post();
		      $wpcargo_status = get_post_meta( get_the_ID(), 'wpcargo_status', true);
			  ?>
				<tr>
					<td><?php echo get_the_title(); ?></td>
					<?php
					if( !empty( $fields ) ){
						foreach($fields as $field) {
							$shipment_meta = wpcargo_get_postmeta( get_the_ID(), $field->field_key );
							?><td><?php echo $shipment_meta; ?></td><?php
						}
					}	
					?>
					<td>
						<?php
							if( $wpcargo_status == $pod_driver_signed ){
								echo $pod_driver_signed;
							}else{
								?><a class="wpcargo-btn wpcargo-btn-sm wpcargo-btn-success" href="<?php echo $page_url; ?>?sid=<?php echo get_the_ID(); ?>"><?php esc_html_e( 'Sign', 'wpcargo-pod' ); ?></a><?php 
							}
						?>
					</td>
				</tr>
			  <?php
			} 
		}else{
			?><tr><td colspan="<?php echo $num_fields + 2; ?>" class="text-center"><?php echo __('No shipment assigned found.', 'wpcargo-pod' ); ?></td></tr><?php
		}
		?>
	</tbody>
</table>
<?php echo wpcargo_pagination( array( 'custom_query' => $shipment_query ) ); ?>	
<?php wp_reset_postdata(); ?>