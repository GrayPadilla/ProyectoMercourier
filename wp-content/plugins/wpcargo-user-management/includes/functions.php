<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
// Helpers
function wpcumanage_get_users($role)
{
    global $wpcargo;
    $users_list = array();
    $args = array('role__in' => $role);
    $users = get_users($args);
    if (!empty($users)) {
        foreach ($users  as $driver) {
            $users_list[$driver->ID] = $wpcargo->user_fullname($driver->ID);
        }
    }
    asort($users_list);
    return $users_list;
}
function wpcumanage_get_branch_managers()
{
    global $wpcargo;
    if (!function_exists('wpcbm_get_all_branch')) {
        return array();
    }
    $managers = array();
    $branches = wpcbm_get_all_branch(-1);
    if (!$branches) {
        return array();
    }
    foreach ($branches as $branch) {
        $branch_manager = maybe_unserialize($branch->branch_manager);
        $manager        = is_array($branch_manager) && !empty($branch_manager) ? $branch_manager : null;
        $manager_opt    = array();
        if ($manager) {
            foreach ($manager as $mid) {
                $manager_opt[$mid] = $wpcargo->user_fullname($mid);
            }
        }
        $managers[$branch->id] = array_filter($manager_opt);
    }
    return array_filter($managers);
}
function wpcumanage_assignment_fields()
{
    $fields = array(
        '__default_client' => array(
            'label' => __('Default Client', 'wpcargo-umanagement'),
            'type' => 'select',
            'options' => wpcumanage_get_users(array('wpcargo_client')),
            'required' => false,
            'target_name' => 'registered_shipper',
            'target_role' => 'wpcargo_client'
        ),
        '__default_agent' => array(
            'label' => __('Default Agent', 'wpcargo-umanagement'),
            'type' => 'select',
            'options' => wpcumanage_get_users(array('cargo_agent')),
            'required' => false,
            'target_name' => 'agent_fields',
            'target_role' => 'cargo_agent'
        ),
        '__default_employee' => array(
            'label' => __('Default Employee', 'wpcargo-umanagement'),
            'type' => 'select',
            'options' => wpcumanage_get_users(array('wpcargo_employee')),
            'required' => false,
            'target_name' => 'wpcargo_employee',
            'target_role' => 'wpcargo_employee'
        )
    );
    return apply_filters('wpcumanage_assignment_fields', $fields);
}
function wpcumanage_driver_assignment_fields_callback($fields)
{
    if (class_exists('WPC_POD_Signature_Metabox')) {
        $fields['__default_driver'] = array(
            'label' => __('Default Driver', 'wpcargo-umanagement'),
            'type' => 'select',
            'options' => wpcumanage_get_users(array('wpcargo_driver')),
            'required' => false,
            'target_name' => 'wpcargo_driver',
            'target_role' => 'wpcargo_driver'
        );
    }
    return $fields;
}
function wpcumanage_branch_assignment_fields_callback($fields)
{
    if (class_exists('WPC_Branch_Manager')) {
        $all_branch = wpcbm_get_all_branch(-1);
        if (!$all_branch) {
            return $fields;
        }
        $branch_options = array();
        foreach ($all_branch as $branch) {
            $branch_options[$branch->id] = $branch->name;
        }
        $branch_fields = array(
            '__default_branch' => array(
                'label'         => __('Default Branch', 'wpcargo-umanagement'),
                'type'          => 'select',
                'options'       => $branch_options,
                'required'      => false,
                'target_name'   => 'shipment_branch',
                'target_role'   => 'shipment_branch'
            ),
            '__default_branch_manager' => array(
                'label'         => __('Default Branch Manager', 'wpcargo-umanagement'),
                'type'          => 'select',
                'options'       => array(),
                'required'      => false,
                'attributes'    => 'readonly',
                'target_name'   => 'wpcargo_branch_manager',
                'target_role'   => 'wpcargo_branch_manager'
            )
        );
        $fields = $branch_fields + $fields;
    }
    return $fields;
}
function wpcumanage_field_generator($meta_key, $field, $class = "")
{
    $required  = $field['required'] == 'true' ? 'required' : '';
    $attr      = array_key_exists('attributes', $field) ? $field['attributes'] : '';
    $tpl_class = $field['type'] == 'radio' || $field['type'] == 'checkbox' ? 'form-check' : 'form-group';
    $template = '<div class="' . $tpl_class . '" >';
    $template .= '<label for="' . $meta_key . '" >' . $field['label'] . '</label>';
    if ($field['type'] == 'textarea') {
        $template .= '<textarea id="' . $meta_key . '" class="form-control ' . $class . '" name="' . $meta_key . '" ' . $required . ' ' . $attr . '></textarea>';
    } elseif ($field['type'] == 'select') {
        $template .= '<select id="' . $meta_key . '" class="form-control browser-default custom-select ' . $class . '" name="' . $meta_key . '" ' . $required . ' ' . $attr . '>';
        $template .= '<option value="">' . esc_html__('-- Select User--', 'wpcargo-umanagement') . '</option>';
        if (!empty($field['options'])) {
            foreach ($field['options'] as $_key => $_value) {
                $template .= '<option value="' . $_key . '">' . trim($_value) . '</option>';
            }
        }
        $template .= '</select>';
    } elseif ($field['type'] == 'radio') {
        if (!empty($field['options'])) {
            foreach ($field['options'] as $_key => $_value) {
                $template .= '<p><input class="form-check-input ' . $class . '" id="' . $meta_key . '_' . $_key . '" type="radio" name="' . $meta_key . '" value="' . $_key . '" ' . $required . '>';
                $template .= '<label for="' . $meta_key . '_' . $_key . '">' . $_value . '</label></p>';
            }
        }
    } elseif ($field['type'] == 'checkbox') {
        if (!empty($field['options'])) {
            foreach ($field['options'] as $_key => $_value) {
                $template .= '<p><input class="form-check-input ' . $class . '" id="' . $meta_key . '_' . $_key . '" type="checkbox" name="' . $meta_key . '" value="' . $_key . '" ' . $required . '>';
                $template .= '<label for="' . $meta_key . '_' . $_key . '">' . $_value . '</label></p>';
            }
        }
    } else {
        $template .= '<input id="' . $meta_key . '" class="form-control ' . $class . '" type="text" name="' . $meta_key . '" ' . $required . ' ' . $attr . '>';
    }
    $template .= '</div>';
    echo $template;
}
function wpcumanage_registered_roles()
{
    $roles = array(
        'wpc_shipment_manager',
        'wpcargo_employee',
        'wpcargo_branch_manager',
        'cargo_agent',
        'wpcargo_driver',
        'wpc_cashier',
        'wpcargo_client',
        'wpcargo_pending_client'
    );
    return apply_filters('wpcumanage_registered_roles', $roles);
}
function wpcumanage_default_users($user_id)
{
    $user_defaults = array();
    $assignments   = array_keys(wpcumanage_assignment_fields());
    if (!empty($assignments)) {
        foreach ($assignments as $assign_key) {
            $assigned_user = get_user_meta($user_id, $assign_key, true);
            if (!(int)$assigned_user) {
                continue;
            }
            $user_defaults[$assign_key] =  $assigned_user;
        }
    }
    return apply_filters('wpcumanage_default_users', $user_defaults, $user_id);
}
function wpcum_access_module_role()
{
    $options        = get_option('wpcargo_option_settings') ?: array();
    $access_dashboard_role = $options['acces_um_role'] ? $options['acces_um_role'] : array('administrator', 'wpcargo_employee');
    if (!in_array('administrator', $access_dashboard_role)) {
        $access_dashboard_role[] = 'administrator';
    }
    return $access_dashboard_role;
}

