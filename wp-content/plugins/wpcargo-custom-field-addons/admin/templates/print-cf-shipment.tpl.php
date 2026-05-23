<?php
	global $wpcargo;
	$shipment_id 	= $shipment_detail->ID;
	$sections 		= wpccf_additional_sections();
?>
<?php foreach( $sections as $section_key => $section_label ): ?>
	<?php
		$section_fields = wpc_cf_get_fields( $section_key );
		$section_counter = 1;
	?>
	<?php if( !empty( $section_fields ) ): ?>
		<div class="additional_sections print-section" id="print-shipment-info">
			<p class="additional_sections-label" class="header-title"><strong><?php echo $section_label; ?></strong></p>
			<div class="section-content">
				<?php foreach( $section_fields as $field): ?>
					<?php
						if( $field->field_type == 'url' ){
							$get_url_key = maybe_unserialize( get_post_meta($shipment_id, $field->field_key, TRUE) );
							if( $get_url_key ){
								$target = ( count( $get_url_key ) == 3 ) ? ' target="_blank" ' : '' ;
								$field_key 	 = '<a href="'.$get_url_key[1].'" '.$target.'>'.$get_url_key[1].'</a>';
							}		
						}elseif( $field->field_type == 'agent' ){
							$agentID = get_post_meta($shipment_id, $field->field_key, TRUE);
							if( $agentID && is_numeric( $agentID ) ){
								$field_key = wpccf_user_displayname( $agentID );
							}else{
								$field_key = $agentID;
							}
						}else{
							$field_key = get_post_meta($shipment_id, $field->field_key, TRUE);
							if( is_serialized($field_key) ){
								$field_key = maybe_unserialize($field_key);
								$field_key = array_filter($field_key);
								$field_key = implode(", ", $field_key);
							}
						}	
					?>
					<div class="col-4">
						<p class="label"><?php echo stripslashes( $field->label ); ?></p>
						<p class="label-info"><?php echo $field_key; ?></p>
					</div>
					<?php if( !( $section_counter % 3 ) ): ?>
						<div style="display:block;clear:both;"></div>
					<?php endif; ?>
					<?php $section_counter++; ?>
				<?php endforeach; ?>
			</div>
			<div style="display:block;clear:both;"></div>
		</div>
	<?php endif; ?>
<?php endforeach; ?>