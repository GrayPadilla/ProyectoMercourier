<?php
$tbl_name = $wpdb->prefix.'wpcargo_custom_fields';
$fields = array();
if($wpdb->get_var("SHOW TABLES LIKE '$tbl_name'") == $tbl_name) {
	$fields = $wpdb->get_results( "SELECT * FROM `".$tbl_name."` WHERE display_flags LIKE '%search%' AND field_type NOT IN ('file', 'textarea', 'agent') ORDER BY weight", OBJECT );
}
if(!empty($fields)) {
	foreach($fields as $field) {
		$label 			= stripslashes( $field->label );
		$field_type 	= $field->field_type;
		$description 	= $field->description;
		$field_key 		= $field->field_key;
		$field_data 	= $field->field_data;
		$display_flags 	= $field->display_flags;
		if(strpos($display_flags, 'search_required') !== false){
			$required = 'required';
		}
		else{
			$required = '';
		}
		if( $field_type == 'text' ) {
			?>
			<td class="track_form_td">
				<input type="text" name="<?php echo $field_key ?>" value="<?php echo isset($_REQUEST[$field_key]) ? $_REQUEST[$field_key] : ''; ?>" <?php echo $required; ?> placeholder="<?php echo $label; ?>" />
			</td>
			<?php
		}
		elseif( $field_type == 'email') {
			?>
			<td class="track_form_td">
				<input type="email" name="<?php echo $field_key ?>" value="<?php echo isset($_REQUEST[$field_key]) ? $_REQUEST[$field_key] : ''; ?>" <?php echo $required; ?> placeholder="<?php echo $label; ?>" />
			</td>
			<?php
		}
		elseif( $field_type == 'number') {
			?>
			<td class="track_form_td">
				<input type="number" name="<?php echo $field_key ?>" value="<?php echo isset($_REQUEST[$field_key]) ? $_REQUEST[$field_key] : ''; ?>" <?php echo $required; ?> placeholder="<?php echo $label; ?>" />
			</td>
			<?php
		}
		elseif( $field_type == 'select') {
			$get_field_options = unserialize($field_data);
			$get_request = isset($_REQUEST[$field_key]) ? $_REQUEST[$field_key] : '';			
			?>
			<td class="track_form_td">
				<select name="<?php echo $field_key ?>" <?php echo $required; ?> >
					<option value="">-- <?php esc_html_e('Select', 'wpcargo-custom-field' ); ?> <?php echo $label; ?> --</option>
					<?php
						foreach($get_field_options as $field_options) {
							if($get_request == $field_options) {
								$selected = 'selected';
							}
							else {
								$selected = '';
							}
							echo '<option value="'.$field_options.'" '.$selected.' >'.$field_options.'</option>';
						}
					?>
				</select>
			</td>
			<?php
		}
		elseif( $field_type == 'multiselect') {
			$get_field_options = unserialize($field_data);
			$get_request = isset($_REQUEST[$field_key]) ? $_REQUEST[$field_key] : array();
			?>
			<td class="track_form_td">
				<select name="<?php echo $field_key.'[]' ?>" multiple="multiple" <?php echo $required; ?> >
					<option value="">-- <?php esc_html_e('Select', 'wpcargo-custom-field' ); ?> <?php echo $label; ?> --</option>
					<?php
						foreach($get_field_options as $field_options) {
							if(!empty($get_request)) {
								if(in_array($field_options, $get_request)) {
									$selected = 'selected';
								}
								else {
									$selected = '';
								}
							}
							echo '<option value="'.$field_options.'" '.$selected.' >'.$field_options.'</option>';
						}
					?>
				</select>
			</td>
			<?php
		}
		elseif( $field_type == 'radio') {
			$get_field_options = unserialize($field_data);
			$get_request = isset($_REQUEST[$field_key]) ? $_REQUEST[$field_key] : '';
			?>
			<td class="track_form_td">
				<p class="wpcargo-label"><?php echo $label; ?></p>
				<?php
					foreach($get_field_options as $field_options) {
						if($get_request == $field_options) {
							$checked = 'checked';
						}
						else {
							$checked = '';
						}
						echo '<p><input type="radio" name="'.$field_key.'" value="'.$field_options.'" '.$checked.' '.$required.' > '.$field_options.'</p>';
					}
				?>
			</td>
			<?php
		}
		elseif( $field_type == 'checkbox') {
			$get_field_options = unserialize($field_data);
			$get_request = isset($_REQUEST[$field_key]) ? $_REQUEST[$field_key] : array();
			?>
			<td class="track_form_td">
				<p class="wpcargo-label"><?php echo $label; ?></p>
				<?php
					foreach($get_field_options as $field_options) {
						if(!empty($get_request)) {
							if(in_array($field_options, $get_request)) {
								$checked = 'checked';
							}
							else {
								$checked = '';
							}
						}
						echo '<p><input type="checkbox" name="'.$field_key.'[]'.'" value="'.$field_options.'" '.$checked.' > '.$field_options.'</p>';
					}
				?>
			</td>
			<?php
		}
		elseif( $field_type == 'date') {
			?>
			<td class="track_form_td">
				<input type="date" name="<?php echo $field_key ?>" value="<?php echo isset($_REQUEST[$field_key]) ? $_REQUEST[$field_key] : ''; ?>" <?php echo $required; ?> placeholder="<?php echo $label; ?>" />
			</td>
			<?php
		}
		elseif( $field_type == 'time') {
			?>
			<td class="track_form_td">
				<input type="time" name="<?php echo $field_key ?>" value="<?php echo isset($_REQUEST[$field_key]) ? $_REQUEST[$field_key] : ''; ?>" <?php echo $required; ?> placeholder="<?php echo $label; ?>" />
			</td>
			<?php
		}
	}
}