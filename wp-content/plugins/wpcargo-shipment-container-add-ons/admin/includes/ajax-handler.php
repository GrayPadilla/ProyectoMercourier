<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}
//** AJAX hooks
add_action('wp_ajax_check_container', 'wpcsc_check_container_callback');
add_action('wp_ajax_bulk_assign_container', 'bulk_assign_container_callback');
add_action('wp_ajax_nopriv_bulk_assign_container', 'bulk_assign_container_callback');
function bulk_assign_container_callback()
{
	$shipment_ids = $_POST['shipmentIDs'];
	$container_id = (int)$_POST['containerID'];
	$shipments = array();
	if (!empty($shipment_ids) && !empty($container_id)) {
		foreach ($shipment_ids as $shipment_id) {
			update_post_meta($shipment_id, 'shipment_container', $container_id);
			$shipments[] = get_the_title($shipment_id);
		}
	}
	do_action('wpcsc_after_save_bulk_assign_container', $shipment_ids, $_POST);
	echo json_encode(array_unique($shipments));
	wp_die();
}
function wpcsc_check_container_callback()
{
	global $wpdb;
	$container_number = sanitize_text_field($_POST['containerNumber']);
	$sql 	= $wpdb->prepare("SELECT `ID` FROM `{$wpdb->prefix}posts` WHERE `post_status` LIKE 'publish' AND `post_type` LIKE 'shipment_container' AND `post_title` LIKE %s LIMIT 1", $container_number);
	$result = $wpdb->get_var($sql);
	echo $result;
	wp_die();
}
add_action('wp_ajax_delete_container', 'wpcsc_delete_container_callback');
function wpcsc_delete_container_callback()
{
	global $wpdb;
	$container_id 	= (int)$_POST['containerID'];
	$assigned_shipments = wpc_shipment_container_get_assigned_shipment($container_id) ?: array();
	$container_title = get_the_title($container_id);
	$message 	 = array(
		'status' => 'warning',
		'icon'	 => 'ti-alert',
		'message' => __('Something went wrong during process, Please try again.', 'wpcargo-shipment-container')
	);
	if (wp_trash_post($container_id)) {
		if (!empty($assigned_shipments) && is_array($assigned_shipments)) {
			foreach ($assigned_shipments as $ship_id) {
				delete_post_meta($ship_id, 'shipment_container');
			}
		}
		$message 	 = array(
			'status' => 'success',
			'icon'	 => 'ti-check',
			'message' => __('Container', 'wpcargo-shipment-container') . ' ' . $container_title . ' ' . __('successfully deleted.', 'wpcargo-shipment-container')
		);
	}
	echo json_encode($message);
	wp_die();
}
add_action('wp_ajax_get_shipments', 'wpc_shipment_container_get_shipment');
function wpc_shipment_container_get_shipment()
{
	global $wpcargo;
	$data = wpcsc_datatable_unassigned_shipment((int)$_POST['postID']);
	echo json_encode($data);
	wp_die();
}
add_action('wp_ajax_assign_shipment_admin', 'wpc_shipment_container_assign_shipment_admin');
function wpc_shipment_container_assign_shipment_admin()
{
	global $wpdb, $wpcargo;
	$shipmentID 	= (int)$_POST['shipmentID'];
	$containerID 	= (int)$_POST['containerID'];
	$result  		= array();
	// Check if the container ID post type is shipment_container
	if (get_post_type($containerID) == 'shipment_container') {
		// Check if the shipment is already Assign to other container
		if (get_post_meta($shipmentID, 'shipment_container', true)) {
			$result = array(
				'status' => 'error',
				'message' => sprintf(__("Shipment %s already assign to container %s.", 'wpcargo-shipment-container'), get_the_title($shipmentID), get_the_title(get_post_meta($shipmentID, 'shipment_container', true)))
			);
		} else {
			update_post_meta($shipmentID, 'shipment_container', $containerID);
			$shipment_title = get_the_title($shipmentID);
			$wpcfe_print_options = wpcfe_print_options();
			$status = get_post_meta($shipmentID, 'wpcargo_status', true);
			ob_start();
?>
			<div id="shipment-<?php echo $shipmentID; ?>" data-shipment="<?php echo $shipmentID; ?>" class="selected-shipment">
				<span class="dashicons dashicons-dismiss" data-id="<?php echo $shipmentID; ?>"></span>
				<?php do_action('wpcsc_before_shipment_content_section', $shipmentID); ?>
				<img src="<?php echo $wpcargo->barcode_url($shipmentID); ?>" alt="<?php echo $shipment_title; ?>" />
				<h3 class="shipment-title"><a style="text-decoration: none;" href="<?php echo admin_url('post.php?post=' . $shipmentID . '&action=edit'); ?>" target="_target"><?php echo $shipment_title; ?></a></h3>
				<?php do_action('wpcsc_after_shipment_content_section', $shipmentID); ?>
			</div>
		<?php
			$output = ob_get_clean();
			$result = array(
				'status' 	=> 'success',
				'message' 	=> $output
			);
		}
	} else {
		$result = array(
			'status' => 'error',
			'message' => __("Selected Container not found! Please reload and try again.", 'wpcargo-shipment-container')
		);
	}
	$result['data'] = wpcsc_datatable_unassigned_shipment($containerID);
	echo json_encode($result);
	wp_die();
}
add_action('wp_ajax_assign_shipment', 'wpc_shipment_container_assign_shipment');
function wpc_shipment_container_assign_shipment()
{
	global $wpdb, $wpcargo;
	$shipmentID 	= (int)$_POST['shipmentID'];
	$containerID 	= (int)$_POST['containerID'] ? (int)$_POST['containerID'] : 'USER' . get_current_user_id();
	$result  		= array();
	// Check if the shipment is already Assign to other container
	if (get_post_meta($shipmentID, 'shipment_container', true)) {
		$result = array(
			'status' => 'error',
			'message' => sprintf(__("Shipment %s already assign to container %s.", 'wpcargo-shipment-container'), get_the_title($shipmentID), get_the_title(get_post_meta($shipmentID, 'shipment_container', true)))
		);
	} else {
		update_post_meta($shipmentID, 'shipment_container', $containerID);
		$shipment_title = get_the_title($shipmentID);
		$wpcfe_print_options = wpcfe_print_options();
		$status = get_post_meta($shipmentID, 'wpcargo_status', true);
		ob_start();
		?>
		<tr id="shipment-<?php echo $shipmentID; ?>" data-shipment="<?php echo $shipmentID; ?>" class="selected-shipment p-1 col-md-4">
			<td class="align-middle"><i class="fa fa-sort mr-3"></i></td>
			<td class="text-center">
				<?php do_action('wpcsc_before_shipment_content_section', $shipmentID); ?>
				<h3 class="shipment-title h6"><a style="text-decoration: none;" href="<?php echo get_the_permalink(wpcfe_admin_page()) . '?wpcfe=track&num=' . $shipment_title; ?>" target="_blank"><?php echo $shipment_title; ?></a></h3>
				<?php do_action('wpcsc_after_shipment_content_section', $shipmentID); ?>
			</td>
			<td><?php echo $status; ?></td>
			<td class="print-shipment text-center">
				<div class="dropdown">
					<!--Trigger-->
					<button class="btn btn-default btn-sm dropdown-toggle m-0 py-1 px-2" type="button" id="dropdownPrint-<?php echo $shipmentID; ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-list"></i></button>
					<!--Menu-->
					<div class="dropdown-menu dropdown-primary">
						<?php foreach ($wpcfe_print_options as $print_key => $print_label): ?>
							<a class="dropdown-item print-<?php echo $print_key; ?> py-1" data-id="<?php echo $shipmentID; ?>" data-type="<?php echo $print_key; ?>" href="#"><?php esc_html_e('Print', 'wpcargo-shipment-container'); ?> <?php echo $print_label; ?></a>
						<?php endforeach; ?>
					</div>
				</div>
			</td>
			<td class="text-center">
				<div class="dropdown">
					<!--Trigger-->
					<button class="btn btn-success btn-sm dropdown-toggle m-0 py-1 px-2" type="button" id="update-<?php echo $shipmentID; ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-edit"></i></button>
					<!--Menu-->
					<div class="dropdown-menu dropdown-primary">
						<?php foreach ($wpcargo->status as $status): ?>
							<a class="update-shipment dropdown-item py-1" data-id="<?php echo $shipmentID; ?>" data-value="<?php echo $status; ?>" href="#"><?php echo $status; ?></a>
						<?php endforeach; ?>
					</div>
				</div>
			</td>
			<td class="text-center">
				<button class="btn btn-danger btn-sm m-0 py-1 px-2 remove-shipment" data-id="<?php echo $shipmentID; ?>" title="<?php esc_html_e('Remove', 'wpcargo-shipment-container'); ?>"><i class="fa fa-trash"></i></button>
			</td>
			<?php do_action('wpcsc_after_shipment_content_section', $shipmentID); ?>
		</tr>
		<?php
		$output = ob_get_clean();
		$result = array(
			'status' 	=> 'success',
			'message' 	=> $output
		);
	}
	$result['data'] = wpcsc_datatable_unassigned_shipment($containerID);
	echo json_encode($result);
	wp_die();
}
add_action('wp_ajax_add_shipments', 'wpc_shipment_container_add_shipment');
function wpc_shipment_container_add_shipment()
{
	global $wpdb, $wpcargo;
	$data 		= urldecode($_POST['data']);
	$postID 	= $_POST['postID'];
	if (!empty($data)) {
		// Set the Container post_status : Publish
		$container_args = array(
			'ID'            => $postID,
			'post_status'   => 'publish',
		);
		wp_update_post($container_args);
		$shipment_parameter = explode("&", $data);
		foreach ($shipment_parameter as $shipment_data) {
			$shipment = explode("=", $shipment_data);
			update_post_meta($shipment[1], 'shipment_container', $postID);
			$shipment_title = get_the_title($shipment[1]);
			ob_start();
		?>
			<div id="shipment-<?php echo $shipment[1]; ?>" data-shipment="<?php echo $shipment[1]; ?>" class="selected-shipment text-center p-1 col-md-4">
				<span class="dashicons dashicons-dismiss" data-id="<?php echo $shipment[1]; ?>"></span>
				<?php do_action('wpcsc_before_shipment_content_section', $shipment[1]); ?>
				<img src="<?php echo $wpcargo->barcode_url($shipment[1]); ?>" alt="<?php echo $shipment_title; ?>" />
				<h3 class="shipment-title h6"><?php echo $shipment_title; ?></h3>
				<?php do_action('wpcsc_after_shipment_content_section', $shipment[1]); ?>
			</div>
	<?php
			$output = ob_get_clean();
			echo $output;
		}
	}
	wp_die();
}
add_action('wp_ajax_remove_shipment', 'wpc_shipment_container_remove_shipment');
function wpc_shipment_container_remove_shipment()
{
	global $wpdb;
	$postID = $_POST['postID'];
	$result = delete_post_meta($postID, 'shipment_container');
	echo $result;
	wp_die();
}
add_action('wp_ajax_update_shipment', 'wpc_shipment_container_update_shipment');
function wpc_shipment_container_update_shipment()
{
	global $wpdb;
	$postID 		= $_POST['postID'];
	$status 		= $_POST['status'];
	$old_status 	= get_post_meta($postID, 'wpcargo_status', true);
	
	// Si el nuevo estado es "LISTO PARA SALIR", guardar el estado anterior Y agregar al historial
	if (stripos($status, 'LISTO PARA SALIR') !== false && !empty($old_status)) {
		update_post_meta($postID, 'wpcargo_status_anterior', $old_status);
		
		// Agregar registro al historial con el estado anterior
		$shipment_history = maybe_unserialize(get_post_meta($postID, 'wpcargo_shipments_update', true));
		if (!is_array($shipment_history)) {
			$shipment_history = array();
		}
		
		// Crear registro con estado anterior
		$new_record = array(
			'status' => $old_status,
			'date' => date('Y-m-d'),
			'time' => date('H:i:s'),
			'updated-name' => (function_exists('wpcargo_user_fullname')) ? 
				call_user_func('wpcargo_user_fullname', get_current_user_id()) : 
				wp_get_current_user()->display_name,
			'location' => get_post_meta($postID, 'location', true),
			'remarks' => 'Estado anterior registrado'
		);
		
		// Agregar al inicio del historial
		array_unshift($shipment_history, $new_record);
		update_post_meta($postID, 'wpcargo_shipments_update', $shipment_history);
	}
	
	update_post_meta($postID, 'wpcargo_status', $status);
	if (function_exists('wpcfe_save_report')) {
		wpcfe_save_report($postID, $old_status, sanitize_text_field($status));
	}
	if ($status != $old_status) {
		if (function_exists('wpcargo_send_email_notificatio')) {
			wpcargo_send_email_notificatio($postID, $status);
		}
		do_action('wpcargo_extra_send_email_notification', $postID, $status);
		do_action('wpc_add_sms_shipment_history', $postID);
	}
	wp_die();
}

