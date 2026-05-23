<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}
class WPCFE_Field_Generator{
	function get_custom_fields( $flag = '' ){
		global $wpdb;
		$table_prefix = $wpdb->prefix;
		//** Flag value Parameter
		//* @shipper_info
		//* @receiver_info
		//* @shipment_info
		$result_fields = $wpdb->get_results( 'SELECT * FROM `'.$table_prefix.'wpcargo_custom_fields` WHERE `section` LIKE "%'.$flag.'%" ORDER BY ABS(weight)', ARRAY_A );
		$fields = array();
		$user_role = array( 'useraccess_not_logged_in' );
		if( is_user_logged_in() ){
			$current_user = wp_get_current_user();
			$user_role = $current_user->roles;
		}
		$counter = 0;
		foreach ($result_fields as $value) {
			$flags 				= maybe_unserialize( $value['display_flags'] ) ? maybe_unserialize( $value['display_flags'] ) : array() ;
			$role_intersected 	= array_intersect($flags, $user_role);
			if( !empty( $role_intersected ) && count( $role_intersected ) <= count( $user_role )  ){
				continue;
			}
			$fields[$counter] = $value;
			$counter++;
		}
		return $fields;
	}
	function get_field_key( $key = '' ){
		global $wpdb;
		$table_prefix = $wpdb->prefix;
		$result = '';
		if( !empty($key) || $key != '' ){
			$result= $wpdb->get_results( 'SELECT * FROM `'.$table_prefix.'wpcargo_custom_fields` WHERE `section` LIKE "%'.$key.'%"', ARRAY_A );
		}
		return $result;
	}
	function get_field_key_list(  ){
		global $wpdb;
		$table_prefix = $wpdb->prefix;
		$field_keys = $wpdb->get_col( 'SELECT `field_key` FROM `'.$table_prefix.'wpcargo_custom_fields`' );
		return $field_keys;
	}
	function get_invoice_id( $shipment_id ){
		global $wpdb;
		$orderID = $wpdb->get_var( "SELECT tbl1.ID FROM `$wpdb->posts` AS tbl1 INNER JOIN `$wpdb->postmeta` AS tbl2 WHERE tbl1.ID = tbl2.post_id AND tbl1.post_type LIKE 'pq_order' AND tbl2.meta_key LIKE 'shipment_id' AND tbl2.meta_value = ".$shipment_id );
		return $orderID;
	}
	function convert_to_form_fields( $fields = array(), $post_id = '' ){
		global $wpcargo;
		ob_start();
		foreach( $fields as $field):
			$value = '';
			if( !empty( $post_id ) ){
				$value = maybe_unserialize( get_post_meta( $post_id, $field['field_key'], TRUE ) );
			}
			$required = ( $field['required'] ) ? 'required' : '' ;
			if( $field['field_type'] == 'text' ){
				?>
				<div id="form-<?php echo $field['id']; ?>x" class="col-md-12">
				    <div class="form-group">
				        <label for="<?php echo $field['field_key']; ?>" ><?php echo $field['label']; ?></label>
				        <input id="<?php echo $field['field_key']; ?>" type="text" class="form-control border-input" name="<?php echo $field['field_key']; ?>" placeholder="<?php echo $field['label']; ?>" value="<?php echo $value; ?>" <?php echo $required; ?> >
				        <?php
						if( !empty( $field['description'] ) ){
							?><p class="field-desc"><?php echo $field['description']; ?></p><?php
						}
						?>
				    </div>
				</div>
			<?php
			}elseif( $field['field_type'] == 'checkbox' ){
				?>
				<div id="form-<?php echo $field['id']; ?>" class="col-md-12">
				    <div class="form-group">
				        <label for="<?php echo $field['field_key']; ?>" ><?php echo $field['label']; ?></label>
				        <?php
						$checkbox_options = maybe_unserialize($field['field_data']);
						?><ul><?php
						$checkbox_option_counter=0;
						foreach( $checkbox_options as $checkbox_option ){
							?><li><input type="<?php echo $field['field_type']; ?>" class="form-control border-input" name="<?php echo $field['field_key']; ?>[]" value="<?php echo trim($checkbox_option); ?>" <?php echo ( $checkbox_option_counter == 0 ) ? $required : ''; ?> <?php echo ( trim($checkbox_option) == $value ) ? 'checked' : '' ; ?> /> <?php echo $checkbox_option; ?></li><?php
							$checkbox_option_counter++;
						}
						?></ul><?php
						if( !empty( $field['description'] ) ){
							?><p class="field-desc"><?php echo $field['description']; ?></p><?php
						}
						?>
				    </div>
				</div>
				<?php
			}elseif( $field['field_type'] == 'radio' ){
				?>
				<div id="form-<?php echo $field['id']; ?>" class="col-md-12">
				    <div class="form-group">
				        <label for="<?php echo $field['field_key']; ?>" ><?php echo $field['label']; ?></label>
				        ?><ul><?php
						$radio_option_counter=0;
						foreach( $radio_options as $radio_option ){
							?><li><input type="<?php echo $field['field_type']; ?>" class="form-control border-input" name="<?php echo $field['field_key']; ?>" value="<?php echo trim($radio_option); ?>" <?php echo ( $radio_option_counter == 0 ) ? $required : ''; ?> <?php echo ( trim($radio_option) == $value ) ? 'checked' : '' ; ?> /> <?php echo $radio_option; ?></li><?php
							$radio_option_counter++;
						}
						?></ul>
				        <?php
						if( !empty( $field['description'] ) ){
							?><p class="field-desc"><?php echo $field['description']; ?></p><?php
						}
						?>
				    </div>
				</div>
				<?php
			}elseif( $field['field_type'] == 'textarea' ){
				?>
				<div id="form-<?php echo $field['id']; ?>" class="col-md-12">
				    <div class="form-group">
				        <label for="<?php echo $field['field_key']; ?>" ><?php echo $field['label']; ?></label>
				        <textarea id="<?php echo $field['field_key']; ?>" class="form-control border-input" name="<?php echo $field['field_key']; ?>" <?php echo $required; ?> placeholder="<?php echo $field['label']; ?>" ><?php echo $value; ?></textarea>
				        <?php
						if( !empty( $field['description'] ) ){
							?><p class="field-desc"><?php echo $field['description']; ?></p><?php
						}
						?>
				    </div>
				</div>
			<?php
			}elseif( $field['field_type'] == 'select' ){
				?>
				<div id="form-<?php echo $field['id']; ?>" class="col-md-12">
				    <div class="form-group">
				        <label for="<?php echo $field['field_key']; ?>" ><?php echo $field['label']; ?></label>
				        <select name="<?php echo $field['field_key']; ?>" class="form-control border-input" id="<?php echo $field['field_key']; ?>" <?php echo $required; ?> >
		                	<option value=""><?php esc_html_e('-- Select One --', 'wpcargo-frontend-manager' ); ?></option>
							<?php
		                    $select_options = maybe_unserialize($field['field_data']);
		                    if( $field['field_key'] == 'wpcargo_status' ){
		                    	$select_options = $wpcargo->status;
		                    }
		                    foreach( $select_options as $select_option ){
		                        ?><option value="<?php echo trim($select_option); ?>" <?php selected( $value, trim($select_option) ); ?> ><?php echo trim($select_option); ?></option><?php
		                    }
		                    ?>
		                </select>
				        <?php
						if( !empty( $field['description'] ) ){
							?><p class="field-desc"><?php echo $field['description']; ?></p><?php
						}
						?>
				    </div>
				</div>
				<?php
			}elseif( $field['field_type'] == 'multiselect' ){
				?>
				<div id="form-<?php echo $field['id']; ?>" class="col-md-12">
				    <div class="form-group">
				        <label for="<?php echo $field['field_key']; ?>" ><?php echo $field['label']; ?></label>
				        <select id="<?php echo $field['field_key']; ?>" class="form-control border-input" name="<?php echo $field['field_key']; ?>[]" multiple size="6" <?php echo $required; ?> >
			                <?php
							$multiselect_options = maybe_unserialize($field['field_data']);
							foreach( $multiselect_options as $multiselect_option ){
								?><option value="<?php echo trim($multiselect_option); ?>" <?php selected( $value, trim($multiselect_option) ); ?> ><?php echo trim($multiselect_option); ?></option><?php
							}
							?>
		                </select>
				        <?php
						if( !empty( $field['description'] ) ){
							?><p class="field-desc"><?php echo $field['description']; ?></p><?php
						}
						?>
				    </div>
				</div>
				<?php
			}elseif( $field['field_type'] == 'number' ){
				?>
				<div id="form-<?php echo $field['id']; ?>" class="col-md-12">
				    <div class="form-group">
				        <label for="<?php echo $field['field_key']; ?>" ><?php echo $field['label']; ?></label>
				        <input id="<?php echo $field['field_key']; ?>" type="text" class="form-control border-input number" name="<?php echo $field['field_key']; ?>" value="<?php echo $value; ?>" <?php echo $required; ?> autocomplete="off" >
				        <?php
						if( !empty( $field['description'] ) ){
							?><p class="field-desc"><?php echo $field['description']; ?></p><?php
						}
						?>
				    </div>
				</div>
			<?php
			}elseif( $field['field_type'] == 'date' ){
				?>
				<div id="form-<?php echo $field['id']; ?>" class="col-md-12">
				    <div class="form-group">
				        <label for="<?php echo $field['field_key']; ?>" ><?php echo $field['label']; ?></label>
				        <input id="<?php echo $field['field_key']; ?>" type="text" class="form-control border-input wpcfe-datepicker" name="<?php echo $field['field_key']; ?>" value="<?php echo $value; ?>" <?php echo $required; ?> autocomplete="off" >
				        <?php
						if( !empty( $field['description'] ) ){
							?><p class="field-desc"><?php echo $field['description']; ?></p><?php
						}
						?>
				    </div>
				</div>
			<?php
			}elseif( $field['field_type'] == 'time' ){
				?>				
				<div id="form-<?php echo $field['id']; ?>" class="col-md-12">
				    <div class="form-group">
				        <label for="<?php echo $field['field_key']; ?>" ><?php echo $field['label']; ?></label>
				        <input id="<?php echo $field['field_key']; ?>" type="text" class="form-control border-input wpcfe-timepicker" name="<?php echo $field['field_key']; ?>" value="<?php echo $value; ?>" <?php echo $required; ?> placeholder="--:-- --" autocomplete="off" >
				        <?php
						if( !empty( $field['description'] ) ){
							?><p class="field-desc"><?php echo $field['description']; ?></p><?php
						}
						?>
				    </div>
				</div>
			<?php
			}elseif( $field['field_type'] == 'file' ){
				?>
				<div id="form-<?php echo $field['id']; ?>" class="col-md-12" >
					<label><?php echo $field['label']; ?></label>
					<div class="wpcargo-uploader">
						<div id="wpcargo-gallery-container_<?php echo $field['id'];?>">
							<ul class="wpcargo_images">
							</ul>
						</div>
						<input id="wpcpq_upload_ids_<?php echo $field['id'];?>" type="hidden" name="<?php echo $field['field_key']; ?>" value="" />
						<a id="wpcargo_select_gallery_<?php echo $field['id'];?>" class="btn btn-large btn-secondary" data-delete="Delete image" data-text="Delete" ><?php esc_html_e('Add Images / Upload Files', 'wpcargo-frontend-manager'); ?></a>
					</div>
					<script>
						jQuery(document).ready(function($){
						// Uploading files
							var file_frame;
							var $image_gallery_ids = $( '#wpcpq_upload_ids_<?php echo $field['id'];?>' );
							var $product_images    = $( '#wpcargo-gallery-container_<?php echo $field['id'];?>' ).find( 'ul.wpcargo_images' );
							$('#wpcargo_select_gallery_<?php echo $field['id']; ?>').live('click', function( event ){
								event.preventDefault();
								// If the media frame already exists, reopen it.
								if ( file_frame ) {
									file_frame.open();
									return;
								}
								// Create the media frame.
								file_frame = wp.media.frames.file_frame = wp.media({
									title: $( this ).data( 'uploader_title' ),
									button: {
										text: $( this ).data( 'uploader_button_text' ),
									},
									multiple: <?php echo apply_filters('wpcpq_multiple_fileupload', 'true' ); ?>
									// Set to true to allow multiple files to be selected
								});
								// When an image is selected, run a callback.
								var attachment_ids;
								file_frame.on( 'select', function() {
									// We set multiple to false so only get one image from the uploader
									//attachment = file_frame.state().get('selection').first().toJSON();
									attachment = file_frame.state().get('selection').map(function( attachment ) {
										// Do something with attachment.id and/or attachment.url here
										attachment = attachment.toJSON();
										if ( attachment.id ) {
											attachment_ids   = attachment_ids ? attachment_ids + ',' + attachment.id : attachment.id;
											var attachment_image = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
											$product_images.append( '<li class="image" data-attachment_id="' + attachment.id + '">'+
												'<img src="' + attachment_image + '" />'+
												'<span class="img-title">'+attachment.title.substr(0,18)+'</span>'+
												'<span class="actions"><a href="#" class="delete" data-imgID="'+attachment.id+'">x</a></span>'+
												'</li>' );
										}
									});
									$image_gallery_ids.val( attachment_ids );
								});
								// Finally, open the modal
								file_frame.open();
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
				</div>
				<?php
			}elseif( $field['field_type'] == 'email' ){
				?>
				<div id="form-<?php echo $field['id']; ?>" class="col-md-12">
				    <div class="form-group">
				        <label for="<?php echo $field['field_key']; ?>" ><?php echo $field['label']; ?></label>
				        <input id="<?php echo $field['field_key']; ?>" type="email" class="form-control border-input" name="<?php echo $field['field_key']; ?>" placeholder="<?php esc_html_e('Email', 'wpcargo-frontend-manager'); ?>" value="<?php echo $value; ?>" <?php echo $required; ?> >
				        <?php
						if( !empty( $field['description'] ) ){
							?><p class="field-desc"><?php echo $field['description']; ?></p><?php
						}
						?>
				    </div>
				</div>
				<?php
			}
		endforeach;
		echo ob_get_clean();
	}
}