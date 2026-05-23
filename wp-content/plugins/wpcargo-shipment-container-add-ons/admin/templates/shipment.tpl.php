<div id="assigned-shipment">
	<h1 class="shipment-top-title"><span><?php esc_html_e( 'Assigned Shipments', 'wpcargo-shipment-container' ); ?></span> <button id="add-shipment" class="button" data-id="<?php echo $post->ID; ?>" ><?php esc_html_e( 'Add Shipment', 'wpcargo-shipment-container' ); ?></button><span class="spinner"></span></h1>
    <div id="container-shipment-list-wrapper">
    	<?php do_action('wpc_admin_before_assigned_shipments'); ?>
    	<?php
		if( !empty( $shipments )){
			foreach( $shipments as $shipment_id ){
				$shipment_title = get_the_title($shipment_id);
				?>
				<div id="shipment-<?php echo $shipment_id; ?>" data-shipment="<?php echo $shipment_id; ?>" class="selected-shipment" >
					<span class="dashicons dashicons-dismiss" data-id="<?php echo $shipment_id; ?>"></span>
					<?php do_action( 'wpcsc_before_shipment_content_section', $shipment_id ); ?>
					<h3 class="shipment-title"><a style="text-decoration: none;" href="<?php echo admin_url('post.php?post='.$shipment_id.'&action=edit'); ?>" target="_target"><?php echo $shipment_title; ?></a></h3>
					<?php do_action( 'wpcsc_after_shipment_content_section', $shipment_id ); ?>
				</div>
				<?php
			}
		}
		?>
        <?php do_action('wpc_admin_after_assigned_shipments'); ?>
    </div>
	<input type="hidden" name="wpcc_sorted_shipments" id="wpcc_sorted_shipments" value="<?php echo wpc_shipment_container_sorted_shipment( $post->ID ); ?>" />
</div>
<div id="shipment-opt-wrapper" class="wpcargo-modal">
	<?php
		$shipper_display 	= get_option('container_shipper_display');
		$receiver_display 	= get_option('container_receiver_display');
		$shipper_label		= wpc_shipment_container_get_field_label( $shipper_display );
		$receiver_label 	= wpc_shipment_container_get_field_label( $receiver_display );
	?>
    <!-- Modal content -->
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title"><?php esc_html_e( 'WPCargo Shipment List', 'wpcargo-shipment-container' ); ?></h2><span class="close">&times;</span>
        </div>
        <div class="modal-body">
			<div id="shipment-options-wrapper">
                    <!-- Table -->
				<table id="shipment-options-table" class="table table-hover mb-0">
				<!-- Table head -->
					<thead>
						<tr>
							<?php foreach( wpcsc_datatable_info_callback() as $header ): ?>
								<th class="th-lg"><?php echo $header; ?></th>
							<?php endforeach; ?>
							<th class="th-lg text-center"><?php echo __( 'Actions', 'wpcargo-shipment-container' ); ?></th>
						</tr>
					</thead>
					<!-- Table head -->
					<!-- Table body -->
					<tbody>
					</tbody>
					<!-- Table body -->
				</table>
			</div>
        </div>
        <div class="modal-footer"></div>
    </div>
</div>