add_action('wp_ajax_add_new_container', 'wpc_shipment_container_add_new_container');
function wpc_shipment_container_add_new_container()
{

	$preassigned_shipments	= wpc_shipment_container_get_preassigned_shipment();
	if (!empty($preassigned_shipments)) {
		foreach ($preassigned_shipments as $shipments_id) {
			if (get_post_meta($shipments_id, 'shipment_container', true)) {
				delete_post_meta($shipments_id, 'shipment_container');
			}
		}
	}
	wp_die();
}

add_action('wp_ajax_page_shipment', 'wpc_shipment_contianer_page_shipment');
function wpc_shipment_contianer_page_shipment()
{
	$page 			= ($_POST['page'] <= 1) ? 1 : $_POST['page'];
	$offset 		= ($page - 1) * WPCARGO_SHIPMENT_CONTAINER_PAGER;
	$results 		= wpc_shipment_container_get_paged_shipment($offset, WPCARGO_SHIPMENT_CONTAINER_PAGER);
	$shipper_display 	= get_option('container_shipper_display');
	$receiver_display 	= get_option('container_receiver_display');
	$shipper_label = wpc_shipment_container_get_field_label($shipper_display);
	$shipper_label = wpc_shipment_container_get_field_label($shipper_display);
	ob_start();
	?>
	<div id="shipment-container">
		<?php if (!empty($results)) : ?>
			<?php foreach ($results as $shipment): ?>
				<li id="shipment-<?php echo $shipment->ID; ?>" class="shipment-section" data-search-term="<?php echo strtolower(get_the_title($shipment->ID)); ?>">
					<input id="shipment-num-<?php echo $shipment->ID; ?>" type="checkbox" class="form-check-input" name="shipment" value="<?php echo $shipment->ID; ?>" />
					<label for="shipment-num-<?php echo $shipment->ID; ?>"><?php echo get_the_title($shipment->ID); ?></label>
					<div class="shipment-info">
						<?php do_action('wpcsc_before_shipment_list_item', $shipment->ID); ?>
						<?php if (!empty($shipper_display)): ?>
							<?php
							$value = maybe_unserialize(get_post_meta($shipment->ID, $shipper_display, true));
							$value = (is_array($value)) ? implode(', ', $value) : $value;
							?>
							<p><strong><?php esc_html_e('Shipper', 'wpcargo-shipment-container'); ?> <?php echo wpc_shipment_container_get_field_label($shipper_display); ?></strong> : <?php echo $value; ?></p>
						<?php endif; ?>
						<?php do_action('wpcsc_before_receiver_shipment_list_item', $shipment->ID); ?>
						<?php if (!empty($receiver_display)): ?>
							<?php
							$value = maybe_unserialize(get_post_meta($shipment->ID, $receiver_display, true));
							$value = (is_array($value)) ? implode(', ', $value) : $value;
							?>
							<p><strong><?php esc_html_e('Receiver', 'wpcargo-shipment-container'); ?> <?php echo wpc_shipment_container_get_field_label($receiver_display); ?></strong> : <?php echo $value; ?></p>
						<?php endif; ?>
						<?php do_action('wpcsc_after_shipment_list_item', $shipment->ID); ?>
					</div>
				</li>
			<?php endforeach; ?>
		<?php else: ?>
			<h2 style="text-align:center;"><?php esc_html_e('No Available Shipments found.', 'wpcargo-shipment-container'); ?></h2>
		<?php endif; ?>
	</div>
	<?php
	$output = ob_get_clean();
	echo $output;
	wp_die();
}
// Bulk - Assign Shipments to Container
function wpc_shipment_contianer_bulk_container_update()
{
	$conStatus  	= $_POST['conStatus'];
	$containers 	= $_POST['containers'];
	$applyShipment 	= $_POST['applyShipment'];
	$results 		= array();
	if (!empty($containers)) {
		foreach ($containers as $container) {
			$_result = update_post_meta($container, 'container_status', $conStatus);
			$results[] = array(
				'id' 		=> $container,
				'result' 	=> $_result,
				'status' 	=> $conStatus
			);
			if ($applyShipment) {
				$shipments = wpc_shipment_container_get_assigned_shipment($container);
				if (!empty($shipments)) {
					foreach ($shipments as $shipment_id) {
						update_post_meta($shipment_id, 'wpcargo_status', $conStatus);
					}
				}
			}
		}
	}
	echo json_encode($results);
	wp_die();
}
add_action('wp_ajax_bulk_container_update', 'wpc_shipment_contianer_bulk_container_update');
// Bulk - Delete  Container
function wpcsc_bulk_delete_container_callback()
{
	$selectedContainer  = $_POST['selectedContainer'];
	if (!can_access_containers() && !delete_containers_roles()) {
		wp_send_json(array(
			'status'  => 'error',
			'message' => __('Permission denied', 'wpcargo-shipment-container')
		));
	}
	if (empty($selectedContainer)) {
		wp_send_json(array(
			'status'  => 'error',
			'message' => wpc_scpt_no_container_selected_message()
		));
	}
	$results = array();
	foreach ($selectedContainer as $container_id) {
		$assigned_shipments = wpc_shipment_container_get_assigned_shipment($container_id) ?: array();
		if (!(int)$container_id || get_post_type($container_id) != 'shipment_container') {
			$results[$container_id] = array(
				'status' => 'error',
				'message' => __('Failed to delete data', 'wpcargo-shipment-container')
			);
			continue;
		}
		if (wp_trash_post($container_id)) {
			if (!empty($assigned_shipments) && is_array($assigned_shipments)) {
				foreach ($assigned_shipments as $ship_id) {
					delete_post_meta($ship_id, 'shipment_container');
				}
			}
			$results[$container_id] = array(
				'status' => 'success',
				'message' => sprintf(__('%s deleted successfully', 'wpcargo-shipment-container'), get_the_title($container_id))
			);
			continue;
		}
		$results[$container_id] = array(
			'status' => 'error',
			'message' => __('Failed to delete data', 'wpcargo-shipment-container')
		);
	}
	wp_send_json(array(
		'status' => 'success',
		'message' => __('Delete process successfully completed'),
		'results' => $results
	));
	wp_die();
}
add_action('wp_ajax_wpcsc_bulk_delete', 'wpcsc_bulk_delete_container_callback');

