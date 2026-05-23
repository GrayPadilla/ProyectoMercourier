<?php
foreach( $sections as $section_key => $section_label ){
	$fields = wpc_cf_get_fields($section_key);
	if( empty( $fields  ) ){
		continue;
	}
	?>
	<div id="<?php echo $section_key; ?>" class="wpcargo-row detail-section">
		<div class="wpcargo-col-md-12">
			<p id="<?php echo $section_key; ?>-header" class="header-title"><strong><?php echo $section_label; ?></strong></p>
		</div>
		<?php
			foreach( $fields as $field){
				$field_key 		= get_post_meta($shipment_id, $field->field_key, TRUE);
				$field_label 	= stripslashes( $field->label );
				if( is_serialized($field_key) ){
					if( $field->field_type == 'url' ) {
						$url_key_unserialized = unserialize($field_key);
						if(is_array($url_key_unserialized)) {
							$target_blank = !empty($url_key_unserialized[2]) ? 'target="_blank"' :'';
							?>
							<div class="wpcargo-col-md-4">
								<p class="wpcargo-label"><strong><?php echo $field_label; ?>:</strong></p>
								<p class="wpcargo-label-info">
									<a href="<?php echo $url_key_unserialized[1]; ?>" <?php echo $target_blank; ?>><?php echo $url_key_unserialized[0]; ?></a>
								</p>
							</div>
							<?php
						}
					}elseif( $field->field_type == 'address' ) {
						$address = wpccf_get_address( $shipment_id, $field->field_key);	
						?>			
						<div class="wpcargo-col-md-4">			
							<p class="wpcargo-label"><strong><?php echo $field_label; ?>:</strong></p>				
							<p class="wpcargo-label-info"><?php echo $address; ?></p>			
						</div>			
						<?php		
					}else{
						$field_key 	= maybe_unserialize($field_key);
						$field_key = array_filter( array_map('trim', $field_key ) );
						$field_key = implode(", ", $field_key);
						?>			
						<div class="wpcargo-col-md-4">			
							<p class="wpcargo-label"><strong><?php echo $field_label; ?>:</strong></p>	
							<p class="wpcargo-label-info"><?php echo $field_key; ?></p>	
						</div>			
						<?php
					}
				}elseif( $field->field_type == 'file' ) {
					$explode_data = explode(",", $field_key);
					?>
					<div class="wpcargo-col-md-4">
						<p class="wpcargo-label"><strong><?php echo $field_label; ?>:</strong></p>
						<?php if(is_array($explode_data)): ?>
							<?php foreach(array_filter($explode_data) as $get_file): ?>
								<div class="file-wrap">
									<a href="<?php echo wp_get_attachment_url($get_file); ?>"><?php echo wp_get_attachment_image($get_file, 'thumbnail', TRUE); ?></a>
								</div>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
					<?php
				}elseif( $field->field_type == 'signature' ) {
				?>
					<div class="wpcargo-col-md-4">
						<p class="wpcargo-label"><strong><?php echo $field_label; ?>:</strong></p>
						<div class="signature-wrap">
							<img src="<?php echo wp_get_attachment_url( get_post_meta($shipment_id, $field->field_key.'-attachement-id', TRUE) ); ?>">
						</div>
					</div>
					<?php
				}elseif( $field->field_type == 'date' ) {
					$field_key 				= get_post_meta($shipment_id, $field->field_key, TRUE);
					$wpc_date_format 		= get_option( 'date_format' );	
					?>			
					<div class="wpcargo-col-md-4">			
						<p class="wpcargo-label"><strong><?php echo $field_label; ?>:</strong></p>			
						<p class="wpcargo-label-info"><?php echo $field_key; ?></p>			
					</div>			
					<?php		
				}elseif( $field->field_type == 'agent' ) {
					$agentID = get_post_meta($shipment_id, $field->field_key, TRUE);
					if( $agentID && is_numeric( $agentID ) ){
					$field_key = wpccf_user_displayname( $agentID );
					}else{
						$field_key = $agentID;
					}
					?>			
					<div class="wpcargo-col-md-4">			
						<p class="wpcargo-label"><strong><?php echo $field_label; ?>:</strong></p>				
						<p class="wpcargo-label-info"><?php echo $field_key; ?></p>			
					</div>			
					<?php		
				}elseif( $field->field_type == 'address' ) {
					$address = wpccf_get_address( $shipment_id, $field->field_key);	
					?>			
					<div class="wpcargo-col-md-4">			
						<p class="wpcargo-label"><strong><?php echo $field_label; ?>:</strong></p>				
						<p class="wpcargo-label-info"><?php echo $address; ?></p>			
					</div>			
					<?php		
				}elseif( $field->field_type == 'url' ) {

					$field_key 		= maybe_unserialize( $field_key );
					$field_key 		= is_array( $field_key ) ? $field_key : array();
					$target_blank 	= count($field_key) == 3 ? 'target="_blank"' :'';

					?>
					<div class="wpcargo-col-md-4">
						<p class="wpcargo-label"><strong><?php echo $field_label; ?>:</strong></p>
						<?php if( !empty( $field_key ) ): ?>
						<p class="wpcargo-label-info">
							<a href="<?php echo $field_key[1]; ?>" <?php echo $target_blank; ?>><?php echo $field_key[0]; ?></a>
						</p>
						<?php endif; ?>
					</div>
					<?php		
				}else{
					$field_key = get_post_meta($shipment_id, $field->field_key, TRUE);
					$value = maybe_unserialize( $field_key  );
					$value = is_array( $value ) ? implode(", ", $value) : $value ;
					?>			
					<div class="wpcargo-col-md-4">	
						<p class="wpcargo-label"><strong><?php echo $field_label; ?>:</strong></p>
						<p class="wpcargo-label-info"><?php echo $value; ?></p>			
					</div>			
					<?php
				}
			}
		?>
	</div>
	<?php
}
