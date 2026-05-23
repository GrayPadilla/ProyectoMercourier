<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}
class WPC_POD_Settings { 
	public $text_domain = 'wpcargo-pod';
	function __construct(){	
		add_action('admin_menu', array( $this, 'wpc_pod_submenu_field_page' ), 87 );
		add_action( 'admin_init', array( $this, 'register_pod_settings') );	
		add_action('wpc_add_settings_nav', array( $this, 'wpc_add_settings_nav_pod') , 87);
	}
	
	public function wpc_pod_submenu_field_page(){
	
		add_submenu_page( 
			'wpcargo-settings', 
			__( 'Proof of Delivery Settings', 'wpcargo-pod' ),
			__( 'Proof of Delivery Settings', 'wpcargo-pod' ),
			'manage_options',
			'admin.php?page=wpc-pod-settings'
		);
		add_submenu_page( 
			'wpc-pod-settings', 
			__( 'Proof of Delivery Settings', 'wpcargo-pod' ),
			__( 'Proof of Delivery Settings', 'wpcargo-pod' ),
			'manage_options',
			'wpc-pod-settings',
			array( $this, 'register_pod_submenu_page_callback' )
		);

		add_submenu_page( 
			'wpcargo-settings', 
			__( 'POD APP Settings', 'wpcargo-pod' ),
			__( 'POD APP Settings', 'wpcargo-pod' ),
			'manage_options',
			'admin.php?page=wpc-podapp-settings'
		);
		add_submenu_page( 
			'wpc-podapp-settings', 
			__( 'POD APP Settings', 'wpcargo-pod' ),
			__( 'POD APP Settings', 'wpcargo-pod' ),
			'manage_options',
			'wpc-podapp-settings',
			array( $this, 'register_podapp_submenu_page_callback' )
		);
		
	}
	
	public function register_pod_submenu_page_callback(){	
		global $wpcargo;
		$options = get_option('wpcargo_pod_option_settings') ? get_option('wpcargo_pod_option_settings') : array();	
		$wpcargo_options = get_option('wpcargo_option_settings');
		ob_start();
		$shipper_fields = get_field_section('shipper_info');
		$receiver_fields = get_field_section('receiver_info');
		$shipper_selected_option = array();
		$receiver_selected_option = array();
		if( array_key_exists('shipper_fields', $options) ){
			$shipper_selected_option = $options['shipper_fields'];
		}
		if( array_key_exists('receiver_fields', $options) ){
			$receiver_selected_option = $options['receiver_fields'];
		}
		?>
        <div class="wrap">
        	<h1><?php esc_html_e('Proof of Delivery Settings', 'wpcargo-pod' ); ?></h1>
            <?php require_once( WPCARGO_POD_PATH.'../wpcargo/admin/templates/admin-navigation.tpl.php'); ?>
            <?php require_once( wpcpod_include_admin_template( 'wpc-pod-settings.tpl' ) ); ?>
        </div>
        <?php
		echo ob_get_clean();
	}

	public function register_podapp_submenu_page_callback(){
		global $wpcargo;
		$podapp_status 	= get_option('wpcargo_podapp_status') ? get_option('wpcargo_podapp_status') : array();	
		$api_status 	= wpcpod_api_shipment_status( );
		$api_delivered 	= !empty( $podapp_status ) && array_key_exists( 'delivered', $podapp_status  ) ? $podapp_status['delivered'] : '' ;
		$api_cancelled 	= !empty( $podapp_status ) && array_key_exists( 'cancelled', $podapp_status  ) ? $podapp_status['cancelled'] : '' ;
		$unrequired_fields = get_option( 'wpcargo_podapp_unrequired_fields' );
		$unrequired_fields = !empty( $unrequired_fields ) && is_array( $unrequired_fields) ? $unrequired_fields : array() ;
		ob_start();
		?>
        <div class="wrap">
        	<h1><?php esc_html_e('Proof of Delivery Settings', 'wpcargo-pod' ); ?></h1>
            <?php require_once( WPCARGO_POD_PATH.'../wpcargo/admin/templates/admin-navigation.tpl.php'); ?>
			<?php require_once( wpcpod_include_admin_template( 'wpc-podapp-settings.tpl' ) ); ?>
        </div>
        <?php
		echo ob_get_clean();
	}
	
	public function register_pod_settings() {
		
		// ------------------------ Delivery Driver Route & POD App Register Settings -------------------------------------- //

		register_setting( 'wpcargo_pod_settings_group', 'wpcargo_pod_option_settings' );
		register_setting( 'wpcargo_pod_settings_group', 'wpcargo_pod_status' );
		register_setting( 'wpcargo_pod_settings_group', 'wpcpod_route_status' );
		register_setting( 'wpcargo_pod_settings_group', 'wpcpod_route_field' );
		register_setting( 'wpcargo_pod_settings_group', 'wpcpod_route_segment_info' );
		register_setting( 'wpcargo_pod_settings_group', 'wpcpod_route_origin' );
		register_setting( 'wpcargo_pod_settings_group', 'wpcpod_page_route' );
		register_setting( 'wpcargo_pod_settings_group', 'wpcpod_page_report' );		
		register_setting( 'wpcargo_podapp_settings_group', 'wpcargo_podapp_status' );
		register_setting( 'wpcargo_podapp_settings_group', 'wpcargo_podapp_unrequired_fields' );

		// ------------------------ Pickup Driver Route Register Settings -------------------------------------- //

		register_setting( 'wpcargo_pod_settings_group', 'wpcpod_pickup_route_origin' );
		register_setting( 'wpcargo_pod_settings_group', 'wpcpod_pickup_route_status' );
		register_setting( 'wpcargo_pod_settings_group', 'wpcpod_pickup_route_field' );
		register_setting( 'wpcargo_pod_settings_group', 'wpcpod_pickup_route_segment_info' );

	}
	
	function wpc_add_settings_nav_pod() {
		$view = $_GET['page'];
		?>	
			<a class="nav-tab <?php echo ( $view == 'wpc-pod-settings') ? 'nav-tab-active' : '' ;  ?>" href="<?php echo admin_url().'admin.php?page=wpc-pod-settings'; ?>" ><?php _e('Proof of Delivery Settings', 'wpcargo-pod'); ?></a>
			<a class="nav-tab <?php echo ( $view == 'wpc-podapp-settings') ? 'nav-tab-active' : '' ;  ?>" href="<?php echo admin_url().'admin.php?page=wpc-podapp-settings'; ?>" ><?php _e('POD APP', 'wpcargo-pod'); ?></a>
		<?php
	}
}
$wpc_pod_settings = new WPC_POD_Settings;