// Import/Export AJAX handler
function wpcsc_export_container_callback()
{
	$formdata = $_POST['formData'];
	if (!wp_verify_nonce($_POST['nonce'], 'wpcsc_import_export_nonce') || !can_access_containers()) {
		wp_send_json(array(
			'status' => 'error',
			'message' => __('Permission Denied.', 'wpcargo-shipment-container')
		));
	}

	$format_list 		= wpcsc_export_file_format_list();
	$file_directory 	= WPCARGO_SHIPMENT_CONTAINER_PATH . "file-storage" . DIRECTORY_SEPARATOR;
	$file_url 			= WPCARGO_SHIPMENT_CONTAINER_URL . "file-storage" . DIRECTORY_SEPARATOR;

	$file_headers 		= array();

	// Remove all Existing Files
	wpcsc_clean_dir($file_directory);
	$file_format  		= apply_filters('wpcsc_export_file_format', "csv");
	$delimiter 			= array_key_exists($file_format, $format_list) ? $format_list[$file_format] : ',';
	if (!array_key_exists(trim($file_format), $format_list)) {
		$file_format 	= 'csv';
		$delimiter 		= ',';
	}
	$file_format 		= str_replace('.', '', $file_format);
	$filename_unique 	= "container-export-" . time() . '.' . trim($file_format);
	$csv_file 			= fopen($file_directory . $filename_unique, "w");
	//write utf-8 characters to file with fputcsv in php
	fprintf($csv_file, chr(0xEF) . chr(0xBB) . chr(0xBF));

	// Extract form submitted data
	$post_data = array();
	foreach ($formdata as $data) {
		$post_data[$data['name']] = sanitize_text_field($data['value']);
	}

	// Preparing files for file header
	$file_info = wpcsc_import_export_headers();
	$file_info = apply_filters('wpcsc_export_data_headers', $file_info);


	$meta_query = array();
	// Filter by status
	if (isset($post_data['wpcargo_status']) && !empty($post_data['wpcargo_status'])) {
		$meta_query['wpcargo_status'] = array(
			'key' 		=> 'wpcargo_status',
			'value' 	=> $post_data['wpcargo_status'],
			'compare' 	=> 'LIKE'
		);
	}
	// Filter by registered shipper
	if (isset($post_data['shipment_author']) && !empty($post_data['shipment_author'])) {
		$meta_query['registered_shipper'] = array(
			'key' 		=> 'registered_shipper',
			'value' 	=> $post_data['shipment_author'],
			'compare' 	=> '='
		);
	}
	$args  = array(
		'post_type'         => 'shipment_container',
		'post_status'       => 'publish'
	);
	$meta_query = apply_filters('wpcsc_export_meta_query', $meta_query);
	if (!empty($meta_query)) {
		$args['meta_query'] = array(
			'relation' => 'AND',
			$meta_query
		);
	}
	// Filter by date created
	if (isset($post_data['date-from']) && !empty($post_data['date-from']) && isset($post_data['date-to']) && !empty($post_data['date-to'])) {
		$args['date_query'] = array(
			array(
				'after'     => $post_data['date-from'],
				'before'    => $post_data['date-to'],
				'inclusive' => true,
			),
		);
	}

	$args = apply_filters('wpcsc_export_query_arguments', $args);

	$wpc_container  = new WP_Query($args);
	if ($wpc_container->have_posts()) :
		fputcsv($csv_file, array_values($file_info), $delimiter);
		while ($wpc_container->have_posts()) : $wpc_container->the_post();
			$row_data = array();
			foreach (array_keys($file_info) as $metakey) {

				if ($metakey == '_container_number') {
					$row_data[] = get_the_title();
					continue;
				}

				if ($metakey == '_assigned_shipments') {
					$shipments 	= wpc_shipment_container_get_assigned_shipment(get_the_ID());
					$shipment_numbers 	= array_map(function ($shipment_id) {
						return get_the_title($shipment_id);
					}, $shipments);
					$row_data[] = implode(", ", $shipment_numbers);
					continue;
				}
				if (in_array($metakey, assignees_list())) {
					foreach (assignees_list() as $assign) {
						if ($metakey == $assign) {
							$assigned_shipper	= get_post_meta(get_the_ID(), $assign, true);
							$shipper_name		= get_userdata($assigned_shipper);
							$first_name = $shipper_name->first_name;
							$last_name = $shipper_name->last_name;
							$full_name = $first_name . ' ' . $last_name;
							$row_data[] = $full_name;
						}
					}
					continue;
				}

				$meta_value =  get_post_meta(get_the_ID(), $metakey, true);
				if (is_array($meta_value)) {
					$meta_value = implode(",", $meta_value);
				}
				$row_data[] = esc_html__($meta_value);
			}
			fputcsv($csv_file, $row_data, $delimiter);
		endwhile;
		fclose($csv_file);
	endif;
	// Reset Post Data
	wp_reset_postdata();

	wp_send_json(array(
		'status' 	=> 'success',
		'message' 	=> __('Proccessing import completed.', 'wpcargo-shipment-container'),
		'file' 		=> array(
			'file_url' => $file_url . '/' . $filename_unique,
			'file_name' => $filename_unique
		),
		'args' 	=> $args
	));

	wp_die();
}
add_action('wp_ajax_wpcsc_export', 'wpcsc_export_container_callback');

