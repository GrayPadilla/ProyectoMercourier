<?php

use PhpOffice\PhpSpreadsheet\Shared\Trend\Trend;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}
//** Helpers
function wpcsc_date_filter_range()
{
	return defined('WPCFE_DATE_FILTER_RANGE') ? WPCFE_DATE_FILTER_RANGE : 120;
}
function wpcsc_shipment_action_rows($container_id)
{
	return apply_filters('wpcsc_shipment_action_rows', array(), $container_id);
}
function wpcsc_shipment_view_action_row($rows, $container_id)
{
	$page_url = get_the_permalink(wpc_container_frontend_page()) . '?wpcsc=track&num=' . urlencode(get_the_title($container_id));
	$rows[] = '<a class="wpcfe-update-shipment text-primary" href="' . esc_url($page_url) . '" title="' . esc_html__('View', 'wpcargo-shipment-container') . '">' . esc_html__('View', 'wpcargo-shipment-container') . '</a>';
	return $rows;
}
function wpcsc_shipment_update_action_row($rows, $container_id)
{
	if (!update_container_role()) return $rows;
	$page_url = get_the_permalink(wpc_container_frontend_page()) . '?wpcsc=edit&id=' . (int)$container_id;
	$rows[] = '<a class="wpcfe-update-shipment text-primary" href="' . esc_url($page_url) . '" title="' . esc_html__('Edit', 'wpcargo-shipment-container') . '">' . esc_html__('Edit', 'wpcargo-shipment-container') . '</a>';
	return $rows;
}
function wpcsc_shipment_delete_action_row($rows, $container_id)
{
	if (!delete_containers_roles()) return $rows;
	$rows[] = '<a href="#" class="wpcsc_container-delete text-danger" data-id="' . (int)$container_id . '" title="' . esc_html__('Trash', 'wpcargo-shipment-container') . '">' . esc_html__('Delete', 'wpcargo-shipment-container') . '</a>';
	return $rows;
}
function wpcsc_import_export_headers()
{
	$file_info 			= array();
	$container_fields 	= wpc_container_info_fields();
	$trip_fields  		= wpc_trip_info_fields();
	$time_fields   		= wpc_time_info_fields();
	$fields_list 		= array_merge($container_fields, $trip_fields, $time_fields);
	$file_info['_container_number'] = wpc_container_number_label();
	foreach ($fields_list as $field) {
		$file_info[$field['field_key']] = $field['label'];
	}
	$file_info['_assigned_shipments'] = wpc_scpt_assinged_shipment_label();
	$file_info['registered_shipper'] = wpc_registered_shipper_label();
	$file_info['agent_fields'] = wpc_agent_fields_label();
	$file_info['wpcargo_employee'] = wpc_wpcargo_employee_label();
	$file_info['wpcargo_branch_manager'] = wpc_wpcargo_branch_manager_label();
	$file_info['wpcargo_driver'] = wpc_wpcargo_driver_label();
	return apply_filters('wpcsc_import_export_headers', $file_info);
}
function wpcsc_file_upload_errors()
{
	return array(
		0 => __('There is no error, the file uploaded with success', 'wpcargo-shipment-container'),
		1 => __('The uploaded file exceeds the upload_max_filesize directive in php.ini', 'wpcargo-shipment-container'),
		2 => __('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form', 'wpcargo-shipment-container'),
		3 => __('The uploaded file was only partially uploaded', 'wpcargo-shipment-container'),
		4 => __('No file was uploaded', 'wpcargo-shipment-container'),
		6 => __('Missing a temporary folder', 'wpcargo-shipment-container'),
		7 => __('Failed to write file to disk.', 'wpcargo-shipment-container'),
		8 => __('A PHP extension stopped the file upload.', 'wpcargo-shipment-container')
	);
}
function wpcsc_get_string_between($string, $start, $end)
{
	$string = ' ' . $string;
	$ini = strrpos($string, $start);
	if ($ini == 0) return '';
	$ini += strlen($start);
	$len = strrpos($string, $end, $ini) - $ini;
	return substr($string, $ini, $len);
}
function wpcsc_export_file_format_list()
{
	$extension = array(
		'xls' => ",",
		'xlt' => ",",
		'xla' => ",",
		'xlw' => ",",
		'csv' => ","
	);
	return apply_filters('wpcsc_export_file_format_list', $extension);
}
function wpcsc_clean_dir($directory)
{
	$files = glob($directory . '*'); // get all file names
	foreach ($files as $file) { // iterate files
		if (is_file($file))
			unlink($file); // delete file
	}
}
function wpcsc_get_container_history($container_id)
{
	$history = maybe_unserialize(get_post_meta($container_id, 'container_history', true));
	return !empty($history) && is_array($history) ? wpcargo_history_order($history) : array();
}
// function wpcsc_allowed_users(){
// 	return apply_filters( 'wpcsc_allowed_users', array('administrator', 'wpcargo_employee') );
// }
function can_access_containers()
{
	$current_user 	= wp_get_current_user();
	$roles 			= $current_user->roles;
	if (!empty(array_intersect($roles, wpcc_can_access_containers()))) {
		return true;
	}
	return false;
}
function wpcsc_generate_number()
{
	global $wpdb;
	$autogen 	= get_option('enable_container_autogen');
	if (!$autogen) {
		return false;
	}
	$prefix 	= esc_html__(get_option('container_prefix'));
	$numdigit  	= apply_filters('wpcsc_generate_number_digit', 12);
	$numstr 	= '';
	for ($i = 1; $i < $numdigit; $i++) {
		$numstr .= 9;
	}
	$container_number = $prefix . str_pad(wp_rand(0, $numstr), $numdigit, "0", STR_PAD_LEFT);
	if (wpcsc_generate_number_exist($container_number)) {
		$container_number = wpcsc_generate_number();
	}
	return apply_filters('wpcsc_generate_number', $container_number);
}
function wpcsc_generate_number_exist($container_number = '')
{
	global $wpdb;
	$result =  $wpdb->get_var("SELECT COUNT(*) FROM `{$wpdb->prefix}posts` WHERE `post_type` LIKE 'shipment_container' AND `post_title` LIKE '" . $container_number . "'");
	return $result;
}
function wpc_container_frontend_page()
{
	$wpc_container_frontend_page 	= get_option('wpc_container_frontend_page');
	$shortcode_id					= '';

	if (!$wpc_container_frontend_page) {
		global $wpdb;
		$sql 			= "SELECT `ID` FROM {$wpdb->prefix}posts WHERE `post_content` LIKE '%[wpcargo-container]%' AND `post_status` LIKE 'publish' LIMIT 1";
		$shortcode_id 	= $wpdb->get_var($sql);


		if (! $shortcode_id) {
			// Create post object
			$continer_args = array(
				'post_title'    => wp_strip_all_tags(__('Containers', 'wpcargo-shipment-container')),
				'post_content'  => '[wpcargo-container]',
				'post_status'   => 'publish',
				'post_type'   	=> 'page',
			);

			// Insert the post into the database
			$shortcode_id = wp_insert_post($continer_args);
		}
		if ($shortcode_id) {
			update_post_meta($shortcode_id, '_wp_page_template', 'dashboard.php');
			update_post_meta($shortcode_id, 'wpcfe_menu_icon', 'fa fa-truck mr-3');
		}
		update_option('wpc_container_frontend_page', $shortcode_id, '', 'yes');
		return $shortcode_id;
	} else {

		return $wpc_container_frontend_page;
	}
}
function wpcsc_get_shipment_container($shipment_id)
{
	global $wpdb;
	
	// Primero intentar obtener por shipment_container (meta antiguo)
	$sql_old = "SELECT tbl2.meta_value FROM `{$wpdb->prefix}posts` AS tbl1 
		LEFT JOIN  `{$wpdb->prefix}postmeta` AS tbl2 ON tbl2.post_id = tbl1.ID 
	WHERE 
		tbl1.post_status LIKE 'publish' 
		AND tbl1.post_type LIKE 'wpcargo_shipment' 
		AND tbl1.ID = %d 
		AND tbl2.meta_key LIKE 'shipment_container' 
	LIMIT 1";
	
	$result = $wpdb->get_var($wpdb->prepare($sql_old, $shipment_id));
	if ($result) {
		return $result;
	}
	
	// Si no encontró por meta antiguo, buscar por shipment_container_recojo
	$sql_recojo = "SELECT tbl2.meta_value FROM `{$wpdb->prefix}posts` AS tbl1 
		LEFT JOIN  `{$wpdb->prefix}postmeta` AS tbl2 ON tbl2.post_id = tbl1.ID 
	WHERE 
		tbl1.post_status LIKE 'publish' 
		AND tbl1.post_type LIKE 'wpcargo_shipment' 
		AND tbl1.ID = %d 
		AND tbl2.meta_key LIKE 'shipment_container_recojo' 
	LIMIT 1";
	
	$result = $wpdb->get_var($wpdb->prepare($sql_recojo, $shipment_id));
	if ($result) {
		return $result;
	}
	
	// Si tampoco encontró en recojo, buscar por shipment_container_entrega
	$sql_entrega = "SELECT tbl2.meta_value FROM `{$wpdb->prefix}posts` AS tbl1 
		LEFT JOIN  `{$wpdb->prefix}postmeta` AS tbl2 ON tbl2.post_id = tbl1.ID 
	WHERE 
		tbl1.post_status LIKE 'publish' 
		AND tbl1.post_type LIKE 'wpcargo_shipment' 
		AND tbl1.ID = %d 
		AND tbl2.meta_key LIKE 'shipment_container_entrega' 
	LIMIT 1";
	
	$result = $wpdb->get_var($wpdb->prepare($sql_entrega, $shipment_id));
	return $result;
}
function wpcsc_get_container_id($container_number)
{
	global $wpdb;
	$sql 	= $wpdb->prepare("SELECT `ID` FROM `{$wpdb->prefix}posts` WHERE `post_status` LIKE 'publish' AND `post_type` LIKE 'shipment_container' AND `post_title` LIKE %s LIMIT 1", $container_number);
	$sql  = apply_filters('wpcsc_get_container_id_query', $sql, $container_number);
	$result = $wpdb->get_var($sql);
	return $result;
}
function wpcsc_get_shipment_id($shipment_number)
{
	global $wpdb;
	$sql 	= $wpdb->prepare("SELECT `ID` FROM `{$wpdb->prefix}posts` WHERE `post_status` LIKE 'publish' AND `post_type` LIKE 'wpcargo_shipment' AND `post_title` LIKE %s LIMIT 1", $shipment_number);
	$sql  = apply_filters('wpcsc_get_shipment_id_query', $sql, $shipment_number);
	$result = $wpdb->get_var($sql);
	return $result;
}
function wpcsc_get_container_number($container_id)
{
	global $wpdb;
	$sql = $wpdb->prepare("SELECT `post_title` FROM `{$wpdb->prefix}posts` WHERE `post_status` LIKE 'publish' AND `post_type` LIKE 'shipment_container' AND `ID` = %d", $container_id);

	$result = $wpdb->get_var($sql);
	return $result;
}
// Can update shipments
function wpcsc_update_shipment_role()
{
	if (!class_exists('WPCargo_Frontend_Template')) {
		return array();
	}
	$update_shipment_role = get_option('wpcfe_update_shipment_role') ? get_option('wpcfe_update_shipment_role') : array('wpcargo_employee');
	return $update_shipment_role;
}
function wpcsc_can_update_shipment()
{
	$can_update_roles 	= wpcsc_update_shipment_role();
	$user 				= wp_get_current_user();
	$result 			= false;
	$current_role 		= !empty($user->roles) ? $user->roles : array();
	if (array_intersect($can_update_roles, $current_role) || in_array('administrator', $current_role)) {
		$result = true;
	}
	return $result;
}
function wpcsc_shipment_bulk_container_assign()
{
?>
	<button id="bulkContainerAssign" class="btn btn-success btn-sm" data-toggle="modal" data-target="#shipmentBulkContainerModal"><?php echo wpc_scpt_assign_to_container_label(); ?></button>
<?php
}
function wpcsc_current_user_role()
{
	$current_user   = wp_get_current_user();
	$user_roles     = $current_user->roles;
	return $user_roles;
}
function wpcsc_include_template($file_name, $dir = '')
{
	$file_slug              = strtolower(preg_replace('/\s+/', '_', trim(str_replace('.tpl', '', $file_name))));
	$file_slug              = preg_replace('/[^A-Za-z0-9_]/', '_', $file_slug);
	$dir 					= $dir ? $dir . '/' : '';
	$custom_template_path   = get_stylesheet_directory() . '/wpcargo/wpcargo-shipment-container/' . $dir . $file_name . '.php';
	if (file_exists($custom_template_path)) {
		$template_path = $custom_template_path;
	} else {
		$template_path  = WPCARGO_SHIPMENT_CONTAINER_PATH . 'templates/' . $dir . $file_name . '.php';
		$template_path  = apply_filters("wpcsc_locate_template_{$file_slug}", $template_path);
	}
	return $template_path;
}
function wpcsc_admin_include_template($file_name, $dir = '')
{
	$file_slug              = strtolower(preg_replace('/\s+/', '_', trim(str_replace('.tpl', '', $file_name))));
	$file_slug              = preg_replace('/[^A-Za-z0-9_]/', '_', $file_slug);
	$dir 					= $dir ? $dir . '/' : '';
	$custom_template_path   = get_stylesheet_directory() . '/wpcargo/wpcargo-shipment-container/admin/' . $dir . $file_name . '.php';
	if (file_exists($custom_template_path)) {
		$template_path = $custom_template_path;
	} else {
		$template_path  = WPCARGO_SHIPMENT_CONTAINER_PATH . 'admin/templates/' . $dir . $file_name . '.php';
		$template_path  = apply_filters("wpcsc_admin_locate_template_{$file_slug}", $template_path);
	}
	return $template_path;
}
/* Display custom column */
function wpc_shipment_container_table_column_display_callback($column, $post_id)
{
	global $wpcargo;
	if ($column == 'flight') {
		echo get_post_meta($post_id, 'container_no', TRUE);
	}
	if ($column == 'shipments') {
		$shipment_count = wpcshcon_shipment_count($post_id);
		echo $shipment_count
			? '<a href="#" class="text-info" data-id="' . $post_id . '"><span class="dashicons dashicons-list-view"></span> ' . sprintf(_n('%s Shipment', '%s Shipments', $shipment_count, 'wpcargo-shipment-container'), $shipment_count) . '</a>'
			: '';
	}
	if ($column == 'agent') {
		echo get_post_meta($post_id, 'container_agent', TRUE);
	}
	if ($column == 'delivery_agent') {
		echo get_post_meta($post_id, 'delivery_agent', TRUE);
	}
	if ($column == 'status') {
		echo get_post_meta($post_id, 'container_status', TRUE);
	}
	if ($column == 'scprint') {
		echo '<a href="' . admin_url('admin.php?page=print-shipment-container&id=' . $post_id) . '" target="_blank"><span class="dashicons dashicons-printer"></span></a>';
	}
	if ($column == 'scmanifest') {
		echo '<a href="' . admin_url('/?wpcscpdf=' . $post_id) . '"><span class="dashicons dashicons-download"></span></a>';
	}
}
add_action('manage_shipment_container_posts_custom_column', 'wpc_shipment_container_table_column_display_callback', 10, 2);

