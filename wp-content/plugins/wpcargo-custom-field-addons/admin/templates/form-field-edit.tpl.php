<?php
global $WPCCF_Fields;global  $field_type_edit;
if( !isset( $_REQUEST['id'] ) || $_REQUEST['id'] == NULL  ){
	?>
	<div class="notice notice-error">
        <p><?php esc_html_e( 'Sorry you can\'t access this page directly.', 'wpcargo-custom-field' ); ?></p>
    </div>
    <?php
	exit;
}
$condition_logic_enable = $form_field->condition_logic_enable ?? '';
$condition_list = maybe_unserialize($form_field->condition_list ?? '');
$get_custom_fields = $WPCCF_Fields->get_custom_fields();
?>
<div class="wpcargo-add-form-fields postbox" style="clear: both;">
    <div class="inside">
    	<h2><?php esc_html_e('Edit Form Field', 'wpcargo-custom-field' ); ?></h2>
        <form method="post" action="<?php echo admin_url(); ?>admin.php?page=wpc-cf-manage-form-field&action=edit&id=<?php echo $_REQUEST['id'] ?>">
        <?php wp_nonce_field( 'wpc_cf_edit_field_action', 'wpc_cf_edit_field' ); ?>
        <table class="wpcargo form-table">
        	<?php do_action('wpc_cf_before_form_field_edit'); ?>
        	<tr>
                <th><?php esc_html_e('Field Type (required)', 'wpcargo-custom-field' ); ?></th>
                <td>
                    <select name="field_type" id="field-type" required >
                    	<option value=""><?php esc_html_e('--Select One--', 'wpcargo-custom-field' ); ?></option>
                        <?php if( !empty( wpccf_field_type_list() ) ): ?>
                            <?php foreach ( wpccf_field_type_list() as $list_key => $list_value): ?>
                                <option value="<?php echo $list_key; ?>" <?php selected( $form_field->field_type, $list_key ); ?>><?php echo $list_value; ?></option>
								<?php 	if($form_field->field_type ==$list_key ){
											$field_type_edit= $list_key;
										}
                             endforeach; ?>    
                        <?php endif; ?>
                    </select>
                </td>
            </tr>
            <tr>
            	<th><?php esc_html_e('Field Label (required)', 'wpcargo-custom-field' ); ?></th>
                <td><input type="text" name="label" required="required" value="<?php echo stripslashes( $form_field->label ); ?>" /></td>
            </tr>

            <tr class="html_row" >
            	<th><?php esc_html_e('HTML', 'wpcargo-custom-field' ); ?></th>
                <td><?php 
					$content = stripslashes( $form_field->html );
$custom_editor_id =  'html';

$args = array(
		'media_buttons' => false, // This setting removes the media button.
		'textarea_name' => 'html', // Set custom name.
		'textarea_rows' => get_option('default_post_edit_rows', 10), //Determine the number of rows.
		'quicktags' => true, // Remove view as HTML button.
	);