// Import - Download template
function wpcsc_download_template_container_callback()
{
	$file_info 			= wpcsc_import_export_headers();
	$file_info 			= apply_filters('wpcsc_import_data_headers', $file_info);
	// Merge header key value pairs
	array_walk($file_info, function (&$a, $b) {
		$a = "($b) $a";
	});
	$format_list 		= wpcsc_export_file_format_list();
	$file_directory 	= WPCARGO_SHIPMENT_CONTAINER_PATH . "file-storage" . DIRECTORY_SEPARATOR;
	$file_url 			= WPCARGO_SHIPMENT_CONTAINER_URL . "file-storage" . DIRECTORY_SEPARATOR;
	$file_headers 		= array();
	// Remove all Existing Files
	wpcsc_clean_dir($file_directory);
	$delimiter 			= apply_filters('wpcsc_import_delimiter', ',');
	$filename_unique 	= "container-import-template-" . time() . '.csv';
	$csv_file 			= fopen($file_directory . $filename_unique, "w");
	//write utf-8 characters to file with fputcsv in php
	fprintf($csv_file, chr(0xEF) . chr(0xBB) . chr(0xBF));
	fputcsv($csv_file, array_values($file_info), $delimiter);
	fclose($csv_file);

	wp_send_json(array(
		'status' 	=> 'success',
		'message' 	=> __('Proccessing import completed.', 'wpcargo-shipment-container'),
		'file' 		=> array(
			'file_url' => $file_url . '/' . $filename_unique,
			'file_name' => $filename_unique
		)
	));
}
add_action('wp_ajax_wpcsc_download_template', 'wpcsc_download_template_container_callback');