/* Add custom column to post list */
function wpcsc_datatable_info_callback()
{
	$shipper_display 	= get_option('container_shipper_display');
	$receiver_display 	= get_option('container_receiver_display');

	if (empty($shipper_display)) {
		$shipper_display 	= 'wpcargo_shipper_name';
		$shipper_label		= __('Shipper', 'wpcargo-shipment-container');
	} else {
		$shipper_label		= wpc_shipment_container_get_field_label($shipper_display);
	}
	if (empty($receiver_display)) {
		$receiver_display 	= 'wpcargo_receiver_name';
		$receiver_label		= __('Receiver', 'wpcargo-shipment-container');
	} else {
		$receiver_label 	= wpc_shipment_container_get_field_label($receiver_display);
	}

	$datatable = array(
		'shipping_no' 		=> wpc_scpt_shipping_no_label(),
		$shipper_display	=> $shipper_label,
		$receiver_display  	=> $receiver_label,
		'registered_to' 	=> __('Client', 'wpcargo-shipment-container'),
		'agent' 			=> __('Agent', 'wpcargo-shipment-container'),
	);
	return apply_filters('wpcsc_datatable_info_callback', $datatable);
}
function wpc_shipment_container_key_label_header_callback()
{
	$key_header = array(
		'flight' 	=> __('Flight/ Container No.', 'wpcargo-shipment-container'),
		'shipments'	=> wpc_scpt_shipments_label(),
		'agent'		=> __('Agent', 'wpcargo-shipment-container'),
		'delivery_agent'	=> __('Driver', 'wpcargo-shipment-container'),
		'status'	=> __('Status', 'wpcargo-shipment-container'),
		'scprint'	=> __('Print', 'wpcargo-shipment-container'),
		'scmanifest' => __('Manifest', 'wpcargo-shipment-container'),
	);
	return apply_filters('wpcsc_table_key_label', $key_header);
}
function wpc_shipment_container_table_column_callback($columns)
{
	return array_merge($columns, wpc_shipment_container_key_label_header_callback());
}
add_filter('manage_shipment_container_posts_columns', 'wpc_shipment_container_table_column_callback');

function wpc_shipment_container_get_all_user($user_field = '')
{
	$users = apply_filters('wpcsc_container_user', get_users(array('role' => array($user_field))), $user_field);
	return $users;
}
//** Template hooks
function container_track_form_title_callback()
{
	echo '<h3 id="container-trackform-header">' . apply_filters('wpc_container_trackform_header', __('Enter Container No.', 'wpcargo-shipment-container')) . '</h3>';
}
function container_track_form_description_callback()
{
	echo  '<p class="description">' . apply_filters('wpc_container_trackform_description', __('Ex. CO123456', 'wpcargo-shipment-container')) . '</p>';
}
add_action('container_track_form_title', 'container_track_form_title_callback', 1, 1);
add_action('container_track_form_description', 'container_track_form_description_callback', 1, 1);

function wpc_shipment_container_get_user_name($userID = '')
{
	global $wpcargo;
	return $wpcargo->user_fullname($userID);
}
function wpcsc_get_field_data($fieldID)
{
	global $wpdb;
	$table_prefix = $wpdb->prefix;
	$result_fields = $wpdb->get_row('SELECT `label`, `field_key`, `field_type` FROM `' . $table_prefix . 'wpcargo_custom_fields` WHERE `id` = ' . $fieldID, ARRAY_A);
	return $result_fields;
}
function wpcsc_is_load_pre_assigned_shipments()
{
	$pre_load = get_option('load_pre_assigned') ? true : false;
	return $pre_load;
}
function wpc_shipment_container_get_preassigned_shipment()
{
	global $wpdb;
	$postID = 'USER' . get_current_user_id();
	$sql = "SELECT tbl1.ID FROM {$wpdb->prefix}posts AS tbl1 ";
	$sql .= "RIGHT JOIN {$wpdb->prefix}postmeta as tbl2 ON tbl1.ID = tbl2.post_id ";
	$sql .= "WHERE tbl1.post_status LIKE 'publish' AND tbl1.post_type LIKE 'wpcargo_shipment' ";
	$sql .= " AND tbl2.meta_key LIKE 'shipment_container' ";
	$sql .= " AND tbl2.meta_value = %s ";
	$result = $wpdb->get_col($wpdb->prepare($sql, $postID));
	return $result ? $result : array();
}