wp_editor( $content, $custom_editor_id, $args );
				?></td>
            </tr>
              <tr class="desc_row" >
            	<th><?php esc_html_e('Field description (optional)', 'wpcargo-custom-field' ); ?></th>
                <td><textarea name="description" type="text"><?php echo $form_field->description; ?></textarea></td>
            </tr>
            <tr id="select-list">
            	<?php  $field_data = maybe_unserialize( $form_field->field_data );  ?>
            	<th><?php esc_html_e('Field Options for select lists, radio buttons and checkboxes(required)', 'wpcargo-custom-field' ); ?></th>
                <td>
                	<textarea name="field_data" cols="50" rows="2" ><?php echo !empty( $field_data ) ? implode( ',', array_filter( $field_data ) ) : '' ; ?></textarea>
                    <?php esc_html_e('Comma (,) separated list of options', 'wpcargo-custom-field' ); ?>
                </td>
            </tr>
            <tr>
            	<th><?php esc_html_e('Field meta key', 'wpcargo-custom-field' ); ?></th>
                <td>
                	<p id="field-select"><input type="radio" name="field-key-select" value="existing" ><?php _e('Existing', 'wpcargo-custom-field' ); ?> &nbsp;<input type="radio" name="field-key-select" value="new">
    <?php esc_html_e('New', 'wpcargo-custom-field' ); ?></p>	
    				<div id="existing" style="display:none;" >
                	<select name="dummy" id="field-dummy" >
                    	<option value=""><?php esc_html_e('--Select One--', 'wpcargo-custom-field' ); ?></option>
                    	<?php 
    						foreach( $metakeys as $key){
    							if( empty($key->meta_key) || $key->meta_key == "_edit_lock" ){ continue; }
    							?>
                                <option value="<?php echo $key->meta_key; ?>" <?php echo ($form_field->field_key == $key->meta_key ) ? 'selected' : '' ; ?> ><?php echo $key->meta_key; ?></option>
                                <?php
	
    						}
    					?>
                    </select>
                    </div>
                    <div id="new" >
                    	<input type="text" name="field_key" value="<?php echo $form_field->field_key; ?>" required />
                    </div>
                </td>
            </tr>
            <tr>
            	<th><?php esc_html_e('Is Field required?', 'wpcargo-custom-field' ); ?></th>
                <td><input type="checkbox" name="required" <?php echo ( !empty ($form_field->required ) ) ? 'checked' : '' ; ?>/></td>
            </tr>
            <tr class="table-header">
            	<td colspan="2"><h2><?php esc_html_e('Visibility', 'wpcargo-custom-field' ); ?></h2></td>
            </tr>
            <tr>
            	<th><?php esc_html_e('Enable Conditional Logic?', 'wpcargo-custom-field' ); ?></th>
                <td><input type="checkbox" name="condition_logic_enable" id="condition_logic_enable" class="condition_logic_enable" <?php echo ( !empty ($form_field->condition_logic_enable ) ) ? 'checked' : ''; ?> /></td>
            </tr>
            <tr>
                <th class="conditional-logic-section-wrapper" colspan="10">
                    <div class="conditional-logic-section <?php echo $condition_logic_enable ? '' : 'd-none'; ?>">
                        <select name="condition_show_hide" id="condition_show_hide" class="condition_show_hide main-required">
                            <option value=""><?php _e('Choose...', WPCARGO_CUSTOM_FIELD_TEXTDOMAIN); ?></option>
                            <?php foreach(wpccf_show_hide_options() as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php selected($key, ($form_field->condition_show_hide ?? '')); ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="spacer"> <?php _e('this field if', WPCARGO_CUSTOM_FIELD_TEXTDOMAIN); ?> </span>
                        <select name="condition_options" id="condition_options" class="condition_options main-required">
                            <option value=""><?php _e('Choose...', WPCARGO_CUSTOM_FIELD_TEXTDOMAIN); ?></option>
                            <?php foreach(wpccf_condition_options() as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php selected($key, ($form_field->condition_options ?? '')); ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="spacer"> <?php _e('of the following match(es):', WPCARGO_CUSTOM_FIELD_TEXTDOMAIN); ?> </span>
                        <div class="condition-repeater" style="padding: 5px 0px;">
                            <?php if($condition_list): ?>
                                <div data-repeater-list="condition_list">
                                    <?php foreach($condition_list as $condition): 
                                        $condition_field_to_toggle_toggler = $condition['condition_field_to_toggle_toggler'] ?? '';
                                        $condition_field_to_toggle = $condition['condition_field_to_toggle'] ?? '';
                                        $condition_checker = $condition['condition_checker'] ?? '';
                                        $condition_field_value = $condition['condition_field_value'] ?? '';
                                        ?>
                                        <div style="margin: 5px 0px;" data-repeater-item class="condition_list_item">
                                            <select class="condition_field_to_toggle_toggler" name="condition_field_to_toggle_toggler">
                                                <option value="">-- <?php _e('Select', WPCARGO_CUSTOM_FIELD_TEXTDOMAIN); ?> --</option>
                                                <option value="existing" <?php echo ('existing' === $condition_field_to_toggle_toggler) ? 'selected' : ''; ?>><?php _e('Existing', WPCARGO_CUSTOM_FIELD_TEXTDOMAIN); ?></option>
                                                <option value="new" <?php echo ('new' === $condition_field_to_toggle_toggler) ? 'selected' : ''; ?>><?php _e('New', WPCARGO_CUSTOM_FIELD_TEXTDOMAIN); ?></option>
                                            </select>
                                            <input type="text" name="condition_field_to_toggle" class="condition_field_to_toggle_text" style="display: none;" disabled value="<?php echo $condition_field_to_toggle; ?>" />
                                            <select name="condition_field_to_toggle" class="condition_field_to_toggle condition_field_to_toggle_select main-required">
                                                <option value=""><?php _e('Choose...', WPCARGO_CUSTOM_FIELD_TEXTDOMAIN); ?></option>
                                                <?php foreach($get_custom_fields as $field_key): 
                                                $metakey = $field_key['field_key'] ?? '';
                                                $_label = $field_key['label'] ?? '';
                                                ?>
                                                    <option value="<?php echo $metakey; ?>" <?php selected($metakey, $condition_field_to_toggle); ?>><?php echo $_label; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <select name="condition_checker" class="condition_checker main-required">
                                                <option value=""><?php _e('Choose...', WPCARGO_CUSTOM_FIELD_TEXTDOMAIN); ?></option>
                                                <?php foreach(wpccf_condition_checker_options() as $key => $label): ?>
                                                    <option value="<?php echo $key; ?>" <?php selected($key, $condition_checker); ?>><?php echo $label; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="text" name="condition_field_value" class="condition_field_value main-required" value="<?php echo $condition_field_value; ?>" />
                                            <input data-repeater-delete type="button" value="Delete"/>
                                        </div>
                                    <?php endforeach; ?>
                                    </div>
                                <input data-repeater-create type="button" style="margin-top: 5px;" value="Add"/>
                            <?php else: ?>
                                <div style="margin: 5px 0px;" data-repeater-list="condition_list">
                                    <div data-repeater-item class="condition_list_item">
                                        <select class="condition_field_to_toggle_toggler">
                                            <option value="existing"><?php _e('Existing', WPCARGO_CUSTOM_FIELD_TEXTDOMAIN); ?></option>
                                            <option value="new"><?php _e('New', WPCARGO_CUSTOM_FIELD_TEXTDOMAIN); ?></option>
                                        </select>
                                        <input type="text" name="condition_field_to_toggle" class="condition_field_to_toggle_text" style="display: none;" disabled />
                                        <select name="condition_field_to_toggle" class="condition_field_to_toggle condition_field_to_toggle_select main-required">
                                            <option value=""><?php _e('Choose...', WPCARGO_CUSTOM_FIELD_TEXTDOMAIN); ?></option>
                                            <?php foreach($get_custom_fields as $field_key): 
                                                $metakey = $field_key['field_key'] ?? '';
                                                $_label = $field_key['label'] ?? '';
                                                ?>
                                                <option value="<?php echo $metakey; ?>"><?php echo $_label; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <select name="condition_checker" class="condition_checker main-required">
                                            <option value=""><?php _e('Choose...', WPCARGO_CUSTOM_FIELD_TEXTDOMAIN); ?></option>
                                            <?php foreach(wpccf_condition_checker_options() as $key => $label): ?>
                                                <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="text" name="condition_field_value" class="condition_field_value main-required" />
                                        <input data-repeater-delete type="button" value="Delete"/>
                                    </div>
                                </div>
                                <input data-repeater-create type="button" style="margin-top: 5px;" value="Add"/>
                            <?php endif; ?>
                        </div>
                    </div>
                </th>
            </tr>
            <?php do_action('wpc_cf_before_display_option_edit'); ?>
            <tr class="table-header">
            	<td colspan="2"><h2><?php esc_html_e('Field display options', 'wpcargo-custom-field' ); ?></h2></td>
            </tr>
            <?php do_action('wpc_cf_after_display_option_edit'); ?>
            <tr>
            	<th><p><?php esc_html_e('What Section do you want to display?', 'wpcargo-custom-field' ); ?></p></th>
                <td>
                	<ul><li><input name="section" value="" type="radio" <?php echo ($form_field->section == '' ) ? 'checked' : '' ; ?> /> <?php esc_html_e('None', 'wpcargo-custom-field' ); ?></li>
                    	<li><input name="section" value="shipper_info" type="radio" <?php echo ($form_field->section == 'shipper_info' ) ? 'checked' : '' ; ?> /> <?php esc_html_e('Shipper Information', 'wpcargo-custom-field' ); ?></li>
                        <li><input name="section" value="receiver_info" type="radio" <?php echo ($form_field->section == 'receiver_info' ) ? 'checked' : '' ; ?> /> <?php esc_html_e('Receiver Information', 'wpcargo-custom-field' ); ?></li>
                        <?php
                            if( !empty( wpccf_additional_sections() ) ){
                                foreach( wpccf_additional_sections() as $section_key => $section_label ){
                                    ?><li><input name="section" value="<?php echo $section_key; ?>" type="radio" <?php checked( $section_key, $form_field->section ) ?>/> <?php echo $section_label; ?></li><?php
                                }
                            }
    					?>
                        <?php do_action('wpc_cf_after_display_edit_section', $form_field ); ?>
                    </ul>
                </td>
            </tr>
            <?php if($form_field->field_type === 'text' || $form_field->field_type === 'number'): 
                ?>
                <tr class="currency_row" >
                    <th><?php esc_html_e('Calculations', 'wpcargo-custom-field' ); ?></th>
                     <td><textarea name="formula" class="mention-metakeys" placeholder="Ex. {meta_key1} * {meta_key2}" style="position: relative;"><?php echo  stripslashes( $form_field->formula); ?></textarea></td>
                </tr>
                <tr class="currency_row">
                    <th><?php esc_html_e('Currency', 'wpcargo-custom-field' ); ?></th>
                    <td>
                        <select name="currency" id="currency">
                            <option value="">-- <?php esc_html_e('Select Currency', 'wpcargo-custom-field' ) ?> --</option>
                            <?php foreach(wpccf_currency_list() as $symbol => $name): ?>
                                <option value="<?php echo $symbol; ?>" <?php selected($form_field->currency, $symbol); ?>><?php echo $name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            <?php endif; ?>
            <tr>
            	<th><p><?php esc_html_e('Class', 'wpcargo-custom-field' ); ?></p></th>
                <td>
                    <input type="text" name="class_attribute" value="<?php echo  $form_field->classes; ?>" />
                    <p class="description"><?php esc_html_e('HTML class attribute', 'wpcargo-custom-field' ); ?></p>
                </td>
            </tr>
            <?php 
    			
    			$display_flags = $form_field->display_flags;
    			$flags = maybe_unserialize( $display_flags ); 
    		?>
            <tr>
            	<th><?php esc_html_e('Do you want to display on tracking page form?', 'wpcargo-custom-field' ); ?></th>
                <td><input name="display_flags[]" value="search" type="checkbox" <?php echo is_array($flags) && in_array( 'search', $flags) ? 'checked' : ''; ?> /></td>
            </tr>
            <tr>
            	<th><?php esc_html_e('Is field required on tracking page form?', 'wpcargo-custom-field' ); ?></th>
                <td><input name="display_flags[]" value="search_required" type="checkbox" <?php echo is_array($flags) && in_array( 'search_required', $flags) ? 'checked' : ''; ?> /></td>
            </tr>
            <tr>
            	<th><?php esc_html_e('Do you want to display it on result page?', 'wpcargo-custom-field' ); ?></th>
                <td><input name="display_flags[]" value="result" type="checkbox" <?php echo is_array($flags) && in_array( 'result', $flags) ? 'checked' : ''; ?> /></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Select user NOT to access this field', 'wpcargo-custom-field' ); ?></th>
                <td>
                    <input name="display_flags[]" value="useraccess_not_logged_in" type="checkbox" <?php echo is_array($flags) && in_array( 'useraccess_not_logged_in', $flags) ? 'checked' : ''; ?>><?php esc_html_e('Not Logged In', 'wpcargo-custom-field' ); ?><br/>
                    <?php foreach ( $wp_roles->roles as $key => $value ): ?>
                        <input name="display_flags[]" value="<?php echo $key; ?>" type="checkbox"  <?php echo is_array($flags) && in_array( $key, $flags) ? 'checked' : ''; ?> ><?php echo $value['name']; ?><br/>
                    <?php endforeach; ?>
                    <p class="description"><?php esc_html_e('Note: This option applies only in the front end form using custom field manager.', 'wpcargo-custom-field' ); ?></p>
                </td>
            </tr>
            <?php do_action('wpc_cf_after_form_field_edit', $flags ); ?>
        </table>
        <input type="hidden" name="form_field_id" value="<?php echo $_GET['id']; ?>" />
        <input class="button button-primary" type="submit" name="submit_form_field" value="<?php esc_html_e('Edit Field', 'wpcargo-custom-field' ); ?>" />
        </form>
				<script>
		jQuery(document).ready(function($){
			<?php if( $field_type_edit!='html'){?>
			 $(".html_row").hide();
			
			<?php }?>
			<?php if( $field_type_edit!='text' || $field_type_edit!='number'){?>
			 $(".html_row").hide();
			
			<?php }?>
			
  			$("#field-type").on("change", function(){
   			 	if($(this).val()!='html'){
					$('.html_row').hide();
				}else{
					$('.html_row').show();
				}
 			 });

            $("#field-type").on("change", function(){
                    if($(this).val()=='text' || $(this).val()=='number'){
                    $('.currency_row').show();
                }else{
                    $('.currency_row').hide();
                }
            });

            const conditionalLogicMetaKeyChanger = () => {
                let a = $('select.condition_field_to_toggle_toggler');
                if(a.length > 0) {
                    a.each(function(){
                        let b = $(this).val() || '';
                        let c = $(this).closest('div.condition_list_item');
                        let d = c.find('input.condition_field_to_toggle_text');
                        let e = c.find('select.condition_field_to_toggle_select');
                        if(b) {
                            switch (b) {
                                case 'new':
                                    d.css('display', 'inline');
                                    d.prop('disabled', false);
                                    e.css('display', 'none');
                                    e.prop('disabled', true);
                                    break;
                                case 'existing':
                                    e.css('display', 'inline');
                                    e.prop('disabled', false);
                                    d.css('display', 'none');
                                    d.prop('disabled', true);
                                    break;
                            
                                default:
                                    break;
                            }
                        }
                    });
                }
            }

            conditionalLogicMetaKeyChanger();

            $('body').on('change', 'select.condition_field_to_toggle_toggler', function(){
                conditionalLogicMetaKeyChanger();
            });
		});
		</script>
		
    </div>
</div>