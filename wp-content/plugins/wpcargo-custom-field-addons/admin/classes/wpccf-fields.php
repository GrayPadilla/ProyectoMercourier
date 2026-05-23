<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}
class WPCCF_Fields{
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
	function get_fields_data( $flag = '', $shipment_id = 0, $attachment_image = true ){
		global $wpcargo;
		$field_keys = $this->get_custom_fields( $flag );
		ob_start();
		if( !empty( $field_keys ) ){
			foreach( $field_keys as $field ){
				$field_data = maybe_unserialize( get_post_meta( $shipment_id, $field['field_key'], TRUE ) );
				if( is_array( $field_data ) ){
					$field_data = implode(", ", $field_data);
				}
				$form_label = apply_filters( 'wpccf_field_form_label_'.$field['field_key'], stripslashes( $field['label'] ) );
				if( $field['field_type'] == 'file' ){
					$files = array_filter( array_map( 'trim', explode(",", $field_data) ) );
					if( !empty( $files ) ){
						?>
						<div class="wpccfe-files-data">
							<label><strong><?php echo $form_label; ?></strong></label>
							<div id="wpcargo-gallery-container_<?php echo $field['id'];?>">
								<ul class="wpccf_uploads">
									<?php
										foreach ( $files as $file_id ) {
											$att_meta = wp_get_attachment_metadata( $file_id );
											?>
											<li class="image">
												<a href="<?php echo wp_get_attachment_url($file_id); ?>" download>
													<?php if( $attachment_image ): ?>
													<?php echo wp_get_attachment_image($file_id, 'thumbnail', TRUE); ?>
													<?php endif; ?>
													<span class="img-title" title="<?php echo get_the_title($file_id); ?>"><?php echo get_the_title($file_id); ?></span>
												</a>
											</li>
											<?php
										}
									?>
								</ul>
							</div>
						</div>
						<?php
					}
				}elseif( $field['field_type'] == 'url' ){
					$url_data = maybe_unserialize( get_post_meta( $shipment_id, $field['field_key'], TRUE ) );
					$target   = count( $url_data ) > 2 ? '_blank' : '' ;
					$url 	  = $url_data[1] ? $url_data[1] : '#' ;
					$label 	  = $url_data[0];
					?><p><strong><?php echo $form_label; ?>:</strong> <a href="<?php echo $url; ?>" target="<?php echo $target; ?>"><?php echo $label; ?></a></p><?php
				}else{
					?><p><strong><?php echo $form_label; ?>:</strong> <?php echo $field_data; ?></p><?php
				}	
			}
		}
		$output = ob_get_clean();
		return $output;
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
	function get_field_options( $meta_key ){
		global $wpdb;
		$table_prefix = $wpdb->prefix;
		$field_options = $wpdb->get_var( "SELECT `field_data` FROM `".$table_prefix."wpcargo_custom_fields` WHERE `field_key`='".$meta_key."'" );
		$unserialized_field_options = array();
		if( is_serialized( $field_options ) ){
			$unserialized_field_options = maybe_unserialize( $field_options );
		}
		return $unserialized_field_options;
	}
	function convert_to_form_fields( $fields = array(), $post_id = false, $class="", $id="" ){
		global $wpcargo;
		$html = '';
		foreach( $fields as $field):
			$value 		= (int)$post_id ? maybe_unserialize( get_post_meta( $post_id, $field['field_key'], TRUE ) ) : '';
			$required 	= ( $field['required'] ) ? 'required' : '' ;
			$wrap_class = isset($field['classes'])? $field['classes'] : '';
			$wrap_class = strpos( $wrap_class, 'col-') === false ? $wrap_class.' col-md-12' : $wrap_class;
			$form_label = apply_filters( 'wpccf_field_form_label_'.$field['field_key'], stripslashes( $field['label'] ) );

			// conditional options
			$is_conditional = (($field['condition_logic_enable'] ?? '') ? true : false);
			$is_show = $field['condition_show_hide'] ?? '';
			$condition_options = $field['condition_options'] ?? '';
			$condition_list = maybe_unserialize($field['condition_list'] ?? '');
			$data_condition = "";
			$data_condition_opts = "";
			$field_class = "";

			// add "wpccf-conditionize" class if field is conditional
			if($is_conditional){
				$field_class .= " wpccf-conditionize";
				$the_condition = "";
				$operator = ($condition_options === 'all') ? '&&' : '||';
				$the_condition_opts = array();
				if($condition_list){
					$condition_list_count = count($condition_list) - 1;
					foreach($condition_list as $idx => $condition){
						$field_to_toggle = $condition['condition_field_to_toggle'] ?? '';
						$field_to_toggle_data = wpccf_get_field_by_metakey($field_to_toggle) ?: array();
						$field_to_toggle_field_type = $field_to_toggle_data['field_type'] ?? '';
						$field_condition = $condition['condition_checker'] ?? '';
						$field_val_to_check = sanitize_text_field($condition['condition_field_value'] ?? '');
						$_field_data = array_filter(maybe_unserialize(($field_to_toggle_data['field_data'] ?? '') ?: '') ?: array());

						// set conditional statments
						$the_condition .= "{$field_to_toggle}";

						// special cases for checkboxes and multiselects
						if($field_to_toggle_field_type === 'checkbox' || $field_to_toggle_field_type === 'multiselect'){
							$the_condition .= "[]";
						}

						if($field_condition === 'is'){

							// special cases for checkboxes and multiselects
							if($field_to_toggle_field_type === 'checkbox' || $field_to_toggle_field_type === 'multiselect'){
								if($field_to_toggle_field_type === 'checkbox') {
									if(count($_field_data) > 1) {
										$the_condition .= ".includes('{$field_val_to_check}')";
									} else {
										$the_condition .= " === '{$field_val_to_check}'";
									}
								} else {
									$the_condition .= ".includes('{$field_val_to_check}')";
								}
							} else {
								$the_condition .= " === '{$field_val_to_check}'";
							}
	
						} elseif($field_condition === 'is-not') {

							// special cases for checkboxes and multiselects
							if($field_to_toggle_field_type === 'checkbox' || $field_to_toggle_field_type === 'multiselect'){
								if($field_to_toggle_field_type === 'checkbox') {
									if(count($_field_data) > 1) {
										$the_condition = "!{$the_condition}";
										$the_condition .= ".includes('{$field_val_to_check}')";
									} else {
										$the_condition .= " !== '{$field_val_to_check}'";
									}
								} else {
									$the_condition = "!{$the_condition}";
									$the_condition .= ".includes('{$field_val_to_check}')";
								}
							} else {
	
								$the_condition .= " !== '{$field_val_to_check}'";
							}
	
						} elseif($field_condition === 'empty'){
	
							$the_condition .= " === ''";
	
						} elseif($field_condition === 'not-empty'){
	
							$the_condition .= " !== ''";
	
						}

						if($condition_list_count != $idx){
							$the_condition .= " {$operator} ";
						}
					}
				}

				// setup settings to be converted to json
				$the_condition_opts = array(
					'is_conditional' => $is_conditional,
					'is_show' => $is_show,
					'is_required' => $required
				);

				// stringify condition options
				$_the_condition_opts = json_encode($the_condition_opts);
				$_the_condition = apply_filters('wpccf_the_condition', $the_condition, $is_conditional, $is_show, $field_to_toggle, $field_condition, $field_val_to_check);

				$data_condition = 'data-condition="'.$_the_condition.'"';
				$data_condition_opts = "data-condition_opts='{$_the_condition_opts}'";
			}

			// autocalculate options
			$formula 		= ($field['formula'] ?? '') ?: '';
			$currency 	= ($field['currency'] ?? '') ?: '';
			$jautocalc 	= '';
			if($formula) {
				$jautocalc = 'jAutoCalc="'.$formula.'"';
			}
			ob_start();
			?><section class="<?php echo $wrap_class; ?>"><?php
				?><div id="form-<?php echo $id.$field['id']; ?>" class="form-group <?php echo $class; ?>"><?php
				if( $field['field_type'] == 'text' ){
					$value = is_array( $value ) ? implode(", ", $value) : $value;
					?>
					<label for="<?php echo $id.$field['field_key']; ?>" ><?php echo $form_label; ?></label>
					<?php if($currency): ?>
						<div class="input-group flex-nowrap">
						<span class="input-group-text" style="border-right: 0px; border-radius: .25rem 0rem 0rem .25rem;"><?php echo wpccf_get_woocommerce_currency_symbol($currency); ?></span>
					<?php endif; ?>
					<input id="<?php echo $id.$field['field_key']; ?>" 
				 <?php echo $data_condition; ?> <?php echo $data_condition_opts; ?> <?php echo $jautocalc; ?> type="text" class="form-control <?php echo $field['field_key']; ?> <?php echo $field_class; ?>" name="<?php echo $field['field_key']; ?>" value="<?php echo $value; ?>" <?php echo $required; ?> <?php echo (($field['uid'] ?? '') ?: '')? "data-cell=\"".(($field['uid'] ?? '') ?: '')."\"":''; ?> <?php echo (($field['format'] ?? '') ?: '')? "data-format=\"".(($field['format'] ?? '') ?: '')."\"":''; ?> <?php echo (($field['formula'] ?? '') ?: '')? "data-formula=\"".(($field['formula'] ?? '') ?: '')."\"":''; ?> >
					<?php if($currency): ?>
						</div>
					<?php endif; ?>
					<?php
				}elseif( $field['field_type'] == 'checkbox' ){
					?>
					<p><?php echo $form_label; ?></p>
					<?php
					$checkbox_options = array_filter( maybe_unserialize($field['field_data']) );
					if( empty( $value ) ){
						$value = array();
					}
					$checkbox_options = apply_filters( 'wpccf_field_options', $checkbox_options, $field['field_key'] );
					if( !empty( $checkbox_options ) ){
						?><ul><?php
						$checkbox_option_counter=0;
						foreach( $checkbox_options as $checkbox_option ){
							$option_id = strtolower( preg_replace('/[^a-zA-Z0-9\w_]+/u', '', $checkbox_option) );
							?>
							<li><input id="<?php echo $id.$field['id'].'-'.$option_id; ?>" <?php echo $data_condition; ?> <?php echo $data_condition_opts; ?> type="<?php echo $field['field_type']; ?>" class="form-check-input <?php echo $field['field_key']; ?> <?php echo $field_class; ?>" name="<?php echo $field['field_key']; ?>[]" value="<?php echo trim($checkbox_option); ?>" <?php echo ( $checkbox_option_counter == 0 ) ? $required : ''; ?> <?php echo in_array( trim($checkbox_option), $value ) ? 'checked' : '' ; ?> /> <label for="<?php echo $id.$field['id'].'-'.$option_id; ?>" class="form-check-label" ><?php echo $checkbox_option; ?></label>
							</li><?php
							$checkbox_option_counter++;
						}
						?></ul><?php
					}else{
						?><p class="field-desc"><?php esc_html__('No options available', 'wpcargo-custom-field') ; ?></p><?php
					}
				}elseif( $field['field_type'] == 'radio' ){
					?>
					<p><?php echo $form_label; ?></p>
					<?php 
					$radio_options = array_filter( maybe_unserialize($field['field_data']) );
					$radio_options = apply_filters( 'wpccf_field_options', $radio_options, $field['field_key'] );
					if( !empty( $radio_options ) ){
						?><ul><?php
						$radio_option_counter=0;
						foreach( $radio_options as $radio_option ){
							$option_id = strtolower( preg_replace('/[^a-zA-Z0-9\w_]+/u', '', $radio_option) );
							$checked = '';
							$name_attr = $field['field_key'];
							if(is_array($value)) {
								if(in_array(trim($radio_option), $value)) {
									$checked = 'checked';
								}
							} else {
								if(trim($radio_option) == trim($value)) {
									$checked = 'checked';
								}
							}
							?>
							<li>
								<input id="<?php echo $id.$field['id'].'-'.$option_id; ?>" <?php echo $data_condition; ?> <?php echo $data_condition_opts; ?> type="<?php echo $field['field_type']; ?>" class="form-check-input <?php echo $field['field_key']; ?> <?php echo $field_class; ?>" name="<?php echo $name_attr; ?>" data-default="<?php echo trim($radio_option); ?>" value="<?php echo trim($radio_option); ?>" <?php echo ( $radio_option_counter == 0 ) ? $required : ''; ?> <?php echo $checked; ?> /> <label for="<?php echo $id.$field['id'].'-'.$option_id; ?>" class="form-check-label" ><?php echo $radio_option; ?></label>
							</li><?php
							$radio_option_counter++;
						}
						?></ul>
						<?php
					}else{
						?><p class="field-desc"><?php esc_html__('No options available', 'wpcargo-custom-field') ; ?></p><?php
					}
				}elseif( $field['field_type'] == 'textarea' ){
					$value = is_array( $value ) ? implode(", ", $value) : $value;
					?>
					<label for="<?php echo $id.$field['field_key']; ?>" ><?php echo $form_label; ?></label>
					<textarea id="<?php echo $id.$field['field_key']; ?>" <?php echo $data_condition; ?> <?php echo $data_condition_opts; ?> class="md-textarea form-control <?php echo $field['field_key']; ?> <?php echo $field_class; ?>" name="<?php echo $field['field_key']; ?>" <?php echo $required; ?> <?php echo ($field['uid'])? "data-cell=\"".$field['uid']."\"":''; ?> <?php echo ($field['format'])? "data-format=\"".$field['format']."\"":''; ?> <?php echo ($field['formula'])? "data-formula=\"".$field['formula']."\"":''; ?> ><?php echo $value; ?></textarea>
					<?php
				}elseif( $field['field_type'] == 'html' ){
					?>
					<div class="<?php echo $field_class; ?>" <?php echo $data_condition; ?> <?php echo $data_condition_opts; ?>>
					<?php echo stripslashes( $field['html']); ?>
				  </div>
					<?php
				}elseif( $field['field_type'] == 'select' ){
					?>
					<label for="<?php echo $id.$field['field_key']; ?>" ><?php echo $form_label; ?></label>
					<?php 
					$select_options = array_filter( maybe_unserialize($field['field_data']) );
					$select_options = apply_filters( 'wpccf_field_options', $select_options, $field['field_key'] );
					if( !empty( $select_options ) ){
						?>
						<select name="<?php echo $field['field_key']; ?>" <?php echo $data_condition; ?> <?php echo $data_condition_opts; ?> class="form-control browser-default custom-select <?php echo $field['field_key']; ?> <?php echo $field_class; ?>" id="<?php echo $id.$field['field_key']; ?>" <?php echo $required; ?> >
							<option value=""><?php esc_html_e('-- Select One --', 'wpcargo-custom-field'  ); ?></option>
							<?php
							foreach( $select_options as $select_option ){
								?><option value="<?php echo trim($select_option); ?>" <?php selected( $value, trim($select_option) ); ?> ><?php echo trim($select_option); ?></option><?php
							}
							?>
						</select>
						<?php
					}else{
						?><p class="field-desc"><?php esc_html__('No options available', 'wpcargo-custom-field') ; ?></p><?php
					}
				}elseif( $field['field_type'] == 'multiselect' ){
					?>
					<label for="<?php echo $id.$field['field_key']; ?>" ><?php echo $form_label; ?></label>
					<?php
					$multiselect_options = array_filter( maybe_unserialize($field['field_data']) );
					$multiselect_options = apply_filters( 'wpccf_field_options', $multiselect_options, $field['field_key'] );
					if( empty( $value ) ){
						$value = array();
					}
					if( !empty( $multiselect_options ) ){
						?>
						<select id="<?php echo $id.$field['field_key']; ?>" <?php echo $data_condition; ?> <?php echo $data_condition_opts; ?> class="form-control browser-default custom-select <?php echo $field['field_key']; ?> <?php echo $field_class; ?>" name="<?php echo $field['field_key']; ?>[]" multiple size="6" <?php echo $required; ?> >
							<option style="display: none !important;" value=" " selected="selected" ></option>
							<?php
							foreach( $multiselect_options as $multiselect_option ){
								?><option value="<?php echo trim($multiselect_option); ?>" <?php echo in_array( trim($multiselect_option), $value ) ? 'selected' : '' ; ?> ><?php echo trim($multiselect_option); ?></option><?php
							}
							?>
						</select>
						<?php
					}else{
						?><p class="field-desc"><?php esc_html__('No options available', 'wpcargo-custom-field') ; ?></p><?php
					}
				}elseif( $field['field_type'] == 'number' ){
					$value = is_array( $value ) ? implode(", ", $value) : $value;
					?>
					<label for="<?php echo $id.$field['field_key']; ?>" ><?php echo $form_label; ?></label>
					<?php if($currency): ?>
						<div class="input-group flex-nowrap">
						<span class="input-group-text" style="border-right: 0px; border-radius: .25rem 0rem 0rem .25rem;"><?php echo wpccf_get_woocommerce_currency_symbol($currency); ?></span>
					<?php endif; ?>
					<input id="<?php echo $id.$field['field_key']; ?>" <?php echo $data_condition; ?> <?php echo $data_condition_opts; ?> <?php echo $jautocalc; ?> type="number" class="form-control wpccf-number <?php echo $field['field_key']; ?> <?php echo $field_class; ?>" name="<?php echo $field['field_key']; ?>" value="<?php echo $value; ?>" autocomplete="off" <?php echo $required; ?> >
					<?php if($currency): ?>
						</div>
					<?php endif; ?>
					<?php
				}elseif( $field['field_type'] == 'date' ){
					$value = is_array( $value ) ? implode(", ", $value) : $value;
					?>
					<label for="<?php echo $id.$field['field_key']; ?>" ><?php echo $form_label; ?></label>
					<input id="<?php echo $id.$field['field_key']; ?>" <?php echo $data_condition; ?> <?php echo $data_condition_opts; ?> type="text" class="form-control wpccf-datepicker <?php echo $field['field_key']; ?> <?php echo $field_class; ?>" name="<?php echo $field['field_key']; ?>" value="<?php echo $value; ?>"  autocomplete="off" <?php echo $required; ?> >
					<?php
				}elseif( $field['field_type'] == 'time' ){
					$value = is_array( $value ) ? implode(", ", $value) : $value;
					?>				
					<label for="<?php echo $id.$field['field_key']; ?>" ><?php echo $form_label; ?></label>
					<input id="<?php echo $id.$field['field_key']; ?>" <?php echo $data_condition; ?> <?php echo $data_condition_opts; ?> type="text" class="form-control wpccf-timepicker <?php echo $field['field_key']; ?> <?php echo $field_class; ?>" name="<?php echo $field['field_key']; ?>" value="<?php echo $value; ?>" autocomplete="off" <?php echo $required; ?>>
					<?php
				}elseif( $field['field_type'] == 'datetime' ){
					$value = is_array( $value ) ? implode(", ", $value) : $value;
					?>				
					<label for="<?php echo $id.$field['field_key']; ?>" ><?php echo $form_label; ?></label>
					<input id="<?php echo $id.$field['field_key']; ?>" <?php echo $data_condition; ?> <?php echo $data_condition_opts; ?> type="text" class="form-control wpccf-datetimepicker <?php echo $field['field_key']; ?> <?php echo $field_class; ?>" name="<?php echo $field['field_key']; ?>" value="<?php echo $value; ?>" autocomplete="off" <?php echo $required; ?>>
					<?php
				}elseif( $field['field_type'] == 'url' ){
					?>				
					<label for="label-<?php echo $id.$field['field_key']; ?>" ><?php echo $form_label; ?></label>
					<input type="text" id="label-<?php echo $id.$field['field_key']; ?>" <?php echo $data_condition; ?> <?php echo $data_condition_opts; ?> class="form-control <?php echo $field['field_key']; ?> <?php echo $field_class; ?>" name="<?php echo $field['field_key'].'[]'; ?>" value="<?php echo is_array($value) ? $value[0] : ''; ?>" size="25" <?php echo ( $required ) ? 'required' : '' ; ?> placeholder="<?php esc_html_e('URL Label', 'wpcargo-custom-field' ); ?>" style="margin-bottom: 5px;" />
					<input type="text" id="<?php echo $id.$field['field_key']; ?>" <?php echo $data_condition; ?> <?php echo $data_condition_opts; ?> class="form-control <?php echo $field['field_key']; ?> <?php echo $field_class; ?>" name="<?php echo $field['field_key'].'[]'; ?>" value="<?php echo is_array($value) ? $value[1] : ''; ?>" size="25" <?php echo ( $required ) ? 'required' : '' ; ?> placeholder="<?php esc_html_e('http://www.sample.com', 'wpcargo-custom-field' ); ?>" style="margin-bottom: 5px;" />
					<input type="checkbox" id="<?php echo $id; ?>new-window-link" class="form-check-input <?php echo $field['field_key']; ?> <?php echo $field_class; ?>" name="<?php echo $field['field_key'].'[]'; ?>" <?php echo is_array($value) && !empty($value[2]) ? 'checked' : ''?> ><label for="<?php echo $id; ?>new-window-link" class="form-check-label" ><?php esc_html_e('Open new window?', 'wpcargo-custom-field' ); ?></label>
					<?php
				}elseif( $field['field_type'] == 'file' ){
					$files = array_filter( array_map( 'trim', explode(",", $value) ) );
					?>
					<label><?php echo $form_label; ?></label>
					<div class="wpcargo-uploader">
						<div id="wpcargo-gallery-container_<?php echo $id.$field['id'];?>">
							<ul class="wpccf_uploads">
								<?php
								if( !empty( $files ) ){
									foreach ( $files as $file_id ) {
										?>
										<li class="image" data-attachment_id="<?php echo $file_id; ?>">
											<a href="<?php echo wp_get_attachment_url($file_id); ?>" target="_blank"><?php echo wp_get_attachment_image($file_id, array('120', '120'), TRUE); ?></a>
											<span class="img-title" title="<?php echo get_the_title($file_id); ?>"><?php echo substr( get_the_title($file_id), 0, 18 ); ?></span>
											<span class="actions"><a href="#" class="delete" data-imgID="<?php echo $file_id; ?>" data-section="<?php echo $field['id']; ?>">x</a></span>
										</li>
										<?php
									}
								}
								?>
							</ul>
						</div>
						<input id="wpcpq_upload_ids_<?php echo $id.$field['id'];?>" <?php echo $data_condition; ?> <?php echo $data_condition_opts; ?> class="<?php echo $field['field_key']; ?> <?php echo $field_class; ?>" type="hidden" name="<?php echo $field['field_key']; ?>" value="<?php echo $value; ?>" />
						<a id="wpcargo_select_gallery_<?php echo $id.$field['id'];?>" class="wpccf-upload-attachment btn btn-sm btn-secondary" data-section="<?php echo $field['id']; ?>" ><?php esc_html_e('Add Images / Upload Files', 'wpcargo-custom-field' ); ?></a>
					</div>
					<?php
				}elseif( $field['field_type'] == 'email' ){
					$value = is_array( $value ) ? implode(", ", $value) : $value;
					?>
					<label for="<?php echo $id.$field['field_key']; ?>" ><?php echo $form_label; ?></label>
					<input id="<?php echo $id.$field['field_key']; ?>" <?php echo $data_condition; ?> <?php echo $data_condition_opts; ?> type="email" class="form-control <?php echo $field['field_key']; ?> <?php echo $field_class; ?>" name="<?php echo $field['field_key']; ?>" value="<?php echo $value; ?>" <?php echo $required; ?> >
					<?php
				}elseif( $field['field_type'] == 'address' ){
					$wpcargo_country = array_map( 'trim', explode( ',', wpcargo_country_list() ) );
					$address 		 = wpccf_extract_address( $post_id, $field['field_key'] );
					if( !empty( wpccf_address_fields_data() ) ){
						$counter = 1;
						foreach ( wpccf_address_fields_data() as $_addr_meta => $_addr_label ) {
							if( $_addr_meta == 'country' ){
								?>
								<div class="country_address-section p-0 col-md-12">
									<label for="<?php echo $id.$field['field_key']; ?>[<?php echo $_addr_meta; ?>]"><?php echo $_addr_label; ?></label>
									<select class="form-control browser-default custom-select <?php echo $field['field_key']; ?> <?php echo $field_class; ?>" <?php echo $data_condition; ?> <?php echo $data_condition_opts; ?> id="<?php echo $id.$field['field_key']; ?>[<?php echo $_addr_meta; ?>]" name="<?php echo $field['field_key']; ?>[<?php echo $_addr_meta; ?>]" <?php echo $required; ?>>
										<option value =""><?php esc_html_e(' -- Select Country -- ', 'wpcargo-custom-field'); ?></option>
										<?php foreach ($wpcargo_country as $country) : ?>
											<option value="<?php echo $country ?>" <?php echo selected( $address[$_addr_meta], $country ); ?>><?php echo $country ?></option>
										<?php endforeach; ?>
									</select>
								</div>
								<?php
							}else{
								?>
								<div class="form-group street_address-section p-0 col-md-12">
									<label for="<?php echo $id.$field['field_key']; ?>[<?php echo $_addr_meta; ?>]" ><?php echo $_addr_label; ?></label>
									<input id="<?php echo $id.$field['field_key']; ?>[<?php echo $_addr_meta; ?>]" <?php echo $data_condition; ?> <?php echo $data_condition_opts; ?> type="text" class="form-control <?php echo $_addr_meta.' '.$field['field_key']; ?> <?php echo $field_class; ?>" name="<?php echo $field['field_key']; ?>[<?php echo $_addr_meta; ?>]" value="<?php echo $address[$_addr_meta]; ?>" <?php echo ( $required && $counter == 1 ) ? 'required' : '' ; ?>>
								</div>
								<?php
							}
							$counter ++;
						}
					}
				}
				if( !empty( $field['description'] ) ){
					?><p class="field-desc"><?php echo $field['description']; ?></p><?php
				}
				?>
				</div>
			</section><?php
			$html_field = ob_get_clean();
			$html .= apply_filters( 'wpccf_field_html_template_'.$field['field_key'], $html_field, $field['field_key'], $post_id, $class, $id, $field );
		endforeach;
		echo $html;
	}
}
$WPCCF_Fields = new WPCCF_Fields;