function wpc_shipment_container_update_preassigned_shipment($shipments_id)
{
	global $wpdb;

	$new_value = "";

	$sql = "UPDATE {$wpdb->prefix}postmeta as tbl1 ";
	$sql .= "SET tbl1.meta_value = %s ";
	$sql .= "WHERE tbl1.meta_key LIKE 'shipment_container' ";
	$sql .= "AND tbl1.post_id = %d";

	$result = $wpdb->query($wpdb->prepare($sql, $new_value, $shipments_id));

	return $result;
}

function wpc_shipment_container_get_assigned_shipment_count($postID)
{
	global $wpdb;
	
	// Obtener envíos asignados al contenedor con tipo (recojo/entrega/legacy)
	$assigned_shipments_with_type = wpc_shipment_container_get_assigned_shipment_with_type($postID);
	
	if (!is_array($assigned_shipments_with_type) || empty($assigned_shipments_with_type)) {
		return 0;
	}
	
	// Filtrar: solo envíos SIN motorizado asignado ESPECÍFICO para su tipo de contenedor
	$pending_count = 0;
	foreach ($assigned_shipments_with_type as $shipment_id => $tipo_asignacion) {
		$motorizado_recojo = get_post_meta($shipment_id, 'wpcargo_motorizo_recojo', true);
		$motorizado_entrega = get_post_meta($shipment_id, 'wpcargo_motorizo_entrega', true);
		
		$es_pendiente = false;
		
		// Verificar si es pendiente según el tipo de contenedor
		switch ($tipo_asignacion) {
			case 'recojo':
				// En contenedor RECOJO: necesita motorizado_recojo
				$es_pendiente = empty($motorizado_recojo) || $motorizado_recojo === '0';
				break;
			case 'entrega':
				// En contenedor ENTREGA: necesita motorizado_entrega
				$es_pendiente = empty($motorizado_entrega) || $motorizado_entrega === '0';
				break;
			case 'legacy':
			default:
				// Sistema antiguo: necesita cualquiera de los dos
				$tiene_motorizado = (!empty($motorizado_recojo) && $motorizado_recojo !== '0') 
					|| (!empty($motorizado_entrega) && $motorizado_entrega !== '0');
				$es_pendiente = !$tiene_motorizado;
				break;
		}
		
		if ($es_pendiente) {
			$pending_count++;
		}
	}
	
	error_log("📦 [PENDING_COUNT] Container #{$postID}: Total asignados=" . count($assigned_shipments_with_type) . " | Pendientes=" . $pending_count);
	
	return $pending_count;
}

function wpc_shipment_container_get_assigned_shipment($postID)
{
	global $wpdb;
	
	// Buscar envíos por el meta antiguo 'shipment_container'
	$sql = "SELECT tbl1.ID FROM {$wpdb->prefix}posts AS tbl1 ";
	$sql .= "RIGHT JOIN {$wpdb->prefix}postmeta as tbl2 ON tbl1.ID = tbl2.post_id ";
	$sql .= "WHERE tbl1.post_status LIKE 'publish' AND tbl1.post_type LIKE 'wpcargo_shipment' ";
	$sql .= " AND tbl2.meta_key LIKE 'shipment_container' ";
	$sql .= " AND tbl2.meta_value = %s ";
	$assigned_shipments_old = $wpdb->get_col($wpdb->prepare($sql, $postID));
	
	// Buscar envíos por el meta nuevo 'shipment_container_recojo' (MERC EMPRENDEDOR)
	// FILTRO: Los envíos tipo 'normal' SOLO aparecen en recojo cuando están en PENDIENTE, RECOGIDO o NO RECOGIDO
	$sql_recojo = "SELECT DISTINCT tbl1.ID FROM {$wpdb->prefix}posts AS tbl1 ";
	$sql_recojo .= "RIGHT JOIN {$wpdb->prefix}postmeta as tbl2 ON tbl1.ID = tbl2.post_id ";
	$sql_recojo .= "LEFT JOIN {$wpdb->prefix}postmeta as tbl3 ON tbl1.ID = tbl3.post_id AND tbl3.meta_key = 'wpcargo_status' ";
	$sql_recojo .= "LEFT JOIN {$wpdb->prefix}postmeta as tbl4 ON tbl1.ID = tbl4.post_id AND tbl4.meta_key = 'tipo_envio' ";
	$sql_recojo .= "WHERE tbl1.post_status LIKE 'publish' AND tbl1.post_type LIKE 'wpcargo_shipment' ";
	$sql_recojo .= " AND tbl2.meta_key LIKE 'shipment_container_recojo' ";
	$sql_recojo .= " AND tbl2.meta_value = %s ";
	// Lógica: Los envíos tipo 'normal' SOLO aparecen cuando están en PENDIENTE, RECOGIDO o NO RECOGIDO
	$sql_recojo .= " AND (tbl4.meta_value != 'normal' OR tbl3.meta_value IN ('PENDIENTE', 'RECOGIDO', 'NO RECOGIDO')) ";
	$assigned_shipments_recojo = $wpdb->get_col($wpdb->prepare($sql_recojo, $postID));
	
	// Buscar envíos por el meta nuevo 'shipment_container_entrega' (MERC EMPRENDEDOR)
	$sql_entrega = "SELECT tbl1.ID FROM {$wpdb->prefix}posts AS tbl1 ";
	$sql_entrega .= "RIGHT JOIN {$wpdb->prefix}postmeta as tbl2 ON tbl1.ID = tbl2.post_id ";
	$sql_entrega .= "WHERE tbl1.post_status LIKE 'publish' AND tbl1.post_type LIKE 'wpcargo_shipment' ";
	$sql_entrega .= " AND tbl2.meta_key LIKE 'shipment_container_entrega' ";
	$sql_entrega .= " AND tbl2.meta_value = %s ";
	$assigned_shipments_entrega = $wpdb->get_col($wpdb->prepare($sql_entrega, $postID));
	
	// Combinar todos los resultados y remover duplicados
	$assigned_shipments = array_unique(array_merge(
		(array)$assigned_shipments_old,
		(array)$assigned_shipments_recojo,
		(array)$assigned_shipments_entrega
	));
	
	$sorted_shipment 		= array();
	$sorted_shipments 		= !empty(trim(wpc_shipment_container_sorted_shipment($postID))) ? explode(",", wpc_shipment_container_sorted_shipment($postID)) : array();

	if (!empty($assigned_shipments)) {
		foreach ($sorted_shipments as $shipment) {
			if (!in_array($shipment, $assigned_shipments)) {
				continue;
			}
			$sorted_shipment[] = $shipment;
			if (($key = array_search($shipment, $assigned_shipments)) !== false) {
				unset($assigned_shipments[$key]);
			}
		}
	}
	return array_merge($sorted_shipment, $assigned_shipments);
}

