<?php
if (! defined('ABSPATH')) {
	exit;
}
function wpcargo_pod_dashboard_callback($shipment_id)
{
	$assigned_driver 	= get_post_meta($shipment_id, 'wpcargo_driver', true);
	$signature 			= get_post_meta($shipment_id, 'wpcargo-pod-signature', true);
	$images 			= get_post_meta($shipment_id, 'wpcargo-pod-image', true);
	require_once(wpcpod_include_template('assigned-driver.tpl'));
}
// Add script in the Dashboard script
function wpcargo_pod_dashboard_registered_styles($styles)
{
	$styles[] = 'wpcargo-pod-dashboard-style';
	return $styles;
}
// Add script in the Dashboard script
function wpcargo_pod_dashboard_registered_scripts($script)
{
	$script[] = 'wpcargo-pod-signature-scripts';
	$script[] = 'wpcargo-pod-scripts';
	return $script;
}
add_filter('wpcfe_registered_styles', 'wpcargo_pod_dashboard_registered_styles', 10, 1);
add_filter('wpcfe_registered_scripts', 'wpcargo_pod_dashboard_registered_scripts', 10, 1);
// Add Shipment table header "Sign"
function wpcargo_pod_dashboard_table_header_action()
{
	echo '<th class="wpcpod-sign_data text-center hide-me">' . apply_filters('pod_table_header_sign_label', __('Sign', 'wpcargo-pod')) . '</th>';
}
function wpcargo_pod_dashboard_table_table_action($shipment_id)
{
	$signature = get_post_meta($shipment_id, 'wpcargo-pod-signature', true);
	$btn_label = apply_filters('pod_table_header_sign_label', __('Sign', 'wpcargo-pod'));
	$btn_color = 'btn-outline-info';
	if ($signature) {
		$btn_label = apply_filters('pod_table_header_signed_label', __('Signed', 'wpcargo-pod'));
		$btn_color = 'btn-outline-blue-grey';
	}
	// Habilitar el botón sólo si el estado del envío es 'EN RUTA'
	$shipment_status = get_post_meta($shipment_id, 'wpcargo_status', true);
	$enabled_states = array('EN RUTA', 'EN_RUTA', 'ENRUTA', 'EN-RUTA');
	$is_enabled = false;
	if (!empty($shipment_status)) {
		$s = strtoupper(trim($shipment_status));
		if (in_array($s, $enabled_states, true)) {
			$is_enabled = true;
		}
	}

	$disabled_attr = $is_enabled ? '' : ' disabled="disabled" aria-disabled="true"';
	$disabled_class = $is_enabled ? '' : ' disabled';

	echo '<td class="text-center"><button type="button" class="wpcpod-sign_data show-signaturepad btn ' . $btn_color . ' btn-rounded btn-small py-1 px-4 hide-me' . $disabled_class . '" data-toggle="modal" data-target="#wpc_pod_signature-modal" data-id="' . $shipment_id . '"' . $disabled_attr . '>' . $btn_label . '</button></td>';
}
function wpcargo_pod_after_admin_page_load_action()
{
?>
	<!-- Modal -->
	<div class="modal fade top" id="wpc_pod_signature-modal" tabindex="-1" role="dialog" aria-labelledby="podModalPreview" aria-hidden="true">
		<div class="modal-dialog modal-lg modal-frame modal-top" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="podModalPreview"><?php echo apply_filters('pod_modal_title', __('Proof of Delivery', 'wpcargo-pod')) ?></h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body my-4">
					<?php _e('Loading...', 'wpcargo-pod'); ?>
				</div>
			</div>
		</div>
	</div>
	<!-- Modal -->
<?php
	do_action('wpcargo_pod_after_sign_modal');
}
function wpcargo_pod_show_shignaturepad()
{
	global $wpcargo;
	$shipment_id 				= $_POST['sid'];
	$options  					= get_option('wpcargo_pod_option_settings');
	$shipper_selected_option 	= array();
	$receiver_selected_option 	= array();
	$wpcargo_get_status 		= get_post_meta($shipment_id, 'wpcargo_status', true);
	if (!empty($options) && array_key_exists('shipper_fields', $options)) {
		$shipper_selected_option = $options['shipper_fields'];
	}
	if (!empty($options) && array_key_exists('receiver_fields', $options)) {
		$receiver_selected_option = $options['receiver_fields'];
	}
	if (!current_user_can('upload_files')) {
		$user = get_role('wpcargo_driver');
		$user->add_cap('upload_files');
	}
	ob_start();
	require_once(wpcpod_include_template('wpc-pod-sign.tpl'));
	$output = ob_get_clean();
	echo $output;
	wp_die();
}
function wpcargo_pod_signed_load_action()
{
	global $wpcargo;
	if (!wp_verify_nonce($_POST['nonce'], 'wpcpod_signature-nonce')) {
		wp_send_json(array(
			'status' => 'error',
			'message' => __('Something went wrong while processing your request, please reload the page and try again', 'wpcargo-pod')
		));
		wp_die();
	}

	$history_metakeys 		= array_keys(wpcpod_signature_field_list());
	$current_user 			= wp_get_current_user();
	$form_data 				= $_POST['formData'];
	$shipment_id 			= wpcpod_find_metakey($form_data, '__pod_id');
	$shipment_id 			= $shipment_id ? (int)$shipment_id['value'] : 0;
	$signature_id 			= wpcpod_find_metakey($form_data, '__pod_signature');
	$signature_id 			= $signature_id ? (int)$signature_id['value'] : false;
	$shipment_status 		= wpcpod_find_metakey($form_data, 'status');
	$shipment_status 		= $shipment_status ? $shipment_status['value'] : false;

	// Save Shipment History
	$history 				= maybe_unserialize(get_post_meta($shipment_id, 'wpcargo_shipments_update', true));
	$history				= $history && is_array($history) ? $history : array();
	$pod_history 			= array(
		'date'          => current_time($wpcargo->date_format),
		'time' 			=> current_time($wpcargo->time_format),
		'updated-by' 	=> $current_user->ID,
		'updated-name' 	=> $wpcargo->user_fullname($current_user->ID),
	);

	if (!empty($history_metakeys)) {
		foreach ($history_metakeys as $sign_key) {
			$meta_data = wpcpod_find_metakey($form_data, $sign_key);
			$pod_history[$sign_key] = $meta_data['value'];
		}
	}
	$pod_history		= apply_filters('wpcargo_pod_current_history', $pod_history);
	$history[] 			= $pod_history;
	update_post_meta($shipment_id,	'wpcargo_shipments_update',	$history);
	// Save Signature
	if ($signature_id) {
		update_post_meta($shipment_id,	'wpcargo-pod-signature', $signature_id);
	}
	// Save Shipment Status
	update_post_meta($shipment_id,	'wpcargo_status', $shipment_status);

	do_action('wpcargo_extra_pod_saving', $shipment_id, $form_data);

	// Save Custom Field data
	foreach ($form_data as $data) {
		$data_value = is_array($data['value']) ? $data_value : sanitize_text_field($data['value']);
		$data_info 	= wpcpod_custom_fields_data($data['name']);
		$data_info 	= apply_filters('wpcpod_custom_fields_data_results', $data_info, $data);
		if (!$data_info) {
			continue;
		}
		if ($data_info->field_type == 'file') {
			$file_data = get_post_meta($shipment_id, $data_info->field_key, true);
			$file_data = explode(",", $file_data);
			$file_data = array_filter(array_map('trim', $file_data));
			$file_data[] = $data['value'];
			$file_str = implode(',', $file_data);
			update_post_meta($shipment_id, $data_info->field_key, $file_str);
			continue;
		}
		if ($data_info->field_type == 'textarea') {
			$data_value = sanitize_textarea_field($data['value']);
		}
		update_post_meta($shipment_id, $data_info->field_key, $data_value);
	}
	wpcargo_send_email_notificatio($shipment_id, $shipment_status);
	do_action('wpcargo_extra_send_email_notification', $shipment_id, $shipment_status);
	do_action('wpc_add_sms_notification', $shipment_id);
	wp_send_json(array(
		'status' => 'success',
		'message' => sprintf(__('Shipment %s successfully udpated!', 'wpcargo-pod'), get_the_title($shipment_id))
	));
	wp_die();
}