function wpcum_update_module_role()
{
    $options        = get_option('wpcargo_option_settings') ?: array();
    $access_dashboard_role = $options['acces_um_role'] ? $options['acces_um_role'] : array('administrator', 'wpcargo_employee');
    if (!in_array('administrator', $access_dashboard_role)) {
        $access_dashboard_role[] = 'administrator';
    }
    return $access_dashboard_role;
}


function can_wpcumanage_access()
{
    $current_user   = wp_get_current_user();
    $user_roles     = $current_user->roles;
    $roles          = wpcum_access_module_role();
    $allowed_roles  = apply_filters('can_wpcumanage_access_roles', $roles);
    if (array_intersect($user_roles, $allowed_roles)) {
        return true;
    }
    return false;
}

function can_wpcumanage_add()
{
    $current_user   = wp_get_current_user();
    $user_roles     = $current_user->roles;
    $allowed_roles  = apply_filters('can_wpcumanage_add_roles', array('administrator'));
    if (array_intersect($user_roles, $allowed_roles)) {
        return true;
    }
    return false;
}
function can_wpcumanage_update()
{
    $current_user   = wp_get_current_user();
    $roles          = wpcum_update_module_role();
    $allowed_roles  = apply_filters('can_wpcumanage_access_roles', $roles);
    if (array_intersect($roles, $allowed_roles)) {
        return true;
    }
    return false;
}
function can_wpcumanage_delete()
{
    $current_user   = wp_get_current_user();
    $user_roles     = $current_user->roles;
    $allowed_roles  = apply_filters('can_wpcumanage_delete_roles', array('administrator'));
    if (array_intersect($user_roles, $allowed_roles)) {
        return true;
    }
    return false;
}
function wpcumanage_locate_template($file_name)
{
    $file_slug              = strtolower(preg_replace('/\s+/', '_', trim(str_replace('.tpl', '', $file_name))));
    $file_slug              = preg_replace('/[^A-Za-z0-9_]/', '_', $file_slug);
    $custom_template_path   = get_stylesheet_directory() . '/wpcargo/wpcargo-user-management/' . $file_name . '.php';
    if (file_exists($custom_template_path)) {
        $template_path = $custom_template_path;
    } else {
        $template_path  = WPCU_MANAGEMENT_PATH . 'templates/' . $file_name . '.php';
        $template_path  = apply_filters("wpcumanage_locate_template_{$file_slug}", $template_path);
    }
    return $template_path;
}
function wpcumanage_admin_locate_template($file_name)
{
    $file_slug              = strtolower(preg_replace('/\s+/', '_', trim(str_replace('.tpl', '', $file_name))));
    $file_slug              = preg_replace('/[^A-Za-z0-9_]/', '_', $file_slug);
    $custom_template_path   = get_stylesheet_directory() . '/wpcargo/wpcargo-user-management/' . $file_name . '.php';
    if (file_exists($custom_template_path)) {
        $template_path = $custom_template_path;
    } else {
        $template_path  = WPCU_MANAGEMENT_PATH . 'admin/templates/' . $file_name . '.php';
        $template_path  = apply_filters("wpcumanage_admin_locate_template_{$file_slug}", $template_path);
    }
    return $template_path;
}
function wpcumanage_user_data($user_data)
{
    $user_meta = array_merge(wpcfe_personal_info_fields(), wpcfe_billing_address_fields());
    try {
        if (!$user_data) {
            throw new ErrorException('User NOT found!');
        }

        if (!empty($user_data)) {
            if (!empty($user_meta)) {
                foreach ($user_meta as $meta_key => $meta_value) {
                    $meta_key = $meta_key == 'phone' ? 'billing_phone' : $meta_key;
                    $user_data->$meta_key = get_user_meta($user_data->ID, $meta_key, true);
                }
            }
            $user_groups = get_user_meta($user_data->ID, 'user_groups', true);
            $user_groups = !empty($user_groups) ? $user_groups : array();
            $user_data->groups = $user_groups;
        }
        return $user_data;
    } catch (Exception $e) {
        return new WP_Error('user', $e->getMessage());
    }
}
function wpcumanage_user_formatted_data($user_data)
{
    $user_meta = array_merge(wpcfe_personal_info_fields(), wpcfe_billing_address_fields());
    if (!empty($user_data)) {
        return $user_data;
    }
    $user_data = new stdClass;
    $user_data->data  = new stdClass;
    if (!empty($user_meta)) {
        foreach ($user_meta as $meta_key => $meta_value) {
            $meta_key = $meta_key == 'phone' ? 'billing_phone' : $meta_key;
            $user_data->data->$meta_key = '';
        }
    }
    $user_data->roles = array();
    return $user_data;
}
function wpcumanage_generate_template($fields, $user_data, $update = false)
{
    $required_fields = array('first_name', 'last_name', 'billing_email');
    foreach ($fields as $field): ?>
        <?php
        // Force field to be required            
        if (in_array($field['field_key'], $required_fields)) {
            $field['required'] = true;
        }
        $field_key = $field['field_key'] == 'phone' ? 'billing_phone' : $field['field_key'];
        ?>
        <?php if ($field_key == 'billing_email' && $update) continue; ?>
        <div class="form-group col-md-6">
            <?php $select = $field['field_type'] == 'select' ? 'browser-default' : ''; ?>
            <label for="<?php echo $field['field_key']; ?>"><?php echo $field['label']; ?></label>
            <?php
            $__keys = array_keys((array)$user_data->data);
            $field_value = "";
            if (!empty($user_data)) {
                if ($field['field_key'] == 'email') {
                    $field_value = (in_array('user_email', $__keys)) ? $user_data->data->user_email : '';
                } elseif ($field['field_key'] == 'phone') {
                    $field_value = (in_array('billing_phone', $__keys)) ? $user_data->data->{$field_key} : '';
                } else {
                    $field_value = (in_array($field['field_key'], $__keys)) ? $user_data->data->{$field_key} : '';
                }
            }
            echo wpcargo_field_generator($field, $field['field_key'], $field_value, 'form-control ' . $select);
            ?>
        </div>
    <?php
    endforeach;
}
function wpcumanage_access_list()
{
    $access = array(
        'add'       => __('Add Shipment', 'wpcargo-umanagement'),
        'update'    => __('Update Shipment', 'wpcargo-umanagement'),
        'delete'    => __('Delete Shipment', 'wpcargo-umanagement'),
        'invoice'   => __('Print Invoice', 'wpcargo-umanagement'),
        'label'     => __('Print Label', 'wpcargo-umanagement'),
        'waybill'   => __('Print Waybill', 'wpcargo-umanagement'),
        'bol'       => __('Print BOL', 'wpcargo-umanagement'),
        'assign_client'   => __('Assign Client', 'wpcargo-umanagement'),
        'assign_agent'    => __('Assign Agent', 'wpcargo-umanagement'),
        'assign_employee'   => __('Assign Employee', 'wpcargo-umanagement')
    );
    return apply_filters('wpcumanage_access_list', $access);
}
function wpcumanage_access_label($access)
{
    if (!is_array($access)) {
        return array();
    }
    return array_map(function ($value) {
        return wpcumanage_access_list()[$value];
    }, $access);
}
function wpcumanage_user_access($user_id)
{
    return get_user_meta($user_id, '_wpcargo_access', true);
}
// Callbacks
function wpcumanage_dashboard_side_menu()
{
    if (!can_wpcumanage_access()) {
        return false;
    }
    $users_page = wpcumanage_users_page();
    ?>
    <a href="<?php echo get_the_permalink($users_page); ?>" class="list-group-item wpcargo_umanage-page <?php echo get_the_ID() == $users_page ? 'active' : ''; ?>">
        <i class="fa fas fa-users mr-md-3" aria-hidden="true"></i><?php _e('Users', 'wpcargo-umanagement'); ?>
    </a>
<?php
}
// Pagination function
function wpcumanage_pagination($pagelink, $numpages, $paged)
{
    $pagination_args = array(
        'base' => $pagelink . '%_%',
        'format' => '&wpcumanage_page=%#%',
        'total' => $numpages,
        'current' => $paged,
        'show_all' => false,
        'end_size' => 1,
        'mid_size' => 4,
        'prev_next' => true,
        'prev_text' => '&laquo;',
        'next_text' => '&raquo;',
        'type' => 'plain',
        'add_args' => false,
        'add_fragment' => ''
    );
    $paginate_links  = paginate_links($pagination_args);
    if ($paginate_links) {
        echo "<nav class='wpcsr-pagination'>";
        echo "<span class='page-numbers page-num'>" . wpcumanage_page_label() . " " . $paged . " / " . $numpages . "</span> ";
        echo $paginate_links;
        echo "</nav>";
    }
}
function wpumanage_user_group_table()
{
    global $wpdb;
    return $wpdb->prefix . WPCU_MANAGEMENT_DB_USER_GROUP;
}
function wpcumanage_get_user_group_by_id($id)
{
    global $wpdb;
    if (!$id) {
        return false;
    }
    $user_group_table = wpumanage_user_group_table();
    $users_group = $wpdb->get_row("SELECT * FROM {$user_group_table} WHERE `user_group_id` = {$id}", ARRAY_A);
    $obj_data = new stdClass;
    if ($users_group) {
        foreach ($users_group as $key => $value) {
            $obj_data->$key = stripcslashes($value);
        }
    }
    return $obj_data;
}
function wpcumanage_get_users_group()
{
    global $wpdb;
    $user_group_table = wpumanage_user_group_table();
    $users_group = $wpdb->get_results("SELECT * FROM {$user_group_table}");
    return $users_group;
}
function wpcumanage_get_group_id_by_userid($user_id)
{
    global $wpdb;
    $user_group_table = wpumanage_user_group_table();
    $user_ids = $wpdb->get_var("SELECT user_group_id FROM {$user_group_table} WHERE users LIKE '%{$user_id}%'");
    return $user_ids;
}
function wpcumanage_get_all_user_group_ids()
{
    global $wpdb;
    $user_group_table = wpumanage_user_group_table();
    $user_group_ids = $wpdb->get_col("SELECT `user_group_id` FROM {$user_group_table}");
    return $user_group_ids;
}
function wpcumanage_get_all_user_group_id_and_label()
{
    global $wpdb;
    $user_group_table = wpumanage_user_group_table();
    $results = $wpdb->get_results("SELECT `user_group_id`, `label` FROM {$user_group_table}");
    return $results;
}
function wpcumanage_get_group_ids_by_userid($user_id)
{
    global $wpdb;
    $user_group_table = wpumanage_user_group_table();
    $user_ids = $wpdb->get_results("SELECT user_group_id, label FROM {$user_group_table} WHERE users LIKE '%{$user_id}%' GROUP BY user_group_id");
    return $user_ids;
}
function wpcumanage_get_user_group_label($group_id)
{
    global $wpdb;
    $user_group_table = wpumanage_user_group_table();
    $group_label = $wpdb->get_var("SELECT label FROM {$user_group_table} WHERE user_group_id={$group_id}");
    return $group_label;
}
function wpcumanage_get_array_groups_ids($user_id)
{
    $user_ids = array();
    $groups = wpcumanage_get_group_ids_by_userid($user_id);
    if (!empty($groups)) {
        foreach ($groups as $group) {
            $user_ids[$group->user_group_id] = $group->label;
        }
    }
    return $user_ids;
}
function wpcumanage_get_userlist_by_group_id($group_id)
{
    global $wpdb;
    $user_group_table = wpumanage_user_group_table();
    $sql = "SELECT users FROM {$user_group_table} WHERE user_group_id={$group_id}";
    $user_ids = $wpdb->get_var($sql);
    return maybe_unserialize($user_ids);
}
function wpcumanage_get_user_branch($user_type, $user_id)
{
    global $wpdb, $wpcargo;
    $table          = $wpdb->prefix . 'wpcargo_branch';
    $tblcolumn      = "branch_" . $user_type;
    $table_columns  = $wpdb->get_col("DESC {$table}", 0);
    if (!in_array($tblcolumn, $table_columns)) {
        return null;
    }
    $sql    = $wpdb->prepare("SELECT * FROM {$table} WHERE `{$tblcolumn}` LIKE %s LIMIT 1", '%:"' . $user_id . '";%');
    return $wpdb->get_row($sql);
}
function wpcumanage_get_default_branch()
{
    global $wpdb, $wpcargo;
    $table  = $wpdb->prefix . 'wpcargo_branch';
    $sql    = apply_filters('wpcumanage_get_default_branch_sql', "SELECT * FROM {$table} ORDER BY `id` ASC LIMIT 1");
    return $wpdb->get_row($sql);
}
function wpcumanage_update_branch_assignment($shipment_id, $branch)
{
    if (!$branch) {
        return false;
    }
    $branch_manager     = maybe_unserialize($branch->branch_manager);
    $branch_agent       = maybe_unserialize($branch->branch_agent);
    $branch_employee    = maybe_unserialize($branch->branch_employee);
    $branch_driver      = maybe_unserialize($branch->branch_driver);

    // Get assigment
    $manager            = is_array($branch_manager) && !empty($branch_manager) ? $branch_manager[0] : null;
    $agent              = is_array($branch_agent) && !empty($branch_agent) ? $branch_agent[0] : null;
    $employee           = is_array($branch_employee) && !empty($branch_employee) ? $branch_employee[0] : null;
    $driver             = is_array($branch_driver) && !empty($branch_driver) ? $branch_driver[0] : null;

    // Branch Meta key - shipment_branch
    update_post_meta($shipment_id, 'shipment_branch', $branch->id);
    update_post_meta($shipment_id, 'wpcargo_branch_manager', $manager);
    update_post_meta($shipment_id, 'agent_fields', $agent);
    update_post_meta($shipment_id, 'wpcargo_driver', $driver);
    update_post_meta($shipment_id, 'wpcargo_employee', $employee);
}
function wpcumanage_assign_shipment_branch($shipment_id)
{
    if (!class_exists('WPC_Branch_Manager')) {
        return false;
    }
    // Auto assign shipment to branch
    if (!is_user_logged_in()) {
        $branch = wpcumanage_get_default_branch();
        wpcumanage_update_branch_assignment($shipment_id, $branch);
        return false;
    }
    $current_user   = wp_get_current_user();
    $user_roles     = $current_user->roles;
    $user_type      = null;
    if (in_array('wpc_shipment_manager', $user_roles)) {
        $user_type = 'manager';
    }
    if (in_array('wpcargo_employee', $user_roles)) {
        $user_type = 'employee';
    }
    if (in_array('wpcargo_driver', $user_roles)) {
        $user_type = 'driver';
    }
    if (in_array('cargo_agent', $user_roles)) {
        $user_type = 'agent';
    }
    if (in_array('wpcargo_client', $user_roles)) {
        $user_type = 'client';
    }
    $branch = wpcumanage_get_user_branch($user_type, $current_user->ID);
    if (!$branch) {
        $branch = wpcumanage_get_default_branch();
    }
    wpcumanage_update_branch_assignment($shipment_id, $branch);
}
function wpcumanage_get_all_user_groups($user_id)
{
    $group_ids = get_user_meta($user_id, 'user_groups', true);
    return is_serialized($group_ids) ? maybe_unserialize($group_ids) : array();
}

