<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
class WPCFE_Scripts{
    function __construct(){
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
        add_action( 'wp_print_styles', array( $this, 'dequeue_scripts' ), 100 );
    }
    function enqueue_scripts() {
        global $post;
        if( !empty( $post ) ){
            $template = get_page_template_slug( $post->ID );
            if( $template == 'dashboard.php' 
                || $post->ID == wpcfe_admin_page() 
                || ( is_a( $post, 'WP_Post' ) && ( has_shortcode( $post->post_content, 'wpcfe_registration')  ) )
              ){
                wp_enqueue_style( 'wpcfe-bootstrap-styles', WPCFE_URL . 'assets/css/bootstrap.min.css', array(), WPCFE_VERSION );
                wp_enqueue_style( 'wpcfe-font-awesome-styles', WPCFE_URL . 'assets/css/font-awesome.min.css', WPCFE_VERSION );                    
                wp_enqueue_style( 'wpcfe-select2-styles', WPCFE_URL . 'assets/css/select2.min.css', array(), WPCFE_VERSION );
                wp_enqueue_style( 'wpcfe-mdb-styles', WPCFE_URL . 'assets/css/mdb.min.css', array(), WPCFE_VERSION );
                wp_enqueue_style( 'wpcfe-croppie-styles', WPCFE_URL . 'assets/css/croppie.css', array(), WPCFE_VERSION );
                wp_enqueue_style( 'wpcfe-styles', WPCFE_URL . 'assets/css/style.css', WPCFE_VERSION );
                wp_enqueue_style( 'wpcfe-wpcfm-styles', WPCFE_URL . 'assets/css/wpcfm-style.css', WPCFE_VERSION );
                // Scripts       
                wp_register_script( 'wpcfe-dashboard-theme', WPCFE_URL. 'assets/js/jquery-3.3.1.min.js', array( 'jquery' ), WPCFE_VERSION, true );
                wp_register_script( 'wpcfe-popper-scripts', WPCFE_URL. 'assets/js/popper.min.js', array( 'jquery' ), WPCFE_VERSION, true );
                wp_register_script( 'wpcfe-bootstrap-scripts', WPCFE_URL. 'assets/js/bootstrap.min.js', array( 'jquery' ), WPCFE_VERSION, true );
                wp_register_script( 'wpcfe-mdb-scripts', WPCFE_URL. 'assets/js/mdb.min.js', array( 'jquery', 'wpcfe-bootstrap-scripts' ), WPCFE_VERSION, true );
                wp_register_script( 'wpcfe-select2-scripts', WPCFE_URL. 'assets/js/select2.min.js', array( 'jquery' ), WPCFE_VERSION, true );  
                wp_register_script( 'wpcfe-croppie-scripts', WPCFE_URL. 'assets/js/croppie.js', array( 'jquery' ), WPCFE_VERSION, true );               
                wp_register_script( 'wpcfe-repeater-js', WPCFE_URL. 'assets/js/jquery.repeater.min.js', array( 'jquery' ), WPCFE_VERSION, true );  
                wp_register_script( 'wpcfe-datetime-scripts', WPCFE_URL . 'assets/js/datetime-scripts.js', array( 'jquery' ), WPCFE_VERSION, true );           	
                wp_register_script( 'wpcfe-chart-scripts', WPCFE_URL . 'assets/js/chart.min.js', array( 'jquery' ), WPCFE_VERSION, false );           	
                wp_register_script( 'wpcfe-chart-util-scripts', WPCFE_URL . 'assets/js/util.js', array( 'jquery' ), WPCFE_VERSION, false );           	
            	wp_register_script( 'wpcfe-registration-scripts', WPCFE_URL . 'assets/js/registration-scripts.js', array( 'jquery' ), WPCFE_VERSION, true );
            	
            	wp_register_script( 'wpcfe-scripts', WPCFE_URL . 'assets/js/script.js', array( 'jquery' ), WPCFE_VERSION, true );
            	
                wp_enqueue_script( 'jquery' );
                wp_enqueue_media();
                wp_enqueue_script( 'wp-mediaelement' );
                wp_enqueue_script('wpcfe-dashboard-theme');
                wp_enqueue_script('wpcfe-popper-scripts');  
                wp_enqueue_script('wpcfe-bootstrap-scripts');        
                wp_enqueue_script('wpcfe-mdb-scripts');        
                wp_enqueue_script('wpcfe-bootstrap-datepicker-scripts');
                wp_enqueue_script('wpcfe-select2-scripts');
                wp_enqueue_script('wpcfe-croppie-scripts');
                wp_enqueue_script('wpcfe-repeater-js');
                wp_enqueue_script('wpcfe-datetime-scripts' );

                if( isset($_GET['wpcfe']) && $_GET['wpcfe'] == 'dashboard' ){
                    wp_enqueue_script('wpcfe-chart-scripts' );
                    wp_enqueue_script('wpcfe-chart-util-scripts' );
                }

            	wp_enqueue_script('wpcfe-scripts' );
                $notification = array();
                if( isset( $_POST['wpcfe-notification'] ) ){
                    $notification = $_POST['wpcfe-notification'];
                    unset( $_POST['wpcfe-notification'] );
                }
                $wpcfe_admin = get_option( 'wpcfe_admin', 0 );
                $dashboardURL = ( $wpcfe_admin ) ? get_the_permalink( $wpcfe_admin ) : get_the_permalink() ;
                $translation   = array(
                    'ajaxurl'               => admin_url( 'admin-ajax.php' ),
                    'notification'          => json_encode( $notification ),
                    'shipmentConfirmation'  => __('Are you sure you want to delete this selected Shipment?', 'wpcargo-frontend-manager'),
                    'downloadErrorMessage'  => __('No shipment selected, Please select atleast one Shipment.', 'wpcargo-frontend-manager'),
                    'downloadFileErrorMessage'  => __('Something went wrong while processing your request, please reload and try again.', 'wpcargo-frontend-manager'),
                    'inputTooShort'         => __('Please enter more characters', 'wpcargo-frontend-manager'),
                    'inputTooLong'          => __('Please delete some character', 'wpcargo-frontend-manager'),
                    'errorLoading'          => __('Error loading results', 'wpcargo-frontend-manager'),
                    'loadingMore'           => __('Loading more results', 'wpcargo-frontend-manager'),
                    'noResults'             => __('No results found', 'wpcargo-frontend-manager'),
                    'searching'             => __('Searching...', 'wpcargo-frontend-manager'),
                    'maximumSelected'       => __('Error loading results', 'wpcargo-frontend-manager'),
                    'avatar_placeholder'    => WPCFE_URL.'assets/images/wpc-avatar.png',
                    'errorInCorrectEmail'   => __('Incorrect Email Format.', 'wpcargo-frontend-manager'),
                    'errorEmailExist'       => __('Email already Exist.', 'wpcargo-frontend-manager'),
                    'errorPasswordNotMatch' => __('Password NOT matched.', 'wpcargo-frontend-manager'),
                    'errorPasswordlength'   => __('Password must at least 6 Characters.', 'wpcargo-frontend-manager'),
                    'dashboardURL'          => $dashboardURL,
                    'usernameLabel'         => __('Username', 'wpcargo-frontend-manager'),
                    'passwordLabel'         => __('Password', 'wpcargo-frontend-manager'),
                    'downloadLabel'         => __('Download', 'wpcargo-frontend-manager'),
                    'confirmRepeaterDelete' => __('Are you sure you want to delete section?', 'wpcargo-frontend-manager'),
                    'bulkUpdateError'       => __('No shipment has been updated', 'wpcargo-frontend-manager'),
                    'bulkUpdateSuccess'     => __('Selected Shipements has been updated', 'wpcargo-frontend-manager'),
                    'pageURL'               => get_the_permalink(),
                );
                $translation_datetime   = array(
                    'disableDatepicker'    => apply_filters( 'wpcfe_disable_datepicker', false ),
                    'disableTimepicker'    => apply_filters( 'wpcfe_disable_timepicker', false ),
                    'dateFormat'            => apply_filters( 'wpcfe_date_format','yyyy-mm-dd' ),
                    'timeFormat'            => apply_filters( 'wpcfe_time_twelvehour_format', false ),
                );
            	wp_localize_script( 'wpcfe-scripts', 'wpcfeAjaxhandler', $translation );
            	wp_localize_script( 'wpcfe-datetime-scripts', 'wpcfeDateTimeAjaxhandler', $translation_datetime );
            }
        }
        if ( class_exists( 'WooCommerce' ) ) {
            wp_register_script( 'wpcfe-registration-scripts', WPCFE_URL . 'assets/js/registration-scripts.js', array( 'jquery' ), WPCFE_VERSION, true );
            wp_enqueue_script( 'wpcfe-registration-scripts');
            wp_localize_script( 'wpcfe-registration-scripts', 'wpcfeRegistrationAjaxhandler', array(
                'ajaxurl'              => admin_url( 'admin-ajax.php' ),
                'baseCountry'          => wpcfe_get_base_country(),
                'selectType'          => __('-- Select Type --', 'wpcargo-frontend-manager')
            ) );
        }
		wp_register_script( 'non-wpcfe-scripts', WPCFE_URL . 'assets/js/non-script.js', array( 'jquery' ), WPCFE_VERSION, true );
		wp_enqueue_script( 'non-wpcfe-scripts' );
		wp_localize_script( 'non-wpcfe-scripts', 'nonwpcfeAjaxhandler', array('ajaxurl' => admin_url( 'admin-ajax.php' )) );
    }
    
