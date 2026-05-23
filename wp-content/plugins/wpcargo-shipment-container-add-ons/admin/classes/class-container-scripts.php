<?php
if (!defined('ABSPATH')){
    exit; // Exit if accessed directly
}
class WPCargo_Container_Scripts{
	public $text_domain = 'wpcargo-shipment-container';
	function __construct(){
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ), 100 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'wp_print_styles', array( $this, 'dequeue_scripts' ), 100 );
	}
	function frontend_scripts() {
		global $wp_query; 
		$page_id = $wp_query->get_queried_object_id();

		$box_page = '';
		if( function_exists('wpcsb_box_frontend_page')){
			$box_page = wpcsb_box_frontend_page();
		}

		# enqueue only on containers page
		if(($page_id == wpc_container_frontend_page()) || ($page_id == wpcfe_admin_page()) || ( !empty($box_page) && $page_id == $box_page ) ){
			//** Styles
			wp_enqueue_style( 'shipment-container-styles', WPCARGO_SHIPMENT_CONTAINER_URL . 'assets/css/wpc-container.styles.css', array(),  WPCARGO_SHIPMENT_CONTAINER_VERSION );
			wp_enqueue_style( 'shipment-container-datatable-styles', WPCARGO_SHIPMENT_CONTAINER_URL . 'assets/css/datatables.min.css', array(),  WPCARGO_SHIPMENT_CONTAINER_VERSION );
			//** Scripts
			wp_enqueue_script( 'shipment-container-sortable-scripts', WPCARGO_SHIPMENT_CONTAINER_URL . 'assets/js/sortable.js', array('jquery'), WPCARGO_SHIPMENT_CONTAINER_VERSION, true );
			wp_enqueue_script( 'shipment-container-datatable-scripts', WPCARGO_SHIPMENT_CONTAINER_URL . 'assets/js/datatables.min.js', array('jquery'), WPCARGO_SHIPMENT_CONTAINER_VERSION, true );
			wp_enqueue_script( 'shipment-container-scripts', WPCARGO_SHIPMENT_CONTAINER_URL . 'assets/js/wpc-container-scripts.js', array('jquery'), WPCARGO_SHIPMENT_CONTAINER_VERSION, true );
			$data_array = array(
				'ajaxurl' 				=> admin_url( 'admin-ajax.php' ),
				'loadPreAssigned' => wpcsc_is_load_pre_assigned_shipments(),
				'includeUrl'			=> includes_url( ),
				'processError'			=> __( 'Something went wrong during process, Please reload the page.', 'wpcargo-shipment-container'),
				'bulkDeleteConfirm'		=> wpc_scpt_delete_containers_confirm_message(),
				'messageStatus'			=> wpc_scpt_select_status_update_message(),
				'messageContainers'		=> wpc_scpt_select_containers_update_message(),
				'messageContainersExist'=> wpc_scpt_containers_exist_message(),
				'dataTableInfo' 		=> wpcsc_datatable_info_callback(),
				'selectedErrorMessage'  => wpc_scpt_no_shipment_selected_message(),
				'bulkUpdateSuccess'     => wpc_scpt_selected_shipment_assigned_message(),
				'bulkUpdateError'       => wpc_scpt_no_shipment_updated_message(),
				'hideLabel'       		=> __('Hide', 'wpcargo-shipment-container'),
				'showLabel'       		=> __('Show', 'wpcargo-shipment-container'),
				'dataTablePageLength' 	=> apply_filters( 'wpccon_assign_shipment_page_length', 12 ),
				'dataTablePaging'  		=> apply_filters( 'wpccon_assign_shipment_paging', true ),
			);
			wp_localize_script( 'shipment-container-scripts', 'shipmentContainerAjaxHandler', $data_array );
		}

		if( isset( $_GET['wpcsc'] ) && ( $_GET['wpcsc'] == 'import' || $_GET['wpcsc'] == 'export' ) ){
			wp_enqueue_script( 'wpcsc-importexport-scripts', WPCARGO_SHIPMENT_CONTAINER_URL . 'assets/js/wpcsc-importexport.js', array('jquery'), WPCARGO_SHIPMENT_CONTAINER_VERSION, true );
			wp_localize_script( 'wpcsc-importexport-scripts', 'shipmentContainerAjaxHandler', array(
				'ajaxurl' 		=> admin_url( 'admin-ajax.php' ),
				'nonce' 		=> wp_create_nonce('wpcsc_import_export_nonce'),
				'processExport' => __('Processing data to export', 'wpcargo-shipment-container'),
				'uploadingFile' => __('Uploading file', 'wpcargo-shipment-container'),
				'processingData' => __('Processing Data', 'wpcargo-shipment-container'),
				'processingCompleted' => __('Process Completed', 'wpcargo-shipment-container'),
				'downloadTemplate' => __('Downloading import template', 'wpcargo-shipment-container')
			) );
		}

	}
	function admin_scripts(){
		global $wpcargo;
		$postID = isset($_GET['post'])? $_GET['post']: '';
		$post_type = get_post_type($postID);

		//** Styles
		if( ( isset($_GET['page']) && $_GET['page'] == 'wpc-container-settings' )){
		wp_enqueue_script( 'shipment-container-settings', WPCARGO_SHIPMENT_CONTAINER_URL . 'admin/assets/js/settings-helper.js', array('jquery'), WPCARGO_SHIPMENT_CONTAINER_VERSION );
		wp_enqueue_style( 'shipment-container-admin-styles', WPCARGO_SHIPMENT_CONTAINER_URL . 'admin/assets/css/admin-styles.css', array(), WPCARGO_SHIPMENT_CONTAINER_VERSION );
			}
		if( ( isset($_GET['post_type']) && $_GET['post_type'] == 'shipment_container' )
			|| ( isset( $_GET['page'] ) && $_GET['page'] == 'print-shipment-container' ) || $post_type == 'shipment_container'){
			//** Styles
			wp_enqueue_style( 'shipment-container-admin-styles', WPCARGO_SHIPMENT_CONTAINER_URL . 'admin/assets/css/admin-styles.css', array(), WPCARGO_SHIPMENT_CONTAINER_VERSION );
			wp_enqueue_style( 'shipment-container-datetimepicker-styles', WPCARGO_SHIPMENT_CONTAINER_URL . 'admin/assets/css/jquery.datetimepicker.min.css', array(), WPCARGO_SHIPMENT_CONTAINER_VERSION );
			wp_enqueue_style( 'shipment-container-select2-styles', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css', array(), WPCARGO_SHIPMENT_CONTAINER_VERSION );
			wp_enqueue_style( 'shipment-container-datatable-styles', WPCARGO_SHIPMENT_CONTAINER_URL . 'assets/css/datatables.min.css', array(),  WPCARGO_SHIPMENT_CONTAINER_VERSION );
			//** for Jquery dialog box css
			wp_enqueue_style( 'shipment-container-jquery-ui-styles', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css', array(), WPCARGO_SHIPMENT_CONTAINER_VERSION );
			//** Scripts
			wp_register_script( 'wpcargo-repeater', WPCARGO_SHIPMENT_CONTAINER_URL . 'admin/assets/js/jquery.repeater.js', array('jquery'), WPCARGO_SHIPMENT_CONTAINER_VERSION );
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'jquery-ui-dialog' );
			wp_enqueue_script( 'wpcargo-repeater' );
			wp_enqueue_script( 'jquery-ui-autocomplete' );
			wp_enqueue_script( 'wpcargo-select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js', array('jquery'), WPCARGO_SHIPMENT_CONTAINER_VERSION );
			wp_enqueue_script( 'shipment-container-datetimepicker-scripts', WPCARGO_SHIPMENT_CONTAINER_URL . 'admin/assets/js/jquery.datetimepicker.full.min.js', array('jquery'), WPCARGO_SHIPMENT_CONTAINER_VERSION );
			wp_enqueue_script( 'shipment-container-datatable-scripts', WPCARGO_SHIPMENT_CONTAINER_URL . 'assets/js/datatables.min.js', array('jquery'), WPCARGO_SHIPMENT_CONTAINER_VERSION, true );
			wp_enqueue_script( 'shipment-container-scripts', WPCARGO_SHIPMENT_CONTAINER_URL . 'admin/assets/js/admin-scripts.js', array('jquery'), WPCARGO_SHIPMENT_CONTAINER_VERSION );
			$data_array = array(
				'ajaxurl' 		=> admin_url( 'admin-ajax.php' ),
				'includeUrl'	=> includes_url( ),
				'processError'	=> __( 'Something went wrong during process, Please reload the page.', 'wpcargo-shipment-container'),
				'messageStatus'	=> __( 'Please select status to update.', 'wpcargo-shipment-container'),
				'messageContainers'	=> __( 'Please Select '.wpc_container_label_plural().' to update.', 'wpcargo-shipment-container'),
				'dataTableInfo' 	=> wpcsc_datatable_info_callback(),
				'date_format'   		=> $wpcargo->date_format,
				'time_format'   		=> $wpcargo->time_format,
				'datetime_format'   	=> $wpcargo->datetime_format,
			);
			wp_localize_script( 'shipment-container-scripts', 'shipmentContainerAjaxHandler', $data_array );
		}
	}
	function dequeue_scripts(){
		global $post, $wp_scripts;	
		if ( is_a( $post, 'WP_Post' )
            &&  has_shortcode( $post->post_content, 'wpcargo-container')  ) {
				wp_deregister_script( 'jquery-ui-sortable' );
		}
    }
}
new WPCargo_Container_Scripts;
add_action('admin_head', function(){
	$options 		= get_option('wpcargo_option_settings');
	if( empty( $options ) ){
		$options = array();
	}
	$baseColor 		= '#00A924';
	if( array_key_exists('wpcargo_base_color', $options) ){
		$baseColor = ( $options['wpcargo_base_color'] ) ? $options['wpcargo_base_color'] : $baseColor ;
	}
	?>
	<style type="text/css">
		#container-history thead th,
		.post-type-shipment_container .ui-dialog .ui-dialog-titlebar,
		.button.wpcargo-button{
			background-color: <?php echo $baseColor; ?> !important;
			color:#fff;
		}
	</style>
	<?php
});