// Additional Update user fields if User Manager is active
function wpcum_personal_info_fields()
{
    $user_roles = wpcfe_current_user_role();
    $wpcfe_personal_info_fields = array();
    if (can_edit_uname_role()) {
        $wpcfe_personal_info_fields['user_login'] = array(
            'id'            => 'user_login',
            'label'            => __('Username', 'wpcargo-frontend-manager'),
            'field'            => 'text',
            'field_type'    => 'text',
            'required'        => false,
            'options'        => array(),
            'field_data'    => array(),
            'field_key'        => 'user_login'
        );
    }

    if (can_edit_email_role()) {
        $wpcfe_personal_info_fields['email'] = array(
            'id'            => 'email',
            'label'            => __('Email Address', 'wpcargo-frontend-manager'),
            'field'            => 'email',
            'field_type'    => 'email',
            'required'        => false,
            'options'        => array(),
            'field_data'    => array(),
            'field_key'        => 'email'
        );
    }
    return $wpcfe_personal_info_fields;
}

//add_filter( 'wpcfe_personal_info_fields', 'wpcum_personal_info_fields' );   


//update username / user_login
function wpcum_update_user_login($user_id, $new_user)
{
    global $wpdb;
    $wpdb->update(
        $wpdb->users,
        ['user_login' => $new_user],
        ['ID' => $user_id]
    );
}