// Save Image
function wpcpod_delete_image()
{
	$shipmentID 		= $_REQUEST['shipmentID'];
	$attchID 			= $_REQUEST['attchID'];
	$saved_images       = get_post_meta($shipmentID, 'wpcargo-pod-image', true);
	$arr_images     	= !empty($saved_images) ? explode(',', $saved_images) : array();
	if (($key = array_search($attchID, $arr_images)) !== false) {
		unset($arr_images[$key]);
	}
	$return  = update_post_meta($shipmentID, 'wpcargo-pod-image', implode(',', $arr_images));
	$message = $return ? sprintf(__('Attachment successfully removed in %s', 'wpcargo-pod'), get_the_title($shipmentID)) : sprintf(__('Attachment failed removed in %s', 'wpcargo-pod'), get_the_title($shipmentID));
	wp_send_json(array(
		'status' 	=> $return,
		'message' 	=> $message,
		'shipmentID' => $shipmentID,
		'attchID'	=> $attchID,
		'$arr_images' => $$arr_images
	));
	die();
}
add_action('wp_ajax_wpcpod_delete_image', 'wpcpod_delete_image');
add_action('wp_ajax_nopriv_wpcpod_delete_image', 'wpcpod_delete_image');

// Manejador de upload directo sin exponer la biblioteca de medios
function wpcpod_direct_upload_image() {
	// Aumentar timeout de ejecución para archivos grandes
	@set_time_limit(300); // 5 minutos

	// Validar nonce
	$nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
	if ( ! wp_verify_nonce( $nonce, 'wpcpod_upload_image' ) && ! wp_verify_nonce( $nonce, 'wpcpod_nonce' ) ) {
		wp_send_json(array(
			'success' => false,
			'message' => 'Error de seguridad: nonce inválido'
		));
	}

	if (!isset($_FILES['files']) || !isset($_POST['shipmentID'])) {
		wp_send_json(array(
			'success' => false,
			'message' => 'Faltan parámetros requeridos'
		));
	}

	$shipmentID = intval($_POST['shipmentID']);
	$files = $_FILES['files'];
	$uploaded_ids = array();
	$valid_mime_types = array('image/png', 'image/jpeg', 'image/gif', 'image/svg+xml');

	error_log('WPCPod: Starting upload for shipment ' . $shipmentID . ' with ' . (is_array($files['name']) ? count($files['name']) : 1) . ' file(s)');

	// Procesar cada archivo
	if (is_array($files['name'])) {
		for ($i = 0; $i < count($files['name']); $i++) {
			if (!empty($files['name'][$i])) {
				$file_array = array(
					'name'     => $files['name'][$i],
					'type'     => $files['type'][$i],
					'tmp_name' => $files['tmp_name'][$i],
					'error'    => $files['error'][$i],
					'size'     => $files['size'][$i]
				);
				error_log('WPCPod: Processing file ' . ($i + 1) . '/' . count($files['name']) . ': ' . $file_array['name']);
				$result = wpcpod_handle_single_file_upload($file_array, $valid_mime_types);
				if ($result !== false) {
					$uploaded_ids[] = $result;
				}
			}
		}
	} else {
		// Un solo archivo
		$file_array = array(
			'name'     => $files['name'],
			'type'     => $files['type'],
			'tmp_name' => $files['tmp_name'],
			'error'    => $files['error'],
			'size'     => $files['size']
		);
		error_log('WPCPod: Processing single file: ' . $file_array['name']);
		$result = wpcpod_handle_single_file_upload($file_array, $valid_mime_types);
		if ($result !== false) {
			$uploaded_ids[] = $result;
		}
	}

	if (empty($uploaded_ids)) {
		error_log('WPCPod: No valid images processed for shipment ' . $shipmentID);
		wp_send_json(array(
			'success' => false,
			'message' => 'No se pudieron procesar los archivos. Verifica que sean imágenes válidas.'
		));
	}

	// Guardar en meta
	$replace_existing = isset($_POST['replace_existing']) && in_array(strval($_POST['replace_existing']), array('1', 'true', 'yes'), true);
	if ($replace_existing) {
		$set_attachments = array_slice($uploaded_ids, 0, 1);
	} else {
		$saved_images = get_post_meta($shipmentID, 'wpcargo-pod-image', true);
		$explode_images = !empty($saved_images) ? explode(',', $saved_images) : array();
		$set_attachments = array_unique(array_merge($uploaded_ids, array_filter($explode_images)));
	}
	update_post_meta($shipmentID, 'wpcargo-pod-image', implode(',', $set_attachments));

	error_log('WPCPod: Successfully saved ' . count($uploaded_ids) . ' images for shipment ' . $shipmentID);

	// Generar HTML de respuesta
	ob_start();
	echo '<p class="header-pod-result">' . __('Your current captured images', 'wpcargo-pod') . ':</p>';
	foreach ($set_attachments as $attachment) {
		echo '<div class="gallery-thumb" data-id="' . esc_attr($attachment) . '"><div class="single-img">';
		echo wp_get_attachment_image($attachment, 'full', false, array('style' => 'max-width:100%;height:auto;'));
		echo '</div><span class="delete-attachment" title="Remove">x</span></div>';
	}
	$html = ob_get_clean();

	wp_send_json(array(
		'success' => true,
		'message' => 'Imágenes subidas correctamente',
		'html' => $html,
		'count' => count($uploaded_ids)
	));
}
add_action('wp_ajax_wpcpod_direct_upload_image', 'wpcpod_direct_upload_image');
add_action('wp_ajax_nopriv_wpcpod_direct_upload_image', 'wpcpod_direct_upload_image');