// Nueva función que retorna array con información del tipo de meta
function wpc_shipment_container_get_assigned_shipment_with_type($postID)
{
	global $wpdb;
	$result = array();
	
	// Buscar envíos por el meta antiguo 'shipment_container'
	$sql = "SELECT tbl1.ID FROM {$wpdb->prefix}posts AS tbl1 ";
	$sql .= "RIGHT JOIN {$wpdb->prefix}postmeta as tbl2 ON tbl1.ID = tbl2.post_id ";
	$sql .= "WHERE tbl1.post_status LIKE 'publish' AND tbl1.post_type LIKE 'wpcargo_shipment' ";
	$sql .= " AND tbl2.meta_key LIKE 'shipment_container' ";
	$sql .= " AND tbl2.meta_value = %s ";
	$assigned_shipments_old = $wpdb->get_col($wpdb->prepare($sql, $postID));
	
	foreach ((array)$assigned_shipments_old as $shipment_id) {
		$result[$shipment_id] = 'legacy';
	}
	
	// Buscar envíos por el meta nuevo 'shipment_container_recojo'
	// FILTRO: Los envíos tipo 'normal' SOLO aparecen en recojo cuando están en PENDIENTE, RECOGIDO o NO RECOGIDO
	$sql_recojo = "SELECT DISTINCT tbl1.ID FROM {$wpdb->prefix}posts AS tbl1 ";
	$sql_recojo .= "RIGHT JOIN {$wpdb->prefix}postmeta as tbl2 ON tbl1.ID = tbl2.post_id ";
	$sql_recojo .= "LEFT JOIN {$wpdb->prefix}postmeta as tbl3 ON tbl1.ID = tbl3.post_id AND tbl3.meta_key = 'wpcargo_status' ";
	$sql_recojo .= "LEFT JOIN {$wpdb->prefix}postmeta as tbl4 ON tbl1.ID = tbl4.post_id AND tbl4.meta_key = 'tipo_envio' ";
	$sql_recojo .= "WHERE tbl1.post_status LIKE 'publish' AND tbl1.post_type LIKE 'wpcargo_shipment' ";
	$sql_recojo .= " AND tbl2.meta_key LIKE 'shipment_container_recojo' ";
	$sql_recojo .= " AND tbl2.meta_value = %s ";
	// Lógica: Los envíos tipo 'normal' SOLO aparecen en recojo cuando están en PENDIENTE, RECOGIDO o NO RECOGIDO
	$sql_recojo .= " AND (tbl4.meta_value != 'normal' OR tbl3.meta_value IN ('PENDIENTE', 'RECOGIDO', 'NO RECOGIDO')) ";
	$assigned_shipments_recojo = $wpdb->get_col($wpdb->prepare($sql_recojo, $postID));
	
	foreach ((array)$assigned_shipments_recojo as $shipment_id) {
		$result[$shipment_id] = 'recojo';
	}
	
	// Buscar envíos por el meta nuevo 'shipment_container_entrega'
	$sql_entrega = "SELECT tbl1.ID FROM {$wpdb->prefix}posts AS tbl1 ";
	$sql_entrega .= "RIGHT JOIN {$wpdb->prefix}postmeta as tbl2 ON tbl1.ID = tbl2.post_id ";
	$sql_entrega .= "WHERE tbl1.post_status LIKE 'publish' AND tbl1.post_type LIKE 'wpcargo_shipment' ";
	$sql_entrega .= " AND tbl2.meta_key LIKE 'shipment_container_entrega' ";
	$sql_entrega .= " AND tbl2.meta_value = %s ";
	$assigned_shipments_entrega = $wpdb->get_col($wpdb->prepare($sql_entrega, $postID));
	
	foreach ((array)$assigned_shipments_entrega as $shipment_id) {
		$result[$shipment_id] = 'entrega';
	}
	
	return $result;
}
function wpcshcon_shipment_count($postID)
{
	global $wpdb;
	$sql = "SELECT count(tbl1.ID) FROM {$wpdb->prefix}posts AS tbl1 ";
	$sql .= "RIGHT JOIN {$wpdb->prefix}postmeta as tbl2 ON tbl1.ID = tbl2.post_id ";
	$sql .= "WHERE tbl1.post_status LIKE 'publish' AND tbl1.post_type LIKE 'wpcargo_shipment' ";
	$sql .= " AND tbl2.meta_key LIKE 'shipment_container' ";
	$sql .= " AND tbl2.meta_value = %s ";
	return $wpdb->get_var($wpdb->prepare($sql, $postID));
}
function wpc_shipment_container_sorted_shipment($postID)
{
	return get_post_meta($postID, 'wpcc_sorted_shipments', true);
}
function wpc_shipment_container_get_custom_fields($flag = '')
{
	global $wpdb;
	$table_prefix = $wpdb->prefix;
	$result_fields = $wpdb->get_results('SELECT * FROM `' . $table_prefix . 'wpcargo_custom_fields` WHERE `section` LIKE "' . $flag . '" ORDER BY ABS(weight)', ARRAY_A);
	return $result_fields;
}
function wpc_shipment_container_get_field_label($key = '')
{
	global $wpdb;
	$table_prefix = $wpdb->prefix;
	$result = '';
	if (!empty($key) || $key != '') {
		$result = $wpdb->get_var('SELECT `label` FROM `' . $table_prefix . 'wpcargo_custom_fields` WHERE `field_key` LIKE "' . $key . '" LIMIT 1');
	}
	return $result;
}
function wpcsc_datatable_unassigned_shipment($container_id)
{
	global $wpcargo;
	$shipments 			= wpc_shipment_container_get_unassigned_shipment();
	$datatable 			= array_keys(wpcsc_datatable_info_callback());
	$icons 				= !is_admin() ? 'fa fa-plus-circle fa-lg' : 'dashicons dashicons-plus-alt';
	$data = array();
	if (!empty($shipments)) {
		foreach ($shipments as $shipment) {
			$shipment_array =  array(
				'id' 			=> $shipment,
				"DT_RowId"      => "opt_" . $shipment,
				'actions' 		=> '<span class="shipment-assign-icon text-info ' . $icons . '" data-id="' . $shipment . '" data-ctn="' . $container_id . '"></span>',
			);
			if (!empty($datatable)) {
				foreach ($datatable as $meta_key) {
					$value = get_post_meta($shipment, $meta_key, true);
					if ($meta_key == 'registered_to') {
						$value = $wpcargo->user_fullname(get_post_meta($shipment, 'registered_shipper', true));
					} elseif ($meta_key == 'agent') {
						$value = $wpcargo->user_fullname(get_post_meta($shipment, 'agent_fields', true));
					} elseif ($meta_key == 'shipping_no') {
						$value = get_the_title($shipment);
					}
					$shipment_array[$meta_key] = $value;
				}
			}
			$data[] = $shipment_array;
		}
	}
	return $data;
}
function wpc_shipment_container_get_unassigned_shipment()
{
	global $wpdb;
	$assigned_shipments = get_option('container_assigned_shipments') ? get_option('container_assigned_shipments') : array();
	$assigned_shipments = array_map(function ($value) {
		return str_replace(array("'", '"'), ' ', $value);
	}, $assigned_shipments);
	$shipment_status = '';
	if (!empty($assigned_shipments)) {
		$shipment_status = implode("','", $assigned_shipments);
	} else {
		$shipment_status = 'Pending';
	}
	
	// MEJORADO: Incluir envíos MERC EMPRENDEDOR (tipo='normal') que estén parcialmente asignados
	// Es decir: que tengan contenedor_recojo pero sin motorizado_recojo, o contenedor_entrega sin motorizado_entrega
	
	$sql = "SELECT DISTINCT tbl1.ID FROM `$wpdb->posts` AS tbl1 
	LEFT JOIN `$wpdb->postmeta` tbl2 ON tbl1.ID=tbl2.post_id AND tbl2.meta_key='shipment_container' 
	LEFT JOIN `$wpdb->postmeta` tbl3 ON tbl1.ID=tbl3.post_id AND tbl3.meta_key='wpcargo_status'
	LEFT JOIN `$wpdb->postmeta` tbl_recojo ON tbl1.ID=tbl_recojo.post_id AND tbl_recojo.meta_key='shipment_container_recojo'
	LEFT JOIN `$wpdb->postmeta` tbl_entrega ON tbl1.ID=tbl_entrega.post_id AND tbl_entrega.meta_key='shipment_container_entrega'
	LEFT JOIN `$wpdb->postmeta` tbl_motorizo_rec ON tbl1.ID=tbl_motorizo_rec.post_id AND tbl_motorizo_rec.meta_key='wpcargo_motorizo_recojo'
	LEFT JOIN `$wpdb->postmeta` tbl_motorizo_ent ON tbl1.ID=tbl_motorizo_ent.post_id AND tbl_motorizo_ent.meta_key='wpcargo_motorizo_entrega'
	WHERE tbl1.post_status='publish' AND tbl1.post_type='wpcargo_shipment' 
	AND tbl3.meta_value IN ('" . $shipment_status . "')
	AND (
		-- Envíos SIN asignar en el sistema antiguo (sin shipment_container)
		( tbl2.meta_key IS NULL OR tbl2.meta_value LIKE '' )
		-- Envíos MERC EMPRENDEDOR parcialmente asignados
		OR (
			tbl_recojo.meta_value != '' AND (tbl_motorizo_rec.meta_value = '' OR tbl_motorizo_rec.meta_value IS NULL OR tbl_motorizo_rec.meta_value = '0')
		)
		OR (
			tbl_entrega.meta_value != '' AND (tbl_motorizo_ent.meta_value = '' OR tbl_motorizo_ent.meta_value IS NULL OR tbl_motorizo_ent.meta_value = '0')
		)
	)
	ORDER BY tbl1.post_title ASC";
	
	$sql = apply_filters('wpcsc_get_unassigned_shipment_sql', $sql);
	$result = $wpdb->get_col($sql);
	return $result;
}
function wpc_shipment_container_get_all_unassigned_shipment()
{
	global $wpdb;
	$assigned_shipments = get_option('container_assigned_shipments');
	$shipment_status = '';
	if (!empty($assigned_shipments)) {
		$shipment_status = implode("','", $assigned_shipments);
	} else {
		$shipment_status = 'Pending';
	}
	
	// MEJORADO: Incluir envíos parcialmente asignados
	$sql = "SELECT COUNT(DISTINCT tbl1.ID) FROM `$wpdb->posts` tbl1 
	LEFT JOIN `$wpdb->postmeta` tbl2 ON tbl1.ID=tbl2.post_id AND tbl2.meta_key='shipment_container' 
	LEFT JOIN `$wpdb->postmeta` tbl3 ON tbl1.ID=tbl3.post_id AND tbl3.meta_key='wpcargo_status'
	LEFT JOIN `$wpdb->postmeta` tbl_recojo ON tbl1.ID=tbl_recojo.post_id AND tbl_recojo.meta_key='shipment_container_recojo'
	LEFT JOIN `$wpdb->postmeta` tbl_entrega ON tbl1.ID=tbl_entrega.post_id AND tbl_entrega.meta_key='shipment_container_entrega'
	LEFT JOIN `$wpdb->postmeta` tbl_motorizo_rec ON tbl1.ID=tbl_motorizo_rec.post_id AND tbl_motorizo_rec.meta_key='wpcargo_motorizo_recojo'
	LEFT JOIN `$wpdb->postmeta` tbl_motorizo_ent ON tbl1.ID=tbl_motorizo_ent.post_id AND tbl_motorizo_ent.meta_key='wpcargo_motorizo_entrega'
	WHERE tbl1.post_status='publish' AND tbl1.post_type='wpcargo_shipment' 
	AND tbl3.meta_value IN ('" . $shipment_status . "')
	AND (
		-- Envíos SIN asignar en el sistema antiguo (sin shipment_container)
		( tbl2.meta_key IS NULL OR tbl2.meta_value LIKE '' )
		-- Envíos MERC EMPRENDEDOR parcialmente asignados
		OR (
			tbl_recojo.meta_value != '' AND (tbl_motorizo_rec.meta_value = '' OR tbl_motorizo_rec.meta_value IS NULL OR tbl_motorizo_rec.meta_value = '0')
		)
		OR (
			tbl_entrega.meta_value != '' AND (tbl_motorizo_ent.meta_value = '' OR tbl_motorizo_ent.meta_value IS NULL OR tbl_motorizo_ent.meta_value = '0')
		)
	)";
	
	$sql = apply_filters('wpcsc_get_all_unassigned_shipment_sql', $sql);
	$result = $wpdb->get_var($sql);

	return $result;
}
function wpc_shipment_container_get_paged_shipment($offset, $items_per_page)
{
	global $wpdb;
	$assigned_shipments = get_option('container_assigned_shipments');
	$shipment_status = '';
	if (!empty($assigned_shipments)) {
		$shipment_status = implode("','", $assigned_shipments);
	} else {
		$shipment_status = 'Pending';
	}
	
	// MEJORADO: Incluir envíos parcialmente asignados
	$sql = "SELECT DISTINCT tbl1.ID FROM `$wpdb->posts` tbl1 
	LEFT JOIN `$wpdb->postmeta` tbl2 ON tbl1.ID=tbl2.post_id AND tbl2.meta_key='shipment_container' 
	LEFT JOIN `$wpdb->postmeta` tbl3 ON tbl1.ID=tbl3.post_id AND tbl3.meta_key='wpcargo_status'
	LEFT JOIN `$wpdb->postmeta` tbl_recojo ON tbl1.ID=tbl_recojo.post_id AND tbl_recojo.meta_key='shipment_container_recojo'
	LEFT JOIN `$wpdb->postmeta` tbl_entrega ON tbl1.ID=tbl_entrega.post_id AND tbl_entrega.meta_key='shipment_container_entrega'
	LEFT JOIN `$wpdb->postmeta` tbl_motorizo_rec ON tbl1.ID=tbl_motorizo_rec.post_id AND tbl_motorizo_rec.meta_key='wpcargo_motorizo_recojo'
	LEFT JOIN `$wpdb->postmeta` tbl_motorizo_ent ON tbl1.ID=tbl_motorizo_ent.post_id AND tbl_motorizo_ent.meta_key='wpcargo_motorizo_entrega'
	WHERE tbl1.post_status='publish' AND tbl1.post_type='wpcargo_shipment' 
	AND tbl3.meta_value IN ('" . $shipment_status . "')
	AND (
		-- Envíos SIN asignar en el sistema antiguo (sin shipment_container)
		( tbl2.meta_key IS NULL OR tbl2.meta_value LIKE '' )
		-- Envíos MERC EMPRENDEDOR parcialmente asignados
		OR (
			tbl_recojo.meta_value != '' AND (tbl_motorizo_rec.meta_value = '' OR tbl_motorizo_rec.meta_value IS NULL OR tbl_motorizo_rec.meta_value = '0')
		)
		OR (
			tbl_entrega.meta_value != '' AND (tbl_motorizo_ent.meta_value = '' OR tbl_motorizo_ent.meta_value IS NULL OR tbl_motorizo_ent.meta_value = '0')
		)
	)
	ORDER BY tbl1.ID DESC LIMIT " . $offset . ", " . $items_per_page;
	
	$sql = apply_filters('wpcsc_get_paged_shipment_sql', $sql);
	$result = $wpdb->get_results($sql, OBJECT);
	return $result;
}
function wpc_shipment_container_get_user_fullname($userID)
{
	$user_info = get_userdata($userID);
	$fullname = '';
	if (!empty($user_info->first_name) && !empty($user_info->last_name)) {
		$fullname = ucfirst($user_info->first_name) . ' ' . ucfirst($user_info->last_name);
	} else {
		$fullname = $user_info->user_email;
	}
	return $fullname;
}
function get_shipment_container_post()
{
	$args = array(
		'post_type'         => 'shipment_container',
		'post_status'       => 'publish',
		'posts_per_page' 	=> -1
	);
	$container_post = new WP_Query($args);
	return $container_post;
}
function get_shipment_containers()
{
	global $wpdb;
	$sql 		= "SELECT ID, `post_title` FROM `{$wpdb->prefix}posts` WHERE `post_type` LIKE 'shipment_container' AND `post_status` LIKE 'publish' ORDER BY `post_title` ASC";
	$results 	= $wpdb->get_results($sql);
	return $results;
}
add_action('quick_edit_custom_box', 'wpc_shipment_container_bulk_update_status', 10, 2);
add_action('bulk_edit_custom_box', 'wpc_shipment_container_bulk_update_status', 10, 2);
function wpc_shipment_container_bulk_update_status($column_name,  $screen_post_type)
{
	global $wpcargo, $post;
	if ($screen_post_type == 'shipment_container') {
		wp_nonce_field('container_bulk_update_action', 'container_bulk_update_nonce');
		if ($column_name == 'status') {
			require(wpcsc_admin_include_template('quick-edit-history.tpl'));
		}
		if ($column_name == 'delivery_agent') {
			require(wpcsc_admin_include_template('quick-edit-agent.tpl'));
		}
	}
}
add_action('save_post', 'wpc_shipment_container_bulk_save');

