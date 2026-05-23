<?php
	global $wpcargo;
	$shipment_id 		= $shipment_detail->ID;
	$shipper_fields 	= wpc_cf_get_fields('shipper_info');
	$receiver_fields 	= wpc_cf_get_fields('receiver_info');
?>
<div id="shipper-info" class="wpcargo-row">
	<?php if( empty( $options ) || !array_key_exists( 'wpc_cf_disable_shipper', $options ) ): ?>
    <div class="wpcargo-col-md-6 detail-section">
            <p id="shipper-header" class="header-title"><strong><?php echo apply_filters('result_shipper_address', __('Shipper Address', 'wpcargo-custom-field' )); ?></strong></p>
            <div class="shipper details">
			<?php
                if( !empty( $shipper_fields ) ){
                    foreach( $shipper_fields as $field){
						$field_key 		= get_post_meta($shipment_id, $field->field_key, TRUE);
						$field_label 	= stripslashes( $field->label );
						if( is_serialized($field_key) ){
							if( $field->field_type == 'url' ) {
								$url_key_unserialized = unserialize($field_key);
								if(is_array($url_key_unserialized)) {	
									$target_blank = !empty($url_key_unserialized[2]) ? 'target="_blank"' :'';
									echo '<p><span class="label">'.$field_label.' : </span>'.'<a href="'.$url_key_unserialized[1].'" '.$target_blank.' >'.$url_key_unserialized[0].'</a>'.'</p>';
								}
							} elseif( $field->field_type == 'address' ) {
								$address = wpccf_get_address( $shipment_id, $field->field_key);							
								echo '<p><span class="label">'.$field_label.' : </span>'.$address.'</p>';
							} else {
								$field_key = maybe_unserialize($field_key);
								$field_key = array_filter( array_map('trim', $field_key ) );
								$field_key = implode(", ", $field_key);
								echo '<p><span class="label">'.$field_label.' : </span>'.$field_key.'</p>';
							}
						}
						elseif( $field->field_type == 'file' ){
							echo '<div class="wpcargo-col-4">';
							echo '<p class="label">'.$field_label.'</p>';
							$explode_data = explode(",", $field_key);
							if(is_array($explode_data)){
								foreach(array_filter($explode_data) as $get_file){
									echo '<div class="file-wrap">';
										?><a href="<?php
										echo wp_get_attachment_url($get_file);
										?>"><?php
										echo wp_get_attachment_image($get_file, 'thumbnail', TRUE);
										?></a>
										<?php
										
									echo '</div>';
								}
							}
							echo '</div>';
						}
						elseif( $field->field_type == 'address' ) {
							$address = wpccf_get_address( $shipment_id, $field->field_key);							
							echo '<p><span class="label">'.$field_label.' : </span>'.$address.'</p>';
						}
						elseif( $field->field_type == 'url' ) {
							$field_key 		= maybe_unserialize( $field_key );
							$field_key 		= is_array( $field_key ) ? $field_key : array();
							$target_blank 	= count($field_key) == 3 ? 'target="_blank"' :'';
							$target_blank = count($field_key) == 3 ? 'target="_blank"' :'';
							if( !empty( $field_key ) ):
								echo '<p><span class="label">'.$field_label.' : </span>'.'<a href="'.$field_key[1].'" '.$target_blank.' >'.$field_key[0].'</a>'.'</p>';
							else:
								echo '<p><span class="label">'.$field_label.' : </span></p>';
							endif;
						}						
						else{
							echo '<p><span class="label">'.$field_label.' : </span>'.$field_key.'</p>';
						}
                    }
                }
            ?>
            </div>
    </div>
	<?php endif; ?>
    <?php if( empty( $options ) || !array_key_exists( 'wpc_cf_disable_receiver', $options ) ): ?>
    <div class="wpcargo-col-md-6 detail-section">
            <p id="receiver-header" class="header-title"><strong><?php echo apply_filters('result_receiver_address', __('Receiver Address', 'wpcargo-custom-field' )); ?></strong></p>
            <div class="receiver details">
			<?php
                if( !empty( $receiver_fields ) ){
                    foreach( $receiver_fields as $field){
						$field_key 		= get_post_meta($shipment_id, $field->field_key, TRUE);
						$field_label 	= stripslashes( $field->label );

						if( is_serialized($field_key) ){
							if( $field->field_type == 'url' ) {
								$url_key_unserialized = unserialize($field_key);
								if(is_array($url_key_unserialized)) {	
									$target_blank = !empty($url_key_unserialized[2]) ? 'target="_blank"' :'';
									echo '<p><span class="label">'.$field_label.' : </span>'.'<a href="'.$url_key_unserialized[1].'" '.$target_blank.' >'.$url_key_unserialized[0].'</a>'.'</p>';
								}
							} elseif( $field->field_type == 'address' ) {	
								$address = wpccf_get_address( $shipment_id, $field->field_key);							
								echo '<p><span class="label">'.$field_label.' : </span>'.$address.'</p>';
							} else {
								$field_key = maybe_unserialize($field_key);
								$field_key = array_filter( array_map('trim', $field_key ) );
								$field_key = implode(", ", $field_key);
								echo '<p><span class="label">'.$field_label.' : </span>'.$field_key.'</p>';						
							}
						}
						elseif( $field->field_type == 'file' ){
							echo '<div class="wpcargo-col-4">';
							echo '<p class="label">'.$field_label.'</p>';			
							$explode_data = explode(",", $field_key);
							if(is_array($explode_data)){
								foreach(array_filter($explode_data) as $get_file){
									echo '<div class="file-wrap">';
									
										?><a href="<?php
										
										echo wp_get_attachment_url($get_file);
										?>"><?php
										echo wp_get_attachment_image($get_file, 'thumbnail', TRUE);
										?></a>
										<?php
									echo '</div>';
								}					
							}
							echo '</div>';
						}
						elseif( $field->field_type == 'date' ){
							$wpc_date_format 		= $wpcargo->date_format;	
							echo '<p><span class="label">'.$field_label.' : </span>'.!empty($field_key) ? date_i18n($wpc_date_format, strtotime($field_key)) : ''.'</p>';
						}
						elseif( $field->field_type == 'address' ) {
							$address = wpccf_get_address( $shipment_id, $field->field_key);							
							echo '<p><span class="label">'.$field_label.' : </span>'.$address.'</p>';
						}
						elseif( $field->field_type == 'url' ) {
							$field_key 		= maybe_unserialize( $field_key );
							$field_key 		= is_array( $field_key ) ? $field_key : array();
							$target_blank 	= count($field_key) == 3 ? 'target="_blank"' :'';
							$target_blank = count($field_key) == 3 ? 'target="_blank"' :'';
							if( !empty( $field_key ) ):
								echo '<p><span class="label">'.$field_label.' : </span>'.'<a href="'.$field_key[1].'" '.$target_blank.' >'.$field_key[0].'</a>'.'</p>';
							else:
								echo '<p><span class="label">'.$field_label.' : </span></p>';
							endif;
						}
						else{
							echo '<p><span class="label">'.$field_label.' : </span>'.$field_key.'</p>';
						}
                    }
                }
            ?>
		</div>
    </div>
    <?php endif; ?>
    <div class="clear-line"></div>
</div>
