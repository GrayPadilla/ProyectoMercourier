<?php
if (! defined('ABSPATH')) exit; // Exit if accessed directly
add_filter('wpcsr_get_address_book_query', 'wpc_address_book_get_address_book_query_callback', 10, 2);
function wpc_address_book_get_address_book_query_callback($sql, $book_type)
{
	global $wpdb;
	$prefix = $wpdb->prefix;
	$sql 	= "SELECT `post_id` FROM `{$prefix}posts` AS tbl1, `{$prefix}postmeta` AS tbl2 WHERE tbl1.ID = tbl2.post_id AND tbl1.post_author = " . get_current_user_id() . " AND tbl1.post_type LIKE 'wpc_address_book' AND tbl2.meta_key LIKE 'book' AND tbl2.meta_value LIKE '{$book_type}' OR ( tbl2.meta_key LIKE 'public_{$book_type}' AND tbl2.meta_value LIKE 1 ) GROUP BY `post_id`";
	return $sql;
}

function wpc_address_books_register_frontend_script($scripts)
{
	$scripts[] = 'address-book-frontend-scripts';
	$scripts[] = 'address-book-autofill-scripts';
	return $scripts;
}
function wpc_address_books_register_frontend_style($styles)
{
	$styles[] = 'address-book-frontend-styles';
	$styles[] = 'address-book-global-styles';
	return $styles;
}
function wpcabook_owner_header_callback()
{
?><th><?php _e('Assigned To', 'wpcargo-address-book'); ?></th>
<?php
}
function wpcabook_owner_data_callback($address_id)
{
	global $wpcargo;
	$client_id = get_post_field('post_author', $address_id);
?><td><?php echo $wpcargo->user_fullname($client_id); ?></td>
<?php
}
function wpcabook_form_callback()
{
	$wpcargo_client 	= wpcfe_get_users('wpcargo_client');
?>
	<section class="col-md-12">
		<div id="form-add-address-book-8" class="form-group ">
			<label for="add-address-book-wpcargo_receiver_email" class=""><?php _e('Assigned To', 'wpcargo-address-book'); ?></label>
			<select name="_assigned_to" class="form-control browser-default custom-select _assigned_to">
				<option value=""><?php esc_html_e('-- Select Client --', 'wpcargo-frontend-manager'); ?></option>
				<?php if (!empty($wpcargo_client)): ?>
					<?php foreach ($wpcargo_client as $key => $value): ?>
						<option value="<?php echo $key; ?>" <?php selected(get_post_meta($shipment_id, 'registered_shipper', TRUE), $key); ?>><?php echo $value; ?></option>
					<?php endforeach; ?>
				<?php endif; ?>
			</select>
		</div>
	</section>
	<?php
}