// Función auxiliar para procesar un archivo individual
function wpcpod_handle_single_file_upload($file_array, $valid_mime_types) {
	// Validar errores del upload primero
	if ($file_array['error'] !== UPLOAD_ERR_OK) {
		error_log('WPCPod Upload Error: ' . $file_array['error'] . ' para archivo ' . $file_array['name']);
		return false;
	}

	// Validar MIME type
	if (!in_array($file_array['type'], $valid_mime_types)) {
		error_log('WPCPod Invalid MIME: ' . $file_array['type'] . ' para archivo ' . $file_array['name']);
		return false;
	}

	// Validar tamaño (máximo 10MB)
	if ($file_array['size'] > 10 * 1024 * 1024) {
		error_log('WPCPod File too large: ' . $file_array['size'] . ' bytes para archivo ' . $file_array['name']);
		return false;
	}

	// Validar que el archivo existe y es legible
	if (!file_exists($file_array['tmp_name']) || !is_readable($file_array['tmp_name'])) {
		error_log('WPCPod Temp file not readable: ' . $file_array['tmp_name']);
		return false;
	}

	// Subir el archivo
	require_once(ABSPATH . 'wp-admin/includes/file.php');
	require_once(ABSPATH . 'wp-admin/includes/image.php');
	require_once(ABSPATH . 'wp-admin/includes/media.php');

	$upload_overrides = array('test_form' => false);
	$uploaded_file = wp_handle_upload($file_array, $upload_overrides);

	if (isset($uploaded_file['error'])) {
		error_log('WPCPod Upload failed: ' . $uploaded_file['error']);
		return false;
	}

	if (!isset($uploaded_file['file'])) {
		error_log('WPCPod No file in upload result');
		return false;
	}

	// Crear entry en la biblioteca de medios
	$attachment = array(
		'post_mime_type' => $uploaded_file['type'],
		'post_title'     => preg_replace('/\.[^.]+$/', '', basename($uploaded_file['file'])),
		'post_content'   => '',
		'post_status'    => 'inherit'
	);

	$attach_id = wp_insert_attachment($attachment, $uploaded_file['file']);

	if (!$attach_id || is_wp_error($attach_id)) {
		error_log('WPCPod Attachment creation failed: ' . ($attach_id ? $attach_id->get_error_message() : 'unknown'));
		return false;
	}

	// Generar metadatos
	$attach_data = wp_generate_attachment_metadata($attach_id, $uploaded_file['file']);
	wp_update_attachment_metadata($attach_id, $attach_data);

	error_log('WPCPod Image uploaded success: Attachment ID ' . $attach_id);
	return $attach_id;
}

