<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
// Update shipment hooks
add_action( 'plugins_loaded', 'wpcfe_initialize_hooks' );
function wpcfe_initialize_hooks(){  
    // Frontend Manager Shipment Filter Hook
    add_action( 'wpcfe_after_shipment_filters', 'wpcfe_shipment_created_date_filter_callback', 100 );
    add_filter( 'wpcfe_dashboard_arguments', 'wpcfe_shipment_created_date_query_args_callback' );
    add_action( 'wpcfe_before_shipment_table', 'wpcfe_shipment_searched_callback' );
    add_action( 'wpcfe_before_dashboard_status_report_grid', 'wpcfe_shipment_searched_callback' );
    // Frontend Manager Sidebar Hook
    add_action( 'wpcfe_before_add_shipment', 'wpcfe_shipment_dashboard_menu_callback' );
    // Add actions
	add_action( 'before_wpcfe_shipment_form_submit', 'wpcfe_shipment_history_template', 60, 1 );
    add_action( 'before_wpcfe_shipment_form_submit', 'wpcfe_assigned_shipment_template', 40, 1 );
    add_action( 'after_wpcfe_shipment_form_fields', 'wpcfe_shipment_multipackage_template', 10, 1 );
    add_action( 'after_wpcfe_shipment_form_fields', 'wpcfe_shipment_history_table_template', 10, 2 );
	// After save shipment Hook
	add_action( 'after_wpcfe_save_shipment', 'wpcfe_save_shipment_history', 10, 2 );
	add_action( 'after_wpcfe_save_shipment', 'wpcfe_assigned_shipment_save', 10, 2 );
    add_action( 'after_wpcfe_save_shipment', 'wpcfe_shipment_multipackage_save', 10, 2 );
    add_action( 'after_wpcfe_save_shipment', 'wpcfe_shipment_status_save', 20, 2 );
    // Print BOL
    add_filter( 'wpcfe_print_options', 'wpcfe_print_options_callback', 10, 1 );
    add_action( 'wpcfe_print_html_head', 'wpcfe_print_html_head_style_callback', 10, 1 );
    // Fonts
    add_filter( 'wpcargo_print_fonts', 'wpcfe_print_fonts_callback', 10, 1 );
    // After User Registration
    add_action( 'wpcfe_after_user_registration_success', 'wpcfe_user_registration_success', 10, 2 );
    add_filter( 'wp_new_user_notification_email', 'wpcfe_wp_new_user_notification_email', 10, 3 );
    add_filter( 'wp_new_user_notification_email_admin', 'wpcfe_wp_new_user_notification_email_admin', 10, 3 );
    // Assign Shipment Hooks
   // if( ( isset( $_GET['wpcfe'] ) && $_GET['wpcfe'] == 'add') ||  ( isset( $_GET['wpcfe'] ) && $_GET['wpcfe'] == 'update') ){
         // Assign Client
        add_action( 'wpcfe_assign_form_content', 'wpcfe_assign_client_callback', 10, 1 );
        add_action( 'wpcfe_bulk_assign_form_content', 'wpcfe_assign_client_callback', 10, 1 );
        // Assign Agent
        add_action( 'wpcfe_assign_form_content', 'wpcfe_assign_agent_callback', 10, 1 );
        add_action( 'wpcfe_bulk_assign_form_content', 'wpcfe_assign_agent_callback', 10, 1 );
        // Employee Agent
        add_action( 'wpcfe_assign_form_content', 'wpcfe_assign_employee_callback', 10, 1 );
        add_action( 'wpcfe_bulk_assign_form_content', 'wpcfe_assign_employee_callback', 10, 1 );
  //  }
   
    // Bulk Print Template Hook
    add_action( 'wpcfe_after_bulkprint_template', 'wpcfe_after_bulkprint_template_callback', 10, 3 );
    // Users Column Callback
    add_filter('manage_users_columns', 'wpcfe_add_user_custom_column');
    add_action('manage_users_custom_column',  'wpcfe_show_user_custom_column_content', 10, 3);
    // Approve Client
    add_action( 'wp_ajax_wpcfe_approve_client', 'wpcmerch_approve_client_callback' );
    // N-Sequnce hooks
    add_filter('wpcargo_generated_shipment_number', 'wpcargo_generated_shipment_number_callback', 10, 2);
}

add_action( 'plugins_loaded', function(){
    // Shipment History Map - Remove the defaul script
    remove_action('before_wpcargo_shipment_history', 'wpcargo_shipment_history_map_callback', 10, 1 );
    remove_action('wpcsr_create_order_after_form_wrapper', 'wpcargo_shipment_history_map_callback', 10, 1);
    remove_action('wpc_pq_after_form_wrapper', 'wpcargo_shipment_history_map_callback', 10, 1);
    // Customize History Map
    add_action('before_wpcargo_shipment_history', 'wpcfe_shipment_history_map_callback', 10, 1 );
    add_action('wpcsr_create_order_after_form_wrapper', 'wpcfe_shipment_history_map_callback', 10, 1);
    add_action('wpc_pq_after_form_wrapper', 'wpcfe_shipment_history_map_callback', 10, 1);
    // Customize Custom fields - Add class autocomplete
    foreach ( wpcfe_autocomplete_address_fields() as $metakey ) {
        add_filter( 'wpccf_field_html_template_'.$metakey, 'wpcfe_autocomplete_address_field_template', 10, 6 );
    }
}, 100 );

