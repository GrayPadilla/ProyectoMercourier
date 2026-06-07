<div id="wpcargo-history-section" class="wpcargo-history-details print-section wpcargo-table-responsive table-responsive">
	<p class="header-title"><strong><?php echo apply_filters( 'wpc_shipment_history_header', esc_html__( 'Shipment History' , 'wpcargo') ); ?></strong></p>
	<?php do_action('before_wpcargo_shipment_history', $shipment->ID); ?>
	<table id="shipment-history" class="table wpcargo-table" style="width: 100%;">
        <thead>
			<tr>
			<?php foreach( wpcargo_history_fields() as $history_name => $history_fields ): ?>
				<th><?php echo $history_fields['label']; ?></th>
			<?php endforeach; ?>
            <th><?php esc_html_e('Motorizado Recojo', 'wpcargo'); ?></th>
			<th><?php esc_html_e('Motorizado Entrega', 'wpcargo'); ?></th>
			<?php do_action('wpcargo_shipment_history_header'); ?>
		</tr>
		</thead>
        <tbody>
        <?php            
            $shipment_history       = $wpcargo->history( $shipment->ID );
            $sort_shipment_history  = wpcargo_history_order( $shipment_history );
            
            // Obtener valores actuales de motorizado
            $motorizado_recojo_id = get_post_meta($shipment->ID, 'wpcargo_motorizo_recojo', true);
            $motorizado_entrega_id = get_post_meta($shipment->ID, 'wpcargo_motorizo_entrega', true);
            
            $nombre_recojo = '';
            $nombre_entrega = '';
            
            if (!empty($motorizado_recojo_id)) {
                $user_recojo = get_userdata($motorizado_recojo_id);
                $nombre_recojo = $user_recojo ? $user_recojo->display_name : 'Motorizado #' . $motorizado_recojo_id;
            }
            
            if (!empty($motorizado_entrega_id)) {
                $user_entrega = get_userdata($motorizado_entrega_id);
                $nombre_entrega = $user_entrega ? $user_entrega->display_name : 'Motorizado #' . $motorizado_entrega_id;
            }
            if(!empty($sort_shipment_history)){
                foreach($sort_shipment_history as $shipments){
                    ?>
                    <tr class="history-row">
						<?php foreach( wpcargo_history_fields() as $history_name => $history_fields ): ?>
							<?php
								$value = array_key_exists( $history_name, $shipments ) ? $shipments[$history_name] : '' ;
							?>
							<td class="history-data <?php echo wpcargo_to_slug($history_name); ?> <?php echo wpcargo_to_slug($value); ?>"><?php echo esc_html( $value ); ?></td>
						<?php endforeach; ?>
                        <td class="history-data motorizado-recojo"><?php echo esc_html($nombre_recojo); ?></td>
                        <td class="history-data motorizado-entrega"><?php echo esc_html($nombre_entrega); ?></td>
                        <?php do_action('wpcargo_shipment_history_data', $shipments ); ?>
                    </tr>
                    <?php
                }
            }
        ?>
        </tbody>
    </table>
    <?php do_action('after_wpcargo_shipment_history', $shipment->ID); ?>
</div>