function wpcpod_save_attachment()
{
	$shipmentID 		= $_REQUEST['shipmentID'];
	$attachments 		= $_REQUEST['attachments'];
	$saved_images       = get_post_meta($shipmentID, 'wpcargo-pod-image', true);
	$explode_images     = !empty($saved_images) ? explode(',', $saved_images) : array();
	$set_attachments 	= array_unique(array_merge($attachments, array_filter($explode_images)));

	update_post_meta($shipmentID, 'wpcargo-pod-image', implode(',', $set_attachments));
	if (isset($attachments)) {
		echo '<p class="header-pod-result">' . __('Your current captured images', 'wpcargo-pod') . ':</p>';
		foreach ($set_attachments as $attachment) {
			echo '<div class="gallery-thumb" data-id="' . $attachment . '"><div class="single-img">';
			echo wp_get_attachment_image($attachment, 'full', false, array('style' => 'max-width:100%;height:auto;'));
			echo '</div><span class="delete-attachment" title="Remove">x</span></div>';
		}
	}
	die();
}
add_action('wp_ajax_wpcpod_save_attachment', 'wpcpod_save_attachment');
add_action('wp_ajax_nopriv_wpcpod_save_attachment', 'wpcpod_save_attachment');

function wpcargo_pod_assign_driver_save($shipment_id, $data)
{
	if (isset($data['wpcargo_driver']) && (int)$data['wpcargo_driver'] && can_wpcfe_assign_driver()) {
		$old_driver = get_post_meta($shipment_id, 'wpcargo_driver', true);
		update_post_meta($shipment_id, 'wpcargo_driver', (int)$data['wpcargo_driver']);
		// check if the driver is changed Send email notification
		if ($old_driver != (int)$data['wpcargo_driver'] && wpcargo_pod_can_send_email_driver()) {
			wpcargo_assign_shipment_email($shipment_id, (int)$data['wpcargo_driver'], __('Driver', 'wpcargo-pod'));
		}
	} elseif (isset($data['wpcargo_driver']) && !(int)$data['wpcargo_driver']) {
		update_post_meta($shipment_id, 'wpcargo_driver', '');
	}
}
function wpcargo_pod_assign_driver_dropdown($shipment_id)
{
	$assigned_driver = get_post_meta($shipment_id, 'wpcargo_driver', true);
	if (!can_wpcfe_assign_driver()) {
		return false;
	}
?>
	<div class="form-group">
		<div class="select-no-margin">
			<label><?php esc_html_e('Driver', 'wpcargo-pod'); ?></label>
			<select id="wpcargo_driver" name="wpcargo_driver" class="form-control browser-default mdb-select">
				<option value=""><?php echo apply_filters('pod_assign_vehicle_label', __('-- Select Driver --', 'wpcargo-pod')); ?></option>
				<?php foreach (wpcargo_pod_get_drivers() as $driverID => $driver_name): ?>
					<option value="<?php echo $driverID; ?>" <?php selected(get_post_meta($shipment_id, 'wpcargo_driver', true), $driverID); ?>><?php echo $driver_name; ?></option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>
<?php
}
function wpcargo_pod_wpcfe_bulk_update_form_fields()
{
?>
	<div class="form-group">
		<div class="select-no-margin">
			<label><?php _e('Driver', 'wpcargo-pod'); ?></label>
			<select id="wpcargo_driver" name="wpcargo_driver" class="form-control browser-default mdb-select">
				<option value=""><?php echo apply_filters('pod_assign_vehicle_label', __('-- Select Driver --', 'wpcargo-pod')); ?></option>
				<?php foreach (wpcargo_pod_get_drivers() as $driverID => $driver_name): ?>
					<option value="<?php echo $driverID; ?>"><?php echo $driver_name; ?></option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>
<?php
}
function wpcargo_pod_can_send_email_driver()
{
	$gen_settings = get_option('wpcargo_option_settings');
	$email_driver = !array_key_exists('wpcargo_email_driver', $gen_settings) ? true : false;
	return $email_driver;
}
function wpcargo_pod_assign_email_options($options)
{
?>
	<tr>
		<th><?php esc_html_e('Disable Email for Driver?', 'wpcargo-pod'); ?></th>
		<td>
			<input type="checkbox" name="wpcargo_option_settings[wpcargo_email_driver]" <?php echo (!empty($options['wpcargo_email_driver']) && $options['wpcargo_email_driver'] != NULL) ? 'checked' : ''; ?> />
		</td>
	</tr>
<?php
}
// Sidebar Menu
add_action('wp_loaded', 'wpcargo_pod_create_pages');
function wpcargo_pod_create_pages()
{
	wpcargo_pod_create_report_page();
	wpcpod_route_page();
	wpcpod_pickup_route_page();
	wpcpod_set_driver_access();
}
function wpcargo_pod_generate_page($post_title, $post_name, $post_content)
{
	$page_args    = array(
		'comment_status' => 'closed',
		'ping_status' 	=> 'closed',
		'post_author' 	=> 1,
		'post_date' 	=> date('Y-m-d H:i:s'),
		'post_content' 	=> $post_content,
		'post_name' 	=> $post_name,
		'post_status' 	=> 'publish',
		'post_title' 	=> $post_title,
		'post_type' 	=> 'page',
	);
	$page_id = wp_insert_post($page_args, false);
	update_post_meta($page_id, '_wp_page_template', 'dashboard.php');
	return $page_id;
}
function is_wpcpod_page_exist($shortcode)
{

	global $wpdb;
	$sql = "SELECT `ID` FROM `{$wpdb->prefix}posts` WHERE `post_status` LIKE 'publish' AND `post_type` LIKE 'page' AND `post_content` LIKE %s LIMIT 1";

	return $wpdb->get_var($wpdb->prepare($sql, '%' . $shortcode . '%'));
}
function wpcargo_pod_create_report_page()
{
	$wpcpod_page_report 	= get_option('wpcpod_page_report');

	if (!$wpcpod_page_report) {
		$page_id = is_wpcpod_page_exist('[wpcpod_report]');
		update_option('wpcpod_page_report', $page_id, '', 'yes');

		if ($page_id) {
			return $page_id;
		}

		$post_title 	= __('Driver Report', 'wpcargo-pod');
		$post_name 		= 'wpcpod-report-order';
		$post_content 	= '[wpcpod_report]';
		$page_id = wpcargo_pod_generate_page($post_title, $post_name, $post_content);
		update_option('wpcpod_page_report', $page_id, 'yes');
	}

	return $wpcpod_page_report;
}