add_action( 'admin_bar_menu', 'admin_bar_item', 500 );
function admin_bar_item ( WP_Admin_Bar $admin_bar ) {
    if ( !current_user_can( 'manage_options' ) ) {
        return;
    }
    $args = array(
        'parent' => 'site-name',
        'id'     => 'wpcargo-dashboard-view-page',
        'title'  => __('WPCargo Dashboard', 'wpcargo-frontend-manager'),
        'href'   => get_the_permalink( wpcfe_admin_page() ),
        'meta'   => false        
    );
    $admin_bar->add_node( $args );
}
// myStickyElement Compatibility
function wpcfe_plugin_compatibility_hooks(){
    global $front_settings_page, $post;
    if( !empty( $post ) ){
        $template = get_page_template_slug( $post->ID );
        if( $template == 'dashboard.php' && class_exists('MyStickyElementsFrontPage_pro') ){
            remove_action('wp_footer', array( $front_settings_page, 'mystickyelement_element_footer'), 999);
        }
    }
}
add_action( 'wp_head', 'wpcfe_plugin_compatibility_hooks' );
// Bulk Print Template Hook Callback
function wpcfe_after_bulkprint_template_callback( $counter, $shipment_num, $print_type ){
    if( $counter != $shipment_num ){
        ?><div class="page_break"></div><?php
    }
}
// Users Table Callback
function wpcfe_add_user_custom_column($columns) {
    $columns['wpcfe_approval_status'] = __('Client Approval', 'wpcargo-frontend-manager');
    return $columns;
}
function wpcfe_show_user_custom_column_content($value, $column_name, $user_id) {
    $user_info          = get_userdata( $user_id );     
    if( 'wpcfe_approval_status' == $column_name ){
        if( !wpcfe_approval_registration() ){
            return __('Disabled', 'wpcargo-frontend-manager');
        }
        if( in_array( 'wpcargo_pending_client', $user_info->roles ) ){
            return '<a href="#" class="button wpcfe-approve-client" data-id="'.$user_id.'">'.__('Pending Client', 'wpcargo-frontend-manager').'</a>';
        }elseif( in_array( 'wpcargo_client', $user_info->roles ) ){
            return __('Approved', 'wpcargo-frontend-manager');
        }else{
            return '--';
        }
    }
    return $value;
}
function wpcmerch_approve_client_callback(){;
    $userID 	= sanitize_text_field( $_POST['userID'] );
    $pwdreg     = get_user_meta( $userID, '_wpcfe_pwdreg', true );
    $password   = $pwdreg ? $pwdreg : wp_generate_password( 12, true );
    $user       = new WP_User($userID);
    $user->add_role( 'wpcargo_client' );
    $user->remove_role( 'wpcargo_pending_client' );
    $user_login = stripslashes($user->user_login);
    $user_email = stripslashes($user->user_email);
    if( $pwdreg  ){
        delete_user_meta( $userID, '_wpcfe_pwdreg' );
    }else{
        wp_set_password( $password, $userID );
    }
    
    $subject    = sprintf( '[%s] Your credentials.', get_option('blogname'));
    $header     = array('Content-Type: text/html; charset=UTF-8');
    $header[]   = 'From: '.get_option('blogname').' <no-reply@mail.com>';
    $message  = '<p>'.__('Hi there,') .'</p>';
    $message .= '<p>'.sprintf(__("Welcome to %s! Here's how to log in:", 'wpcargo-frontend-manager'), get_option('blogname')) .'</p>';
    $message .= '<p>'.sprintf(__("Login URL:  %s", 'wpcargo-frontend-manager'), get_the_permalink( wpcfe_admin_page() ) ) .'</p>';
    $message .= '<p>'.sprintf(__('Username: %s', 'wpcargo-frontend-manager'), $user_login) . '</p>';
    $message .= '<p>'.sprintf(__('Password: %s', 'wpcargo-frontend-manager'), $password) .'</p>';
    $message .= '<p>'.sprintf(__('If you have any problems, please contact our support at %s.', 'wpcargo-frontend-manager'), get_option('blogname')) .'</p>';
    $message .= '<p>'.__('Thank you for trusting us!', 'wpcargo-frontend-manager').'</p>';
    $mail = wp_mail($user_email, $subject, $message, $header );

    $role_labels = array_map( function( $role ){
        global $wp_roles;
        return $wp_roles->roles[$role]['name'];
    }, $user->roles);

    wp_send_json( array(
        'role' => implode(",", $role_labels ) 
    ) );
    wp_die();
}
// Assign Users Callback
function wpcfe_assign_client_callback( $shipment_id ){
    $wpcargo_client 	= wpcfe_get_users('wpcargo_client');
    if( can_wpcfe_assign_client() ): ?>
        <div class="form-group">
            <div class="select-no-margin">
                <label><?php esc_html_e('Client','wpcargo-frontend-manager'); ?></label>
                <select name="registered_shipper" class="wpcfe-select mdb-select mt-0 form-control browser-default" id="registered_client" >
                    <option value=""><?php esc_html_e('-- Select Client --','wpcargo-frontend-manager'); ?></option>
                    <?php if( !empty( $wpcargo_client ) ): ?>
                        <?php foreach( $wpcargo_client as $key => $value ): ?>
                            <option value="<?php echo $key; ?>" <?php selected( get_post_meta( $shipment_id, 'registered_shipper', TRUE ), $key ); ?>><?php echo $value; ?></option>
                        <?php endforeach; ?>	
                    <?php  endif; ?>	                
                </select>
            </div>
        </div>
    <?php endif;
}
function wpcfe_assign_agent_callback( $shipment_id ){
    $wpcargo_agent 		= wpcfe_get_users('cargo_agent');
    if( can_wpcfe_assign_agent() ): ?>
        <div class="form-group">
            <div class="select-no-margin">
                <label><?php esc_html_e('Agent','wpcargo-frontend-manager'); ?></label>
                <select name="agent_fields" class="mdb-select mt-0 form-control browser-default" id="agent_fields" >
                    <option value=""><?php esc_html_e('-- Select Agent --','wpcargo-frontend-manager'); ?></option>
                    <?php if( !empty( $wpcargo_agent ) ): ?>
                        <?php foreach( $wpcargo_agent as $agentID => $agentName ): ?>
                            <option value="<?php echo $agentID; ?>" <?php selected( get_post_meta( $shipment_id, 'agent_fields', TRUE ), $agentID ); ?>><?php echo $agentName; ?></option>
                        <?php endforeach; ?>	
                    <?php  endif; ?>	                
                </select>
            </div>
        </div>
    <?php endif;
}
function wpcfe_assign_employee_callback( $shipment_id ){
    $wpcargo_employee 	= wpcfe_get_users('wpcargo_employee');
    if( can_wpcfe_assign_employee() ): ?>
        <div class="form-group">
            <div class="select-no-margin">
                <label><?php esc_html_e('Employee','wpcargo-frontend-manager'); ?></label>
                <select name="wpcargo_employee" class="mdb-select mt-0 form-control browser-default" id="wpcargo_employee" >
                    <option value=""><?php esc_html_e('-- Select Employee --','wpcargo-frontend-manager'); ?></option>
                    <?php if( !empty( $wpcargo_employee ) ): ?>
                        <?php foreach( $wpcargo_employee as $empID => $empName ): ?>
                            <option value="<?php echo $empID; ?>" <?php selected( get_post_meta( $shipment_id, 'wpcargo_employee', TRUE ), $empID ); ?>><?php echo $empName; ?></option>
                        <?php endforeach; ?>	
                    <?php endif; ?>	                
                </select>
            </div>
        </div>
    <?php endif;
}
// Hooks Dashboard
function wpcfe_shipment_created_date_filter_callback(){
    $date_range     = wpcfe_date_range_filter();
    $date_start     = $date_range ? date('Y-m-d', strtotime('today - '.$date_range.' days')) : '';
    $date_end       = $date_range ? date('Y-m-d') : '';
    $date_start     = isset( $_GET['date_start'] ) ? $_GET['date_start'] : $date_start;
    $date_end       = isset( $_GET['date_end'] ) ? $_GET['date_end'] : $date_end;
    $current_user = wp_get_current_user();
    $user_roles = (array) $current_user->roles;
    $is_driver = in_array( 'wpcargo_driver', $user_roles, true );
    $is_client = in_array( 'wpcargo_client', $user_roles, true );
    if ( $is_driver ) {
        $date_start = $date_end = current_time( 'Y-m-d' );
    }
    // Para clientes: por defecto mostrar los envíos creados hoy cuando el usuario
    // no ha especificado un rango (no hay GET de date_start/date_end).
    if ( $is_client && ! isset( $_GET['date_start'] ) && ! isset( $_GET['date_end'] ) ) {
        $date_start = $date_end = current_time( 'Y-m-d' );
    }
    ?>
    <div id="wpcfe-created-fields" class="form-group wpcfe-filter receiver-filter p-0 mx-1">
        <div class="md-form form-group">
            <?php _e('Date Created', 'wpcargo-frontend-manager' ); ?>
            <input id="date_start" type="text" name="date_start" class="form-control daterange_picker start_date px-2 py-1 mx-2" placeholder="YYYY-MM-DD" value="<?php echo $date_start; ?>" autocomplete="off" style="width: 106px;" <?php echo $is_driver ? 'disabled' : ''; ?> >
            <div class="input-group-addon"><?php _e('to', 'wpcargo-frontend-manager' ); ?></div>
            <input id="date_end" type="text" name="date_end" class="form-control daterange_picker end_date px-2 py-1 mx-2" placeholder="YYYY-MM-DD" value="<?php echo $date_end; ?>" autocomplete="off" style="width: 106px;" <?php echo $is_driver ? 'disabled' : ''; ?> >
            <?php if ( $is_driver ): ?>
                <input type="hidden" name="date_start" value="<?php echo esc_attr( $date_start ); ?>">
                <input type="hidden" name="date_end" value="<?php echo esc_attr( $date_end ); ?>">
                <small style="display:block; margin-top:6px; color:#6c757d;">Mostrando únicamente envíos del día de hoy.</small>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
function wpcfe_shipment_created_date_query_args_callback( $args ){
    $date_range = wpcfe_date_range_filter();
    if( array_key_exists( 's', $args ) && !empty( $args['s'] ) ){
        return $args;
    }
    $date_start     = $date_range ? date('Y-m-d', strtotime('today - '.$date_range.' days')) : '';
    $date_end       = $date_range ? date('Y-m-d') : '';
    $date_start     = isset( $_GET['date_start'] ) ? $_GET['date_start'] : $date_start;
    $date_end       = isset( $_GET['date_end'] ) ? $_GET['date_end'] : $date_end;
    // Forzar hoy para motorizados
    $current_user = wp_get_current_user();
    $user_roles = (array) $current_user->roles;
    if ( in_array( 'wpcargo_driver', $user_roles, true ) ) {
        $date_start = $date_end = current_time( 'Y-m-d' );
    }
    // Para clientes: por defecto mostrar los envíos creados hoy cuando el usuario
    // no ha especificado un rango (no hay GET de date_start/date_end).
    if ( in_array( 'wpcargo_client', $user_roles, true ) && ! isset( $_GET['date_start'] ) && ! isset( $_GET['date_end'] ) ) {
        $date_start = $date_end = current_time( 'Y-m-d' );
    }
    // Return arguments when both dates are empty
    if( !$date_start && !$date_end ){
        return $args;
    }
    // Set Date Start when empty
    if( !$date_start && $date_end ){
        $date_start = $date_end;
    }
    // Set Date End when empty
    if( $date_start && !$date_end ){
        $date_end = $date_start;
    }
    $args['date_query'] = array(
        array(
            'after'     => $date_start,
            'before'    => $date_end,
            'inclusive' => true,
        ),
    );
    return $args;
}
function wpcfe_shipment_searched_callback(){
    $date_range = wpcfe_date_range_filter();
    $date_start     = $date_range ? date('Y-m-d', strtotime('today - '.$date_range.' days')) : '';
    $date_end       = $date_range ? date('Y-m-d') : '';
    $date_start     = isset( $_GET['date_start'] ) ? $_GET['date_start'] : $date_start;
    $date_end       = isset( $_GET['date_end'] ) ? $_GET['date_end'] : $date_end;
    // Return arguments when both dates are empty
    if( !$date_start && !$date_end ){
        return false;
    }

    $date_format    = get_option( 'date_format' );
    $date_start     = date( $date_format, strtotime( $date_start ) );
    $date_end       = date( $date_format, strtotime( $date_end ) );
    ?>
    <div class="wpcfe-search-details col-lg-12 p-0 mb-2 text-muted font-italic">
        <?php printf( __('Date Created: %s to %s', 'wpcargo-frontend-manager'), $date_start, $date_end ); ?>
    </div>
    <?php
}
function wpcfe_shipment_dashboard_menu_callback(){
    $active_class = ( get_the_ID() == wpcfe_admin_page() && isset( $_GET['wpcfe']) && $_GET['wpcfe'] == 'dashboard' ) ? 'active' : '' ;
    ?>
    <a href="<?php echo get_the_permalink( wpcfe_admin_page() ); ?>/?wpcfe=dashboard" class="wpcfe-dashboard list-group-item waves-effect <?php echo $active_class; ?>"> <i class="fa fa-line-chart mr-md-3 mr-3"></i><?php echo apply_filters( 'wpcfe_report_menu_label', __( 'Dashboard', 'wpcargo-frontend-manager') ); ?> </a>
    <?php
}
// Dashboard Menu Class Hook
add_filter( 'nav_menu_css_class' , 'wpcfe_current_nav_class' , 10 , 2);
function wpcfe_current_nav_class ($classes, $item) {
    if (in_array('current-menu-item', $classes) ){
        $classes[] = 'active ';
    }
    return $classes;
}
add_action( 'before_wpcfe_shipment_form_fields', 'wpcfe_shipment_title_field', 1 );
function wpcfe_shipment_title_field( $shipment_id ){  
    global $wpcargo;

    if( array_key_exists( 'wpcargo_title_prefix_action', $wpcargo->settings ) && !$shipment_id ){
        return false;
    }

    if( ( array_key_exists( 'wpcargo_title_prefix_action', $wpcargo->settings ) && $shipment_id ) || !can_wpcfe_update_shipment_number() ){
        ?>
        <div class="col-md-12 mb-3">
            <div class="card">
                <div class="card-header text-center">
                    <?php echo apply_filters( 'wpcfe_shipment_number_label', __('Shipment Number', 'wpcargo-frontend-manager' ) ); ?>
                    <h5><?php echo get_the_title( $shipment_id ); ?></h5>
                </div>
            </div>
        </div>
        <?php
        return false;
    }

    $shipment_title = $shipment_id ? get_the_title( $shipment_id ) : '';
    ?>
    <div id="shipment-number" class="col-md-12 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="col-auto p-0">
                <!-- Default input -->
                <label class="sr-only" for="wpcfe_shipment_title"><?php echo apply_filters( 'wpcfe_shipment_number_label', __( 'Shipment Number', 'wpcargo-frontend-manager' ) ); ?></label>
                <div class="input-group">
                    <div class="input-group-prepend">
                    <div class="input-group-text"><i class="fa fa-barcode mr-3"></i><?php echo apply_filters( 'wpcfe_shipment_number_label', __( 'Shipment Number', 'wpcargo-frontend-manager' ) ); ?></div>
                    </div>
                    <input type="text" class="form-control py-0" id="wpcfe_shipment_title" name="wpcfe_shipment_title" value="<?php echo $shipment_title; ?>" >
                </div>
                </div>
            </div>
        </div> 
    </div>
    <?php
}


add_filter( 'wpcfe_shipment_number_update', 'wpcfe_shipment_title_update', 10, 2 );
add_filter( 'wpcfe_shipment_number', 'wpcfe_shipment_title_update', 10, 2 );
function  wpcfe_shipment_title_update( $shipment_title, $request ){
    if( isset( $request['wpcfe_shipment_title'] ) ){
        return  sanitize_text_field( $request['wpcfe_shipment_title'] );
    }
    return  $shipment_title;
}
// Bulk update callback
add_action( 'wpcfe_before_after_shipment_table', 'wpcfe_bulk_update_action_callback', 10 );
function wpcfe_bulk_update_action_callback(){
     // Restriction
    $user_roles = wpcfe_current_user_role();
    if( !wpcfe_is_super_admin() && !can_wpcfe_add_shipment() && !can_wpcfe_update_shipment() ){
        return false;
    }
	if( !in_array( 'wpcargo_client', $user_roles ) ){
		?>
		<!-- Button trigger modal -->
		<button id="shipmentBulkUpdate" type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#shipmentBulkUpdateModal"><i class="fa fa-edit text-white"></i> <?php esc_html_e('Bulk Assign', 'wpcargo-frontend-manager'); ?></button>
		<!-- Modal -->
		<?php
	}
}
add_action( 'wpcfe_after_shipment_data', 'wpcfe_bulk_update_modal_action_callback', 10 );
function wpcfe_bulk_update_modal_action_callback(){
    global $wpcargo;
	$user_roles 		= wpcfe_current_user_role();
    // Restriction
    if( !wpcfe_is_super_admin() && !can_wpcfe_add_shipment() && !can_wpcfe_update_shipment() ){
        return false;
    }
	if( !in_array( 'wpcargo_client', $user_roles ) ){
		$template = wpcfe_include_template( 'bulk-assign-shipment.tpl' );
		require_once( $template );
	}
}
/*
 * Save updated data
 */
add_action( 'wp', 'wpcfe_add_shipment' );
function wpcfe_add_shipment(){
    if ( isset( $_POST['wpcfe_add_form_fields'] ) && wp_verify_nonce( $_POST['wpcfe_add_form_fields'], 'wpcfe_add_action' ) ) {
    	wpcfe_save_shipment( $_POST );
    }else{
        return false;
    }
}
add_action( 'wp', 'wpcfe_update_shipment' );
function wpcfe_update_shipment(){
    global $WPCCF_Fields;
    if ( isset( $_POST['wpcfe_form_fields'] ) && wp_verify_nonce( $_POST['wpcfe_form_fields'], 'wpcfe_edit_action' ) && isset( $_POST['shipment_id'] ) && is_wpcfe_shipment($_POST['shipment_id'] )  ) {
    	wpcfe_save_shipment( $_POST, $_POST['shipment_id'] ); 
    }else{
        return false;
    }
}

/*
//  Remove Access Employee to enter wp-admin page
add_action( 'admin_head','wpcfe_restrict_employee_to_wpadmin' );
function wpcfe_restrict_employee_to_wpadmin(){
    $wpcargo_roles  = wpcfe_access_dashboard_role();
    $current_user   = wp_get_current_user();
    $user_role      =  $current_user->roles;
    if( is_admin() && in_array('wpcargo_employee', $user_role) ){
        if( wpcfe_admin_page() && array_intersect( $wpcargo_roles , $user_role ) ){
            wp_redirect( get_permalink( wpcfe_admin_page() ) );
        }else{
            wp_redirect(home_url());
        }
        exit;
    }
}
*/

// add_filter( 'login_redirect', 'wpcfe_custom_login_redirect', 10, 3 );
function wpcfe_custom_login_redirect( $redirect_to, $request, $user ) {   
    $wpcargo_roles = wpcfe_access_dashboard_role();
    $wpcargo_roles = apply_filters( 'wpcfe_login_redirect_dashboard_role', $wpcargo_roles );
    if ( isset( $user->roles ) && is_array( $user->roles ) ) {
        if ( in_array( 'administrator', $user->roles ) ) {
            return get_admin_url();
        } elseif( array_intersect( $wpcargo_roles , $user->roles ) ) {
            $redirect_to = get_permalink( wpcfe_admin_page() );
            return $redirect_to;
        }else{
            return get_admin_url();
        }
    } else {
        return $redirect_to;
    }
}
add_action( 'wp_login_failed', 'wpcfe_login_fail_redirect' );  // hook failed login
function wpcfe_login_fail_redirect( $username ) {
    $referrer=isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : ''; // where did the post submission come from?
	$wpcfe_dashboard   = get_the_permalink( wpcfe_admin_page() );
	// if there's a valid referrer, and it's not the default log-in screen
	if ( !empty($referrer) && !strstr($referrer,'wp-login') && !strstr($referrer,'wp-admin') && wpcfe_admin_page() ) {
		$redirect = apply_filters( 'wpcfe_login_failed_redirect', $wpcfe_dashboard. '?login=failed&user='.$username, $wpcfe_dashboard );
		wp_redirect( $redirect);  // let's append some information (login=failed) to the URL for the theme to use
		exit;
	}
}
// Registration page redirection when user is Logged In
add_action( 'template_redirect', 'wpcfe_registration_page_template_redirect' );
function wpcfe_registration_page_template_redirect(){
    global $post;
    if( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'wpcfe_registration') && is_user_logged_in() ){
        $wpcfe_admin = get_option( 'wpcfe_admin', 0 );
        $dashboardURL = ( $wpcfe_admin ) ? get_the_permalink( $wpcfe_admin ) : home_url( ) ;
        wp_redirect( $dashboardURL );
        die;
    }
}
add_filter('views_edit-wpcargo_shipment','wpcfe_download_waybill_callback');
function wpcfe_download_waybill_callback( $views ){
    $print_options = wpcfe_print_options();
    if( empty( $print_options ) ){
        return  $views;
    }
    $html = '<span class="dashicons dashicons-printer" style="vertical-align: middle;"></span> <select id="wpcfe-bulkprint" name="wpcfe-bulkprint">';
        $html .= '<option value="">'.__('Select Template', 'wpcargo-frontend-manager').'</option>';
    foreach( $print_options as $print_key => $print_label ):
        $html .= '<option value="'.$print_key.'">'.$print_label.'</option>';
    endforeach;
    $html .= '</select>';
	$views['wpcfe-download-waybill'] = $html;
	return $views;
}
add_filter( 'wpcargo_history_fields', 'wpcfe_require_history_fields' );
function wpcfe_require_history_fields( $history_fields ){
	if( isset( $_GET['wpcfe'] ) && $_GET['wpcfe'] == 'add' ){
		foreach( $history_fields as $history_key => $history_field ){
			$history_fields[$history_key]['required'] = 'true';
		}
	}
	return $history_fields;
}
// Fonts
function wpcfe_print_fonts_callback( $fonts ){
    $fonts['amiri'] = array(
        'url' => 'https://fonts.googleapis.com/css2?family=Amiri&display=swap',
        'fontfamily' => "'Amiri', serif"
    );
    $fonts['rubik'] = array(
        'url' => 'https://fonts.googleapis.com/css2?family=Rubik&display=swap',
        'fontfamily' => "'Rubik', sans-serif"
    );
    return $fonts;
}
// BOL callback
function wpcfe_print_options_callback( $options ){
    if( wpcfe_bol_enable() ){
        $options['bol'] = esc_html__('BOL', 'wpcargo-frontend-manager');
    }
    return $options;
}
function wpcfe_print_html_head_style_callback(){
    $print_fonts 		    = wpcargo_print_fonts();
    $ffamily 			    = get_option('wpcargo_print_ffamily');
    $fsize 				    = get_option('wpcargo_print_fsize') ? get_option('wpcargo_print_fsize') : 12 ;
    $wpcfe_font_url         = apply_filters( 'wpcfe_print_custom_font_url', 'https://www.wpcargo.com/repository/fonts/NotoSansSC-Regular.ttf' );
    ?>
    <style type="text/css">         
            <?php if( wpcfe_customfont_enable() ): ?>
                @font-face {
                    font-family: 'wpcfeCustomFont';
                    src: url('<?php echo $wpcfe_font_url; ?>') format('truetype')
                }
                *{
                    font-family: 'wpcfeCustomFont', sans-serif !important;
                    font-size: <?php echo $fsize; ?>px;
                }
            <?php else: ?>
                <?php if( $ffamily && array_key_exists( $ffamily, $print_fonts ) ): ?>
                    @import url('<?php echo $print_fonts[$ffamily]['url']; ?>');
                    *{
                        font-family: <?php echo $print_fonts[$ffamily]['fontfamily']; ?> !important;
                        font-size: <?php echo $fsize; ?>px;
                    } 
                <?php endif; ?>
            <?php endif; ?>
            
            html{ margin:0px; padding:0px; }
            body{ margin:0px; padding:12px; }
            h1, h2, h3, h4, h5, h6{ font-weight: normal !important; }
            h6, h5{ font-size: 1.1rem!important; }
            h4, h3{ font-size: 1.2rem!important; }
            h2{ font-size: 1.4rem!important; }
            h1{ font-size: 1.6rem!important; }
            .page_break { page-break-before: always; }
            @media screen, print{ .page_break { page-break-before: always; } }
        </style>
    <?php
}
// Register Hook
function wpcfe_after_login_form_registration(){
    if( wpcfe_disable_registration() ){
        return false;
    }
    $register_template = wpcfe_include_template( 'registration.tpl' );
    ?>
    <p><?php esc_html_e('Not a member?', 'wpcargo-frontend-manager' ); ?> <a href="#" data-toggle="modal" data-target="#wpcfe-registration" title="<?php esc_html_e( 'Registration', 'wpcargo-frontend-manager' ); ?>"><?php esc_html_e('Register', 'wpcargo-frontend-manager' ); ?></a></p>
    <?php
    require_once( $register_template );
}
add_action( 'wpcfe_after_login_form', 'wpcfe_after_login_form_registration', 10);
// Frontend Manager hooks
// Registered Shipper
function wpcfe_assigned_shipment_template( $shipment_id ){
    $shipment       = new stdClass();
    $shipment->ID   = $shipment_id;
	$template = wpcfe_include_template( 'assign-shipment.tpl' );
	require_once( $template );
}
function wpcfe_assigned_shipment_save( $shipment_id, $data ){
    // Assign Shipment to Client
    if( isset( $data['registered_shipper'] ) 
        && (int)$data['registered_shipper'] 
        && ( can_wpcfe_add_shipment() || can_wpcfe_update_shipment() )
    ){   
        if( can_wpcfe_assign_client() ){
            $old_client = get_post_meta( $shipment_id, 'registered_shipper', true );
            if( $old_client != (int)$data['registered_shipper'] && wpc_can_send_email_client() ){
                wpcargo_assign_shipment_email( $shipment_id, (int)$data['registered_shipper'], __('Client', 'wpcargo-frontend-manager' ) );
            }
        }
        update_post_meta( $shipment_id, 'registered_shipper', (int)$data['registered_shipper'] );
    }
    // Assign Shipment to Employee 
    if( isset( $data['wpcargo_employee'] ) && in_array( 'administrator', wpcfe_current_user_role() ) ){
        $old_employee = get_post_meta( $shipment_id, 'wpcargo_employee', true );
        update_post_meta( $shipment_id, 'wpcargo_employee', (int)$data['wpcargo_employee'] );
        // Check if the employee is changed Send email notification
        if( $old_employee != (int)$data['wpcargo_employee'] && wpc_can_send_email_employee() ){
            wpcargo_assign_shipment_email( $shipment_id, (int)$data['wpcargo_employee'], __('Employee', 'wpcargo-frontend-manager' ) );
        }   
    }elseif( 
        ( can_wpcfe_add_shipment() || can_wpcfe_update_shipment() ) 
        && in_array( 'wpcargo_employee', wpcfe_current_user_role() ) 
        && !isset( $data['wpcargo_employee'] )
    ){
        // Assign Shipment to Employee when user has a role of Employee
        update_post_meta( $shipment_id, 'wpcargo_employee', get_current_user_id() );
    }
    // Assign Shipment to Agent
    if( isset( $data['agent_fields'] ) && can_wpcfe_assign_agent() ){
        $old_agent = get_post_meta( $shipment_id, 'agent_fields', true );
        update_post_meta( $shipment_id, 'agent_fields', (int)$data['agent_fields'] );
        // check if the agent is changed Send email notification
        if( $old_agent != (int)$data['agent_fields'] && wpc_can_send_email_agent() ){
            wpcargo_assign_shipment_email( $shipment_id, (int)$data['agent_fields'], __('Agent', 'wpcargo-frontend-manager' ) );
        }
    }elseif( is_wpcfe_agent() && can_wpcfe_add_shipment() ){
        update_post_meta( $shipment_id, 'agent_fields', get_current_user_id() );
    }
    do_action( 'wpcfe_assigned_shipment_save', $shipment_id, $data );
}
// Multiple Package
function wpcfe_shipment_multipackage_template( $shipment_id ){

    $wpcargo_settings = !empty( get_option('wpc_mp_settings') ) ? get_option('wpc_mp_settings') : array();
    if( !array_key_exists( 'wpc_mp_enable_admin', $wpcargo_settings ) ){
        return false;
    }
	$user_roles = wpcfe_current_user_role();
	if( !( in_array( 'cargo_agent', (array)$user_roles ) ) && !( in_array( 'wpcargo_driver', (array)$user_roles ) ) ){
		$shipment       = new stdClass();
		$shipment->ID   = $shipment_id;
		$template = wpcfe_include_template( 'multiple-package.tpl' );
		require_once( $template );
	}
}