//Can update username
function wpcum_can_edit_uname()
{
    $wpcum_can_edit_uname = array('administrator', 'wpcargo_employee');
    if (!in_array('administrator', $wpcum_can_edit_uname)) {
        $wpcum_can_edit_uname[] = 'administrator';
    }
    return apply_filters('wpcum_can_edit_uname', $wpcum_can_edit_uname);
}

function can_edit_uname_role()
{
    $user_roles     = wpcfe_current_user_role();
    $result         = false;
    if (array_intersect(wpcum_can_edit_uname(), $user_roles) || in_array('administrator', $user_roles)) {
        $result = true;
    }
    return apply_filters('can_edit_uname_role', $result);
}

function wpcum_can_edit_email()
{
    $wpcum_can_edit_email = array('administrator', 'wpcargo_employee');
    if (!in_array('administrator', $wpcum_can_edit_email)) {
        $wpcum_can_edit_email[] = 'administrator';
    }
    return apply_filters('wpcum_can_edit_email', $wpcum_can_edit_email);
}

function can_edit_email_role()
{
    $user_roles     = wpcfe_current_user_role();
    $result         = false;
    if (array_intersect(wpcum_can_edit_email(), $user_roles) || in_array('administrator', $user_roles)) {
        $result = true;
    }
    return apply_filters('can_edit_email_role', $result);
}