function wpcpod_route_page()
{
	$wpcpod_route_page = get_option('wpcpod_route_page');

	if (!$wpcpod_route_page) {
		$page_id = is_wpcpod_page_exist('[wpcpod_route]');
		update_option('wpcpod_route_page', $page_id, 'yes');

		if ($page_id) {
			return $page_id;
		}

		$post_title 	= __('Driver Route Planner', 'wpcargo-pod');
		$post_name 		= 'wpcpo-route';
		$post_content 	= '[wpcpod_route]';
		$page_id = wpcargo_pod_generate_page($post_title, $post_name, $post_content);
		update_option('wpcpod_route_page', $page_id, 'yes');
	}

	return $wpcpod_route_page;
}

function wpcpod_pickup_route_page()
{
	$wpcpod_pickup_route_page = get_option('wpcpod_pickup_route_page');

	if (!$wpcpod_pickup_route_page) {
		$page_id = is_wpcpod_page_exist('[wpcpod_pickup_route]');
		update_option('wpcpod_pickup_route_page', $page_id, 'yes');

		if ($page_id) {
			return $page_id;
		}

		$post_title 	= __('Pickup Driver Route Planner', 'wpcargo-pod');
		$post_name 		= 'wpcpo-pickup-route';
		$post_content 	= '[wpcpod_pickup_route]';
		$page_id = wpcargo_pod_generate_page($post_title, $post_name, $post_content);
		update_option('wpcpod_pickup_route_page', $page_id, 'yes');
	}

	return $wpcpod_pickup_route_page;
}