function wpc_shipment_container_bulk_save($post_id)
{
	global $wpcargo;
	$slug = 'shipment_container';
	if (!current_user_can('edit_post', $post_id)) {
		return;
	}
	if (!isset($_REQUEST["container_bulk_update_nonce"])) {
		return;
	}
	if (!wp_verify_nonce($_REQUEST["container_bulk_update_nonce"], 'container_bulk_update_action')) {
		return;
	}
	$current_user 	= wp_get_current_user();
	$history_fields = wpcargo_history_fields();
	if (isset($_REQUEST['_wpcsh_status']) && $_REQUEST['_wpcsh_status'] != '') {
		$selected_status 	= trim(sanitize_text_field($_REQUEST['bulk_container_status']));
		$apply_to_shipment 	= (isset($_REQUEST['apply_shipment']) && $_REQUEST['apply_shipment']) ? true : false;
		$current_chistory 	= wpcsc_get_container_history($post_id);
		$shipments 			= wpc_shipment_container_get_assigned_shipment($post_id);
		$container_history 	= array();
		foreach ($history_fields as $field_key => $field_value) {
			$_value = isset($_REQUEST['_wpcsh_' . $field_key]) ? trim(sanitize_text_field($_REQUEST['_wpcsh_' . $field_key])) : '';
			if ('updated-name' == $field_key) {
				$_value = $wpcargo->user_fullname(get_current_user_id());
			}
			$container_history[$field_key] = $_value;
		}
		array_push($current_chistory, $container_history);
		update_post_meta($post_id, 'container_status', $selected_status);
		update_post_meta($post_id, 'container_history', $current_chistory);
		if ($apply_to_shipment) {
			if (!empty($shipments)) {
				foreach ($shipments as $shipment_id) {
					$old_status = get_post_meta($shipment_id, 'wpcargo_status', true);
					update_post_meta($shipment_id, 'wpcargo_status', $selected_status);
					$shipment_history 		= maybe_unserialize(get_post_meta($shipment_id, 'wpcargo_shipments_update', true));
					if (!empty($shipment_history)) {
						array_push($shipment_history, $container_history);
						update_post_meta($shipment_id, 'wpcargo_shipments_update', $shipment_history);
					} else {
						update_post_meta($shipment_id, 'wpcargo_shipments_update', array($container_history));
					}
					// Add Report Records
					if (function_exists('wpcfe_save_report')) {
						wpcfe_save_report($shipment_id, $old_status, sanitize_text_field($selected_status));
					}
					// Send Email Notification
					if ($selected_status != $old_status) {
						wpcargo_send_email_notificatio($shipment_id, $selected_status);
					}
				}
			}
		}
	}
	if (isset($_REQUEST['wpcsc_delivery_agent']) && $_REQUEST['wpcsc_delivery_agent'] != '') {
		$delivery_agent  = $_REQUEST['wpcsc_delivery_agent'];
		update_post_meta($post_id, 'delivery_agent', $delivery_agent);
		$apply_to_driver = (isset($_REQUEST['apply_driver'])) ? true : false;
		if ($apply_to_driver) {
			if (!empty($shipments)) {
				foreach ($shipments as $shipment_id) {
					update_post_meta($shipment_id, 'wpcargo_driver', (int)$delivery_agent);
				}
			}
		}
	}
}

function wpcargo_container_get_drivers()
{
	$data = array();
	if (function_exists('wpcargo_pod_get_drivers')) {
		$data = wpcargo_pod_get_drivers();
	}
	return $data;
}