function wpcadd_book_plugins_loaded_callback()
{
	add_filter('wpcfe_registered_scripts', 'wpc_address_books_register_frontend_script', 10, 1);
	add_filter('wpcfe_registered_styles', 'wpc_address_books_register_frontend_style', 10, 1);

	if (function_exists('wpcfe_is_super_admin')) {
		if (wpcfe_is_super_admin()) {
			add_action('wpcabook_after_field_header', 'wpcabook_owner_header_callback');
			add_action('wpcabook_after_field_data', 'wpcabook_owner_data_callback');
			add_action('wpc_address_book_after_shipper_fields', 'wpcabook_form_callback', 10, 2);
		}
	}
}
add_action('plugins_loaded', 'wpcadd_book_plugins_loaded_callback');
// Auto Fill Hooks
function wpc_address_books_shipper_autofill()
{
	if (get_option('wpc_disable_address_shipper_search')) {
		return false;
	}
	if (is_user_logged_in()) {
		$search_field = wpcaddress_search_data('shipper');
		$search_label = $search_field ? sprintf(__('Search %s', 'wpcargo-address-book'), $search_field['meta_label']) : __('-- Select One --', 'wpcargo-address-book');
	?>
		<section class="wpcabook-autofill-wrapper col-md-12">
			<div id="shipper_addressbook_autofill" class="form-group">
				<select class="form-control md-form wpc_addressbook_autofill" id="shipper-address-list" data-type="shipper" data-placeholder="<?php echo $search_label; ?>">
				</select>
			</div>
			<div class="form-check">
				<input id="__wpcab_add_shipper" type="checkbox" name="__wpcab_add_shipper" class="_wpcab_add_input form-check-input" value="1"> <label class="form-check-label" for="__wpcab_add_shipper"><?php _e('Add to Address Book?', 'wpcargo-address-book'); ?></label>
			</div>
		</section>
	<?php
	}
}
function wpc_address_books_receiver_autofill()
{
	if (get_option('wpc_disable_address_receiver_search')) {
		return false;
	}
	if (is_user_logged_in()) {
		$search_field = wpcaddress_search_data('receiver');
		$search_label = $search_field ? sprintf(__('Search %s', 'wpcargo-address-book'), $search_field['meta_label']) : __('-- Select One --', 'wpcargo-address-book');
	?>
		<section class="wpcabook-autofill-wrapper col-md-12">
			<div id="receiver_addressbook_autofill" class="form-group">
				<select class="form-control md-form wpc_addressbook_autofill" id="receiver-address-list" data-type="receiver" data-placeholder="<?php echo $search_label; ?>" data-allow-clear="true">
				</select>
			</div>
			<div class="form-check">
				<input id="__wpcab_add_receiver" type="checkbox" name="__wpcab_add_receiver" class="_wpcab_add_input form-check-input" value="1"> <label class="form-check-label" for="__wpcab_add_receiver"><?php _e('Add to Address Book?', 'wpcargo-address-book'); ?></label>
			</div>
		</section>
		<?php
	}
}

function wpcabook_get_address_list_book_callback()
{
	$address_list 	= array();
	$bookType 		= $_REQUEST['filter'];
	$param 			= $_REQUEST['q'];
	$field_list 	= wpc_address_book_get_field_key($bookType . '_info');
	$user_id 		= wp_get_current_user()->ID;
	$default_ID 	= get_user_meta(get_current_user_id(), 'default_' . $bookType, true);
	$address 		= can_wpcfe_update_shipment() ? wpcabook_search_addresses($param, $bookType) : wpcabook_search_addresses($param, $bookType, $user_id);
	if (!empty($address) && !empty($field_list)) {
		foreach ($address as $address_id) {
			$field_container 	= array();
			foreach ($field_list as $field_info) {
				$value = maybe_unserialize(get_post_meta($address_id, $field_info['field_key'], true));
				if (!wpcabook_get_field_type($field_info['field_key']) == 'address' && is_array($value)) {
					$value = implode(",", $value);
				}
				$field_container[$field_info['field_key']] = $value;
			}
			$field_container['_assigned_to'] = get_post_field('post_author', $address_id);
			// Add default status
			$default = get_post_meta($address_id, 'default_' . $bookType, true);
			$field_container['default_' . $bookType] = $default_ID === $address_id ? 1 : 0;
			$address_list[$address_id] = $field_container;
		}
	}
	wp_send_json($address_list);
	wp_die();
}
add_action('wp_ajax_get_address_list', 'wpcabook_get_address_list_book_callback');
add_action('wp_ajax_nopriv_get_address_list', 'wpcabook_get_address_list_book_callback');