// Import - Shipment Container
function wpcsc_import_container_callback()
{

	if (!wp_verify_nonce($_POST['nonce'], 'wpcsc_import_export_nonce') || !can_access_containers()) {
		wp_send_json(array(
			'status' => 'error',
			'message' => __('Permission Denied.', 'wpcargo-shipment-container')
		));
	}

	$valid_extensions 		= array('vnd.ms-excel', 'csv', 'text/csv');
	$delimiters 	    	= array(",", ";", "\t", ":", "|");
	$phpFileUploadErrors 	= wpcsc_file_upload_errors();

	if (!in_array($_FILES['uploadedfile']['type'], $valid_extensions)) {
		wp_send_json(array(
			'status' 		=> 'error',
			'message' 		=> __('File type is not allowed, required .CSV file format.', 'wpcargo-shipment-container'),
			'file_format' 	=> $_FILES['uploadedfile']['type'],
			'file' 			=> $_FILES
		));
	}
	if ($_FILES['uploadedfile']['error'] !== 0) {
		wp_send_json(array(
			'status' => 'error',
			'message' => $phpFileUploadErrors[$_FILES['uploadedfile']['error']]
		));
	}

	if (($handle = fopen($_FILES['uploadedfile']['tmp_name'], "r")) !== FALSE) {
		$declared_delimiter = get_option('wpcie_declared_delimiter', ',');
		$file_headers       = array();
		$data_header 	    = array();
		$orders_data 	    = array();

		// Set the delimiter for the submitted file
		foreach ($delimiters as $delimter) {
			$file_headers = fgetcsv($handle, null, $delimter);
			if (!empty($file_headers) && is_array($file_headers)) {
				$declared_delimiter = $delimter;
				break;
			}
		}
		update_option('wpcie_declared_delimiter', $declared_delimiter);

		// Check if file header is empty
		if (empty($file_headers)) {
			wp_send_json(array(
				'status' => 'error',
				'message' => __('File header required', 'wpcargo-shipment-containers')
			));
		}
		// Get metakeys based on the file headers
		$registered_keys 	= [];
		foreach ($file_headers as $header) {
			$registered_keys[] = trim(wpcsc_get_string_between($header, '(', ')'));
		}
		// Get row data based on the registered metakeys
		while (($datas = fgetcsv($handle, null, $declared_delimiter)) !== FALSE) {
			if (empty($datas) || count($datas) != count($registered_keys)) {
				continue;
			}
			$data_container     = array();
			foreach ($datas as $key => $data) {
				// Make sure the the meta key exist in the shipment
				if (empty($registered_keys[$key])) {
					continue;
				}
				$data_container[$registered_keys[$key]] = sanitize_text_field($data);
			}
			$orders_data[] = $data_container;
		}
		wp_send_json(array(
			'status' 	=> 'success',
			'message' 	=> __('File import completed', 'wpcargo-shipment-containers'),
			'data' 		=> $orders_data
		));
	}

	wp_die();
}
add_action('wp_ajax_wpcsc_import', 'wpcsc_import_container_callback');
function wpcsc_save_records_container_callback()
{
	$records 		 =  (isset($_POST['record']) ?: $_POST['record']) ?? '';
	if (empty($records)) {
		wp_send_json(array(
			'status' 			=> 'error',
			'message' 			=> __('Failed to process.', 'wpcargo-shipment-container'),
			'container_number' 	=> ''
		));
		wp_die();
	}
	$file_info 		  = wpcsc_import_export_headers();
	$container_number = isset($records['_container_number']) ? sanitize_text_field($records['_container_number']) : false;
	$assign_shipments = isset($records['_assigned_shipments']) ? sanitize_text_field($records['_assigned_shipments']) : false;

	// Unset reserve meta_key fields
	unset($records['_container_number']);
	unset($records['_assigned_shipments']);

	$container_id = wpcsc_get_container_id($container_number);
	if (!$container_id) {
		$container_number = $container_number ? $container_number : wpcsc_generate_number();
		$container_args = array(
			'post_title'    => sanitize_text_field($container_number),
			'post_status'   => 'publish',
			'post_author'   => get_current_user_id(),
			'post_type'		=> 'shipment_container'
		);
		$container_id = wp_insert_post($container_args);
	}

	if (is_wp_error($container_id)) {
		wp_send_json(array(
			'status' 			=> 'error',
			'message' 			=> sprintf($container_number . ' %s ' . $container_id->get_error_message(), __('Failed to process.', 'wpcargo-shipment-container')),
			'container_number' 	=> $container_number
		));
		wp_die();
	}

	foreach ($records as $key => $value) {
		if (!in_array($key, array_keys($file_info))) {
			continue;
		}
		update_post_meta($container_id, $key, sanitize_text_field($value));
	}

	$assign_shipments = array_filter(explode(",", $assign_shipments));
	// Assigning shipment to shipment container
	if (!empty($assign_shipments)) {
		foreach ($assign_shipments as $shipment_number) {
			$shipment_id = wpcsc_get_shipment_id($shipment_number);
			// Check if shipment number exist in the shipment
			if (!$shipment_id) {
				continue;
			}
			// make sure that the shipment is not assigned to any shipment container
			if (get_post_meta((int)$shipment_id, 'shipment_container', true)) {
				continue;
			}
			update_post_meta((int)$shipment_id, 'shipment_container', $container_id);
		}
	}
	do_action('wpcsc_after_save_container_records', $container_id, $records);
	wp_send_json(array(
		'status'    		=> 'success',
		'message'   		=> sprintf(wpc_container_number_label() . ' ' . $container_number . ' %s', __('Successfully saved.', 'wpcargo-shipment-container')),
		'container_id'     	=> $container_id,
		'container_number' 	=> $container_number,
	));
	wp_die();
}
add_action('wp_ajax_wpcsc_save_records', 'wpcsc_save_records_container_callback');

