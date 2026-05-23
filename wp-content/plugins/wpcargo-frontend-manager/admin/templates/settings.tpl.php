<div class="postbox">
    <div class="inside">
        <form method="POST" action="options.php">
            <?php settings_fields( 'wpcfe_settings_group' ); ?>
            <?php do_settings_sections( 'wpcfe_settings_group' ); ?>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e('Number of Days Filtered', 'wpcargo-frontend-manager' ); ?></th>
                    <td>
                        <input type="number" min="0" name="wpcfe_date_range_filter" value="<?php echo wpcfe_date_range_filter(); ?>" required="">
                        <p class="description"><?php esc_html_e('Note: This is the number of days for the date range to be filtered and displayed in the shipments list table.', 'wpcargo-frontend-manager' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Enable Registration Approval', 'wpcargo-frontend-manager' ); ?></th>
                    <td>
                        <input type="checkbox" name="wpcfe_approval_registration" value="1" <?php echo checked( wpcfe_approval_registration(), 1 ) ?>>
                        <p class="description"><?php esc_html_e('Note: This will need the registration to approve the account.', 'wpcargo-frontend-manager' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Disable Registration', 'wpcargo-frontend-manager' ); ?></th>
                    <td>
                        <input type="checkbox" name="wpcfe_disable_registration" value="1" <?php echo checked( wpcfe_disable_registration(), 1 ) ?>>
                        <p class="description"><?php esc_html_e('Note: This will remove member registration functionality in the login page.', 'wpcargo-frontend-manager' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Disable Create Order', 'wpcargo-frontend-manager' ); ?></th>
                    <td>
                        <input type="checkbox" name="wpcfe_add_shipment_deactivated" value="1" <?php echo checked( $add_shipment_deactivated, 1 ) ?>>
                        <p class="description"><?php esc_html_e('Note: This will remove the Create Shipment functionality in the front-end dashboard.', 'wpcargo-frontend-manager' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Allow Employee Access all Shipment?', 'wpcargo-frontend-manager' ); ?></th>
                    <td>
                        <input type="checkbox" name="wpcfe_employee_all_access" value="1" <?php echo checked( $wpcfe_employee_all_access, 1 ) ?>>
                        <p class="description"><?php esc_html_e('Note: This will allow wpcargo employee access all the shipment.', 'wpcargo-frontend-manager' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Allow Client to Add Shipment?', 'wpcargo-frontend-manager' ); ?></th>
                    <td>
                        <input type="checkbox" name="wpcfe_client_can_add_shipment" value="1" <?php echo checked( $wpcfe_client_can_add_shipment, 1 ) ?>>
                        <p class="description"><?php esc_html_e('Note: This will allow wpcargo client to add shipment in the dashboard page.', 'wpcargo-frontend-manager' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Set default shipment status', 'wpcargo-frontend-manager' ); ?></th>
                    <td>
                        <?php
                        if( !empty($status) ){
                            ?><select id="wpfe-admin" name="wpcfe_default_status" class="wpcfe-select" style="width:360px;"><?php
                                ?><option value=""><?php esc_html_e('Select Status', 'wpcargo-frontend-manager' ); ?></option><?php
                                foreach ( $status as $stat ) {
									?><option value="<?php echo $stat; ?>" <?php echo selected( $wpcfe_default_status, $stat ); ?>><?php echo $stat; ?></option><?php
							    }
                            ?></select><?php
                        }else{
                            esc_html_e('NO Status found.', 'wpcargo-frontend-manager' );
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Set Page as Frontend Dashboard admin', 'wpcargo-frontend-manager' ); ?></th>
                    <td>
                        <?php
                        if( !empty($pages) ){
                            ?><select id="wpfe-admin" name="wpcfe_admin" class="wpcfe-select" style="width:360px;"><?php
                                ?><option value=""><?php esc_html_e('Select Page', 'wpcargo-frontend-manager' ); ?></option><?php
                                foreach ( $pages as $page ) {
									?><option value="<?php echo $page->ID; ?>" <?php echo selected( $wpcfe_admin, $page->ID ); ?>><?php echo $page->post_title; ?></option><?php
							    }
                            ?></select><?php
                        }else{
                            esc_html_e('NO pages found.', 'wpcargo-frontend-manager' );
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Access Frontend Dashboard Roles', 'wpcargo-frontend-manager' ); ?></th>
                    <td>
                        <select class="wpcfe-select" name="wpcfe_access_dashboard_role[]" multiple="multiple" style="width:360px;">
                            <?php
                            if( !empty( $roles ) ){
                                foreach ($roles as $_key => $_value) {
                                    ?><option value="<?php echo $_key; ?>" <?php echo in_array( $_key, $access_dashboard_role ) ? 'selected' : '' ; ?>><?php echo $_value; ?></option><?php
                                }
                            }
                            ?>
                        </select>
                        <p class="description"><?php esc_html_e('Note: This options applicable only in front end dashboard.', 'wpcargo-frontend-manager'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Update Shipments Roles', 'wpcargo-frontend-manager' ); ?></th>
                    <td>
                        <select class="wpcfe-select" name="wpcfe_update_shipment_role[]" multiple="multiple" style="width:360px;">
                            <?php
                            if( !empty( $roles ) ){
                                foreach ($roles as $_key => $_value) {
                                    ?><option value="<?php echo $_key; ?>" <?php echo in_array( $_key, $update_shipment_role ) ? 'selected' : '' ; ?>><?php echo $_value; ?></option><?php
                                }
                            }
                            ?>
                        </select>
                        <p class="description"><?php esc_html_e('Note: This options applicable only in front end dashboard.', 'wpcargo-frontend-manager'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Delete Shipments Roles', 'wpcargo-frontend-manager'); ?></th>
                    <td>
                        <select class="wpcfe-select" name="wpcfe_delete_shipment_role[]" multiple="multiple" style="width:360px;">
                            <?php
                            if( !empty( $roles ) ){
                                foreach ($roles as $_key => $_value) {
                                    ?><option value="<?php echo $_key; ?>" <?php echo in_array( $_key, $delete_shipment_role ) ? 'selected' : '' ; ?>><?php echo $_value; ?></option><?php
                                }
                            }
                            ?>
                        </select>
                        <p class="description"><?php esc_html_e('Note: This options applicable only in front end dashboard.', 'wpcargo-frontend-manager'); ?></p>
                    </td>
                </tr>
            </table>
            <?php require_once( WPCFE_PATH.'admin/templates/sequence-settings.tpl.php' ); ?>
            <?php require_once( WPCFE_PATH.'admin/templates/print-settings.tpl.php' ); ?>
            <?php require_once( WPCFE_PATH.'admin/templates/address-mapping.tpl.php' ); ?>
            <input class="primary button-primary" type="submit" name="submit" value="<?php esc_html_e('Save Settings', 'wpcargo-frontend-manager' ); ?>" />
        </form>
    </div>
</div>