<?php
global $WPCCF_Fields;
$get_custom_fields = $WPCCF_Fields->get_custom_fields();
?>
<div class="wpcargo-add-form-fields postbox" style="clear:both;">
    <div class="inside">
    	<h2><?php esc_html_e('Add Form Field', 'wpcargo-custom-field' ); ?></h2>
        <form method="post" action="<?php echo admin_url(); ?>admin.php?page=wpc-cf-manage-form-field&action=add">
        <?php wp_nonce_field( 'wpc_cf_custom_field_action', 'wpc_cf_custom_field' ); ?>
        <table class="wpcargo form-table">
        	<?php do_action('wpc_cf_before_form_field_add'); ?>
        	<tr>
                <th><?php esc_html_e('Field Type (required)', 'wpcargo-custom-field' ); ?></th>
                <td>
                    <select name="field_type" id="field-type" required >
                    	<option value=""><?php esc_html_e('--Select One--', 'wpcargo-custom-field' ); ?></option>
                        <?php if( !empty( wpccf_field_type_list() ) ): ?>
                            <?php foreach ( wpccf_field_type_list() as $list_key => $list_value): ?>
                                <option value="<?php echo $list_key; ?>"><?php echo $list_value; ?></option>
                            <?php endforeach; ?>    
                        <?php endif; ?>
                    </select>
                </td>
            </tr>
            <tr>
            	<th><?php esc_html_e('Field Label (required)', 'wpcargo-custom-field' ); ?></th>
                <td><input type="text" name="label" required="required" /></td>
            </tr>
            <tr class="html_row" >
            	<th><?php esc_html_e('HTML', 'wpcargo-custom-field' ); ?></th>
                 <td><?php 
					$content = '';
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
                 <td><textarea name="description" type="text"></textarea></td>
            </tr>

            <tr id="select-list">
            	<th><?php esc_html_e('Field Options for select lists, radio buttons and checkboxes(required)', 'wpcargo-custom-field' ); ?></th>
                <td>
                	<textarea name="field_data" cols="50" rows="2" readonly="readonly"></textarea>
                    <?php esc_html_e('Comma (,) separated list of options', 'wpcargo-custom-field' ); ?>
                </td>
            </tr>
            <tr>
            	<th><?php esc_html_e('Field meta key', 'wpcargo-custom-field' ); ?></th>
                <td>
                	<p id="field-select">
						<input type="radio" name="field-key-select" value="existing" checked=""><?php esc_html_e('Existing', 'wpcargo-custom-field' ); ?> &nbsp;
						<input type="radio" name="field-key-select" value="new"><?php esc_html_e('New', 'wpcargo-custom-field' ); ?>
					</p>	
    				<div id="existing">
                	<select name="field_key" id="field-key" required >
                    	<option value=""><?php esc_html_e('--Select One--', 'wpcargo-custom-field' ); ?></option>
                    	<?php 
    						foreach( $metakeys as $key){
    							if( empty($key->meta_key) || $key->meta_key == "_edit_lock" ){ continue; }
    							?>
                                <option value="<?php echo $key->meta_key; ?>"><?php echo $key->meta_key; ?></option>
                                <?php
    						}
    					?>
                    </select>
                    </div>
                    <div id="new" style="display:none;">
                    	<input type="text" name="dummy"/>
                    </div>
                </td>
            </tr>
            <tr>
            	<th><?php esc_html_e('Is Field required?', 'wpcargo-custom-field' ); ?></th>
                <td><input type="checkbox" name="required" /></td>
            </tr>
            <tr class="table-header">
            	<td colspan="2"><h2><?php esc_html_e('Visibility', 'wpcargo-custom-field' ); ?></h2></td>
            </tr>
            <tr>
            	<th><?php esc_html_e('Enable Conditional Logic?', 'wpcargo-custom-field' ); ?></th>
                <td><input type="checkbox" name="condition_logic_enable" id="condition_logic_enable" class="condition_logic_enable" /></td>
            </tr>
            <tr>
                <th class="conditional-logic-section-wrapper" colspan="10">
                    <div class="conditional-logic-section d-none">
                        <select name="condition_show_hide" id="condition_show_hide" class="condition_show_hide main-required">
                            <option value=""><?php _e('Choose...', WPCARGO_CUSTOM_FIELD_TEXTDOMAIN); ?></option>
                            <?php foreach(wpccf_show_hide_options() as $key => $label): ?>
                                <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="spacer"> <?php _e('this field if', WPCARGO_CUSTOM_FIELD_TEXTDOMAIN); ?> </span>
                        <select name="condition_options" id="condition_options" class="condition_options main-required">
                            <option value=""><?php _e('Choose...', WPCARGO_CUSTOM_FIELD_TEXTDOMAIN); ?></option>
                            <?php foreach(wpccf_condition_options() as $key => $label): ?>
                                <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="spacer"> <?php _e('of the following match(es):', WPCARGO_CUSTOM_FIELD_TEXTDOMAIN); ?> </span>
                        <div class="condition-repeater" style="padding: 5px 0px;">
                            <div style="margin: 5px 0px;" data-repeater-list="condition_list">
                                <div data-repeater-item class="condition_list_item">
                                    <select class="condition_field_to_toggle_toggler" name="condition_field_to_toggle_toggler">
                                        <option value="">-- <?php _e('Select', WPCARGO_CUSTOM_FIELD_TEXTDOMAIN); ?> --</option>
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
                        </div>
                    </div>
                </th>
            </tr>
            <?php do_action('wpc_cf_before_display_option_add'); ?>
            <tr class="table-header">
            	<td colspan="2"><h2><?php esc_html_e('Field display options', 'wpcargo-custom-field' ); ?></h2></td>
            </tr>
            <?php do_action('wpc_cf_after_display_option_add'); ?>
            <tr>
            	<th><p><?php esc_html_e('What Section do you want to display?', 'wpcargo-custom-field' ); ?></p></th>
                <td>
                	<ul>
                    <li><input name="section" value="" type="radio"  /> <?php esc_html_e('None', 'wpcargo-custom-field' ); ?></li>
                    	<li><input name="section" value="shipper_info" type="radio"> <?php esc_html_e('Shipper Information', 'wpcargo-custom-field' ); ?></li>
                        <li><input name="section" value="receiver_info" type="radio"> <?php esc_html_e('Receiver Information', 'wpcargo-custom-field' ); ?></li>
                        <?php
                            if( !empty( wpccf_additional_sections() ) ){
                                foreach( wpccf_additional_sections() as $section_key => $section_label ){
                                    ?> <li><input name="section" value="<?php echo $section_key; ?>" type="radio"> <?php echo $section_label; ?></li><?php
                                }
                            }
    					?>
                        <?php do_action('wpc_cf_after_display_add_section'); ?>
                    </ul>
                </td>
            </tr>
			<tr class="currency_row" >
            	<th><?php esc_html_e('Calculations', 'wpcargo-custom-field' ); ?></th>
                 <td><textarea name="formula" class="mention-metakeys" placeholder="Ex. {meta_key1} * {meta_key2}" style="position: relative;"></textarea></td>
            </tr>
			<tr class="currency_row">
            	<th><?php esc_html_e('Currency', 'wpcargo-custom-field' ); ?></th>
                <td>
                    <select name="currency" id="currency">
                        <option value="">-- <?php esc_html_e('Select Currency', 'wpcargo-custom-field' ) ?> --</option>
                        <?php foreach(wpccf_currency_list() as $symbol => $name): ?>
                            <option value="<?php echo $symbol; ?>"><?php echo $name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
            	<th><p><?php esc_html_e('Class', 'wpcargo-custom-field' ); ?></p></th>
                <td>
                    <input type="text" name="class_attribute" />
                    <p class="description"><?php esc_html_e('HTML class attribute', 'wpcargo-custom-field' ); ?></p>
                </td>
            </tr>

            <tr>
            	<th><?php esc_html_e('Do you want to display on tracking page form?', 'wpcargo-custom-field' ); ?></th>
                <td><input name="display_flags[]" value="search" type="checkbox"></td>
            </tr>
            <tr>
            	<th><?php esc_html_e('Is field required on tracking page form?', 'wpcargo-custom-field' ); ?></th>
                <td><input name="display_flags[]" value="search_required" type="checkbox"></td>
            </tr>
            <tr>
            	<th><?php esc_html_e('Do you want to display it on result page?', 'wpcargo-custom-field' ); ?></th>
                <td><input name="display_flags[]" value="result" type="checkbox"></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Select user to NOT access this field', 'wpcargo-custom-field' ); ?></th>
                <td>
                    <input name="display_flags[]" value="useraccess_not_logged_in" type="checkbox"><?php esc_html_e('Not Logged In', 'wpcargo-custom-field' ); ?><br/>
                    <?php foreach ( $wp_roles->roles as $key => $value ): ?>
                        <input name="display_flags[]" value="useraccess_<?php echo $key; ?>" type="checkbox"><?php echo $value['name']; ?><br/>
                    <?php endforeach; ?>
                    <p class="description"><?php esc_html_e('Note: This option applies only in the front end form using custom field manager.', 'wpcargo-custom-field' ); ?></p>
                </td>
            </tr>
            <?php do_action('wpc_cf_after_form_field_add'); ?>
        </table>
        <input class="button button-primary" type="submit" name="submit_form_field" value="<?php esc_html_e('Add Field', 'wpcargo-custom-field' ); ?>" />
        </form>
		<script>
		jQuery(document).ready(function($){
			 $(".html_row").hide();
			 $(".currency_row").hide();

  			$("#field-type").on("change", function(){
   			 	if($(this).val()=='html'){
					$('.html_row').show();
				}else{
					$('.html_row').hide();
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

            $('body').on('change', 'select.condition_field_to_toggle_toggler', function(){
                conditionalLogicMetaKeyChanger();
            });
		});
		</script>
    </div>
</div>
