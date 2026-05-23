<?php
	global $wpcargo;
	$shipment_id 		= $shipment_detail->ID;
	$shipper_fields = wpc_cf_get_fields('shipper_info');
	$receiver_fields = wpc_cf_get_fields('receiver_info');
?>
<div id="print-shipper-info" class="wpcargo-row print-section" style="overflow: hidden;">
	<?php if( empty( $options ) || !array_key_exists( 'wpc_cf_disable_shipper', $options ) ): ?>
    <div class="one-half first">
		<p id="print-shipper-header" class="header-title"><strong><?php echo apply_filters('result_shipper_address', __('Shipper Address', 'wpcargo-custom-field')); ?></strong></p>
		<p class="shipper details">
		<?php
			if( !empty( $shipper_fields ) ){
				foreach( $shipper_fields as $field){
					$field_key = get_post_meta($shipment_id, $field->field_key, TRUE);
					if( is_serialized($field_key) ){
						$field_key = maybe_unserialize($field_key);
						$field_key = array_filter($field_key);
						$field_key = implode(", ", $field_key);
						if( $field->field_type == 'url' ){
							$get_url_key = maybe_unserialize( get_post_meta($shipment_id, $field->field_key, TRUE) );
							if( $get_url_key ){
								$target = ( count( $get_url_key ) == 3 ) ? ' target="_blank" ' : '' ;
								$field_key 	 = '<a href="'.$get_url_key[1].'" '.$target.'>'.$get_url_key[1].'</a>';
							}
						}
						echo stripslashes( $field->label ).' : '.$field_key.'<br />';
					}else{	
						echo stripslashes( $field->label ).' : '.get_post_meta($shipment_id, $field->field_key, TRUE).'<br />';
					}
				}
			}
		?>
		</p>
    </div>
    <?php endif; ?>
    <?php if( empty( $options ) || !array_key_exists( 'wpc_cf_disable_receiver', $options ) ): ?>
    <div class="one-half">
		<p id="print-receiver-header" class="header-title"><strong><?php echo apply_filters('result_receiver_address', __('Receiver Address', 'wpcargo-custom-field')); ?></strong></p>
		<p class="receiver details">
		<?php
			if( !empty( $receiver_fields ) ){
				foreach( $receiver_fields as $field){
					$field_key = get_post_meta($shipment_id, $field->field_key, TRUE);
					if( is_serialized($field_key) ){
						$field_key = maybe_unserialize($field_key);
						$field_key = array_filter($field_key);
						$field_key = implode(", ", $field_key);
						if( $field->field_type == 'url' ){
							$get_url_key = maybe_unserialize( get_post_meta($shipment_id, $field->field_key, TRUE) );
							if( $get_url_key ){
								$target = ( count( $get_url_key ) == 3 ) ? ' target="_blank" ' : '' ;
								$field_key 	 = '<a href="'.$get_url_key[1].'" '.$target.'>'.$get_url_key[1].'</a>';
							}
						}
						echo stripslashes( $field->label ).' : '.$field_key.'<br />';
					}else{
						echo stripslashes( $field->label ).' : '.get_post_meta($shipment_id, $field->field_key, TRUE).'<br />';
					}
				}
			}
		?>
		</p>
    </div>
    <?php endif; ?>
</div>
