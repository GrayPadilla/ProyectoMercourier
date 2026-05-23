<div id="container-info">
	<h1><?php esc_html_e( 'Container Information', 'wpcargo-shipment-container' ); ?></h1>
    <table class="form-table">
    	<tbody>
			<?php foreach( wpc_container_info_fields() as $container_info_name => $container_info_fields ): ?>
				<tr>
					<th><?php echo $container_info_fields['label']; ?></th>
					<td>
						<?php if( $container_info_name == 'container_agent' && empty( $container_info_fields['options'] ) ): ?>
							<span class="description"><?php esc_html_e( 'No registered Agent.', 'wpcargo-shipment-container' ); ?> <a href="<?php echo admin_url('user-new.php'); ?>"><?php esc_html_e( 'Add Agent', 'wpcargo-shipment-container' ); ?></a></span>
						<?php else: ?>
							<?php echo wpcargo_field_generator( $container_info_fields, $container_info_name, get_post_meta( $post->ID, $container_info_name, true ) ); ?>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			<?php do_action('wpc_shipment_container_after_container_info_admin', $post->ID); ?>
        </tbody>
    </table>
</div><!-- #container-info -->
<div id="trip-info">
	<h1><?php esc_html_e( 'Trip Information', 'wpcargo-shipment-container' ); ?></h1>
    <table class="form-table">
    	<tbody>
			<?php foreach( wpc_trip_info_fields() as $trip_info_name => $trip_info_fields ): ?>
				<tr>
					<th><?php echo $trip_info_fields['label']; ?></th>
					<td>
						<?php if( $trip_info_name == 'driver' && empty( $trip_info_fields['options'] ) ): ?>
							<span class="description"><?php esc_html_e( 'No registered Driver.', 'wpcargo-shipment-container' ); ?> <a href="<?php echo admin_url('user-new.php'); ?>"><?php esc_html_e( 'Add Driver', 'wpcargo-shipment-container' ); ?></a></span>
						<?php else: ?>
							<?php echo wpcargo_field_generator( $trip_info_fields, $trip_info_name, get_post_meta( $post->ID, $trip_info_name, true ) ); ?>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			<?php do_action('wpc_shipment_container_after_trip_info_admin', $post->ID); ?>
        </tbody>
    </table>
</div><!-- #container-info -->
<div id="time-info">
	<h1><?php esc_html_e( 'Time Information', 'wpcargo-shipment-container' ); ?></h1>
    <table class="form-table">
    	<tbody>
			<?php foreach( wpc_time_info_fields() as $time_info_name => $time_info_fields ): ?>
				<?php

					$picker_class = '';
					if( $time_info_name == 'date' || $time_info_name == 'expected_date' ){
						$picker_class = 'wpcargo-datepicker';
					}elseif( $time_info_name == 'time' ){
						$picker_class = 'wpcargo-timepicker';
					}
				?>
				<tr>
					<th><?php echo $time_info_fields['label']; ?></th>
					<td>
						<?php if( $time_info_name == 'travel_mode' && empty( $time_info_fields['options'] ) ): ?>
							<span class="description"><?php esc_html_e( 'No Options for Travel mode is registered.', 'wpcargo-shipment-container' ); ?> <a href="<?php echo admin_url('admin.php?page=wpc-container-settings'); ?>"><?php esc_html_e( 'Add Travel Mode', 'wpcargo-shipment-container' ); ?></a></span>
						<?php else: ?>
							<?php echo wpcargo_field_generator( $time_info_fields, $time_info_name, get_post_meta( $post->ID, $time_info_name, true ), $picker_class.' status_'.$time_info_name  ); ?>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			<?php do_action('wpc_shipment_container_after_time_info_admin', $post->ID); ?>
        </tbody>
    </table>
</div><!-- #container-info -->
<?php do_action('wpc_shipment_additional_container_info_admin', $post->ID); ?>