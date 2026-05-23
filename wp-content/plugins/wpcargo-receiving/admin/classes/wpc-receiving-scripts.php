<?php
if ( ! defined( 'ABSPATH' ) ) {	
	exit;
}
class WPC_Receiving_Admin_Scripts {	
	public function __construct(){	
		add_action( 'admin_enqueue_scripts', array( $this, 'wpc_receiving_enqueue' ) );	
		add_action( 'wp_enqueue_scripts', array( $this, 'wpc_receiving_enqueue' ) );	
	}	
	public function wpc_receiving_enqueue() {
	     global $post; 
	    
	     if ( is_a( $post, 'WP_Post' ) && (has_shortcode( $post->post_content, 'wpcr-update') || $post->ID == wpcfe_admin_page())  || ( isset($_GET['page'] ) && ($_GET['page'] == 'wpc-receiving' ))) {
		$get_options = get_option('wpcargo_receiving_settings');
		$enableBeepSound = !empty($get_options['beeb_sound'])? $get_options['beeb_sound'] : '';
		$translation   = array(
                'wpc_ajax_receiving_url'	=> admin_url( 'admin-ajax.php' ),
                'adminURL'	=> admin_url( ),
                'bulkErrorMessage'      => __('No shipment selected, Please select atleast one Shipment.', 'wpcargo-receiving'),
                'bulkFileErrorMessage'  => __('Something went wrong while processing your request, please reload and try again.', 'wpcargo-receiving'),
                'beepSoundSrc' 	=> WPCARGO_RECEIVING_URL.'admin/assets/audio/laser-beep.mp3',
                'enableBeepSound' => $enableBeepSound,
                'siteUrl' => site_url(),
            );
            
   
       
		wp_enqueue_script( 'ajax-receiving-script', WPCARGO_RECEIVING_URL.'admin/assets/js/wpc-receiving-ajax.js', array('jquery') );
		wp_enqueue_script('script', WPCARGO_RECEIVING_URL.'assets/js/script.js', array(), WPCARGO_RECEIVING_VERSION);
		wp_localize_script( 'ajax-receiving-script', 'wpcajaxReceiving', $translation );	
		wp_enqueue_style('wpcargo-receiving-admin-style', WPCARGO_RECEIVING_URL . 'admin/assets/css/receiving-admin-style.css');
		
    }
	}
}
$wpc_receiving_admin_scripts =  new WPC_Receiving_Admin_Scripts;