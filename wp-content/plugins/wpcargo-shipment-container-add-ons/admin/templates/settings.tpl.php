<form method="POST" action="options.php" enctype="multipart/form-data">
    <?php settings_fields( 'wpc_container_settings_group' ); ?>
    <?php do_settings_sections( 'wpc_container_settings_group' ); ?>
    <table class="form-table">
        <tbody>
            <tr>      
                <th scope="row"><?php esc_html_e( 'Enable Auto generate Container Title', 'wpcargo-shipment-container' ); ?></th>
                <td><input type="checkbox" name="enable_container_autogen" value="1" <?php echo checked( get_option('enable_container_autogen'), 1 ) ; ?>></td>    
            </tr>
            <tr>      
                <th scope="row"><?php esc_html_e( 'Enable Pre-assigned Shipments?', 'wpcargo-shipment-container' ); ?></th>
                <td><input type="checkbox" name="load_pre_assigned" value="1" <?php echo checked( get_option('load_pre_assigned'), 1 ) ; ?>></td>    
            </tr>
            <tr>      
                <th scope="row"><?php esc_html_e( 'Container Title Prefix', 'wpcargo-shipment-container' ); ?></th>
                <td>
                    <input type="text" name="container_prefix" value="<?php echo get_option('container_prefix'); ?>" placeholder="WPCONTR" >
                    <p class="description"><?php esc_html_e( 'Note: This will be apply when Auto generate Container Title is Enable.', 'wpcargo-shipment-container' ); ?></p>
                </td>    
            </tr>
        	<tr valign="top">
                <th scope="row"><?php esc_html_e('Add Travel Mode Selection', 'wpcargo-shipment-container' ); ?></th>
                <td>
                    <textarea id="travel_mode" cols="60" rows="4" name="travel_mode"><?php echo get_option('travel_mode'); ?></textarea>
                    <p class="description"><?php esc_html_e('Must be comma(,) separated. Example : Normal, Express', 'wpcargo-shipment-container' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Access Container Roles', 'wpcargo-shipment-container' ); ?></th>
                <td>
                    <select class="wpcfe-select" name="wpcc_container_access[]" multiple="multiple" style="width:360px;">
                        <?php
                        if( !empty( $roles ) ){
                            foreach ($roles as $_key => $_value) {
                                ?><option value="<?php echo $_key; ?>" <?php echo in_array( $_key, $access_container_role ) ? 'selected' : '' ; ?>><?php echo $_value; ?></option><?php
                            }
                        }
                        ?>
                    </select>
                    <p class="description"><?php esc_html_e('Note: This will give access to selected roles to containers page.', 'wpcargo-frontend-manager'); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Update Container Role', 'wpcargo-shipment-container' ); ?></th>
                <td>
                    <select class="wpcfe-select" name="update_container_role[]" multiple="multiple" style="width:360px;">
                        <?php
                        if( !empty( $roles ) ){
                            foreach ($roles as $_key => $_value) {
                                ?><option value="<?php echo $_key; ?>" <?php echo in_array( $_key, $can_update_container ) ? 'selected' : '' ; ?>><?php echo $_value; ?></option><?php
                            }
                        }
                        ?>
                    </select>
                    <p class="description"><?php esc_html_e('Note: This will allow selected role to add or update containers', 'wpcargo-frontend-manager'); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Delete Container Role', 'wpcargo-shipment-container' ); ?></th>
                <td>
                    <select class="wpcfe-select" name="delete_container_role[]" multiple="multiple" style="width:360px;">
                        <?php
                        if( !empty( $roles ) ){
                            foreach ($roles as $_key => $_value) {
                                ?><option value="<?php echo $_key; ?>" <?php echo in_array( $_key, $can_delete_container ) ? 'selected' : '' ; ?>><?php echo $_value; ?></option><?php
                            }
                        }
                        ?>
                    </select>
                    <p class="description"><?php esc_html_e('Note: This will allow selected role to delete containers', 'wpcargo-frontend-manager'); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Import/Export Container Role', 'wpcargo-shipment-container' ); ?></th>
                <td>
                    <select class="wpcfe-select" name="wpcc_ie_container_role[]" multiple="multiple" style="width:360px;">
                        <?php
                        if( !empty( $roles ) ){
                            foreach ($roles as $_key => $_value) {
                                ?><option value="<?php echo $_key; ?>" <?php echo in_array( $_key, $can_import_export_container ) ? 'selected' : '' ; ?>><?php echo $_value; ?></option><?php
                            }
                        }
                        ?>
                    </select>
                    <p class="description"><?php esc_html_e('Note: This will allow selected role to import/export containers', 'wpcargo-frontend-manager'); ?></p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row" colspan="2"><h1><?php esc_html_e( 'Container Shipment Print Header / Footer', 'wpcargo-shipment-container' ); ?></h1></th>
            </tr>
            <tr>
                <th scope="row" colspan="2">
                    <p><label for="container_print_header"><?php esc_html_e('Header additional content', 'wpcargo-shipment-container' ); ?></label></p>
                    <textarea id="container_print_header" cols="120" rows="8" name="container_print_header" placeholder="WPCargo&#10;133 R. Mapa Street , Mandurriao&#10;Iloilo City, Philippines 5000"><?php echo get_option('container_print_header'); ?></textarea>
                    <p class="description"><?php esc_html_e('This content will display in the shipment container print header layout. Accepts html tags.', 'wpcargo-shipment-container' ); ?></p>
                </th>
            </tr>
            <tr>
                <th scope="row" colspan="2">
                   <p><label for="container_print_footer"><?php esc_html_e('Footer addidtional content', 'wpcargo-shipment-container' ); ?></label></p>
                    <textarea id="container_print_footer" cols="120" rows="8" name="container_print_footer" placeholder="Copyright: &copy; 2016 All Rights Reserved.&#10;www.WPCargo.com"><?php echo get_option('container_print_footer'); ?></textarea>
                    <p class="description"><?php esc_html_e('This content will display in the shipment container print footer layout. Accepts html tags.', 'wpcargo-shipment-container' ); ?></p>
                </th>
            </tr>
            <tr valign="top">
                <th scope="row" colspan="2"><h1><?php esc_html_e( 'Container Shipment List Display Settings', 'wpcargo-shipment-container' ); ?></h1></th>
            </tr>
            <tr valign="top">
                <th scope="row"><?php esc_html_e('Shipment Status to be included for adding in Shipment Container', 'wpcargo-shipment-container' ); ?></th>
                <td>
                	<?php
					if( !empty( $shipment_status ) ){
						foreach( $shipment_status as $status ){
							?><input class="wpc-checkbox" type="checkbox" name="container_assigned_shipments[]" value="<?php echo trim($status); ?>" <?php echo ( in_array( trim($status), $assigned_shipments) ) ?'checked' : '' ; ?> /> <?php echo trim($status); ?><br/><?php
						}
					}else{
						?><p><?php esc_html_e('No registered shipment status. Please add shipment status', 'wpcargo-shipment-container' ); ?> <a href="<?php echo admin_url('admin.php?page=wpcargo-settings'); ?>"><?php esc_html_e('here', 'wpcargo-shipment-container' ); ?></a></p><?php
					}
					?>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php esc_html_e('Select Shipper Field to be Display in the Shipment List', 'wpcargo-shipment-container' ); ?></th>
                <td>
                	<?php
					if( !empty( $shipper_fields ) ){
						foreach( $shipper_fields as $field ){
							?><input class="wpc-radio" type="radio" name="container_shipper_display" value="<?php echo $field['field_key']; ?>" <?php checked( $shipper_display, $field['field_key'] ); ?> /> <?php echo $field['label']; ?><br/><?php
						}
					}else{
						?><p><?php esc_html_e('No registered custom fields. Please add custom field', 'wpcargo-shipment-container' ); ?> <a href="<?php echo admin_url('admin.php?page=wpc-cf-manage-form-field'); ?>"><?php esc_html_e('here', 'wpcargo-shipment-container' ); ?></a></p><?php
					}
					?>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php esc_html_e('Select Receiver Field to be Display in the Shipment List', 'wpcargo-shipment-container' ); ?></th>
                <td>
                	<?php
					if( !empty( $receiver_fields ) ){
						foreach( $receiver_fields as $field ){
							?><input class="wpc-radio" type="radio" name="container_receiver_display" value="<?php echo $field['field_key']; ?>" <?php checked( $receiver_display, $field['field_key'] ); ?> /> <?php echo $field['label']; ?><br/><?php
						}
					}else{
						?><p><?php esc_html_e('No registered custom fields. Please add custom field', 'wpcargo-shipment-container' ); ?> <a href="<?php echo admin_url('admin.php?page=wpc-cf-manage-form-field'); ?>"><?php esc_html_e('here', 'wpcargo-shipment-container' ); ?></a></p><?php
					}
					?>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row" colspan="2"><h1><?php esc_html_e( 'Container Shipment Manifest Settings', 'wpcargo-shipment-container' ); ?></h1></th>
            </tr>
            <tr valign="top">
                <td scope="row" colspan="2">
                    <h2><?php esc_html_e('Select field to display in shipments table', 'wpcargo-shipment-container' ); ?></h2>
                    <p class="description"><?php esc_html_e('Selected fields will be display in the manifest shipment table list.', 'wpcargo-shipment-container' ); ?></p>
                    <section id="manifest-shipper-fields" class="field-section">
                        <?php 
                            if( !empty( $shipper_fields ) ){
                                ?><ul><?php
                                foreach ($shipper_fields as $value ) {
                                    ?><li><input type="checkbox" name="container_field_manifest[]" value="<?php echo $value['id']; ?>" <?php echo in_array( $value['id'], $manifest_fields ) ? 'checked' : '' ?>><?php echo $value['label'] ?></li><?php
                                }
                                ?></ul><?php
                            }
                        ?>
                    </section>
                    <section id="manifest-receiver-fields" class="field-section">
                        <?php 
                            if( !empty( $receiver_fields ) ){
                                ?><ul><?php
                                foreach ($receiver_fields as $value ) {
                                    ?><li><input type="checkbox" name="container_field_manifest[]" value="<?php echo $value['id']; ?>" <?php echo in_array( $value['id'], $manifest_fields ) ? 'checked' : '' ?>><?php echo $value['label'] ?></li><?php
                                }
                                ?></ul><?php
                            }
                        ?>
                    </section>
                    <section id="manifest-shipments-fields" class="field-section">
                        <?php 
                            if( !empty( $shipment_fields ) ){
                                ?><ul><?php
                                foreach ($shipment_fields as $value ) {
                                    ?><li><input type="checkbox" name="container_field_manifest[]" value="<?php echo $value['id']; ?>" <?php echo in_array( $value['id'], $manifest_fields ) ? 'checked' : '' ?>><?php echo $value['label'] ?></li><?php
                                }
                                ?></ul><?php
                            }
                        ?>
                    </section>
					<?php do_action( 'wpc_additional_section', $manifest_fields ); ?>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php esc_html_e('Paper Size', 'wpcargo-shipment-container' ); ?></th>
                <td>
                    <select name="container_manifest_size">
                        <option value="A2" <?php echo selected( get_option('container_manifest_size'), 'A2'); ?>>A2</option>
                        <option value="A3" <?php echo selected( get_option('container_manifest_size'), 'A3'); ?>>A3</option>
                        <option value="A4" <?php echo selected( get_option('container_manifest_size'), 'A4'); ?>>A4</option>
                    </select>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php esc_html_e('Paper Orientation', 'wpcargo-shipment-container' ); ?></th>
                <td>
                    <select name="container_manifest_orient">
                        <option value="portrait" <?php echo selected( get_option('container_manifest_orient'), 'portrait'); ?>><?php esc_html_e('Portrait', 'wpcargo-shipment-container' ); ?></option>
                        <option value="landscape" <?php echo selected( get_option('container_manifest_orient'), 'landscape'); ?>><?php esc_html_e('Landscape', 'wpcargo-shipment-container' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row" colspan="2">
                    <p><label for="container_manifest_acknowledge"><?php esc_html_e('Manifest Acknowledgemant', 'wpcargo-shipment-container' ); ?></label></p>
                    <textarea id="container_manifest_acknowledge" cols="120" rows="4" name="container_manifest_acknowledge" placeholder="<?php esc_html_e('I acknowledge that all information in the shipping manifest is true. Information falsification is a serious offense and may result in immediate dismissal/fines.', 'wpcargo-shipment-container' ); ?> "><?php echo get_option('container_manifest_acknowledge'); ?></textarea>
                    <p class="description"><?php esc_html_e('This content will display in the container Acknowledgement section layout. Accepts html tags.', 'wpcargo-shipment-container' ); ?></p>
                </th>
            </tr>
        </tbody>
    </table>
    <input class="primary button-primary" type="submit" name="submit" value="<?php esc_html_e('Save Container Settings', 'wpcargo-shipment-container' ); ?>" />
</form>