/* ============================== add/update container ajax ======================================== */
function wp_ajax_wpcsc_add_update_container_cb()
{
	# convert serialized form data to array
	parse_str($_POST['formData'], $data);

	# get container id
	$container_id = $data['container_id'] ?? false;

	$is_update = false;

	# if container id does not exist, add new container and update it otherwise
	if (!$container_id) { # add
		$container_args = array(
			'post_title'  => sanitize_text_field($data['wpcsc_number']),
			'post_status' => 'publish',
			'post_author' => get_current_user_id(),
			'post_type'		=> 'shipment_container'
		);
		$container_id = wp_insert_post($container_args);
	} else { # update
		$container_args = array(
			'ID'            => $container_id,
			'post_title'    => sanitize_text_field($data['wpcsc_number']),
			'post_status'   => 'publish',
		);
		wp_update_post($container_args);
		$is_update = true;
	}

	# setup response
	$container_title = get_the_title($container_id);
	$toggle_text = ($is_update ? 'updated' : 'added');
	$response = array(
		'message' => sprintf(__('Container %s %s successfully.', WPCARGO_SHIPMENT_CONTAINER_TEXTDOMAIN), $container_title, $toggle_text),
		'status' => 'success',
		'icon' => 'check',
	);

	# additional data update
	$info_fields = wpc_container_info_fields();
	if (!empty($info_fields)) {
		foreach ($info_fields as $key => $value) {
			if (isset($data[$key]) && !empty($data[$key])) {
				update_post_meta($container_id, $key, sanitize_text_field($data[$key]));
			}
		}
	}
	$trip_fields = wpc_trip_info_fields();
	if (!empty($trip_fields)) {
		foreach ($trip_fields as $key => $value) {
			if (isset($data[$key]) && !empty($data[$key])) {
				update_post_meta($container_id, $key, sanitize_text_field($data[$key]));
			}
		}
	}
	$time_fields = wpc_time_info_fields();
	if (!empty($time_fields)) {
		foreach ($time_fields as $key => $value) {
			if (isset($data[$key]) && !empty($data[$key])) {
				update_post_meta($container_id, $key, sanitize_text_field($data[$key]));
			}
		}
	}

	# assigned shipments update// Assinged Shipments
	if (!$is_update) {
		$pre_assigned_shipments = wpc_shipment_container_get_preassigned_shipment();
		if (!empty($pre_assigned_shipments)) {
			foreach ($pre_assigned_shipments  as $pre_shipment_id) {
				update_post_meta((int)$pre_shipment_id, 'shipment_container', $container_id);
			}
		}
	}
	$assigned_shipments = sanitize_text_field($data['wpcc_sorted_shipments']);
	update_post_meta($container_id, 'wpcc_sorted_shipments', $assigned_shipments);
	$assigned_shipments = $assigned_shipments ? explode(',', $assigned_shipments) : array();
	foreach ($assigned_shipments as $shipment_id) {
		if (!(int)$shipment_id) {
			continue;
		}
		update_post_meta((int)$shipment_id, 'shipment_container', $container_id);
	}

	# save container history
	wpcsc_save_history($container_id, $data);

	# do action hook for additional actions after container is created/updated
	do_action('after_container_publish', $container_id, $data);

	# send response
	echo wp_send_json($response);
	wp_die();
}
add_action('wp_ajax_wpcsc_add_update_container', 'wp_ajax_wpcsc_add_update_container_cb');

