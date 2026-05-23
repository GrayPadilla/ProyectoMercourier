<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
function wpcumanage_enqueue_scripts(){
    global $post;
    if( !function_exists( 'wpcfe_admin_page' ) ){
        return false;
    }
    if( !empty( $post ) ){
        $template = get_page_template_slug( $post->ID );
        if( ( $template == 'dashboard.php' && $post->ID == wpcumanage_users_page() ) || wpcfe_admin_page() == $post->ID || wpc_profile_get_frontend_page() == $post->ID || $post->ID == wpcumanage_users_page() ){
            // Styles
            wp_enqueue_style( 'wpcumanage-select2-styles', WPCU_MANAGEMENT_URL . 'assets/css/select2-bootstrap.min.css', array(), WPCU_MANAGEMENT_VERSION );
            wp_enqueue_style( 'wpcumanage-styles', WPCU_MANAGEMENT_URL . 'assets/css/styles.css', array(), WPCU_MANAGEMENT_VERSION );
            // Scripts
            wp_register_script( 'wpcumanage-select2-scripts', WPCU_MANAGEMENT_URL . 'assets/js/select2.min.js', array(), WPCU_MANAGEMENT_VERSION, true );
            wp_register_script( 'wpcumanage-scripts', WPCU_MANAGEMENT_URL . 'assets/js/scripts.js', array( 'jquery' ), WPCU_MANAGEMENT_VERSION, true );
            wp_enqueue_script( 'jquery' );
            wp_enqueue_script( 'wpcumanage-scripts' );
            // Local translation
            $translation   = array(
                'ajaxurl'               => admin_url( 'admin-ajax.php' ),
                'pageURL'               => get_the_permalink(),
                'userDefault'           => wpcumanage_default_users( get_current_user_id() ),
                'assignFields'          => wpcumanage_assignment_fields( ),
                'optEmployee'           => wpcumanage_get_users( array( 'wpcargo_employee' ) ),
                'optDriver'             => wpcumanage_get_users( array( 'wpcargo_driver' ) ),
                'optAgent'              => wpcumanage_get_users( array( 'cargo_agent' ) ),
                'optClient'             => wpcumanage_get_users( array( 'wpcargo_client' ) ),
                'optClient'             => wpcumanage_get_users( array( 'wpcargo_client' ) ),
                'optBranch'             => wpcumanage_get_branch_managers(),
                'selectRoleLabel'       => __( 'Select User Roles', 'wpcargo-umanagement' ),
                'selectGroupLabel'      => __( 'Select User Groups', 'wpcargo-umanagement' ),
                'selectAccessLabel'     => __( 'Select Access', 'wpcargo-umanagement' ),
                'selectOptionLabel'     => __( 'Select Option', 'wpcargo-umanagement' ),
                'userConfimation'       => __( 'Are you sure you want to Delete user? You cannot restore a user account when deleted.', 'wpcargo-umanagement' )
            );
            wp_localize_script( 'wpcumanage-scripts', 'wpcumanageAjaxHandler', $translation );
        }
    }
}
add_action( 'wp_enqueue_scripts', 'wpcumanage_enqueue_scripts' );
function wpcumanage_admin_enqueue_scripts(){
    if( isset( $_GET['page'] ) && $_GET['page'] == 'wpcumanage-group' ){
    // Styles
    wp_enqueue_style( 'wpcumanage-admin-styles', WPCU_MANAGEMENT_URL . 'admin/assets/css/admin-styles.css', array(), WPCU_MANAGEMENT_VERSION );
    // Scripts
    wp_register_script( 'wpcumanage-admin-scripts', WPCU_MANAGEMENT_URL . 'admin/assets/js/admin-scripts.js', array(), WPCU_MANAGEMENT_VERSION, true );
    // Enqueue Registered Scripts
    wp_enqueue_script( 'wpcumanage-admin-scripts' );

    $translation = array(
        'ajaxurl' => admin_url('admin-ajax.php'),
    );
    wp_localize_script( 'wpcumanage-admin-scripts', 'wpcumanageAjaxHandler', $translation );  

    }

}


add_action( 'admin_enqueue_scripts', 'wpcumanage_admin_enqueue_scripts' );
function wpcumanage_registered_styles( $styles ){
    $styles[] = 'wpcumanage-select2-styles';
    $styles[] = 'wpcumanage-styles';
    return $styles;
} 
function wpcumanage_registered_scripts( $scripts ){
    $scripts[] = 'wpcumanage-select2-scripts';
    $scripts[] = 'wpcumanage-scripts';
    return $scripts;
}     