// Added to wpcargo hook
function wpcum_select_user_roles($options)
{
    global $wpcargo, $wp_roles, $WPCCF_Fields;
    $roles                      = $wp_roles->get_names();
    $access_module_role            = wpcum_access_module_role();
    $update_module_role            = wpcum_update_module_role();
?>
    <tr>
        <th><?php esc_html_e('Access User Management Roles', 'wpcargo-frontend-manager'); ?></th>
        <td>
            <select class="wpcfe-select" name="wpcargo_option_settings[acces_um_role][]" multiple="multiple" style="width:360px;">
                <?php
                if (!empty($roles)) {
                    foreach ($roles as $_key => $_value) {
                ?><option value="<?php echo $_key; ?>" <?php echo in_array($_key, $access_module_role) ? 'selected' : ''; ?>><?php echo $_value; ?></option><?php
                                                                                                                                                        }
                                                                                                                                                    }
                                                                                                                                                            ?>
            </select>
            <p class="description"><?php esc_html_e('Note: This options applicable only in user managemnent module.', 'wpcargo-frontend-manager'); ?></p>
        </td>
    </tr>

    <tr>
        <th><?php esc_html_e('Can Update Users Roles', 'wpcargo-frontend-manager'); ?></th>
        <td>
            <select class="wpcfe-select" name="wpcargo_option_settings[update_um_role][]" multiple="multiple" style="width:360px;">
                <?php
                if (!empty($roles)) {
                    foreach ($roles as $_key => $_value) {
                ?><option value="<?php echo $_key; ?>" <?php echo in_array($_key, $update_module_role) ? 'selected' : ''; ?>><?php echo $_value; ?></option><?php
                                                                                                                                                        }
                                                                                                                                                    }
                                                                                                                                                            ?>
            </select>
            <p class="description"><?php esc_html_e('Note: This options applicable only in user managemnent module.', 'wpcargo-frontend-manager'); ?></p>
        </td>
    </tr>
<?php
}