// Shipment History
function wpcfe_shipment_history_table_template( $shipment_id ){
	global $wpdb, $wpcargo;
	$current_user 			= wp_get_current_user();
	$gen_settings 			= $wpcargo->settings;
	$edit_history_role 		= array_key_exists( 'wpcargo_edit_history_role', $gen_settings ) && is_array( $gen_settings['wpcargo_edit_history_role'] ) ? $gen_settings['wpcargo_edit_history_role'] : array();
	$role_intersected 		= array_intersect( $current_user->roles, $edit_history_role );
	$shipment       = new stdClass();
	$shipment->ID   = $shipment_id;
    $shipments = maybe_unserialize( get_post_meta( $shipment->ID, 'wpcargo_shipments_update', true ) );
	$template = wpcfe_include_template( 'shipment-history.tpl' );
	require_once( $template );
}
// Save Multiple Package data
function wpcfe_shipment_multipackage_save( $post_id, $data ){
    if( empty( $data ) || !is_array( $data ) ){
        return false;
    }
   $packages = array_key_exists( 'wpc-multiple-package', $data ) ? $data['wpc-multiple-package'] : array();
   update_post_meta( $post_id, 'wpc-multiple-package', $packages );
}
// Save shipment Status
function wpcfe_shipment_status_save( $post_id, $data ){
    // Save Shipment Type
    $shipment_type = get_post_meta( $post_id, '__shipment_type', true );
    if( empty( $shipment_type ) ){
        update_post_meta( $post_id, '__shipment_type', 'wpcargo_default' );
    }
    //  Save the shipment Status
    if( isset( $data['status'] ) &&  $data['status'] != '' ){
        return;
    }
    if( 
        isset( $data['wpcfe_add_form_fields'] ) 
        && wp_verify_nonce( $data['wpcfe_add_form_fields'], 'wpcfe_add_action' ) 
        && can_wpcfe_add_shipment()
    ){
        $__default_status = wpcfe_default_status();
        update_post_meta( $post_id, 'wpcargo_status', sanitize_text_field( $__default_status ) );
        wpcargo_send_email_notificatio( $post_id, sanitize_text_field( $__default_status ) );
    }
}
// Shipment history
function wpcfe_shipment_history_template( $shipment_id ){
    $current_user 			= wp_get_current_user();
    $user_roles             = $current_user->roles;
    if( in_array( 'wpcargo_client', $user_roles ) ){
        return false;
    }
    if( empty( array_intersect( $user_roles, wpcfe_add_shipment_role() ) ) ){
        return false;
    }
    $shipment       = new stdClass();
    $shipment->ID   = $shipment_id;
	$template = wpcfe_include_template( 'history.tpl' );
	require_once( $template );
}

