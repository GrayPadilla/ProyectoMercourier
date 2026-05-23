<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
function wpcfe_register_settings() {
    global $WPCCF_Fields;
    $shipper_fields = array();
    if( class_exists( 'WPCCF_Fields' ) ){
        $shipper_fields 			= $WPCCF_Fields->get_field_key('shipper_info');
    }
    if( !empty( $shipper_fields ) ){
        foreach( $shipper_fields as $field ){
            register_setting( 'wpcfe_settings_group', 'wpcfe_regmap_'.trim($field['field_key']) );
        }
    }
    register_setting( 'wpcfe_settings_group', 'wpcfe_admin' );
    register_setting( 'wpcfe_settings_group', 'wpcfe_waybill_paper_size' );
    register_setting( 'wpcfe_settings_group', 'wpcfe_waybill_paper_orient' );
    register_setting( 'wpcfe_settings_group', 'wpcfe_update_shipment_role' );
    register_setting( 'wpcfe_settings_group', 'wpcfe_add_shipment_deactivated' );
    register_setting( 'wpcfe_settings_group', 'wpcfe_delete_shipment_role' );
    register_setting( 'wpcfe_settings_group', 'wpcfe_access_dashboard_role' );
    register_setting( 'wpcfe_settings_group', 'wpcfe_employee_all_access' );
    register_setting( 'wpcfe_settings_group', 'wpcfe_bol_enable' );
    register_setting( 'wpcfe_settings_group', 'wpcfe_rtl_enable' );
    register_setting( 'wpcfe_settings_group', 'wpcfe_customfont_enable' );
    register_setting( 'wpcfe_settings_group', 'wpcfe_checkout_print' );
    register_setting( 'wpcfe_settings_group', 'wpcfe_client_can_add_shipment' );
    register_setting( 'wpcfe_settings_group', 'wpcfe_default_status' );
    register_setting( 'wpcfe_settings_group', 'wpcfe_approval_registration' );
    register_setting( 'wpcfe_settings_group', 'wpcfe_disable_registration' );
    register_setting( 'wpcfe_settings_group', 'wpcfe_enable_label_multiple_print' );
    register_setting( 'wpcfe_settings_group', 'wpcfe_label_pagination_template' );
    register_setting( 'wpcfe_settings_group', 'wpcfe_date_range_filter' );
    // Sequence Number
    register_setting( 'wpcfe_settings_group', 'wpcfe_nsequence_enable' );
    register_setting( 'wpcfe_settings_group', 'wpcfe_nsequence_start' );
    register_setting( 'wpcfe_settings_group', 'wpcfe_nsequence_digit' );
} 
add_action( 'admin_init', 'wpcfe_register_settings' );
function wpfe_dashboard_register_meta_boxes() {
    add_meta_box( 
        'wpfe_dashboard-id', 
        __( 'WPCargo Dashboard Attributes', 'wpcargo-frontend-manager' ), 
        'wpcfe_page_attributes_callback', 
        'page',
        'side',
        'high'
    );
}
add_action( 'add_meta_boxes', 'wpfe_dashboard_register_meta_boxes' );
function wpcfe_settings_navigation(){
    $view = $_GET['page'];
    ?>
    <a class="nav-tab <?php echo ( $view == 'wpcfe-settings') ? 'nav-tab-active' : '' ;  ?>" href="<?php echo admin_url().'admin.php?page=wpcfe-settings'; ?>" ><?php esc_html_e('Frontend Dashboard', 'wpcargo-frontend-manager' ); ?></a>
    <?php
}
//** Add plugin Setting navigation to the WPCargo settings
add_action( 'wpc_add_settings_nav', 'wpcfe_settings_navigation' , 9);
function wpcfe_page_attributes_callback( $post ){
    ob_start();
    $menu_icon = get_post_meta( $post->ID, 'wpcfe_menu_icon', true );
    ?>
    <div id="wpcfe-menu-icon-wrapper">
        <p><span class="dashicons dashicons-admin-customizer" style="color: #82878c;"></span> <?php esc_html_e('Menu Icon Class', 'wpcargo-frontend-manager' ); ?></p>
        <input name="wpcfe_menu_icon" type="text" id="wpcfe_menu_icon" value="<?php echo $menu_icon; ?>">
        <p class="description"><?php esc_html_e('Note: This menu icon will display only in WPCargo Dashboard Template. You can find icons available in', 'wpcargo-frontend-manager' ); ?> <a href="https://fontawesome.com/icons?d=gallery&m=free" target="_blank">Fontawesome</a> <?php esc_html_e( 'ei. dashboard', 'wpcargo-frontend-manager' ); ?></p>
    </div>
    <?php
    $output = ob_get_clean();
    echo $output;
}
function wpcfe_save_icon_callback( $post_id ){
    if ( isset( $_POST['wpcfe_menu_icon'] ) ) {
        update_post_meta( $post_id, 'wpcfe_menu_icon', sanitize_text_field( $_POST['wpcfe_menu_icon'] ) );
    }
    if ( isset( $_POST['wpcfe_admin'] ) ) {
        update_post_meta( $post_id, 'wpcfe_admin', sanitize_text_field( $_POST['wpcfe_admin'] ) );
    }else{
        update_post_meta( $post_id, 'wpcfe_admin', 0 );
    }
}
add_action( 'save_post', 'wpcfe_save_icon_callback' );