function wpcsc_save_history($container_id, $data)
{
	global $wpcargo;
	//** Get the container status before update
	$drivers 			= wpcargo_container_get_drivers();
	$shipments 			= wpc_shipment_container_get_assigned_shipment($container_id);
	$current_status 	= get_post_meta($container_id, 'container_status', TRUE);
	$selected_status 	= isset($data['_wpcsh_status']) ? trim(sanitize_text_field($data['_wpcsh_status'])) : '';
	$delivery_agent  	= isset($data['delivery_agent']) ? trim(sanitize_text_field($data['delivery_agent'])) : 0;
	$apply_to_shipment  = (isset($data['apply_shipment']) && $data['apply_shipment']) ? true : false;
	$driver_id 	        = array_search($delivery_agent, $drivers);
	if ($driver_id && !empty($shipments)) {
		foreach ($shipments as $shipment_id) {
			update_post_meta($shipment_id, 'wpcargo_driver', (int)$driver_id);
		}
	}
	//  Get latest data for the shipment history
	$current_shipment_history 		= array();
	if (!empty(wpcargo_history_fields())) {
		foreach (wpcargo_history_fields() as $key => $value) {
			if ($key == 'updated-name') {
				$current_shipment_history[$key] = $wpcargo->user_fullname(get_current_user_id());
				continue;
			}
			$value = array_key_exists('_wpcsh_' . $key,  $data) ? $data['_wpcsh_' . $key] : '';
			$current_shipment_history[$key] = trim(sanitize_text_field($value));
		}
	}
	if (!empty($current_shipment_history) && !array_key_exists('updated-name', $current_shipment_history)) {
		$current_shipment_history['updated-name'] = $wpcargo->user_fullname(get_current_user_id());
	}
	// Get latest data for the current container history
	$current_container_history  = array_key_exists('container_history',  $data) ? $data['container_history'] : array();

	//** Update the Container status when the Container History is update
	if ($selected_status) {
		//** Add new container history if the current status is not equal to selected status
		update_post_meta($container_id, 'container_status', $selected_status);
		$current_container_history = array_reverse($current_container_history);
		array_push($current_container_history, $current_shipment_history);

		//** if syn is enable update shipment status ang history
		if ($apply_to_shipment) {

			if (!empty($shipments)) {
				foreach ($shipments as $shipment_id) {
					$old_status         = get_post_meta($shipment_id, 'wpcargo_status', true);
					update_post_meta($shipment_id, 'wpcargo_status', $selected_status);
					$shipment_history 	= maybe_unserialize(get_post_meta($shipment_id, 'wpcargo_shipments_update', true));
					do_action('wpc_shipment_container_extra_save',  $shipment_id, $data);
					update_post_meta($shipment_id, 'wpcargo_remarks', $selected_status);

					if (!empty($shipment_history)) {
						array_push($shipment_history, $current_shipment_history);
						update_post_meta($shipment_id, 'wpcargo_shipments_update', $shipment_history);
					} else {
						update_post_meta($shipment_id, 'wpcargo_shipments_update', array($current_shipment_history));
					}
					// Add Report Records
					if (function_exists('wpcfe_save_report')) {
						wpcfe_save_report($shipment_id, $old_status, sanitize_text_field($selected_status));
					}
					// Send Email Notification
					if ($selected_status != $old_status) {
						wpcargo_send_email_notificatio($shipment_id, $selected_status);
						do_action('wpcargo_extra_send_email_notification', $shipment_id, $selected_status);
						do_action('wpc_add_sms_shipment_history', $shipment_id);
					}
				}
			}
		}
	} else {
		$current_container_history = array_reverse($current_container_history);
	}
	update_post_meta($container_id, 'container_history', $current_container_history);
}
function wpcapi_extract_container_data($containers)
{
	global $wpcargo;
	$container_data 	= array();
	$counter 			= 0;
	$registered_fields 	= array_merge(wpc_container_info_fields(), wpc_trip_info_fields(), wpc_time_info_fields());
	foreach ($containers as $key => $value) {
		$assigned_shipment = wpc_shipment_container_get_assigned_shipment($value['ID']);
		$container_data[$counter]['ID'] 				= $value['ID'];
		$container_data[$counter]['container_number'] 	= $value['container_number'];
		$container_data[$counter]['post_author'] 		= $value['post_author'];
		$container_data[$counter]['post_date'] 			= $value['post_date'];
		$container_data[$counter]['post_date_gmt'] 		= $value['post_date_gmt'];
		$container_data[$counter]['post_modified'] 		= $value['post_modified'];
		$container_data[$counter]['post_modified_gmt'] 	= $value['post_modified_gmt'];
		if (!empty($registered_fields)) {
			foreach ($registered_fields as $field_key => $field_value) {
				$_value = maybe_unserialize(get_post_meta($value['ID'], $field_key, true));
				if (is_array($_value)) {
					$_value = implode(",", $_value);
				}
				$container_data[$counter][$field_key] = $_value;
			}
		}
		$container_data[$counter]['container_history'] = maybe_unserialize(get_post_meta($value['ID'], 'container_history', true));
		$container_data[$counter]['assigned_shipment'] = array_map(function ($shipment) {
			return get_the_title($shipment);
		}, $assigned_shipment);
		$container_data[$counter] = apply_filters('wpcargo_api_container_data', $container_data[$counter], $value['ID']);
		$counter++;
	}
	return apply_filters('wpcargo_api_containers_data', $container_data);
}

function wpcapi_get_user_containers($userID, $page = 1, $all = false)
{
	global $wpdb, $wpcargo;
	$userdata 	= get_userdata($userID);
	$user_roles = $userdata->roles;
	$per_page 	= 12;
	$offset 	= ($page - 1) * $per_page;
	$sql 		= '';
	$user_fullname = $wpcargo->user_fullname($userdata->ID);
	$admin_role = array('administrator', 'wpcargo_api_manager', 'wpc_shipment_manager');
	if (array_intersect($admin_role, $user_roles)) {

		$sql .= "SELECT `ID`, `post_title` AS container_number, `post_author`, `post_date`, `post_date_gmt`, `post_modified`, `post_modified_gmt` FROM `{$wpdb->prefix}posts` WHERE `post_status` LIKE 'publish' AND `post_type` LIKE 'shipment_container' ORDER BY `post_date`";
	} elseif (in_array('wpcargo_driver', $user_roles)) {

		$sql .= "SELECT tbl1.ID, tbl1.post_title AS container_number, tbl1.post_author, tbl1.post_date, tbl1.post_date_gmt, tbl1.post_modified, tbl1.post_modified_gmt";
		$sql .= " FROM `{$wpdb->prefix}posts` AS tbl1";
		$sql .= " INNER JOIN `{$wpdb->prefix}postmeta` tbl2 ON tbl1.ID = tbl2.post_id";
		$sql .= " WHERE tbl1.post_status LIKE 'publish' AND tbl1.post_type LIKE 'shipment_container'";
		$sql .= " AND tbl2.meta_key LIKE 'delivery_agent' AND tbl2.meta_value LIKE '{$user_fullname}'";
		$sql .= " ORDER BY tbl1.post_date";
	} elseif (in_array('cargo_agent', $user_roles)) {

		$sql .= "SELECT tbl1.ID, tbl1.post_title AS container_number, tbl1.post_author, tbl1.post_date, tbl1.post_date_gmt, tbl1.post_modified, tbl1.post_modified_gmt";
		$sql .= " FROM `{$wpdb->prefix}posts` AS tbl1";
		$sql .= " INNER JOIN `{$wpdb->prefix}postmeta` tbl2 ON tbl1.ID = tbl2.post_id";
		$sql .= " WHERE tbl1.post_status LIKE 'publish' AND tbl1.post_type LIKE 'shipment_container'";
		$sql .= " AND tbl2.meta_key LIKE 'container_agent' AND tbl2.meta_value LIKE '{$user_fullname}'";
		$sql .= " ORDER BY tbl1.post_date";
	}
	if (!$all && !empty($sql)) {
		$sql .= " DESC LIMIT {$per_page} OFFSET {$offset}";
	}
	if (empty($sql)) {
		return array();
	}
	$sql 		= apply_filters('wpcapi_get_user_containers_api_sql', $sql, $userID, $page, $all);
	$containers = $wpdb->get_results($sql, ARRAY_A);
	return $containers;
}
function wpcapi_get_unassigned_shipments($userID)
{
	$userdata 			= get_userdata($userID);
	$user_roles 		= $userdata->roles;
	$admin_roles 		= array('administrator', 'wpcargo_api_manager', 'wpc_shipment_manager');
	$can_assign_roles 	= apply_filters('wpcscapi_can_assign_shipments_role', $admin_roles);
	$shipments 			= wpc_shipment_container_get_unassigned_shipment();
	if (empty(array_intersect($user_roles, $can_assign_roles))) {
		return array(
			'status' => 'error',
			'message' => __('Sorry you are restricted to access this route.', 'wpcargo-shipment-container')
		);
	}
	return array_map(function ($shipment) {
		return get_the_title($shipment);
	}, $shipments);
}

function assignees_list()
{
	$assignees = array('registered_shipper', 'agent_fields', 'wpcargo_employee', 'wpcargo_branch_manager', 'wpcargo_driver');
	return apply_filters('assignees_list', $assignees);
}

function wpcc_can_access_containers()
{
	$access_container_role = get_option('wpcc_container_access') ? get_option('wpcc_container_access') : array('administrator', 'wpcargo_employee', 'cargo_agent');
	if (!in_array('administrator', $access_container_role)) {
		$access_container_role[] = 'administrator';
	}
	return apply_filters('wpcc_can_access_containers', $access_container_role);
}

function wpcc_can_update_container()
{
	$can_update_container = get_option('update_container_role') ? get_option('update_container_role') : array('administrator', 'wpcargo_employee');
	if (!in_array('administrator', $can_update_container)) {
		$can_update_container[] = 'administrator';
	}
	return apply_filters('wpcc_can_update_container', $can_update_container);
}

function update_container_role()
{
	$current_user 	= wp_get_current_user();
	$roles 			= $current_user->roles;
	if (!empty(array_intersect($roles, wpcc_can_update_container()))) {
		return true;
	}
	return false;
}

function wpcc_can_delete_container()
{
	$can_delete_container = get_option('delete_container_role') ? get_option('delete_container_role') : array('administrator', 'wpcargo_employee');
	if (!in_array('administrator', $can_delete_container)) {
		$can_delete_container[] = 'administrator';
	}
	return $can_delete_container;
}

function delete_containers_roles()
{
	$current_user 	= wp_get_current_user();
	$roles 			= $current_user->roles;
	if (!empty(array_intersect($roles, wpcc_can_delete_container()))) {
		return true;
	}
	return false;
}

function wpcc_ie_container_role()
{
	$can_delete_container = get_option('wpcc_ie_container_role') ? get_option('wpcc_ie_container_role') : array('administrator', 'wpcargo_employee');
	if (!in_array('administrator', $can_delete_container)) {
		$can_delete_container[] = 'administrator';
	}
	return $can_delete_container;
}