    function admin_enqueue_scripts(){
        $screen = get_current_screen();
        $translation   = array(
            'ajaxurl'                   => admin_url( 'admin-ajax.php' ),
            'optionPlaceholder'         => __('Select an option', 'wpcargo-frontend-manager'),
            'adminURL'                  => admin_url( ),
            'downloadErrorMessage'      => __('No shipment selected, Please select atleast one Shipment.', 'wpcargo-frontend-manager'),
            'downloadFileErrorMessage'  => __('Something went wrong while processing your request, please reload and try again.', 'wpcargo-frontend-manager'),
        );
        // Enqueue script only when the page is WPCFE Settings
        if( ( isset( $_GET['page'] ) && $_GET['page'] == 'wpcfe-settings' ) 
            || ( isset($_GET['post_type']) && $_GET['post_type'] == 'wpcargo_shipment' ) 
            || ( isset($_GET['wpcfe']) && $_GET['wpcfe'] == 'track' )){
            // Styles
            wp_enqueue_style( 'wpcfe-select2-styles', WPCFE_URL . 'assets/css/select2.min.css', array(), WPCFE_VERSION );
            // Scripts
            wp_register_script( 'wpcfe-select2-scripts', WPCFE_URL. 'assets/js/select2.min.js', array( 'jquery' ), WPCFE_VERSION, false );   
            wp_register_script( 'wpcfe-scripts', WPCFE_URL . 'admin/assets/js/script.js', array( 'jquery' ), WPCFE_VERSION, false );
            wp_enqueue_script( 'jquery' );
            wp_enqueue_script('wpcfe-select2-scripts');
            wp_enqueue_script('wpcfe-scripts' );
            
            wp_localize_script( 'wpcfe-scripts', 'wpcfeAjaxhandler', $translation );
        }
        if( $screen->id == 'users' ){
            wp_register_script( 'wpcfe-users-scripts', WPCFE_URL . 'admin/assets/js/users.js', array( 'jquery' ), WPCFE_VERSION, false );
            wp_enqueue_script( 'jquery' );
            wp_enqueue_script('wpcfe-users-scripts' );
            wp_localize_script( 'wpcfe-users-scripts', 'wpcfeAjaxhandler', $translation );
        }
    }
    function dequeue_scripts(){
        global $post, $wp_scripts, $wp_styles;
        if( !empty( $post ) ){
            $template = get_page_template_slug( $post->ID );
            if( $template == 'dashboard.php' || $post->ID == wpcfe_admin_page() ){
                $_scripts = array();
                // Print all loaded Scripts (JS)
                foreach( $wp_scripts->queue as $script ) :
                    $source =  $wp_scripts->registered[$script]->src;
                    $ex_source = explode('/', $source );
                    if( !in_array( $script, wpcfe_registered_scripts() ) ){
                        $_scripts[] = $wp_scripts->registered[$script]->handle;
                        wp_dequeue_script( $wp_scripts->registered[$script]->handle );
                    }
                endforeach;
                $_styles = array();
                // Print all loaded Styles (CSS)
                foreach( $wp_styles->queue as $style ) :
                    $source =  $wp_styles->registered[$style]->src;
                    $ex_source = explode('/', $source );
                    if( !in_array( $style, wpcfe_registered_styles() ) ){
                        $_styles[] = $wp_styles->registered[$style]->handle;
                        wp_dequeue_style( $wp_styles->registered[$style]->handle );
                    }
                endforeach;
                // Dequeue Jquery Script
                $dequeue_script_list = array(
                    "jquery-ui-draggable"
                );
                foreach($dequeue_script_list as $dscript){
                    wp_deregister_script($dscript);
                }
            }
        }
    }
}
new WPCFE_Scripts;