// Save Shipment history
function wpcfe_save_shipment_history( $post_id, $data ) {
	global $wpdb, $wpcargo;
	$current_user 			= wp_get_current_user();
	$gen_settings 			= $wpcargo->settings;
	$edit_history_role 		= ( !empty( $gen_settings ) && array_key_exists( 'wpcargo_edit_history_role', $gen_settings ) ) ? $gen_settings['wpcargo_edit_history_role'] : array();
    $role_intersected 		= array_intersect( $current_user->roles, $edit_history_role );
    $old_status             = get_post_meta($post_id, 'wpcargo_status', true);
	$user_id        = get_current_user_id();
    $full_name      = $wpcargo->user_fullname( $user_id );
	
	if( $role_intersected ){
        $history = array();
        $shipments_update = array();
        if( isset( $data['wpcargo_shipments_update'] ) ){
            $shipments_update = $data['wpcargo_shipments_update'];
        }   
        if( !empty( $shipments_update ) ){
            foreach ($shipments_update as $h_record ) {
                if( array_key_exists( 'updated-name', $h_record ) && empty($h_record['updated-name']) ){
                    $h_record['updated-name'] = $full_name;
                }
                $history[] = $h_record;
            }
        }
        
		if( isset( $data['status'] ) &&  $data['status'] != '' ){
            wpcfe_save_report( $post_id, $old_status, sanitize_text_field( $data['status'] ) );
            update_post_meta( $post_id, 'wpcargo_status', sanitize_text_field( $data['status'] ) );
			update_post_meta( $post_id, 'location', sanitize_text_field( $data['location'] ) );
            do_action( 'wpcargo_extra_fm_saving', $post_id, $data );
			$new_history = array();
			foreach( wpcargo_history_fields() as $history_name => $history_fields ){
				if( $history_name != 'updated-name' ){
                    $value = array_key_exists( $history_name, $data ) ? sanitize_text_field( $data[$history_name] ) : '' ;
					$new_history[$history_name] = $value;
				}
			}
			$new_history['updated-name'] = $full_name;
			$history[] = $new_history;
			if( $data['status'] != $old_status ){
                wpcargo_send_email_notificatio( $post_id, $data['status'] );
                do_action( 'wpcargo_extra_send_email_notification', $post_id, $data['status'] );
                do_action( 'wpc_add_sms_shipment_history', $post_id );
			}
		}
		update_post_meta( $post_id, 'wpcargo_shipments_update', $history );
	}else{
		if( isset( $data['status'] ) &&  $data['status'] != '' ){
            wpcfe_save_report( $post_id, $old_status, sanitize_text_field( $data['status'] ) );
            update_post_meta( $post_id, 'wpcargo_status', sanitize_text_field( $data['status'] ) );
			update_post_meta( $post_id, 'location', sanitize_text_field( $data['location'] ) );
            do_action( 'wpcargo_extra_fm_saving', $post_id, $data );
			$history        = get_post_meta( $post_id, 'wpcargo_shipments_update', true ) ? maybe_unserialize( get_post_meta( $post_id, 'wpcargo_shipments_update', true ) ) : array() ;
			$new_history = array();
			foreach( wpcargo_history_fields() as $history_name => $history_fields ){
				if( $history_name != 'updated-name' ){
                    $value = array_key_exists( $history_name, $data ) ? sanitize_text_field( $data[$history_name] ) : '' ;
					$new_history[$history_name] = $value;
				}
			}
			$new_history['updated-name'] = $full_name;
			$history[] = $new_history;
			update_post_meta( $post_id, 'wpcargo_shipments_update', $history );
			if( $data['status'] != $old_status ){
				wpcargo_send_email_notificatio( $post_id, $data['status'] );
			}
		}
	}
}
function wpcfe_waybill_template( $shipment_id ){
	global $WPCCF_Fields, $wpcargo;
	$template = wpcfe_include_template( 'waybill.tpl' );
    return $template;
}
// User registration hook
function wpcfe_user_registration_success( $user_id, $data ){
    global $WPCCF_Fields;
    $shipper_fields 			= $WPCCF_Fields->get_field_key('shipper_info');
    if( !empty( $shipper_fields ) && post_type_exists( 'wpc_address_book' ) ){
        // Check if the map Settings is Set
        $ismapped = false;
        foreach( $shipper_fields as $field ){
            $assigned_fields = get_option( 'wpcfe_regmap_'.trim($field['field_key']) );
            if( $assigned_fields ){
                $ismapped = true;
                break;
            }
        }
        // Don't create address when the Field Mapping is NOT set
        if( !$ismapped ){
            return false;
        }
        $insert_address = array(
            'post_status'   => 'publish',
            'post_type'		=> 'wpc_address_book',
            'post_author'   => $user_id,
        );   
        // Insert the Address into the database.
        $addressID = wp_insert_post( $insert_address ); 
        // Check if create shipper address Failed 
        if( !$addressID ){
            return false;
        }
        update_post_meta( $addressID, 'book', 'shipper' );
        foreach( $shipper_fields as $field ){
            $assigned_fields = get_option( 'wpcfe_regmap_'.trim($field['field_key']) );
            if( !$assigned_fields ){
                continue;
            }
            if( !array_key_exists( $assigned_fields, $data ) ){
                continue;
            }
            update_post_meta( $addressID, trim($field['field_key']), sanitize_text_field( $data[$assigned_fields] ) );
        }
        do_action( 'after_save_registration_shipper_address', $addressID, $data);
    }
    
}
function wpcfe_wp_new_user_notification_email( $wp_new_user_notification_email, $user, $blogname ) {
    $user_login = stripslashes( $user->user_login );
    $user_email = stripslashes( $user->user_email );
    $headers    = array('Content-Type: text/html; charset=UTF-8');
    $headers[]  = __('From: ', 'wpcargo-frontend-manager' ) . get_bloginfo('name') .' <admin@no-reply.com>';
    $login_url  = wp_login_url();
    $message  = "<p>".__( 'Hi there,', 'wpcargo-frontend-manager' ) . "</p>";
    $message .= "<p>".sprintf( __( "Welcome to %s! Here's how to log in:", 'wpcargo-frontend-manager' ), get_option('blogname') ) . "</p>";
    $message .= "<p>".wp_login_url() . "</p>";
    $message .= "<p>".sprintf( __('Username: %s', 'wpcargo-frontend-manager'), $user_login ) . "</p>";
    $message .= "<p>".sprintf( __('Email: %s', 'wpcargo-frontend-manager'), $user_email ) . "</p>";
    $message .= "<p>".__( 'Password: The one you entered in the registration form. (For security reason, we save encripted password)', 'wpcargo-frontend-manager' ) . "</p>";
    $message .= "<p>".sprintf( __('If you have any problems, please contact me at %s.', 'wpcargo-frontend-manager'), get_option('admin_email') ) . "</p>";
    $message .= "<p>".__( 'Thank you for trusting us!', 'wpcargo-frontend-manager' ). "</p>";
 
    $wp_new_user_notification_email['subject'] = sprintf( '[%s] Your credentials.', $blogname );
    $wp_new_user_notification_email['headers'] = $headers;
    $wp_new_user_notification_email['message'] = $message;
 
    return $wp_new_user_notification_email;
}
function wpcfe_wp_new_user_notification_email_admin( $wp_new_user_notification_email, $user, $blogname ) {
    $user_login = stripslashes( $user->user_login );
    $user_email = stripslashes( $user->user_email );
    $headers    = array('Content-Type: text/html; charset=UTF-8');
    $headers[]  = __('From: ', 'wpcargo-frontend-manager' ) . get_bloginfo('name') .' <admin@no-reply.com>';
    $login_url  = wp_login_url();
    $message  = "<p>".__( 'Hi Admin,', 'wpcargo-frontend-manager' ) . "</p>";
    $message .= "<p>".sprintf( __( "New user registration on your site %s:", 'wpcargo-frontend-manager' ), get_option('blogname') ) . "</p>";
    $message .= "<p>".sprintf( __('Username: %s', 'wpcargo-frontend-manager'), $user_login ) . "</p>";
    $message .= "<p>".sprintf( __('Email: %s', 'wpcargo-frontend-manager'), $user_email ) . "</p>";
 
    $wp_new_user_notification_email['subject'] = sprintf( '[%s] New Registered User.', $blogname );
    $wp_new_user_notification_email['headers'] = $headers;
    $wp_new_user_notification_email['message'] = $message;
 
    return $wp_new_user_notification_email;
}
add_action( 'woocommerce_thankyou', 'wpcfe_woocommerce_thankyou' );
function wpcfe_woocommerce_thankyou( $order_id ){
    if ( in_array( 'woocommerce/woocommerce.php', get_option( 'active_plugins' ) )  ) {
        $wpcfe_print_options = wpcfe_print_options();
        $wpcfe_print_checkout = get_option('wpcfe_checkout_print') ? : array();
        $order = wc_get_order( $order_id );
        $items = $order->get_items(); 
        foreach( $items as $item ){
            $shipment_title = $item['Shipment'] ? $item['Shipment'] : $item['_shipment_num']; 
        }
        $shipment_id = wpcfe_shipment_id( $shipment_title );           
        if( !empty( $wpcfe_print_options ) && $shipment_id ): ?>
            <div class="text-center print-shipment">
                <?php foreach( $wpcfe_print_options as $print_key => $print_label ): ?>
                    <?php if( in_array( $print_key, $wpcfe_print_checkout ) ): ?>
                        <button class="wpcfe-btn-checkout shipment-checkout  print-<?php echo $print_key; ?> py-1 wpcargo-btn wpcargo-btn-primary wpcargo-btn-lg" data-id="<?php echo $shipment_id; ?>" data-type="<?php echo $print_key; ?>">
                            <?php 
                                echo sprintf(
                                    '%s %s',
                                    esc_html__('Print', 'wpcargo-frontend-manager'),
                                    $print_label
                                );
                            ?>
                        </button>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif;
    }
}
add_action( 'wp_head', 'wpcfe_user_sort' );
function wpcfe_user_sort(){
	$wpcfesort_list = array( 10, 25, 50, 100 );
	if( isset( $_GET['wpcfesort'] ) && in_array( $_GET['wpcfesort'], $wpcfesort_list ) ){
		update_user_meta( get_current_user_id(), 'user_wpcfesort', $_GET['wpcfesort'] );
	}
}