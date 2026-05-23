<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
function wpcargo_get_users_by_role_array($role = 'cargo_agent')
{
    $args = array(
        'role'    => $role,
        'fields'  => array('ID', 'display_name')
    );

    $user_query = new WP_User_Query($args);
    $users_array = array();

    if (! empty($user_query->results)) {
        foreach ($user_query->results as $user) {
            $user_data = get_userdata($user->ID);
            $users_array[$user->ID] = $user->display_name;
        }
    }

    return $users_array;
}

function wpc_container_info_fields()
{
    global $wpcargo;
    $agents = wpcargo_get_users_by_role_array('cargo_agent');
    $container_info_fields = array(
        'container_no' => array(
            'id'            => 'container_no',
            'label'         => __('Flight/Container No.', 'wpcargo-shipment-container'),
            'field'         => 'text',
            'field_type'    => 'text',
            'required'      => false,
            'options'       => array(),
            'field_data'    => array(),
            'field_key'     => 'container_no',
            'classes'       => ''
        ),
        'container_agent' => array(
            'id'            => 'container_agent',
            'label'         => __('Agent Name', 'wpcargo-shipment-container'),
            'field'         => 'select',
            'field_type'    => 'select',
            'required'      => false,
            'options'       => $agents,
            'field_data'    => $agents,
            'field_key'     => 'container_agent',
            'classes'       => ''
        ),
        'container_tel' => array(
            'id'            => 'container_tel',
            'label'         => __('Telephone', 'wpcargo-shipment-container'),
            'field'         => 'text',
            'field_type'    => 'text',
            'required'      => false,
            'options'       => array(),
            'field_data'    => array(),
            'field_key'     => 'container_tel',
            'classes'       => ''
        ),
        'passport' => array(
            'id'            => 'passport',
            'label'         => __('Passport', 'wpcargo-shipment-container'),
            'field'         => 'text',
            'field_type'    => 'text',
            'required'      => false,
            'options'       => array(),
            'field_data'    => array(),
            'field_key'     => 'passport',
            'classes'       => ''
        ),
    );
    return apply_filters('wpc_container_info_fields', $container_info_fields);
}
function wpc_trip_info_fields()
{
    $container_info_fields = array(
        'origin' => array(
            'id'            => 'origin',
            'label'         => __('Origin', 'wpcargo-shipment-container'),
            'field'         => 'text',
            'field_type'    => 'text',
            'required'      => false,
            'options'       => array(),
            'field_data'    => array(),
            'field_key'     => 'origin',
            'classes'       => ''
        ),
        'destination' => array(
            'id'            => 'destination',
            'label'         => __('Destination', 'wpcargo-shipment-container'),
            'field'         => 'text',
            'field_type'    => 'text',
            'required'      => false,
            'options'       => array(),
            'field_data'    => array(),
            'field_key'     => 'destination',
            'classes'       => ''
        ),
        'delivery_agent' => array(
            'id'            => 'delivery_agent',
            'label'         => __('Driver', 'wpcargo-shipment-container'),
            'field'         => 'select',
            'field_type'    => 'select',
            'required'      => false,
            'options'       => wpcargo_container_get_drivers(),
            'field_data'    => wpcargo_container_get_drivers(),
            'field_key'     => 'delivery_agent',
            'classes'       => ''
        ),
        'delivery_tel' => array(
            'id'            => 'delivery_tel',
            'label'         => __('Telephone', 'wpcargo-shipment-container'),
            'field'         => 'text',
            'field_type'    => 'text',
            'required'      => false,
            'options'       => array(),
            'field_data'    => array(),
            'field_key'     => 'delivery_tel',
            'classes'       => ''
        ),
    );
    return apply_filters('wpc_trip_info_fields', $container_info_fields);
}
function wpc_container_history_fields()
{
    global $wpcargo;
    $history_fields = array(
        'status_date'  => array(
            'id'            => 'status_date',
            'label'         => __('Date', 'wpcargo-shipment-container'),
            'field'         => 'date',
            'field_type'    => 'date',
            'required'      => false,
            'options'       => array(),
            'field_data'    => array(),
            'field_key'     => 'status_date',
            'classes'       => ''
        ),
        'status_time' => array(
            'id'            => 'status_time',
            'label'         => __('Time', 'wpcargo-shipment-container'),
            'field'         => 'time',
            'field_type'    => 'time',
            'required'      => false,
            'options'       => array(),
            'field_data'    => array(),
            'field_key'     => 'status_time',
            'classes'       => ''
        ),
        'status_location' => array(
            'id'            => 'status_location',
            'label'         => __('Current City', 'wpcargo-shipment-container'),
            'field'         => 'text',
            'field_type'    => 'text',
            'required'      => false,
            'options'       => array(),
            'field_data'    => array(),
            'field_key'     => 'status_location',
            'classes'       => ''
        ),
        'update_status' => array(
            'id'            => 'update_status',
            'label'         => __('Status', 'wpcargo-shipment-container'),
            'field'         => 'select',
            'field_type'    => 'select',
            'required'      => false,
            'options'       => $wpcargo->status,
            'field_data'    => $wpcargo->status,
            'field_key'     => 'update_status',
            'classes'       => ''
        ),
        'updated_by' => array(
            'id'            => 'updated_by',
            'label'         => __('Updated By', 'wpcargo-shipment-container'),
            'field'         => 'text',
            'field_type'    => 'text',
            'required'      => false,
            'options'       => array(),
            'field_data'    => array(),
            'field_key'     => 'updated_by',
            'classes'       => ''
        ),
        'status_remarks' => array(
            'id'            => 'status_remarks',
            'label'         => __('Remarks', 'wpcargo-shipment-container'),
            'field'         => 'textarea',
            'field_type'    => 'textarea',
            'required'      => false,
            'options'       => array(),
            'field_data'    => array(),
            'field_key'     => 'status_remarks',
            'classes'       => ''
        ),
    );
    return apply_filters('wpc_container_history_fields', $history_fields);
}
function wpc_time_info_fields()
{
    $travel_mode_option = trim(get_option('travel_mode'));
    $travel_mode = array();
    if (!empty($travel_mode_option)) {
        $travel_mode = explode(",", $travel_mode_option);
        $travel_mode = array_map('trim', $travel_mode);
        $travel_mode = array_filter($travel_mode);
    }
    $container_info_fields = array(
        'date' => array(
            'id'            => 'date',
            'label'         => __('Date', 'wpcargo-shipment-container'),
            'field'         => 'text',
            'field_type'    => 'date',
            'required'      => false,
            'options'       => array(),
            'field_data'    => array(),
            'field_key'     => 'date',
            'classes'       => ''
        ),
        'time' => array(
            'id'            => 'time',
            'label'         => __('Time', 'wpcargo-shipment-container'),
            'field'         => 'text',
            'field_type'    => 'time',
            'required'      => false,
            'options'       => array(),
            'field_data'    => array(),
            'field_key'     => 'time',
            'classes'       => ''
        ),
        'expected_date' => array(
            'id'            => 'expected_date',
            'label'         => __('Expected Date', 'wpcargo-shipment-container'),
            'field'         => 'text',
            'field_type'    => 'date',
            'required'      => false,
            'options'       => array(),
            'field_data'    => array(),
            'field_key'     => 'expected_date',
            'classes'       => ''
        ),
        'travel_mode' => array(
            'id'            => 'travel_mode',
            'label'         => __('Travel Mode', 'wpcargo-shipment-container'),
            'field'         => 'select',
            'field_type'    => 'select',
            'required'      => false,
            'options'       => $travel_mode,
            'field_data'    => $travel_mode,
            'field_key'     => 'travel_mode',
            'classes'       => ''
        ),
    );
    return apply_filters('wpc_time_info_fields', $container_info_fields);
}
