<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
class WPCargo_Address_Book_IE{
	function __construct(){
		add_action( 'admin_menu', array( $this, 'menu_page' ) );
		add_action ( 'wp_ajax_export_address', array( $this, 'export_callback' ) );
		add_action ( 'admin_head', array( $this, 'import_callback' ) );
		add_action ( 'wp_ajax_download_template', array( $this, 'download_template_callback' ) );
	}
	function menu_page() {
		add_submenu_page(
			'address-book',
			__( 'Import/Export', 'wpcargo-address-book' ),
			__( 'Import/Export', 'wpcargo-address-book' ),
			'manage_options',
			'ei-address-book',
			array( $this, 'ei_page_callback')
		);
	}
	function wpcab_ie_outer_separator() {
		return apply_filters('wpcab_ie_outer_separator', '|');
	}
	function wpcab_ie_inner_separator() {
		return apply_filters('wpcab_ie_inner_separator', ':');
	}
	function export_callback(){
		$userID 	= $_POST['userID'];
		$bookType 	= $_POST['bookType'];
		if( $bookType == 'receiver' ){
			$book_table = 'receiver_info';
		}else{
			$book_table = 'shipper_info';
		}
		$data_fields 	= wpc_address_book_get_custom_fields( $book_table );
		if( empty( $userID  ) ){
			$addresses = wpc_address_book_get_all_data( $bookType );
		}else{
			$addresses = wpc_address_book_get_data_by_user( $userID, $bookType );
		}
		$__field_meta = array();
		$__field_label = array();
		$user_label = array( 'Public' );
		$user_meta = array( 'public_'.$bookType );

		$data_fields = apply_filters('address_data_fields_meta', $data_fields); //added hook for custom address meta data

		if( !empty( $data_fields  ) ){
			foreach ( $data_fields as $value ) {
				$__field_label[] = $value['label'];
				$__field_meta[]  = $value['field_key'];
			}	
		}
		$__field_meta = array_merge( $__field_meta, $user_meta );
		$filename = "address-book-".time().".csv";
		$fileheader = array( __('BookID', 'wpcargo-address-book'), __('userID', 'wpcargo-address-book' ), __('User Name', 'wpcargo-address-book' ) );
		$fileheader = array_merge( $fileheader, $__field_label, $user_label );
		$csv_file = fopen($filename, "w");
		fputcsv( $csv_file, $fileheader  );	
		if( !empty( $addresses ) ){
			foreach ( $addresses as $address ) {
				$author_name = wpc_address_book_get_user_fullname( $address->post_author );
				$data_array = array(
					$address->ID, 
					$address->post_author, 
					$author_name
				);
				if( !empty( $__field_meta ) ){
					foreach ( $__field_meta as $meta_key ) {
						$meta_value = maybe_unserialize( get_post_meta( $address->ID, $meta_key, true ) );
						$meta_value = apply_filters('address_data_meta_value', $meta_value, $meta_key); // added custom meta data on export
						if( is_array( $meta_value ) ){
							$formatted_value = array();
							foreach($meta_value as $k => $v) {
								$formatted_value[] = $k.$this->wpcab_ie_inner_separator().$v;
							}
							if($formatted_value) {
								$meta_value = implode( $this->wpcab_ie_outer_separator(), $formatted_value );
							}
						}
						$data_array[] = $meta_value;
					}
				}
				fputcsv( $csv_file, $data_array );		
			}
		}
		fclose($csv_file);
		echo $filename;
		wp_die();
	}
	function import_callback(){
		if ( isset( $_POST['import_address_field'] ) && wp_verify_nonce( $_POST['import_address_field'], 'import_address_book_action' ) ) {
			$compatiblity = isset( $_POST['compatibility'] ) ? ';' : ',' ; 
			?>
			<script type="text/javascript">
				jQuery(document).ready(function($){
					$('body').append('<div class="wpc-loading"></div>');
					$('#import-section .inside #import-result').append('<h3><?php esc_html_e( 'Importing Address', 'wpcargo-address-book' ); ?></h3>');
				});
			</script>
			<?php
			$allowed_file 	= array( 'application/vnd.ms-excel', 'text/csv' );
			$file_error 	= '';
			$bookType 		= $_POST['book_type'];
			if( $bookType == 'receiver' ){
				$book_table = 'receiver_info';
			}else{
				$book_table = 'shipper_info';
			}
			$data_fields 	= wpc_address_book_get_custom_fields( $book_table );
			$__field_meta = array();
			$__field_label = array();
			if( !empty( $data_fields  ) ){
				foreach ( $data_fields as $value ) {
					$__field_label[] = $value['label'];
					$__field_meta[]  = $value['field_key'];
				}	
			}
			$user_priv_meta = array( 'public_'.$bookType );
			$__field_meta = array_merge( $__field_meta, $user_priv_meta );
			$import_header 	= array();
			$fileheader 	= array( __('userID', 'wpcargo-address-book' ) );
			$user_label 	= array( 'Public' );
			$fileheader 	= array_merge( $fileheader, $__field_label, $user_label );
			$address_file 	= $_FILES['address_file'];


			if( $address_file['error'] > 0 ) {
				$file_error = wpc_address_book_upload_errors( [ $address_file['error'] ] );
			}else{
				if( !in_array( $address_file['type'], $allowed_file ) ) {
					$file_error = __('Wrong File Format uploaded. It must be .CSV format.', 'wpcargo-address-book' );
				}else{
					if ( ( $handle = fopen( $address_file['tmp_name'], "r") ) !== FALSE ) {
						$data_header = fgetcsv( $handle, 1000, $compatiblity );							
						foreach ( $data_header as $meta_field ) {
							$import_header[] = $meta_field;
						}

						if( $import_header == $fileheader ){
							while ( ($data = fgetcsv($handle, 1000, $compatiblity)) !== FALSE) {
								$data_array = array();
								foreach ( $data as $value ) {
									$data_array[] = $value;
								}
								$userID = intval( $data_array[0] );
								$userID = $userID ? $userID : get_current_user_id();
								//** Check if userID is not Zero
								if( $userID ){
									// Add address book One By One
									$insert_address = array(
									    'post_status'   => 'publish',
									    'post_type'		=> 'wpc_address_book',
									    'post_author'   => $userID,
									);
									 
									// Insert the Address into the database.
									$addressID = wp_insert_post( $insert_address );
									// Check if address is Added
									if( $addressID ){ 
										update_post_meta( $addressID,'book', $bookType);
										array_shift( $data_array ); // remove the UserID in array
										foreach ( $data_array as $_key => $_value ) {

											/* this code block will check for data that is attempting to be formatted as array
												Example: "street:37-80 64th Street | city:Woodside | state:New York | postcode:11377 | country:United States"
												where "|" is the outer separator that will separate each key-value pairs and;
												":" will separate the associative array key and value 
											*/

											$outer_data_value_separator = $this->wpcab_ie_outer_separator();
											$inner_data_value_separator = $this->wpcab_ie_inner_separator();
											$is_formatted_array_value = strpos($_value, $outer_data_value_separator);
											if($is_formatted_array_value !== false) {
												$exploded_value = explode($outer_data_value_separator, $_value);
												$final_formatted_value = array();
												if($exploded_value && is_array($exploded_value)) {
													foreach($exploded_value as $exp_val) {
														list($_k, $_v) = explode($inner_data_value_separator, trim($exp_val));
														$final_formatted_value[trim($_k)] = trim($_v);
													}
												}
												$_value = maybe_serialize($final_formatted_value);

												update_post_meta( $addressID, $__field_meta[$_key], $_value);
											} else {

												update_post_meta( $addressID, $__field_meta[$_key], sanitize_text_field( $_value ) );
											}
										}								
									}
									?>
									<script type="text/javascript">
										jQuery(document).ready(function($){
											$('#import-section .inside #import-result').append( '<p class="data-success" > ID <?php echo $addressID; ?> : <?php echo $data_array[0]; ?> <span style="color:#28a745!important" class="dashicons dashicons-yes"></span></p>');
										});
									</script>
									<?php
								}								
							}
						}else{
							$file_error = __('Something went wrong! Please check your file header or Check Mac / International language settings compatibility issue', 'wpcargo-address-book' );
						}						
					}// Open File
				}// If file is corrent format
			}// If file has no error
			?>
			<script type="text/javascript">
				jQuery(document).ready(function($){
					$('body .wpc-loading').remove();
					<?php if( !empty( $file_error ) ): ?>
					$('#import-section .inside #import-result').append( '<p class="data-error"><?php echo $file_error; ?></p>');
					<?php endif; ?>
				});
			</script>
			<?php
		}
	}
	function download_template_callback(){
		$bookType 	= $_POST['bookType'];
		if( $bookType == 'receiver' ){
			$book_table = 'receiver_info';
		}else{
			$book_table = 'shipper_info';
		}
		$data_fields 	= wpc_address_book_get_custom_fields( $book_table );
		$__field_label = array();
		if( !empty( $data_fields  ) ){
			foreach ( $data_fields as $value ) {
				$__field_label[] = $value['label'];
			}	
		}
		$user_label = array( 'Public' );
		$__field_label = array_merge( $__field_label, $user_label );
		$filename = "import-book-template.csv";
		$fileheader = array( __('userID', 'wpcargo-address-book' ) );
		$fileheader = array_merge( $fileheader, $__field_label );
		$csv_file = fopen($filename, "w");
		fputcsv( $csv_file, $fileheader  );	
		fclose($csv_file);
		echo $filename;
		wp_die();
	}
	function ei_page_callback(){
		?>
		<div id="wpc-address-book-ie-wrapper" class="wrap">
			<h1><?php esc_html_e('Address Book Import/Export', 'wpcargo-address-book' ); ?></h1>			
			<?php require_once( WPCARGO_ADDRESS_BOOK_PATH.'admin/templates/import.tpl.php' ); ?>
		</div>
		<?php
	}
}
new WPCargo_Address_Book_IE;