function wpcpod_set_driver_access()
{
	$dashboard_role = get_option('wpcfe_access_dashboard_role');
	$dashboard_role = !empty($dashboard_role) && is_array($dashboard_role) ? $dashboard_role : array();
	if (!in_array('wpcargo_driver', $dashboard_role)) {
		$dashboard_role[] = 'wpcargo_driver';
		update_option('wpcfe_access_dashboard_role', $dashboard_role);
	}
}
function wpcargo_pod_sidebar_menu($menu_array)
{
	if (!function_exists('wpcfe_admin_page')) {
		return false;
	}
	if (wpcpod_route_allowed_user()) {
		$wpcpod_route_class = 'wpcpod-route';
		$wpcpod_pickup_route_class = 'wpcpod-pickup-route';
		if (wpcpod_route_page() == get_the_ID()) {
			$wpcpod_route_class .= ' active';
		}
		if (wpcpod_pickup_route_page() == get_the_ID()) {
			$wpcpod_pickup_route_class .= ' active';
		}
		$menu_array[$wpcpod_route_class] = array(
			'label' => apply_filters('wpcpod_delivery_driver_route_sidemenu_label', __('Entrega de mercadería', 'wpcargo-pod')),
			'permalink' => get_the_permalink(wpcpod_route_page()),
			'icon' => 'fa-map-o'
		);
		$menu_array[$wpcpod_pickup_route_class] = array(
			'label' => apply_filters('wpcpod_pickup_driver_route_sidemenu_label', __('Recojo de mercadería', 'wpcargo-pod')),
			'permalink' => get_the_permalink(wpcpod_pickup_route_page()),
			'icon' => 'fa-map-o'
		);
	}

	if (can_export_wpcpod_report()) {
		$wpcpod_report_class = 'wpcpod-menu';
		if (wpcargo_pod_create_report_page() == get_the_ID()) {
			$wpcpod_report_class .= ' active';
		}
		$menu_array[$wpcpod_report_class] = array(
			'label' => __('Driver Report', 'wpcargo-pod'),
			'permalink' => get_the_permalink(wpcargo_pod_create_report_page()),
			'icon' => 'fa-cloud-download'
		);
	}

	return $menu_array;
}
add_filter('wpcfe_after_sidebar_menus', 'wpcargo_pod_sidebar_menu', 10, 1);
function wpcpod_dashboard_route_script_callback()
{
	if (empty(get_option('shmap_api')) || !wpcpod_route_allowed_user()) {
		return false;
	}
	include_once(wpcpod_include_template('route-planner-script'));
}
add_action('wpcpod_after_route_planner', 'wpcpod_dashboard_route_script_callback');
function wpcpod_pickup_dashboard_route_script_callback()
{
	if (empty(get_option('shmap_api')) || !wpcpod_route_allowed_user()) {
		return false;
	}
	include_once(wpcpod_include_template('pickup-route-planner-script'));
}
add_action('wpcpod_pickup_after_route_planner', 'wpcpod_pickup_dashboard_route_script_callback');
// Driver Report page restriction
function driver_report_page_restriction()
{
	global $post;
	if (!$post) {
		return false;
	}
	if (wpcargo_pod_create_report_page() == $post->ID && !can_export_wpcpod_report()) {
		$dashboard = get_the_permalink(wpcfe_admin_page());
		wp_redirect($dashboard);
		die;
	}
}
add_action('template_redirect', 'driver_report_page_restriction');
function wpcpod_after_sign_popup_form_callback()
{
	$shmap_api          		= get_option('shmap_api');
	$shmap_country_restrict     = get_option('shmap_country_restrict');
	if (empty($shmap_api)) {
		return;
	}
?>
	<script>
		/*
		 ** Google map Script Auto Complete location
		 */
		function wpcpodGetPlaceDynamic() {
			var defaultBounds = new google.maps.LatLngBounds(
				new google.maps.LatLng(-33.8902, 151.1759),
				new google.maps.LatLng(-33.8474, 151.2631)
			);
			var input = document.getElementsByClassName('wpcargo-pod-location');
			var options = {
				bounds: defaultBounds,
				types: ['geocode'],
				<?php if (!empty($shmap_country_restrict)): ?>
					componentRestrictions: {
						country: "<?php echo $shmap_country_restrict; ?>"
					}
				<?php endif; ?>
			};
			for (i = 0; i < input.length; i++) {
				autocomplete = new google.maps.places.Autocomplete(input[i], options);
			}
			<?php do_action('wpcpod_after_get_dynamic_place'); ?>
		}
	</script>
<?php
	echo wpcargo_map_script('wpcpodGetPlaceDynamic');
}
add_action('wpcpod_after_sign_popup_form', 'wpcpod_after_sign_popup_form_callback', 10);

function allow_drivers_to_upload_files()
{

	$role = get_role('wpcargo_driver');
	if ($role && !$role->has_cap('upload_files')) {
		$role->add_cap('upload_files');
	}
	if ($role && !$role->has_cap('edit_posts')) {
		$role->add_cap('edit_posts');
	}
}

add_action('init', 'allow_drivers_to_upload_files');



// function remove_admin_bar()
// {
//     if (!current_user_can('edit_posts') || get_role('wpcargo_driver') || get_role('wpcargo_client')) {
//         return true;
//     }
//     return false;
// }

//add_filter('show_admin_bar', 'remove_admin_bar', PHP_INT_MAX);

add_action('init', 'blockusers_init');
function blockusers_init()
{
	if (is_admin() && !current_user_can('administrator') && !(defined('DOING_AJAX') && DOING_AJAX)) {
		wp_redirect(home_url());
		exit;
	}
}