// Shipment Number Sequence function helper
function wpcfe_nsequence_get_next_shipment( $_lnumber = 0 ){
    global $wpcargo;
	if( !get_option( '_nsequence_started' ) ){
		$last_shipment = (int)wpcfe_nsequence_start() - 1;
		update_option( '_nsequence_started', $last_shipment );
	}else{
		$last_shipment = wpcfe_nsequence_get_last_shipment();
	}
    $last_shipment = str_replace( $wpcargo->prefix, '', $last_shipment );
    if( (int)$_lnumber ){
        return (int)$last_shipment + 1 + (int)$_lnumber;
    }
    return (int)$last_shipment + 1;
}
function wpcfe_nsequence_get_total_shipments(){
    global $wpdb;
    $sql = "SELECT COUNT(*) FROM {$wpdb->posts} WHERE `post_status` IN ('publish','trash','draft','pending', 'request') AND `post_type` LIKE 'wpcargo_shipment'";
    return $wpdb->get_var( $sql );
}
function wpcfe_nsequence_get_last_shipment(){
    global $wpdb;
    $sql = "SELECT `post_title` FROM {$wpdb->posts} WHERE `post_status` IN ('publish','trash','draft','pending', 'request') AND `post_type` LIKE 'wpcargo_shipment' ORDER BY ID DESC LIMIT 1";
    return $wpdb->get_var( $sql );
}
function wpcfe_nsequence_is_shipment_exist( $shipment_number ){
    global $wpdb;
    $sql 	= $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE `post_type` LIKE 'wpcargo_shipment' AND `post_status` IN ('publish','trash','draft','pending', 'request') AND `post_title` LIKE %s", $shipment_number );
    return $wpdb->get_var( $sql );
}
function wpcfe_nsequence_generate_shipment_number( $last_num = 0 ){
    global $wpcargo;
    $shipment_title = $wpcargo->prefix.str_pad( wpcfe_nsequence_get_next_shipment( $last_num ), wpcfe_nsequence_digit(), "0", STR_PAD_LEFT ).$wpcargo->suffix;
    if( wpcfe_nsequence_is_shipment_exist($shipment_title) ){
        $last_num = $last_num + 1;
        return wpcfe_nsequence_generate_shipment_number( $last_num );
    }
    return $shipment_title;
}
function wpcargo_generated_shipment_number_callback( $shipment_number, $generated_number ){
    if( !wpcfe_nsequence_enable() ){
        return $shipment_number;
    }
    return wpcfe_nsequence_generate_shipment_number();
}