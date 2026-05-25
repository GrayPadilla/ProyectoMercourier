<?php
if (!defined('ABSPATH')){
    exit; // Exit if accessed directly
}
add_action( 'wp_head', 'wpc_cf_remove_default_metadata' );
add_action( 'admin_head', 'wpc_cf_remove_default_metadata' );
add_action('wpcargo_before_metabox_section', 'wpc_cf_metabox');
//** Remove default WPCargo metabox
function wpc_cf_remove_default_metadata() {
	global $wpcargo_metabox;
    remove_action( 'wpcargo_shipper_meta_section', array( $wpcargo_metabox, 'wpc_shipper_meta_template' ), 10 );
	remove_action( 'wpcargo_receiver_meta_section', array( $wpcargo_metabox, 'wpc_receiver_meta_template' ), 10 );
	remove_action( 'wpcargo_shipment_meta_section', array( $wpcargo_metabox, 'wpc_shipment_meta_template' ), 10 );
	remove_filter( 'wpcargo_after_reciever_meta_section_sep', array( $wpcargo_metabox, 'wpc_after_reciever_meta_sep' ), 10 );
}
function wpc_cf_metabox(){
	global $post, $WPCCF_Fields ;
	?>
    <div id="wrap" classs="container-fluid">
        <div id="wpccf-metabox" class="row">
            <?php
			$counter = 1;
			$row_class = '';
			foreach ( wpccf_get_shipment_sections() as $section => $section_header ) {		
				if( empty( $section ) ){
					continue;
				}
				$column = 12;
				if( ( $section == 'shipper_info' || $section == 'receiver_info' ) && $counter <= 2 && count(wpccf_get_shipment_sections() ) > 1 ){
					$column = 6;
				}
				$column = apply_filters( 'wpcfe_shipment_form_column', $column, $section ); 

				?>
				<div id="<?php echo $section; ?>" class="col-md-<?php echo $column; ?> mb-4">
					<h3 class="section-title wpccf_section_header"><?php echo $section_header; ?></h3>
					<div class="row">
						<?php if( has_action( 'before_wpcfe_'.$section.'_form_fields' ) ): ?>
							<?php do_action( 'before_wpcfe_'.$section.'_form_fields', $post->ID ); ?>
						<?php endif; ?>
						<?php $section_fields = $WPCCF_Fields->get_custom_fields( $section ); ?>
						<?php $WPCCF_Fields->convert_to_form_fields( $section_fields, $post->ID ); ?>
						<?php if( has_action( 'after_wpcfe_'.$section.'_form_fields' ) ): ?>
							<?php do_action( 'after_wpcfe_'.$section.'_form_fields', $post->ID ); ?>
						<?php endif; ?>
					</div>
				</div>
				<?php
				$counter++;
			}
			?>
        </div>
    </div>
    <?php	
}
function wpc_cf_get_field_section(){
	global $wpdb;
	$table_name = $wpdb->prefix.'wpcargo_custom_fields';
	$section = $wpdb->get_results("SELECT `section` FROM `".$table_name."` GROUP BY `section`", OBJECT);
	return $section;
}
function wpc_cf_show_fields( $section ){
	global $wpdb, $post, $option, $wpcargo, $WPCCF_Fields;
	$fields 	= $WPCCF_Fields->get_custom_fields($section);
	?>
	<table class="wpcargo form-table">
		<?php
		foreach( $fields as $field ) :
			$trow_class = $field['field_type'] == 'file' ? 'image-tr-upload' : '' ;
			?>
			<tr id="trow-field-<?php echo $field['id'] ?>" class="<?php echo $trow_class; ?>">
				<th><label><?php echo stripslashes( $field['label'] ) ; ?></label></th>
			<?php
			if( $field['field_type'] == 'text' ){
				$value = maybe_unserialize( get_post_meta($post->ID, $field['field_key'], true) );
				$value = is_array( $value ) ? implode(", ", $value) : $value;
				?>
				<td>
					<input type="text" id="<?php echo $field['field_key']; ?>" class="<?php echo $field['field_key']; ?>" name="<?php echo $field['field_key']; ?>" value="<?php echo $value; ?>" size="25" <?php echo ( $field['required'] ) ? 'required' : '' ; ?> />
				<?php
					if( $field['description'] ){
						?><p class="wpc-cf-desc"><?php echo $field['description']; ?></p><?php
					}
				?>
				</td>
				<?php
			}elseif( $field['field_type'] == 'email' ){
				$value = maybe_unserialize( get_post_meta($post->ID, $field['field_key'], true) );
				$value = is_array( $value ) ? implode(", ", $value) : $value;
				?>
				<td>
					<input type="email" id="<?php echo $field['field_key']; ?>" class="<?php echo $field['field_key']; ?>" name="<?php echo $field['field_key']; ?>" value="<?php echo $value; ?>" <?php echo ( $field['required'] ) ? 'required' : '' ; ?>/>
					<?php
					if( $field['description'] ){
						?><p class="wpc-cf-desc"><?php echo $field['description']; ?></p><?php
					}
					?>
				</td>
				<?php
			}elseif( $field['field_type'] == 'url' ) {
				?>
					<td>
						<?php
						$get_url_key = get_post_meta($post->ID, $field['field_key'], true);
						$url_key_unserialized = unserialize($get_url_key);
						?>
						<input type="text" id="<?php echo $field['field_key']; ?>" class="<?php echo $field['field_key']; ?>" name="<?php echo $field['field_key'].'[]'; ?>" value="<?php echo is_array($url_key_unserialized) ? $url_key_unserialized[0] : ''; ?>" size="25" <?php echo ( $field['required'] ) ? 'required' : '' ; ?> placeholder="<?php esc_html_e('URL Label', 'wpcargo-custom-field' ); ?>" />
						<br />
						<input type="text" id="<?php echo $field['field_key']; ?>" class="<?php echo $field['field_key']; ?>" name="<?php echo $field['field_key'].'[]'; ?>" value="<?php echo is_array($url_key_unserialized) ? $url_key_unserialized[1] : ''; ?>" size="25" <?php echo ( $field['required'] ) ? 'required' : '' ; ?> placeholder="<?php esc_html_e('http://www.sample.com', 'wpcargo-custom-field' ); ?>"/>
						<br />
						<input type="checkbox" name="<?php echo $field['field_key'].'[]'; ?>" class="<?php echo $field['field_key']; ?>" <?php echo is_array($url_key_unserialized) && !empty($url_key_unserialized[2]) ? 'checked' : ''?> > - <?php esc_html_e('Open new window?', 'wpcargo-custom-field' ); ?>
						<?php
						if( $field['description'] ){
							?><p class="wpc-cf-desc"><?php echo $field['description']; ?></p><?php
						}
						?>
					</td>
				<?php
			}elseif( $field['field_type'] == 'textarea' ){
				$value = maybe_unserialize( get_post_meta($post->ID, $field['field_key'], true) );
				$value = is_array( $value ) ? implode(", ", $value) : $value;
				?>
				<td>
					<textarea id="<?php echo $field['field_key']; ?>" class="<?php echo $field['field_key']; ?>" name="<?php echo $field['field_key']; ?>" <?php echo ( $field['required'] ) ? 'required' : '' ; ?> ><?php echo $value; ?></textarea>
					<?php
					if( $field['description'] ){
						?><p class="wpc-cf-desc"><?php echo $field['description']; ?></p><?php
					}
					?>
				</td>
				<?php
			}elseif( $field['field_type'] == 'html' ){
				$value = maybe_unserialize( get_post_meta($post->ID, $field['field_key'], true) );
				$value = is_array( $value ) ? implode(", ", $value) : $value;
				?>
				<td>
					<?php 
					$content = $value;;
$custom_editor_id =  $field['field_key'];
$custom_editor_name =  $field['field_key'];
$args = array(
		'media_buttons' => false, // This setting removes the media button.
		'textarea_name' => $custom_editor_name, // Set custom name.
		'textarea_rows' => get_option('default_post_edit_rows', 10), //Determine the number of rows.
		'quicktags' => false, // Remove view as HTML button.
	);
wp_editor( $content, $custom_editor_id, $args );
				?>
<!--					<textarea id="<?php echo $field['field_key']; ?>" class="<?php echo $field['field_key']; ?>" name="<?php echo $field['field_key']; ?>" <?php echo ( $field['required'] ) ? 'required' : '' ; ?> ><?php echo $value; ?></textarea>-->
					<?php
					if( $field['description'] ){
						?><p class="wpc-cf-desc"><?php echo $field['description']; ?></p><?php
					}
					?>
				</td>
				<?php
			}elseif( $field['field_type'] == 'number' ){
				$value = maybe_unserialize( get_post_meta($post->ID, $field['field_key'], true) );
				$value = is_array( $value ) ? implode(", ", $value) : $value;
				?>
				<td>
					<input type="number" id="<?php echo $field['field_key']; ?>" class="<?php echo $field['field_key']; ?>" name="<?php echo $field['field_key']; ?>" value="<?php echo $value; ?>" <?php echo ( $field['required'] ) ? 'required' : '' ; ?> />
					<?php
					if( $field['description'] ){
						?><p class="wpc-cf-desc"><?php echo $field['description']; ?></p><?php
					}
					?>
				</td>
				<?php
			}elseif( $field['field_type'] == 'date' ){		
				$value = maybe_unserialize( get_post_meta($post->ID, $field['field_key'], true) );
				$value = is_array( $value ) ? implode(", ", $value) : $value;
				?>
				<td>
					<input class="wpcargo-datepicker" type="text" id="<?php echo $field['field_key']; ?>" class="<?php echo $field['field_key']; ?>" name="<?php echo $field['field_key']; ?>" value="<?php echo $value; ?>" <?php echo ( $field['required'] ) ? 'required' : '' ; ?> autocomplete="off" />
					<?php
					if( $field['description'] ){
						?><p class="wpc-cf-desc"><?php echo $field['description']; ?></p><?php
					}
					?>
				</td>
				<?php
			}elseif( $field['field_type'] == 'time' ){
				$value = maybe_unserialize( get_post_meta($post->ID, $field['field_key'], true) );
				$value = is_array( $value ) ? implode(", ", $value) : $value;
				?>
					<td>
						<input class="wpcargo-timepicker" type="text" id="<?php echo $field['field_key']; ?>" class="<?php echo $field['field_key']; ?>" name="<?php echo $field['field_key']; ?>" value="<?php echo $value; ?>" <?php echo ( $field['required'] ) ? 'required' : '' ; ?> autocomplete="off" />
						<?php
						if( $field['description'] ){
							?><p class="wpc-cf-desc"><?php echo $field['description']; ?></p><?php
						}
						?>
					</td>
				<?php
			}elseif( $field['field_type'] == 'datetime' ){
				$value = maybe_unserialize( get_post_meta($post->ID, $field['field_key'], true) );
				$value = is_array( $value ) ? implode(", ", $value) : $value;
				?>
					<td>
						<input class="wpcargo-datetimepicker" type="text" id="<?php echo $field['field_key']; ?>" class="<?php echo $field['field_key']; ?>" name="<?php echo $field['field_key']; ?>" value="<?php echo $value; ?>" <?php echo ( $field['required'] ) ? 'required' : '' ; ?> autocomplete="off" />
						<?php
						if( $field['description'] ){
							?><p class="wpc-cf-desc"><?php echo $field['description']; ?></p><?php
						}
						?>
					</td>
				<?php
			}elseif( $field['field_type'] == 'select' ){
				?>
				<td>
					<?php
					$field_data 	= maybe_unserialize( $field['field_data'] );
					$field_data 	= apply_filters( 'wpccf_field_options', $field_data, $field['field_key'] );
					if( !empty( $field_data ) ){
					?>
					<select id="<?php echo $field['field_key']; ?>" class="<?php echo $field['field_key']; ?>" name="<?php echo $field['field_key']; ?>" <?php echo ( $field['required'] ) ? 'required' : '' ; ?>>
						<option value="" ><?php _e('-- Select One --', 'wpcargo-custom-field' ); ?></option>
						<?php
						foreach( array_filter($field_data) as $data ){
							?><option value="<?php echo trim($data); ?>" <?php echo ( get_post_meta($post->ID, $field['field_key'], true) == trim($data) ) ? 'selected' : '' ; ?> ><?php echo $data;  ?></option><?php
						}
						?>
					</select>
					<?php
					}else{
						?>
						<span class="meta-box error"><strong><?php echo esc_html__('No Selection setup, Please add selection', 'wpcargo-custom-field' ).' <a href="'.admin_url().'admin.php?page=wpc-cf-manage-form-field&action=edit&id='.$field['id'].'">'.esc_html__('Here', 'wpcargo-custom-field').'</a>.'; ?></strong></span>
						<?php
					}
					?>
					<?php
						if( $field['description'] ){
							?><p class="wpc-cf-desc"><?php echo $field['description']; ?></p><?php
						}
					?>
				</td>
				<?php
			}elseif( $field['field_type'] == 'agent' ){
				$wpc_agent_args  	= array( 'role' => 'cargo_agent', 'orderby' => 'user_nicename', 'order' => 'ASC' );
				$wpc_agents 		= get_users($wpc_agent_args);
				?>
				<td>
					<?php
					if( !empty( $wpc_agents ) ){
					?>
						<select id="<?php echo $field['field_key']; ?>" class="<?php echo $field['field_key']; ?>" name="<?php echo $field['field_key']; ?>" <?php echo ( $field['required'] ) ? 'required' : '' ; ?>>
							<option value="" ><?php _e('-- Select One --', 'wpcargo-custom-field' ); ?></option>
							<?php
							foreach( $wpc_agents as $wpc_agent ){
								?><option value="<?php echo $wpc_agent->ID;  ?>" <?php selected( get_post_meta($post->ID, $field['field_key'], true), $wpc_agent->ID); ?>><?php echo $wpc_agent->display_name;  ?></option><?php
							}
							?>
						</select>
					<?php
					}else{
						?>
						<span class="meta-box error"><strong><?php echo esc_html__('No WPCargo agents, Please add Agents', 'wpcargo-custom-field' ).' <a href="'.admin_url().'/user-new.php">'.esc_html__('Here', 'wpcargo-custom-field' ).'</a> '.esc_html__('make sure the role assign is "WPCargo Agent".', 'wpcargo-custom-field' ); ?></strong></span>
						<?php
					}
					?>
					<?php
						if( $field['description'] ){
							?><p class="wpc-cf-desc"><?php echo $field['description']; ?></p><?php
						}
					?>
				</td>
				<?php
			}elseif( $field['field_type'] == 'radio' ){
				?>
				<td>
					<?php
					$field_data 	= maybe_unserialize( $field['field_data'] );
					$field_data 	= array_filter($field_data);
					$field_data 	= apply_filters( 'wpccf_field_options', $field_data, $field['field_key'] );
					if( !empty( $field_data ) ){
						foreach( $field_data as $data ){
							?><input class="<?php echo $field['field_key']; ?>" class="<?php echo $field['field_key']; ?>" type="radio" name="<?php echo $field['field_key']; ?>" value="<?php echo trim($data); ?>" <?php echo ( get_post_meta($post->ID, $field['field_key'], true) == trim($data) ) ? 'checked' : '' ; ?> <?php echo ( $field['required'] ) ? 'required' : '' ; ?> > <?php echo trim($data); ?><br/><?php
						}
					}else{
						?>
						<span class="meta-box error"><strong><?php esc_html_e('No Selection setup, Please add selection', 'wpcargo-custom-field' ).' <a href="'.admin_url().'admin.php?page=wpc-cf-manage-form-field&action=edit&id='.$field['id'].'">'.esc_html__('Here', 'wpcargo-custom-field').'</a>.'; ?></strong></span>
						<?php
					}
					?>
					<?php
						if( $field['description'] ){
							?><p class="wpc-cf-desc"><?php echo $field['description']; ?></p><?php
						}
					?>
				</td>
				<?php
			}elseif( $field['field_type'] == 'checkbox' ){
				$data_selection = maybe_unserialize( get_post_meta($post->ID, $field['field_key'], true) );
				if( is_array( $data_selection ) && !empty( $data_selection ) ){
					$data_selection = array_filter( $data_selection );
					$data_selection = $data_selection;
				}else{
					$data_selection = array();
				}
				?>
				<td>
					<?php
					$field_data = maybe_unserialize( $field['field_data'] );
					$field_data = array_filter($field_data);
					$field_data = apply_filters( 'wpccf_field_options', $field_data, $field['field_key'] );
					if( !empty( $field_data ) ){
						foreach( $field_data as $data ){
							?><input class="<?php echo $field['field_key']; ?>" class="<?php echo $field['field_key']; ?>" type="checkbox" name="<?php echo $field['field_key']; ?>[]" value="<?php echo trim($data); ?>" <?php echo ( in_array( trim($data), $data_selection ) ) ? 'checked' : '' ; ?> > <?php echo trim($data); ?><br/><?php
						}
					}else{
						?>
						<span class="meta-box error"><strong><?php echo esc_html__('No Selection setup, Please add selection', 'wpcargo-custom-field' ).' <a href="'.admin_url().'admin.php?page=wpc-cf-manage-form-field&action=edit&id='.$field['id'].'">'.esc_html__('Here', 'wpcargo-custom-field' ).'</a>'; ?></strong></span>
						<?php
					}
					?>
					<?php
						if( $field['description'] ){
							?><p class="wpc-cf-desc"><?php echo $field['description']; ?></p><?php
						}
					?>
				</td>
				<?php
			}elseif( $field['field_type'] == 'multiselect' ){
				$data_selection = maybe_unserialize( get_post_meta($post->ID, $field['field_key'], true) );
				if( is_array( $data_selection ) ){
					$data_selection = array_filter( $data_selection );
					if( !empty( $data_selection ) ){
						$data_selection = maybe_unserialize( get_post_meta($post->ID, $field['field_key'], true) );
					}
				}else{
					$data_selection = array();
				}
				?>
				<td>
					<?php
					$field_data =  maybe_unserialize( $field['field_data'] );
					$field_data = array_filter($field_data);
					$field_data = apply_filters( 'wpccf_field_options', $field_data, $field['field_key'] );
					if( !empty( $field_data ) ){
						?>
						<select id="<?php echo $field['field_key']; ?>" class="<?php echo $field['field_key']; ?>" name="<?php echo $field['field_key']; ?>[]" multiple="multiple" <?php echo ( $field['required'] ) ? 'required' : '' ; ?> >
							<?php
							foreach( array_filter($field_data) as $data ){
								?><option value="<?php echo trim($data); ?>" <?php echo ( in_array( trim($data), $data_selection ) ) ? 'selected' : '' ; ?> ><?php echo trim($data);  ?></option><?php
							}
							?>
						</select>
						<?php
					}else{
						?>
						<span class="meta-box error"><strong><?php echo esc_html__('No Selection setup, Please add selection', 'wpcargo-custom-field' ).' <a href="'.admin_url().'admin.php?page=wpc-cf-manage-form-field&action=edit&id='.$field['id'].'">'.esc_html__('Here', 'wpcargo-custom-field').'</a>.'; ?></strong></span>
						<?php
					}
					?>
					<?php
						if( $field['description'] ){
							?><p class="wpc-cf-desc"><?php echo $field['description']; ?></p><?php
						}
					?>
				</td>
				<?php
			}elseif( $field['field_type'] == 'address' ){
				$wpcargo_country = !empty( wpcargo_country_list() ) ? explode( ",", wpcargo_country_list() ) : array() ;
				$address 	 	 = wpccf_extract_address( $post->ID, $field['field_key'] );
				if( !empty( wpccf_address_fields_data() ) ){
					?>
					<td>
						<div id="address-form-<?php echo $field['id']; ?>" class="wpccf-address-group">
							<?php 
							$counter = 1;
							foreach ( wpccf_address_fields_data() as $_addr_meta => $_addr_label ) {
								if( $_addr_meta == 'country' ){
									?>
									<div class="country_address-section">
										<p><label for="<?php echo $field['field_key']; ?>[<?php echo $_addr_meta; ?>]"><?php echo $_addr_label; ?></label></p>
										<select class="form-control browser-default custom-select" id="<?php echo $field['field_key']; ?>[<?php echo $_addr_meta; ?>]" class="<?php echo $field['field_key']; ?>" name="<?php echo $field['field_key']; ?>[<?php echo $_addr_meta; ?>]" <?php echo ( $field['required'] ) ? 'required' : '' ; ?>>
											<option value =""><?php esc_html_e(' -- Select Country -- ', 'wpcargo-custom-field'); ?></option>
											<?php foreach ($wpcargo_country as $country) : ?>
												<option value="<?php echo $country ?>" <?php echo selected( $address[$_addr_meta], $country ); ?>><?php echo $country ?></option>
											<?php endforeach; ?>
										</select>
									</div>
									<?php
								}else{
									?>
									<div class="md-form form-group street_address-section">
										<p><label for="<?php echo $field['field_key']; ?>[<?php echo $_addr_meta; ?>]" ><?php echo $_addr_label; ?></label></p>
										<input id="<?php echo $field['field_key']; ?>[<?php echo $_addr_meta; ?>]" type="text" class="form-control <?php echo $_addr_meta.' '.$field['field_key']; ?>" name="<?php echo $field['field_key']; ?>[<?php echo $_addr_meta; ?>]" value="<?php echo $address[$_addr_meta]; ?>" <?php echo ( $field['required'] && $counter == 1 ) ? 'required' : '' ; ?> >
									</div>
									<?php
								}
								$counter ++;
							}
							?>
						</div>
						<?php
							if( $field['description'] ){
								?><p class="wpc-cf-desc"><?php echo $field['description']; ?></p><?php
							}
						?>
					</td>
					<?php
				}
			}elseif( $field['field_type'] == 'file' ) {		
				$field_data 	= maybe_unserialize($field['field_data']);
				$options    	= isset($field_data['options']) ? $field_data['options'] : '';
				$get_meta_value = get_post_meta($post->ID, $field['field_key'], true);
				?>
				<td>
					<div class="wpcargo-uploader">
						<div id="wpcargo-gallery-container_<?php echo $field['id'];?>">
							<ul class="wpccf_uploads">
								<?php
								if (!empty($get_meta_value) || $get_meta_value != NULL):
									$get_images_id = explode(',', $get_meta_value);
									foreach ($get_images_id as $image_id):
										if (!empty($image_id)) {
											?>
											<li class="image" data-attachment_id="<?php echo $image_id; ?>">
												<a href="<?php echo wp_get_attachment_url($image_id); ?>" target="_blank"><?php echo wp_get_attachment_image($image_id, 'thumbnail', TRUE); ?></a>
												<span class="actions"><a href="#" class="delete" title="<?php esc_html_e('Delete image', 'wpcargo-custom-field');  ?>">X</a></span>
											</li>
											<?php
										}
									endforeach;
								endif;
							?>
							</ul>
						</div>
						<input id="wpcargo_image_gallery_<?php echo $field['id'];?>" class="<?php echo $field['field_key']; ?>" type="hidden" name="<?php echo $field['field_key']; ?>" value="<?php echo $get_meta_value; ?>" />
						<a id="wpcargo_select_gallery_<?php echo $field['id'];?>" class="button" data-delete="Delete image" data-text="Delete" ><?php esc_html_e('Add Images / Upload Files', 'wpcargo-custom-field');  ?></a> 
					</div>
					<?php
						if( $field['description'] ){
							?><p class="wpc-cf-desc"><?php echo $field['description']; ?></p><?php
						}
					?>
				</td>
				<script>
					jQuery(document).ready(function($){
						// Uploading files
						var file_frame;
						var wp_media_post_id = wp.media.model.settings.post.id; // Store the old id
						var set_to_post_id = $("#wpcargo_post_id_<?php echo $field['id'];?>").val(); // Set this
						var $image_gallery_ids = $( '#wpcargo_image_gallery_<?php echo $field['id'];?>' );
						var $product_images    = $( '#wpcargo-gallery-container_<?php echo $field['id'];?>' ).find( 'ul.wpccf_uploads' );
						jQuery('#wpcargo_select_gallery_<?php echo $field['id'];?>').on('click', function( event ) {
							var $el = $( this );
							event.preventDefault();
							// If the media frame already exists, reopen it.
							if ( file_frame ) {
								// Set the post ID to what we want
								file_frame.uploader.uploader.param( 'post_id', set_to_post_id );
								// Open frame
								file_frame.open();
								return;
							} else {
								// Set the wp.media post id so the uploader grabs the ID we want when initialised
								wp.media.model.settings.post.id = set_to_post_id;
							}
								// Create the media frame.
								file_frame = wp.media.frames.file_frame = wp.media({
									title: jQuery( this ).data( 'uploader_title' ),
									button: {
										text: jQuery( this ).data( 'uploader_button_text' ),
									},
									multiple: true  // Set to true to allow multiple files to be selected
								});
								// When an image is selected, run a callback.
								file_frame.on( 'select', function() {
									var selection = file_frame.state().get( 'selection' );
									var attachment_ids = $image_gallery_ids.val();
									selection.map( function( attachment ) {
										attachment = attachment.toJSON();
										if ( attachment.id ) {
											attachment_ids   = attachment_ids ? attachment_ids + ',' + attachment.id : attachment.id;
											var attachment_image = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
											$product_images.append( '<li class="image" data-attachment_id="' + attachment.id + '"><img src="' + attachment_image + '" /><span class="actions"><a href="#" class="delete" title="' + $el.data('delete') + '">X</a></span></li>' );
										}
									});
									$image_gallery_ids.val( attachment_ids );
								});
								// Finally, open the modal
								file_frame.open();
							});
							// Restore the main ID when the add media button is pressed
							jQuery('a.add_media').on('click', function() {
							wp.media.model.settings.post.id = wp_media_post_id;
							});
							// Remove images
							$( '#wpcargo-gallery-container_<?php echo $field['id'];?>' ).on( 'click', 'a.delete', function() {
							$( this ).closest( 'li.image' ).remove();
							var attachment_ids = '';
							$( '#wpcargo-gallery-container_<?php echo $field['id'];?>' ).find( 'ul li.image' ).css( 'cursor', 'default' ).each( function() {
							var attachment_id = jQuery( this ).attr( 'data-attachment_id' );
							attachment_ids = attachment_ids + attachment_id + ',';
							});
							$image_gallery_ids.val( attachment_ids );
							// remove any lingering tooltips
							$( '#tiptip_holder' ).removeAttr( 'style' );
							$( '#tiptip_arrow' ).removeAttr( 'style' );
							return false;
						});
					});
				</script>
				<?php
			}
			
			if(class_exists('WPC_Signature')) {
				$get_field_type = $field['field_type'];
				$get_field_key = $field['field_key'];
			}else{
				$get_field_type ='';
				$get_field_key 	= '';
			}
			echo apply_filters( 'wpc_add_field_generation', $get_field_type, stripslashes( $field['label'] ), $get_field_key );
			?>
			</tr>
			<?php
		endforeach;
		?>
    </table>
    <?php
}
function wpc_cf_get_fields( $section ){
	global $wpdb;
	$table_name = $wpdb->prefix.'wpcargo_custom_fields';
	$fields = $wpdb->get_results("SELECT label, field_type, field_key FROM `".$table_name."` WHERE `section` LIKE '$section' AND display_flags LIKE '%result%' ORDER BY `weight`", OBJECT);
	return $fields;
}