function can_import_containers()
{
	$current_user 	= wp_get_current_user();
	$roles 			= $current_user->roles;
	if (!empty(array_intersect($roles, wpcc_ie_container_role()))) {
		return true;
	}
	return false;
}

//#if container is assigned to user
function wpcsc_is_user_container($container_id)
{
	//#Get current user ID and roles
	$current_user = wp_get_current_user()->ID;
	$user_roles = wpcfe_current_user_role();
	//#get all assigned users
	$client 	= get_post_meta($container_id, 'registered_shipper', true);
	$agent 		= get_post_meta($container_id, 'agent_fields', true);
	$employee 	= get_post_meta($container_id, 'wpcargo_employee', true);
	$driver 	= get_post_meta($container_id, 'wpcargo_driver', true);
	$branch     = get_post_meta($container_id, 'shipment_branch', true);
	$branch_mng	= get_post_meta($container_id, 'wpcargo_branch_manager', true);

	$result = false;
	if (wpcfe_is_super_admin()) {
		$result = true;
	} elseif (in_array('wpcargo_branch_manager', $user_roles) && $branch_mng == $current_user) { // wpcargo_branch_manager
		$result = true;
	} elseif (in_array('cargo_agent', $user_roles) && $agent == $current_user) {
		$result = true;
	} elseif (in_array('wpcargo_driver', $user_roles) && $driver == $current_user) {
		$result = true;
	} elseif (in_array('wpcargo_client', $user_roles) && $client == $current_user) {
		$result = true;
	} elseif (in_array('wpcargo_employee', $user_roles) && $employee == $current_user) {
		$result = true;
	}
	return apply_filters('wpcsc_is_user_container', $result, $container_id);
}

add_action('wp_head', function () {
	if (!isset($_GET['debugger'])) {
		return;
	}

	$container_id = 1192;
	$shipments		    = wpc_shipment_container_get_assigned_shipment($container_id);
	$shipment_ids 		= apply_filters('wpcsc_shipment_manifest_list', $shipments, $container_id);

	$total_boxes = array();
	$volumetric_wieght = array();

	foreach ($shipment_ids as $shipment_id) {


		$delivery_zone 	= maybe_unserialize(get_post_meta($shipment_id, 'wpcargo_delivery_zone', true));

		// Get Total Boxes
		$wpc_multiple_package = wpc_get_multiple_package($shipment_id);
		$total_boxes[$delivery_zone] =  isset($total_boxes[$delivery_zone]) ? $total_boxes[$delivery_zone] + $wpc_multiple_package['quantity'] : $wpc_multiple_package['quantity'];

		// Get Total Boxes
		$volumetric_wieght[$delivery_zone] =  isset($volumetric_wieght[$delivery_zone]) ? $volumetric_wieght[$delivery_zone] + $wpc_multiple_package['volumetric_wieght']['volume'] : $wpc_multiple_package['volumetric_wieght']['volume'];
	}
});

function wpc_get_multiple_package($shipment_id)
{

	$add_quantity = 0;
	$products = array();
	$wpc_multiple_package = maybe_unserialize(get_post_meta($shipment_id, 'wpc-multiple-package', true));
	$volumetric_wieght = wpc_calculate_package_information($wpc_multiple_package);
	foreach ($wpc_multiple_package as $data) {
		$add_quantity += (int)($data["wpc-pm-qty"] ?: 1);
		$products[] = $data['wpc-pm-piece-type'];
	}

	$data = array(
		'quantity' => $add_quantity,
		'products' => $products,
		'volumetric_wieght' => $volumetric_wieght
	);
	return $data;
}

function wpc_calculate_package_information($packages)
{

	if (empty($packages)) {
		return array(
			'packages' 		=> array(),
			'qty' 			=> 0,
			'volume' 		=> 0,
			'total_weight' 	=> 0,
			'sugg_weight' 	=> 0,
			'weight_qty'	=> 0
		);
	}

	$volume 		= (float)wpcargo_calculate_volumetric($packages);
	$total_weight	= (float)wpcargo_calculate_weight($packages);
	$package_weight = $total_weight > $volume ? $total_weight : $volume;
	$qty_weight		= 0.00;

	foreach ($packages as $key => $value) {
		$qty_weight += (float)($value['wpc-pm-weight'] ?: 0);
	}
	$package_qty 	= array_reduce($packages, function ($acc, $value) {
		$qty 		= array_key_exists(wpcargo_package_qty_meta(), $value) ? $value[wpcargo_package_qty_meta()] : 1;
		$acc 		+= (int)$qty;
		return $acc;
	}, 0);
	$package_information = array(
		'packages' 		=> $packages,
		'qty' 			=> $package_qty,
		'volume' 		=> $volume,
		'total_weight' 	=> $total_weight,
		'sugg_weight' 	=> $package_weight,
		'weight_qty'	=> $qty_weight
	);
	return apply_filters('wpc_calculate_package_information', $package_information);
}

function wpc_manifest_labels()
{

	$data = array(
		apply_filters('wpcsc_manifest_title_route', __('ROUTE:', 'wpcargo-shipment-container')),
		apply_filters('wpcsc_manifest_title_no', __('NO.: ', 'wpcargo-shipment-container')),
		apply_filters('wpcsc_manifest_title_loading', __('Loading Date:', 'wpcargo-shipment-container')),
		apply_filters('wpcsc_manifest_title_vehicle', __('VEHICLE NO.: ', 'wpcargo-shipment-container')),
		apply_filters('wpcsc_manifest_title_departure', __('Estimated Departure:', 'wpcargo-shipment-container')),
		apply_filters('wpcsc_manifest_title_arrival', __('Estimated Arrival:', 'wpcargo-shipment-container')),
		apply_filters('wpcsc_manifest_title_qty', __('TOTAL QTY', 'wpcargo-shipment-container')),
		apply_filters('wpcsc_manifest_title_cbm', __('TOTAL CBM', 'wpcargo-shipment-container'))
	);
	return $data;
}
function wpc_manifest_headers()
{
	$data = array();

	$data = array(
		apply_filters('wpcsc_manifest_title_delivery_zone', __('Delivery Zone', 'wpcargo-shipment-container')),
		apply_filters('wpcsc_manifest_title_cbmkilo', __('CBM / KILO', 'wpcargo-shipment-container')),
		apply_filters('wpcsc_manifest_title_total_box', __('TOTAL BOXES', 'wpcargo-shipment-container')),
	);

	return $data;
}
function wpcsc_print_options()
{
	$options = array(
		'pdf'   => esc_html__('PDF', 'wpcargo-frontend-manager'),
		'csv'     => esc_html__('CSV', 'wpcargo-frontend-manager'),
		// 'xlxs'     => esc_html__('XLSX', 'wpcargo-frontend-manager'),
	);
	return apply_filters('wpcsc_print_options', $options);
}

# ========================================================== new functions ====================================================================== #

function wpcsc_formatted_shipment_meta_data($ship_id)
{
	$meta_keys = array_keys(wpcsc_csv_shipments_per_zone_meta_details());
	$translate = array(',' => ' ', ', ' => ' ');
	$res = array_map(function ($mkey) use ($ship_id, $translate) {
		return strtr(get_post_meta($ship_id, $mkey, true), $translate);
	}, $meta_keys);
	return apply_filters('wpcsc_formatted_shipment_meta_data', $res, $ship_id, $translate, $meta_keys);
}

function wpcsc_csv_shipments_per_zone_additional_headers()
{
	$headers = array(
		apply_filters('wpcsc_csv_shipments_per_zone_additional_headers_no_label', __('No', 'wpcargo-container')),
		apply_filters('wpcsc_csv_shipments_per_zone_additional_headers_tn_label', __('Tracking Number', 'wpcargo-container')),
		apply_filters('wpcsc_csv_shipments_per_zone_additional_headers_qty_label', __('Qty', 'wpcargo-container')),
	);
	return apply_filters('wpcsc_csv_shipments_per_zone_additional_headers', $headers);
}

function wpcsc_csv_shipments_per_zone_meta_details()
{
	$meta_key_ids = get_option('container_field_manifest') ?: array();
	$meta_keys = array_map(function ($id) {
		return wpcsc_get_field_data($id);
	}, $meta_key_ids);
	$structured_meta_keys = array();
	if (!empty($meta_keys) && is_array($meta_keys)) {
		foreach ($meta_keys as $key) {
			$structured_meta_keys[$key['field_key']] = $key['label'];
		}
	}
	return apply_filters('wpcsc_csv_shipments_per_zone_meta_details', $structured_meta_keys);
}

function wpcsc_bb_shipment_delivery_zone_meta()
{
	return apply_filters('wpcsc_bb_shipment_delivery_zone_meta', 'wpcargo_reciever_zone');
}

function wpcsc_default_shipment_delivery_zone_meta()
{
	return apply_filters('wpcsc_default_shipment_delivery_zone_meta', 'wpcargo_receiver_address');
}

function wpcsb_delivery_zone_to_slug($dz, $is_lower = true)
{
	$translate = array(', ' => ' ', ',' => ' ');
	$result = strtr($dz, $translate);
	if ($is_lower) {
		$result = strtolower($result);
	}
	return $result;
}