function wc_get_country_code_by_name($country_name)
{
    if (empty($country_name)) {
        return null;
    }

    // If user passed a 2-letter code already, normalize and return it
    $trim = trim($country_name);
    if (preg_match('/^[A-Za-z]{2}$/', $trim)) {
        return strtoupper($trim);
    }

    // Make sure WooCommerce is available
    if (! function_exists('WC') || ! WC()->countries) {
        return null;
    }

    // Build a cached name => code map for faster repeated lookups
    static $name_to_code_map = null;
    if ($name_to_code_map === null) {
        $name_to_code_map = array();
        $countries = WC()->countries->get_countries(); // returns [ 'PH' => 'Philippines', 'US' => 'United States (US)', ... ]

        foreach ($countries as $code => $label) {
            // 1) label as-is (some labels include "(US)" etc.)
            $name_to_code_map[mb_strtolower(trim($label))] = $code;

            // 2) remove trailing "(...)" part -> "United States (US)" => "United States"
            $label_no_paren = preg_replace('/\s*\(.*\)$/u', '', $label);
            $name_to_code_map[mb_strtolower(trim($label_no_paren))] = $code;
        }
    }

    $key = mb_strtolower(trim($country_name));

    // Exact normalized match
    if (isset($name_to_code_map[$key])) {
        return $name_to_code_map[$key];
    }

    // Fuzzy fallback: substring match (useful for "Korea" -> "KR", etc.)
    foreach ($name_to_code_map as $name => $code) {
        if (mb_strpos($name, $key) !== false || mb_strpos($key, $name) !== false) {
            return $code;
        }
    }

    return null;
}


function wpcfe_billing_address_fields_cb_additional($billing_fields)
{
    $billing_fields['billing_country']['required'] = true;
    return $billing_fields;
}


function wpcuser_prefix_id($user)
{
    $prefix = apply_filters("wpcuser_prefix_id", wc_get_country_code_by_name(get_user_meta($user->id, "billing_country", true)), $user);

    return $prefix ? $prefix : "";
}

function wpcuser_unique_id($user)
{
    $user_ID = apply_filters("wpcuser_unique_id", $user->ID);

    return $user_ID ? $user_ID : '';
}
