<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}
class WPC_POD_Scripts {
	public function __construct(){
		add_action( 'wp_enqueue_scripts', array( $this, 'wpc_pod_enqueue' ) );
	}
	function wpc_pod_enqueue() {
		global $post;
		if( !empty( $post ) && ( 
			get_page_template_slug( $post->ID ) == 'dashboard.php' || 
			( function_exists( 'wpcfe_admin_page' ) && $post->ID == wpcfe_admin_page() )
			) ){
			//** Styles
			wp_enqueue_style('wpcargo-pod-dashboard-style', WPCARGO_POD_URL . 'assets/css/pod-dashboard.css');
        }
	    if ( is_a( $post, 'WP_Post' ) && ( 
	    		has_shortcode( $post->post_content, 'wpc_driver_accounts') || 
	    		( 
	    			(  get_page_template_slug( $post->ID ) == 'dashboard.php' || 
	    				( function_exists( 'wpcfe_admin_page' ) && $post->ID == wpcfe_admin_page() ) 
	    			) 
	    		) 
			) ){
	        //** Styles
			wp_enqueue_style('wpcargo-pod-style', WPCARGO_POD_URL . 'assets/css/wpc-pod-results.css');
			//** Scripts
			wp_enqueue_script('jquery');
			wp_register_script( 'wpcargo-pod-signature-scripts', WPCARGO_POD_URL.'assets/js/signature_pad.umd.js', array('jquery'), WPCARGO_POD_VERSION, TRUE );
			wp_register_script( 'wpcargo-pod-scripts', WPCARGO_POD_URL.'assets/js/pod-scripts.js', array('jquery'), WPCARGO_POD_VERSION, TRUE );	
			wp_enqueue_script( 'wpcargo-pod-signature-scripts');
			wp_enqueue_script( 'wpcargo-pod-scripts');
			wp_localize_script( 'wpcargo-pod-scripts', 'wpcargoPODAJAXHandler',
		        array( 
		            'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'sign_nonce' => wp_create_nonce( 'wpcpod_signature-nonce' ),
					'wpcpod_nonce' => wp_create_nonce( 'wpcpod_nonce' ),
					'custom_meta' => apply_filters('wpcpod_custom_meta', array() )
		        )
		    );
			wp_enqueue_media();
		}	
	}
}
new WPC_POD_Scripts;
add_action('wp_head', function(){
	global $post;
	if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'wpc_driver_accounts') ) {
		$options 		= get_option('wpcargo_option_settings') ? get_option('wpcargo_option_settings') : array() ;
		$baseColor 		= '#00A924';
		if( array_key_exists('wpcargo_base_color', $options) ){
			$baseColor = ( $options['wpcargo_base_color'] ) ? $options['wpcargo_base_color'] : $baseColor ;
		}
		?>
		<style type="text/css">
		#vm-pop-up-content-pod #pod-save,
		#vm-pop-up-content-pod #pod-clear,
		.button.button-wpcargo{
			background-color: <?php echo $baseColor; ?> !important;
			border-color: <?php echo $baseColor; ?> !important;
			padding: 6px 12px !important;
			color:#fff !important;
		}
		#vm-pop-up-content-pod #pod-clear,
		.button.button-wpcargo.button-red{
			background-color: #900 !important;
			border-color: #900 !important;
		}
		.button.button-wpcargo.button-gray{
			background-color: #424242 !important;
			border-color: #424242 !important;
		}
		</style>
		<?php
	}
});
add_action('wp_head', function(){
	?>
	<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, user-scalable=no">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black">
	<?php
});