function wpcsc_csv_delivery_zone_detail_values($container_id, $include_shipments = false)
{
	# get assigned shipments
	$assigned_shipments = wpc_shipment_container_get_assigned_shipment($container_id) ?: array();
	$values = array();
	$shipments = array();

	# get delivery zone, total cbm and total boxes per zone
	if (!empty($assigned_shipments) && is_array($assigned_shipments)) {
		foreach ($assigned_shipments as $shipment_id) {
			$shipment_type = get_post_meta($shipment_id, '__shipment_type', true);
			if ($shipment_type == 'shipment-box') { # if balikbayan box, get the delivery zone
				$bb_shipment_delivery_zone_meta = wpcsc_bb_shipment_delivery_zone_meta();
				$bb_delivery_zone = sanitize_text_field(get_post_meta($shipment_id, $bb_shipment_delivery_zone_meta, true));
				$bb_delivery_zone_key = wpcsb_delivery_zone_to_slug($bb_delivery_zone);
				$cbm = (float)(get_post_meta($shipment_id, 'wpcsb_total_charge', true) ?: 0);
				if ($bb_delivery_zone) {
					if (array_key_exists($bb_delivery_zone_key, $values)) {
						$values[$bb_delivery_zone_key]['total_cbm'] += $cbm;
						$values[$bb_delivery_zone_key]['total_boxes'] += 1;
					} else {
						$values[$bb_delivery_zone_key] = array(
							'actual_zone' => $bb_delivery_zone,
							'total_cbm' => $cbm,
							'total_boxes' => 1
						);
					}
					if (array_key_exists($bb_delivery_zone_key, $shipments)) {
						if (!in_array($shipment_id, $shipments[$bb_delivery_zone_key])) {
							$shipments[$bb_delivery_zone_key][] = $shipment_id;
						}
					} else {
						$shipments[$bb_delivery_zone_key] = array($shipment_id);
					}
				} else {
					$bb_no_zone_key = 'BALIKBAYAN-NO-DELIVERY-ZONE';
					if (array_key_exists($bb_no_zone_key, $values)) {
						$values[$bb_no_zone_key]['total_cbm'] += $cbm;
						$values[$bb_no_zone_key]['total_boxes'] += 1;
					} else {
						$values[$bb_no_zone_key] = array(
							'actual_zone' => $bb_no_zone_key,
							'total_cbm' => $cbm,
							'total_boxes' => 1
						);
					}
					if (array_key_exists($bb_no_zone_key, $shipments)) {
						if (!in_array($shipment_id, $shipments[$bb_no_zone_key])) {
							$shipments[$bb_no_zone_key][] = $shipment_id;
						}
					} else {
						$shipments[$bb_no_zone_key] = array($shipment_id);
					}
				}
			} else { # for default shipments, get the receiver address
				$default_shipment_delivery_zone_meta = wpcsc_default_shipment_delivery_zone_meta();
				$non_bb_delivery_zone = sanitize_text_field(get_post_meta($shipment_id, $default_shipment_delivery_zone_meta, true));
				$non_bb_delivery_zone_key = wpcsb_delivery_zone_to_slug($non_bb_delivery_zone);
				if ($non_bb_delivery_zone) {
					if (array_key_exists($non_bb_delivery_zone_key, $values)) {
						$values[$non_bb_delivery_zone_key]['total_boxes'] += 1;
					} else {
						$values[$non_bb_delivery_zone_key] = array(
							'actual_zone' => $non_bb_delivery_zone,
							'total_cbm' => wpcargo_package_actual_weight($shipment_id),
							'total_boxes' => 1
						);
					}
					if (array_key_exists($non_bb_delivery_zone_key, $shipments)) {
						if (!in_array($shipment_id, $shipments[$non_bb_delivery_zone_key])) {
							$shipments[$non_bb_delivery_zone_key][] = $shipment_id;
						}
					} else {
						$shipments[$non_bb_delivery_zone_key] = array($shipment_id);
					}
				} else {
					$non_bb_no_zone_key = 'SHIPMENT-NO-DELIVERY-ZONE';
					if (array_key_exists($non_bb_no_zone_key, $values)) {
						$values[$non_bb_no_zone_key]['total_boxes'] += 1;
					} else {
						$values[$non_bb_no_zone_key] = array(
							'actual_zone' => $non_bb_no_zone_key,
							'total_cbm' => wpcargo_package_actual_weight($shipment_id),
							'total_boxes' => 1
						);
					}
					if (array_key_exists($non_bb_no_zone_key, $shipments)) {
						if (!in_array($shipment_id, $shipments[$non_bb_no_zone_key])) {
							$shipments[$non_bb_no_zone_key][] = $shipment_id;
						}
					} else {
						$shipments[$non_bb_no_zone_key] = array($shipment_id);
					}
				}
			}
		}
	}
	# return headers
	$_final_res = array();
	if (!empty($values)) {
		foreach ($values as $_k => $_v) {
			$actual_zone = wpcsb_delivery_zone_to_slug($_v['actual_zone'], false);
			$_final_res[$_k] = array('actual_zone' => $actual_zone, 'total_cbm' => $_v['total_cbm'], 'total_boxes' => $_v['total_boxes']);
			if ($include_shipments) {
				$_final_res[$_k]['shipments'] = $shipments[$_k];
			}
		}
	}
	return apply_filters('wpcsc_csv_delivery_zone_detail_values', $_final_res, $values, $container_id, $assigned_shipments);
}

function wpcsc_csv_delivery_zone_detail_headers()
{
	# declare default headers
	$headers = array(
		'dv_label' => __('Delivery Zone', 'wpcargo-container'),
		'kl_label' => __('CBM / KILO', 'wpcargo-container'),
		'tb_label' => __('Total Boxes', 'wpcargo-container')
	);

	# return headers
	return apply_filters('wpcsc_csv_delivery_zone_detail_headers', $headers);
}

function wpcsc_csv_container_detail_headers()
{
	# declare default headers
	$headers = array(
		'rt_label' => __('Route', 'wpcargo-container'),
		'no_label' => __('No.', 'wpcargo-container'),
		'ld_label' => __('Loading Date', 'wpcargo-container'),
		'vn_label' => __('Vehicle No.', 'wpcargo-container'),
		'ed_label' => __('Estimated Departure', 'wpcargo-container'),
		'ea_label' => __('Estimated Arrival', 'wpcargo-container'),
		'tq_label' => __('Total Qty', 'wpcargo-container')
	);

	# return headers
	return apply_filters('wpcsc_csv_container_detail_headers', $headers);
}

function wpcsc_csv_container_detail_post_metas()
{
	# declare default post metas
	$post_metas = array(
		'origin',
		'destination',
		'date',
		'container_no',
		'expected_date'
	);

	# return post metas
	return apply_filters('wpcsc_csv_container_detail_post_metas', $post_metas);
}

function wpcsc_csv_container_detail_values($container_id)
{
	# get container title and assigned shipments
	$container_title = get_the_title($container_id);
	$assigned_shipments = wpc_shipment_container_get_assigned_shipment($container_id) ?: array();
	$keys = array(
		'rt_val' => array('origin', 'destination'),
		'no_val' => $container_title,
		'ld_val' => 'date',
		'vn_val' => 'container_no',
		'ed_val' => 'date',
		'ea_val' => 'expected_date',
		'tq_val' => count($assigned_shipments)
	);

	$keys = apply_filters('wpcsc_csv_container_detail_values', $keys, $container_id, $assigned_shipments);

	# extract meta values by meta keys using nested array map
	$meta_values = array_map(function ($meta_key) use ($container_id) {
		$route_separator = apply_filters('wpcsc_csv_route_separator', ' - ');
		if (is_array($meta_key) && !empty($meta_key)) {
			return implode($route_separator, array_map(function ($_meta_key) use ($container_id) {
				if (in_array($_meta_key, wpcsc_csv_container_detail_post_metas())) {
					return get_post_meta($container_id, $_meta_key, true);
				}
			}, $meta_key));
		} else {
			if (in_array($meta_key, wpcsc_csv_container_detail_post_metas())) {
				return get_post_meta($container_id, $meta_key, true);
			} else {
				return $meta_key;
			}
		}
	}, array_values($keys));

	# return meta values
	return $meta_values;
}

function wpcsc_csv_manifest_header_label()
{
	return apply_filters('wpcsc_csv_manifest_header_label', __('MANIFEST', 'wpcargo-container'));
}

function wpcsc_csv_delivery_manifest_header_label()
{
	return apply_filters('wpcsc_csv_delivery_manifest_header_label', __('DELIVERY MANIFEST', 'wpcargo-container'),);
}

function wpcsccsv_generator($rows, $filename)
{
	if (!$rows || !$filename) {
		return;
	}

	# tell the browser it's going to be a csv file
	header('Content-Type: application/csv');

	# tell the browser we want to save it instead of displaying it
	header('Content-Disposition: attachment; filename="' . $filename . '";');

	# declare the delimiter
	$delimiter = apply_filters('wpcsc_csv_delimiter', ',');

	# open raw memory as file so no temp files needed, you might run out of memory though
	$f = fopen('php://output', 'w');

	# loop over the input array
	foreach ($rows as $lines) {
		# generate csv lines from the inner arrays
		fputcsv($f, $lines, $delimiter, ' ');
	}
	# make php send the generated csv lines to the browser
	fclose($f);
	exit;
}

# get weight and dimension units from wpcargo multi package settings
function wpc_shipment_container_wpc_mp_settings()
{
	$wpc_mp_settings = get_option('wpc_mp_settings') ?: array();
	return $wpc_mp_settings;
}

function wpc_shipment_container_wpc_mp_dimension_unit()
{
	$wpc_mp_dimension_unit = (wpc_shipment_container_wpc_mp_settings()['wpc_mp_dimension_unit'] ?? 'cm') ?: 'cm';
	return apply_filters('wpc_shipment_container_wpc_mp_dimension_unit', $wpc_mp_dimension_unit);
}

function wpc_shipment_container_wpc_mp_weight_unit()
{
	$wpc_mp_weight_unit = (wpc_shipment_container_wpc_mp_settings()['wpc_mp_weight_unit'] ?? 'kg') ?: 'kg';
	return apply_filters('wpc_shipment_container_wpc_mp_weight_unit', $wpc_mp_weight_unit);
}