// Modal popup
function wpc_address_book_modal_popup()
{
	global $post;
	if (
		!empty($post)
		&& (
			is_a($post, 'WP_Post')
			&& (has_shortcode($post->post_content, 'wpc_address_book') || has_shortcode($post->post_content, 'wpc-address-book'))
		)
	) {
		$template = get_page_template_slug($post->ID);
		if (isset($_GET['book']) && $_GET['book'] == 'shipper') {
			$book = 'shipper';
		} else {
			$book = 'receiver';
		}
		$book_fields 	= wpc_address_book_get_custom_fields($book . '_info');
		if ($template == 'dashboard.php') {
			require_once(wpcab_include_template('frontend-modal.tpl'));
		} else {
			require_once(wpcab_include_template('default-modal.tpl'));
		}
	}
}
add_action('wp_footer', 'wpc_address_book_modal_popup');
add_action('wp_ajax_wpc_add_address', 'wpc_add_address_book_callback');
add_action('wp_ajax_nopriv_wpc_add_address', 'wpc_add_address_book_callback');
function wpc_add_address_book_callback()
{
	$formData 		= $_POST['formData'];
	$formatted_data = array();
	foreach ($formData as $data) {
		$formatted_data[$data['name']] = $data['value'];
	}

	$book 			= $formatted_data['book'];
	$default_user 	= array_key_exists('default_' . $book, $formatted_data) ? $formatted_data['default_' . $book] : false;
	$public_user 	= array_key_exists('public_' . $book, $formatted_data) ? $formatted_data['public_' . $book] : false;
	$userID 		= array_key_exists('_assigned_to', $formatted_data) ? $formatted_data['_assigned_to'] : false;
	$_search 		= get_option('wpc_address_' . $book . '_search');
	$searched_data  = array_key_exists($_search, $formatted_data) ? $formatted_data[$_search] : null;
	if (!$userID || (function_exists('wpcfe_is_super_admin') && !wpcfe_is_super_admin())) {
		$userID = get_current_user_id();
	}
	$postID 	= wp_insert_post(array('post_status'  => 'publish', 'post_type' => 'wpc_address_book', 'post_author' => $userID));

	if (is_wp_error($postID)) {
		wp_send_json(array(
			'status' => 'error',
			'message' => $postID->get_error_message()
		));
	}

	$reg_metakeys 	= wpc_address_book_metakeys($book . '_info');
	$reg_metakeys[] = 'book';
	$reg_metakeys[] = '_assigned_to';

	foreach ($formatted_data as $metakey => $metavalue) {
		if (!in_array($metakey, $reg_metakeys)) {
			continue;
		}
		$value = is_array($metavalue) ? $metavalue : sanitize_text_field($metavalue);
		update_post_meta($postID, $metakey, $value);
	}

	if (!empty($default_user)) {
		update_user_meta($userID, 'default_' . $book, $postID);
	}
	if ($public_user) {
		update_post_meta($postID, 'public_' . $book, 1);
	}

	// Save Address
	$address_data = wpcab_get_address_serialize_array($formatted_data);
	if ($address_data) {
		foreach ($address_data as $add_metakey => $add_metavalue) {
			update_post_meta($postID, $add_metakey, $add_metavalue);
		}
	}
	do_action('wpc_after_add_address_book_save', $formData, $postID, $book); //added new parameter when saving additional address fields

	wp_send_json(array(
		'status' => 'success',
		'message' => sprintf('%s successfully saved.', $searched_data)
	));

	wp_die();
}
add_action('wp_ajax_wpc_get_address', 'wpc_get_address_book_callback');
add_action('wp_ajax_nopriv_wpc_get_address', 'wpc_get_address_book_callback');
function wpc_get_address_book_callback()
{
	global $wpdb;
	$bookID 		= $_POST['bookID'];
	$bookType 		= $_POST['bookType'];
	$defaultID 		= $_POST['defaultID'];
	$table_prefix 	= $wpdb->prefix;
	$book_fields 	= $wpdb->get_results('SELECT * FROM `' . $table_prefix . 'wpcargo_custom_fields` WHERE `section` LIKE "%' . $bookType . '_info%" ORDER BY ABS(weight)', ARRAY_A);
	$data_array 	= array();
	$data_array['default_' . $bookType] 	= $defaultID;
	$data_array['_assigned_to'] 		= get_post_field('post_author', $bookID);
	foreach ($book_fields as $field) {
		$data_array[$field['field_key']] =  maybe_unserialize(get_post_meta($bookID, $field['field_key'], TRUE));
	}

	$data_array = apply_filters('address_data_array', $data_array, $bookID, $bookType); //added filter for the additional fields AJAX saving

	echo json_encode($data_array);
	wp_die();
}