# load assigned shipments ajax callback
function wp_ajax_wpcsc_load_assigned_shipments_cb()
{
	global $wpcargo;
	$container_id 			= ($_POST['containerID'] ?? false) ?: false;
	$assigned_shipments = wpc_shipment_container_get_assigned_shipment($container_id);
	ob_start();
	if ($assigned_shipments && is_array($assigned_shipments)) {
		foreach ($assigned_shipments as $shipment_id) {
			// FILTRO: Solo mostrar envíos SIN motorizado asignado
			$motorizado_recojo = get_post_meta($shipment_id, 'wpcargo_motorizo_recojo', true);
			$motorizado_entrega = get_post_meta($shipment_id, 'wpcargo_motorizo_entrega', true);
			
			// Si tiene motorizado asignado, saltar
			$tiene_motorizado = (!empty($motorizado_recojo) && $motorizado_recojo !== '0') 
				|| (!empty($motorizado_entrega) && $motorizado_entrega !== '0');
			
			if ($tiene_motorizado) {
				error_log("⏭️  [MODAL SKIP] Envío #{$shipment_id}: Ya tiene motorizado asignado");
				continue;
			}
			
			$title 	 	= get_the_title($shipment_id);
			$barcode 	= $wpcargo->barcode($shipment_id, true);
			$url 			= get_the_permalink(wpcfe_admin_page()) . '/?wpcfe=track&num=' . urlencode(get_the_title($shipment_id));
	?>
			<div class="col-md-6 p-2 border text-center">
				<a href="<?php echo $url; ?>" target="_blank"><?php echo $barcode . $title; ?></a>
			</div>
<?php
		}
	}
	$output = ob_get_clean();
	echo $output;
	wp_die();
}
add_action('wp_ajax_wpcsc_load_assigned_shipments', 'wp_ajax_wpcsc_load_assigned_shipments_cb');

