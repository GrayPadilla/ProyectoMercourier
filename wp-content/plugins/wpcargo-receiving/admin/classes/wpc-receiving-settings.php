<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WPCargo_Receiving_Admin_Scripts{
	public function __construct(){
		add_action('admin_menu', array( $this, 'add_receiving_settings_menu' ) );
		add_action( 'admin_init', array( $this, 'register_wpcargo_receiving_settings') );
		add_action('wpc_add_settings_nav', array( $this, 'wpc_add_settings_nav_receiving') );
	}
	public function add_receiving_settings_menu(){
		add_submenu_page(
			'wpcargo-settings',
			__( 'Receiving Settings', 'wpcargo-receiving' ),
			__( 'Receiving Settings', 'wpcargo-receiving' ),
			'manage_options',
			'admin.php?page=wpcargo-receiving-settings'
		);
		add_submenu_page(
			'wpcargo-receiving-settings',
			__( 'Receiving Settings', 'wpcargo-receiving' ),
			__( 'Receiving Settings', 'wpcargo-receiving' ),
			'manage_options',
			'wpcargo-receiving-settings',
			array( $this, 'add_receiving_settings_menu_callback' )
		);
	}
	public function add_receiving_settings_menu_callback(){
		$options 		= get_option('wpcargo_receiving_settings');
		$page_options 	= get_option('wpcargo_receiving_page_settings');
		?>
		<div class="wrap">
			<h1><?php esc_html_e('Receiving Settings', 'wpcargo-receiving' ); ?></h1>
				<?php
					require_once( WPCARGO_PLUGIN_PATH. 'admin/templates/admin-navigation.tpl.php' );
					require_once( WPCARGO_RECEIVING_PATH. 'admin/templates/wpc-receiving-settings.tpl.php' );
				?>
		</div>
		<?php
	}
	public function register_wpcargo_receiving_settings() {
		//register our settings
		register_setting( 'wpcargo_receiving_settings', 'wpcargo_receiving_settings' );
		register_setting( 'wpcargo_receiving_settings', 'wpcargo_receiving_page_settings' );
	}
	function wpc_add_settings_nav_receiving() {
		$view = $_GET['page'];
		?>
			<a class="nav-tab <?php echo ( $view == 'wpcargo-receiving-settings') ? 'nav-tab-active' : '' ;  ?>" href="<?php echo admin_url().'admin.php?page=wpcargo-receiving-settings'; ?>" ><?php esc_html_e('Receiving Settings', 'wpcargo-receiving' ); ?></a>
		<?php
	}
}
new WPCargo_Receiving_Admin_Scripts;