add_action('wp_ajax_edit_address_book', 'wpc_edit_address_book_callback');
add_action('wp_ajax_nopriv_edit_address_book', 'wpc_edit_address_book_callback');
function wpc_edit_address_book_callback()
{
	$formData 		= $_POST['formData'];
	$formatted_data = array();
	foreach ($formData as $data) {
		$formatted_data[$data['name']] = $data['value'];
	}
	$address_id 	= array_key_exists('address-id', $formatted_data) ? $formatted_data['address-id'] : false;
	$book 			= $formatted_data['book'];
	$default_user 	= array_key_exists('default_' . $book, $formatted_data) ? true : false;
	$public_user 	= array_key_exists('public_' . $book, $formatted_data) ? $formatted_data['public_' . $book] : false;
	$_userID 		= array_key_exists('_assigned_to', $formatted_data) ? $formatted_data['_assigned_to'] : false;
	$userID 		= $_userID;
	$_search 		= get_option('wpc_address_' . $book . '_search');
	$searched_data  = array_key_exists($_search, $formatted_data) ? $formatted_data[$_search] : null;
	if (!$userID || (function_exists('wpcfe_is_super_admin') && !wpcfe_is_super_admin())) {
		$userID = get_current_user_id();
	}
	// Unset Address ID in the array Data
	if (isset($formatted_data['address-id'])) {
		unset($formatted_data['address-id']);
	}
	if (!$address_id) {
		wp_send_json(array(
			'success' => 'error',
			'message' => __('Something went wrong while processing data, please reload the page and try again.')
		));
	}

	// Update Address Author/ - ONly if current user is Admin
	if ((function_exists('wpcfe_is_super_admin') && wpcfe_is_super_admin())
		&& $_userID
	) {
		wp_update_post(array(
			'ID' => $address_id,
			'post_author' => $_userID,
		));
	}

	//** Update book address
	foreach ($formatted_data as $metakey => $metavalue) {
		$value = is_array($metavalue) ? $metavalue : sanitize_text_field($metavalue);
		update_post_meta($address_id, $metakey, $value);
	}
	// Default User
	if (!empty($default_user)) {
		update_user_meta($userID, 'default_' . $book, $address_id);
	}
	if ($public_user) {
		update_post_meta($address_id, 'public_' . $book, 1);
	}

	// Save Address
	$address_data = wpcab_get_address_serialize_array($formatted_data);
	if ($address_data) {
		foreach ($address_data as $add_metakey => $add_metavalue) {
			update_post_meta($address_id, $add_metakey, $add_metavalue);
		}
	}
	do_action('wpc_after_add_address_book_save', $formData, $address_id, ''); // added parameter for additional address book field saving

	wp_send_json(array(
		'status' => 'success',
		'message' => sprintf('%s successfully saved.', $searched_data)
	));
	wp_die();
}
add_action('wp_ajax_edit_public_user', 'edit_public_user_callback');
function edit_public_user_callback()
{
	global $wpdb;
	$userID 		= get_current_user_id();
	$book_ids 		= $_POST['dataIDs'];
	$is_public 		= $_POST['is_public'];
	$book 			= $_POST['book'];
	$bookid 		= $_POST['bookid'];
	if ($bookid) {
		update_post_meta($bookid, 'public_' . $book, $is_public);
		echo json_encode($bookid);
	}
	if (!empty($book_ids)) {
		foreach ($book_ids as $book_id) {
			update_post_meta($book_id, 'public_' . $book, $is_public);
		}
		echo json_encode($book_ids);
	}
	wp_die();
}
add_action('wp_ajax_address_book_list', 'wpc_address_book_list_callback');
add_action('wp_ajax_nopriv_address_book_list', 'wpc_address_book_list_callback');
function wpc_address_book_list_callback()
{
	global $wpdb;
	$table_prefix = $wpdb->prefix;
	$bookType = $_POST['bookType'];
	$result = $wpdb->get_results("SELECT `post_id` FROM `" . $table_prefix . "postmeta` WHERE `meta_key` LIKE 'book' AND `meta_value` LIKE '" . $bookType . "'", OBJECT);
	$book_fields = $wpdb->get_results('SELECT * FROM `' . $table_prefix . 'wpcargo_custom_fields` WHERE `section` LIKE "%' . $bookType . '_info%" ORDER BY ABS(weight)', ARRAY_A);
	if (!empty($result)) {
		foreach ($result as $book) {
			$meta_counter = 0;
		?>
			<div id="book-<?php echo $book->post_id; ?>" class="book-section">
				<?php
				foreach ($book_fields as $field) {
					if ($meta_counter == 2) {
						$meta_counter = 0;
						break;
					}
				?><p><?php echo $field['label'] ?> : <?php echo get_post_meta($book->post_id, $field['field_key'], true); ?></p>
				<?php
					$meta_counter++;
				}
				?>
				<p class="insert-book wpc-button" style="cursor:pointer;" data-id="<?php echo $book->post_id; ?>" data-book="<?php echo $bookType; ?>"><?php esc_html_e('Insert', 'wpcargo-parcel-quotation'); ?></p>
			</div><?php
				}
			} else {
					?>
		<p style="text-align:center;"><?php esc_html_e('NO Book List Found!', 'wpcargo-parcel-quotation'); ?></p>
	<?php
			}
			wp_die();
		}
		add_action('wp_ajax_insert_address_book', 'wpc_insert_address_book_callback');
		add_action('wp_ajax_nopriv_insert_address_book', 'wpc_insert_address_book_callback');
		function wpc_insert_address_book_callback()
		{
			global $wpdb;
			$bookType = $_POST['bookType'];
			$bookID = $_POST['bookID'];

			$table_prefix = $wpdb->prefix;
			$book_fields = $wpdb->get_results('SELECT * FROM `' . $table_prefix . 'wpcargo_custom_fields` WHERE `section` LIKE "%' . $bookType . '_info%" ORDER BY ABS(weight)', ARRAY_A);
			$data_array = array();
			foreach ($book_fields as $field) {
				$data_array[$field['field_key']] =  maybe_unserialize(get_post_meta($bookID, $field['field_key'], TRUE));
			}
			echo json_encode($data_array);
			wp_die();
		}
		add_action('wp_ajax_delete_address_book', 'wpc_delete_address_book_callback');
		add_action('wp_ajax_nopriv_delete_address_book', 'wpc_delete_address_book_callback');
		function wpc_delete_address_book_callback()
		{
			global $wpdb;
			$bookID = $_POST['bookID'];
			wp_delete_post($bookID, TRUE);
			wp_die();
		}
		add_action('wp_ajax_bulk_delete_address_book', 'wpc_bulk_delete_address_book_callback');
		add_action('wp_ajax_nopriv_bulk_delete_address_book', 'wpc_bulk_delete_address_book_callback');
		function wpc_bulk_delete_address_book_callback()
		{
			global $wpdb;
			$addressBookIds = ($_POST['addressBookIds'] ?? array()) ?: array();
			$deleted_count = 0;
			if ($addressBookIds && is_array($addressBookIds)) {
				foreach ($addressBookIds as $bookID) {
					if (!is_wp_error(wp_delete_post($bookID, TRUE))) {
						$deleted_count++;
					}
				}
			}
			if ($deleted_count == count($addressBookIds)) {
				wp_send_json(array(
					'status' => 'success',
					'message' => esc_html('Selected item(s) have been deleted.', 'wpcargo-address-book')
				));
			} else {
				wp_send_json(array(
					'status' => 'danger',
					'message' => esc_html('Something went wrong while deleting item(s).', 'wpcargo-address-book')
				));
			}
			wp_die();
		}
		add_action('wp_ajax_address_book_by_user', 'wpc_address_book_ajax_get_address_list_by_user');
		add_action('wp_ajax_nopriv_address_book_by_user', 'wpc_address_book_ajax_get_address_list_by_user');
		function wpc_address_book_ajax_get_address_list_by_user()
		{
			$userID 		= $_POST['userID'];
			$bookType 		= $_POST['bookType'];
			$search_option 	= get_option('wpc_address_' . $bookType . '_search');
			$address 		= wpc_address_book_get_address_list_by_user($bookType, $userID);
			ob_start();
			if (!empty($address)) {
	?><option value=""><?php esc_html_e('-- Select Address --', 'wpcargo-address-book'); ?></option>
		<?php
				foreach ($address as $value) {
		?><option value="<?php echo $value; ?>"><?php echo get_post_meta($value, $search_option, TRUE); ?></option>
		<?php
				}
			} else {
		?><option value="-1"><?php echo wpc_address_book_get_user_fullname($userID); ?> <?php esc_html_e(' has no registered book address.', 'wpcargo-address-book'); ?></option>
	<?php
			}
			$output = ob_get_clean();
			echo $output;
			wp_die();
		}
		//** Address Book on shipment post type page ( admin )
		add_action('wpc_address_book_after_shipper_fields', 'wpcab_custom_shipper_fields', 10, 2);
		function wpcab_custom_shipper_fields($book, $type)
		{
	?>
	<div id="form-default" class="form-group">
		<input name="<?php echo 'default_' . $book; ?>" type="checkbox" id="shipper_default_address_<?php echo $type; ?>" class="form-check-input" value="yes">
		<label class="form-check-label" for="shipper_default_address_<?php echo $type; ?>"><?php esc_html_e('Default', 'wpcargo-address-book'); ?></label>
	</div>
<?php
		}
		// Frontend Manager Hooks
		add_filter('wpcfe_after_sidebar_menu_items', 'wpc_address_book_sidebar_menu');
		function wpc_address_book_sidebar_menu($menu_items)
		{
			if (function_exists('wpcfe_admin_page') && wpc_can_add_address_book()) {
				$menu_items['address-book-menu'] = array(
					'page-id' => wpc_address_book_get_frontend_page(),
					'label' => __('Address Book', 'wpcargo-address-book'),
					'permalink' => get_permalink(wpc_address_book_get_frontend_page()),
					'icon' => 'fa-book'
				);
			}
			return $menu_items;
		}

		// Save address after shipment is created
		function wpc_adddressbook_after_vehicle_save_shipment_callback($post_id, $data)
		{
			$can_add_shipper = false;
			$can_add_receiver = false;
			if (empty($data) || !is_array($data)) {
				return false;
			}
			foreach ($data as $value) {
				if ($value['name'] == '__wpcab_add_shipper') {
					$can_add_shipper = true;
				}
				if ($value['name'] == '__wpcab_add_receiver') {
					$can_add_receiver = true;
				}
			}
			if (!get_option('wpc_disable_address_shipper_search') && $can_add_shipper) {
				wpc_save_ajax_address_book($data, 'shipper');
			}
			if (!get_option('wpc_disable_address_receiver_search') && $can_add_receiver) {
				wpc_save_ajax_address_book($data, 'receiver');
			}
		}
		function wpc_adddressbook_after_wpcfe_save_shipment_callback($post_id, $data)
		{
			if (!get_option('wpc_disable_address_shipper_search') && array_key_exists('__wpcab_add_shipper', $data)) {
				wpc_save_address_book($data, 'shipper');
			}
			if (!get_option('wpc_disable_address_receiver_search') && array_key_exists('__wpcab_add_receiver', $data)) {
				wpc_save_address_book($data, 'receiver');
			}
		}
		function wpc_adddressbook_save_shipment_callback($post_id, $post)
		{
			if (!is_admin()) {
				return false;
			}
			if ($post->post_type != 'wpcargo_shipment') {
				return false;
			}
			if (!get_option('wpc_disable_address_shipper_search') && array_key_exists('__wpcab_add_shipper', $_REQUEST)) {
				wpc_save_address_book($_REQUEST, 'shipper');
			}
			if (!get_option('wpc_disable_address_receiver_search') && array_key_exists('__wpcab_add_receiver', $_REQUEST)) {
				wpc_save_address_book($_REQUEST, 'receiver');
			}
		}
		// Save Address books - Auto generate - Frontend Manager
		add_action('after_wpcfe_save_shipment', 'wpc_adddressbook_after_wpcfe_save_shipment_callback', 10, 2);
		// Save Address books - Auto generate - Shipping Rate
		add_action('wpcsr_after_save_shipment', 'wpc_adddressbook_after_wpcfe_save_shipment_callback', 10, 2);
		// Save Address books - Auto generate - Parcel Quotation
		add_action('wpcpq_after_save_shipment_details', 'wpc_adddressbook_after_wpcfe_save_shipment_callback', 10, 2);
		// Save Address books - Auto generate - Vehicle Rate
		add_action('wpcvr_after_save_delivery_rate', 'wpc_adddressbook_after_vehicle_save_shipment_callback', 10, 2);
		add_action('wpcvr_after_save_flat_rate', 'wpc_adddressbook_after_vehicle_save_shipment_callback', 10, 2);
		// Save Address books - Auto generate - Shipment Consolidation
		add_action('wpcshco_after_submit_save_order_action', 'wpc_adddressbook_after_wpcfe_save_shipment_callback', 10, 2);
		add_action('save_post_wpcargo_shipment', 'wpc_adddressbook_save_shipment_callback', 20, 2);
		// Plugin Rows Hook
		function wpc_adddressbook_row_action_callback($links)
		{
			$action_links = array(
				'settings' => '<a href="' . admin_url('admin.php?page=wpc-book-settings') . '" aria-label="' . esc_attr__('Settings', 'wpcargo-address-book') . '">' . esc_html__('Settings', 'wpcargo-address-book') . '</a>',
				'license' => '<a href="' . admin_url('admin.php?page=wptaskforce-helper') . '" aria-label="' . esc_attr__('License', 'wpcargo-address-book') . '">' . esc_html__('License', 'wpcargo-address-book') . '</a>',
			);
			return array_merge($action_links, $links);
		}
		add_filter('plugin_action_links_' . WPCARGO_ADDRESS_BOOK_BASENAME, 'wpc_adddressbook_row_action_callback', 10, 2);


		/******************************************************************
		 * Handler for Assign address book to user
		 *****************************************************************/
		function get_all_users_callback()
		{
			global $wpdb;
			$prefix = $wpdb->prefix;
			$search = $_REQUEST['q'];
			$param = '%' . $search . '%';
			$sql 	= "SELECT * FROM `{$prefix}users` WHERE display_name LIKE '%s'";
			$sql	= $wpdb->prepare($sql, $param);
			$query = $wpdb->get_results($sql, ARRAY_A);
			// return $sql;

			wp_send_json($query);
		}
		add_action('wp_ajax_get_all_users', 'get_all_users_callback');
		add_action('wp_ajax_nopriv_get_all_users', 'get_all_users_callback');

		//# Handler - Bulk Delete Address Book
		//#used in frontend dashboard
		function bulk_delete_address_callback()
		{
			global $wpdb;

			if (!can_wpcfe_delete_address()) {
				wp_send_json(array(
					'status' => 'error',
					'message' => __('Opsss! Sorry you are not allowed to delete shipments', 'wpcargo-frontend-manager')
				));
			}

			$addressIDs		= $_REQUEST['selectedAddress'];
			$message		= array();
			$counter 		= 0;
			foreach ($addressIDs as $address) {
				// echo $address;
				if (can_wpcfe_delete_address()) {
					$delete_post = wp_trash_post($address, false);
					$counter++;
				}
			}
			wp_send_json(array(
				'status' => 'success',
				'message' => sprintf(_n('You successfully deleted %s address.', 'You successfully deleted %s address.', $counter, 'wpcargo-frontend-manager'), number_format_i18n($counter))
			));

			wp_die();
		}
		add_action('wp_ajax_bulk_delete_address', 'bulk_delete_address_callback');
		add_action('wp_ajax_nopriv_bulk_delete_address', 'bulk_delete_address_callback');

		//#Delete Button
		function wpca_bulk_delete_callback()
		{
?>
	<button class="remove-address btn btn-danger btn-sm waves-effect waves-light"><i class="fa fa-trash text-white"></i><?php esc_html_e('Delete', 'wpcargo_address_book'); ?></button>
<?php
		}
		add_action('wpcabook_before_field_header', 'wpca_bulk_delete_callback', 10);
