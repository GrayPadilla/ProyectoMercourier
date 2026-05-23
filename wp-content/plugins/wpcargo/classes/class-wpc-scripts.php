<?php
if (!defined('ABSPATH')){
	exit; // Exit if accessed directly
}
class WPCargo_Scripts{
	function __construct(){
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
		add_action( 'wp_print_styles', array( $this, 'dequeue_scripts' ), 100 );
	}
	function frontend_scripts(){
		global $wpcargo, $post, $wpdb;
		$page_url = get_the_permalink( );
		// Styles
		wp_register_style('wpcargo-custom-bootstrap-styles', WPCARGO_PLUGIN_URL . 'assets/css/main.min.css', array(), WPCARGO_VERSION );
		wp_register_style('wpcargo-fontawesome-styles', WPCARGO_PLUGIN_URL . 'assets/css/fontawesome.min.css', array(), WPCARGO_VERSION );
		wp_register_style('wpcargo-styles', WPCARGO_PLUGIN_URL . 'assets/css/wpcargo-style.css', array(), WPCARGO_VERSION );	
		wp_register_style('wpcargo-datetimepicker', WPCARGO_PLUGIN_URL . 'admin/assets/css/jquery.datetimepicker.min.css', array(), WPCARGO_VERSION );

		
		$shortcode_found = false;
		
       
       if ( shortcode_exists( 'wpcargo_trackform' )  || shortcode_exists( 'wpcargo_trackresults' ) ||  shortcode_exists( 'wpcargo_multi_track' ) ||  shortcode_exists( 'wpcargo_multi_track_result' ) ||  shortcode_exists( 'wpcargo_account' ) ||  shortcode_exists( 'wpc-ca-account' )) {
            $shortcode_found = true;
        }

       if ( $shortcode_found ) {
           	wp_enqueue_style('wpcargo-custom-bootstrap-styles');
		    wp_enqueue_style('wpcargo-styles');	
	
           	wp_enqueue_style('wpcargo-fontawesome-styles');
            wp_enqueue_style( 'wpcargo-datetimepicker' );
			wp_register_style('wpcargo-bootstrap-style', WPCARGO_PLUGIN_URL . 'assets/css/bootstrap.min.css', array(), WPCARGO_VERSION );
            wp_enqueue_style( 'wpcargo-bootstrap-style' );

		}

		// Scripts
		$translation_array = array(
			'ajax_url'  		=> admin_url( 'admin-ajax.php' ),
			'pageURL' 			=> $page_url,
			'date_format' 		=> $wpcargo->date_format,
			'time_format' 		=> $wpcargo->time_format,
			'datetime_format' 	=> $wpcargo->datetime_format
		);

		wp_register_script( 'wpcargo-js', WPCARGO_PLUGIN_URL.'assets/js/wpcargo.js', array( 'jquery' ), WPCARGO_VERSION, false );
		wp_register_script( 'wpcargo-datetimepicker', WPCARGO_PLUGIN_URL . 'admin/assets/js/jquery.datetimepicker.full.min.js', array( 'jquery' ), WPCARGO_VERSION, false );
		wp_localize_script( 'wpcargo-js', 'wpcargoAJAXHandler', $translation_array );
		wp_enqueue_script( 'jquery');



		if ( $shortcode_found ) {
		wp_enqueue_script( 'wpcargo-js');
		wp_enqueue_script( 'wpcargo-datetimepicker' );
			wp_register_script( 'wpcargo-util-index', WPCARGO_PLUGIN_URL . 'assets/js/dist/util/index.js', array( 'jquery' ), WPCARGO_VERSION, false );
			wp_register_script( 'wpcargo-event-handler', WPCARGO_PLUGIN_URL . 'assets/js/dist/dom/event-handler.js', array( 'jquery' ), WPCARGO_VERSION, false );
			wp_register_script( 'wpcargo-selector-engine', WPCARGO_PLUGIN_URL . 'assets/js/dist/dom/selector-engine.js', array( 'jquery' ), WPCARGO_VERSION, false );
			wp_register_script( 'wpcargo-dom-data', WPCARGO_PLUGIN_URL . 'assets/js/dist/dom/data.js', array( 'jquery' ), WPCARGO_VERSION, false );
			wp_register_script( 'wpcargo-dom-manipulator', WPCARGO_PLUGIN_URL . 'assets/js/dist/dom/manipulator.js', array( 'jquery' ), WPCARGO_VERSION, false );
			wp_register_script( 'wpcargo-util-config', WPCARGO_PLUGIN_URL . 'assets/js/dist/util/config.js', array( 'jquery' ), WPCARGO_VERSION, false );
			wp_register_script( 'wpcargo-base-component', WPCARGO_PLUGIN_URL . 'assets/js/dist/base-component.js', array( 'jquery' ), WPCARGO_VERSION, false );
			wp_register_script( 'wpcargo-collapse', WPCARGO_PLUGIN_URL . 'assets/js/dist/collapse.js', array( 'jquery' ), WPCARGO_VERSION, false );
			wp_register_script( 'wpcargo-bootstrap-script', WPCARGO_PLUGIN_URL . 'assets/js/bootstrap.min.js', array( 'jquery' ), WPCARGO_VERSION, false );
			wp_enqueue_script( 'wpcargo-util-index' );
			wp_enqueue_script( 'wpcargo-event-handler' );
			wp_enqueue_script( 'wpcargo-selector-engine' );
			wp_enqueue_script( 'wpcargo-dom-data' );
			wp_enqueue_script( 'wpcargo-dom-manipulator' );
			wp_enqueue_script( 'wpcargo-util-config' );
			wp_enqueue_script( 'wpcargo-base-component' );
            wp_enqueue_script( 'wpcargo-collapse' );
			wp_enqueue_script( 'wpcargo-bootstrap-script' );

		}

	}
	function dequeue_scripts(){
		// Dequeue Import / Export Add on Style
        wp_dequeue_style('wpc_import_export_css');
	}
}
new WPCargo_Scripts;
add_action('wp_head', function(){
	$options 		= get_option('wpcargo_option_settings');
	$baseColor 		= '#00A924';
	if( $options ){
		if( array_key_exists('wpcargo_base_color', $options) ){
			$baseColor = ( $options['wpcargo_base_color'] ) ? $options['wpcargo_base_color'] : $baseColor ;
		}
	}
	?>
	<style type="text/css">
		:root {
		  --wpcargo: <?php echo esc_html($baseColor); ?>;
		}
	</style>
	<?php
});