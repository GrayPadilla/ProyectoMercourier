<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
function wpcumanage_activate_license(){
	return __( 'Please activate your license key for WPCargo User Management Addon', 'wpcargo-umanagement').' <a href="'.admin_url().'admin.php?page=wptaskforce-helper" title="WPCargo User Management Addon">'.__( 'Activate', 'wpcargo-umanagement').'</a>.';
}
function wpcumanage_deactivate_error_message(){
    return __('Deactivate user failed.', 'wpcargo-umanagement' );
}
function wpcumanage_deactivate_success_message(){
    return __('Deactivate user success.', 'wpcargo-umanagement' );
}
function wpcumanage_save_user_success_message(){
    return __('New user successfully created.', 'wpcargo-umanagement' );
}
function wpcumanage_update_user_success_message(){
    return __('User updated successfully.', 'wpcargo-umanagement' );
}
function wpcumanage_users_label(){
    return __('Users', 'wpcargo-umanagement' );
}
function wpcumanage_add_user_label(){
    return __('Add New User', 'wpcargo-umanagement' );
}
function wpcumanage_user_group_label(){
    return __('Group', 'wpcargo-umanagement' );
}
function wpcumanage_role_editor_label(){
    return __('Role Editor', 'wpcargo-umanagement' );
}
function wpcumanage_page_label(){
    return apply_filters( 'wpcumanage_page_label', __('Page', 'wpcargo-umanagement' ) );
}
function wpcumanage_group_name_label(){
    return apply_filters( 'wpcumanage_group_name_label', __('Group Name', 'wpcargo-umanagement' ) );
}
function wpcumanage_description_label(){
    return apply_filters( 'wpcumanage_description_label', __('Description', 'wpcargo-umanagement' ) );
}
function wpcumanage_action_label(){
    return apply_filters( 'wpcumanage_action_label', __('Actions', 'wpcargo-umanagement' ) );
}
function wpcumanage_update_group_label(){
    return apply_filters( 'wpcumanage_update_group_label', __('Update User Group', 'wpcargo-umanagement' ) );
}
function wpcumanage_add_group_label(){
    return apply_filters( 'wpcumanage_add_group_label', __('Add Group', 'wpcargo-umanagement' ) );
}
function wpcumanage_update_label(){
    return apply_filters( 'wpcumanage_update_label', __('Update', 'wpcargo-umanagement' ) );
}
function wpcumanage_delete_label(){
    return apply_filters( 'wpcumanage_delete_label', __('Delete', 'wpcargo-umanagement' ) );
}