// Asignar motorizado a pedidos seleccionados
add_action('wp_ajax_wpcsc_assign_partial_shipments', 'wpcsc_assign_partial_shipments_callback');
function wpcsc_assign_partial_shipments_callback() {
    // Verificar nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpcsc_assign_partial')) {
        wp_send_json_error(array('message' => 'Seguridad: nonce inválido'));
        return;
    }
    
    // Verificar permisos
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'No tienes permisos para esta acción'));
        return;
    }
    
    $shipments = isset($_POST['shipments']) ? array_map('intval', $_POST['shipments']) : array();
    $driver_id = isset($_POST['driver_id']) ? intval($_POST['driver_id']) : 0;
    $container_id = isset($_POST['container_id']) ? intval($_POST['container_id']) : 0;
    
    if (empty($shipments) || $driver_id <= 0) {
        wp_send_json_error(array('message' => 'Datos inválidos'));
        return;
    }
    
    $success_count = 0;
    $errors = array();
    
    foreach ($shipments as $shipment_id) {
        // Verificar que el pedido existe
        if (get_post_type($shipment_id) !== 'wpcargo_shipment') {
            $errors[] = "Pedido #{$shipment_id} no encontrado";
            continue;
        }
        
        // Asignar motorizado
        $updated = update_post_meta($shipment_id, 'wpcargo_driver', $driver_id);
        
        if ($updated !== false) {
            // Eliminar del contenedor
            delete_post_meta($shipment_id, 'shipment_container');
            
            // Log para debugging
            error_log("✅ Pedido #{$shipment_id} asignado a motorizado #{$driver_id} y removido del contenedor #{$container_id}");
            
            $success_count++;
        } else {
            $errors[] = "Error al procesar pedido #{$shipment_id}";
        }
    }
    
    // Actualizar la lista de pedidos ordenados del contenedor
    if ($container_id > 0) {
        $sorted_shipments = get_post_meta($container_id, 'wpcc_sorted_shipments', true);
        if (!empty($sorted_shipments)) {
            $sorted_array = explode(',', $sorted_shipments);
            // Remover los pedidos asignados
            $sorted_array = array_diff($sorted_array, $shipments);
            update_post_meta($container_id, 'wpcc_sorted_shipments', implode(',', $sorted_array));
        }
    }
    
    if ($success_count > 0) {
        $driver = get_userdata($driver_id);
        $driver_name = $driver ? $driver->display_name : "Motorizado #{$driver_id}";
        
        $message = "{$success_count} pedido(s) asignado(s) correctamente a {$driver_name} y eliminados del contenedor";
        
        if (!empty($errors)) {
            $message .= ". Algunos pedidos tuvieron errores: " . implode(', ', $errors);
        }
        
        wp_send_json_success(array('message' => $message, 'count' => $success_count));
    } else {
        wp_send_json_error(array('message' => 'No se pudo procesar ningún pedido. Errores: ' . implode(', ', $errors)));
    }
}
